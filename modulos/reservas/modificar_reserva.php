<?php
session_start();
require_once '../..conexion.php'; // $conn

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    header("Location: ../autch/login.html");
    exit();
}

$reserva_id = null;
$reserva_actual = null;
$cliente_info = null; // Para el cliente actual de la reserva
$tipos_habitacion = [];
$habitaciones_disponibles_mod = $_SESSION['mod_habitaciones_disponibles'] ?? [];
$filtros_guardados_mod = $_SESSION['mod_reserva_filtros'] ?? [];


if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $reserva_id = (int)$_GET['id'];
    $_SESSION['mod_reserva_id_actual'] = $reserva_id; // Guardar en sesión para el POST
} elseif (isset($_SESSION['mod_reserva_id_actual'])) {
    $reserva_id = $_SESSION['mod_reserva_id_actual'];
} else {
    $_SESSION['mensaje_error'] = "ID de reserva no proporcionado o inválido.";
    header("Location: gestionar_reservas.php");
    exit();
}

// Cargar tipos de habitación para el filtro
$result_tipos_q = $conn->query("SELECT id, nombre FROM tipo_habitacion ORDER BY nombre");
if ($result_tipos_q) {
    while ($tipo = $result_tipos_q->fetch_assoc()) {
        $tipos_habitacion[] = $tipo;
    }
}

// --- LÓGICA POST PARA ACTUALIZAR O BUSCAR HABITACIONES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion_post = $_POST['accion'] ?? '';

    if ($accion_post === 'buscar_habitaciones_mod') {
        // Similar a nueva_reserva.php pero para el contexto de modificación
        $fecha_entrada_str = $_POST['fecha_entrada'];
        $fecha_salida_str = $_POST['fecha_salida'];
        $tipo_id_filtro = $_POST['tipo_habitacion_filtro'] ?? null;
        $capacidad_filtro = $_POST['capacidad_filtro'] ?? 1;

        $_SESSION['mod_reserva_filtros'] = $_POST; // Guardar filtros
        unset($_SESSION['mod_habitaciones_disponibles']);

        if (empty($fecha_entrada_str) || empty($fecha_salida_str) || $fecha_salida_str <= $fecha_entrada_str) {
            $_SESSION['mensaje_error_mod'] = "Fechas inválidas.";
        } else {
            $fecha_entrada_sql = $fecha_entrada_str . " 14:00:00";
            $fecha_salida_sql = $fecha_salida_str . " 12:00:00";

            // La consulta de disponibilidad debe excluir la PROPIA reserva que se está modificando
            // para que su habitación actual aparezca como disponible si las fechas no cambian mucho.
            $sql_disponibles = "SELECT h.id, h.nombre AS nombre_habitacion, h.precio, h.capacidad, th.nombre AS tipo_nombre
                               FROM habitaciones h
                               JOIN tipo_habitacion th ON h.tipo_id = th.id
                               WHERE h.estado = 'Disponible' AND h.capacidad >= ? ";
            $params_bind_values = [$capacidad_filtro];
            $types_bind_string = "i";

            if (!empty($tipo_id_filtro)) {
                $sql_disponibles .= " AND h.tipo_id = ? ";
                $params_bind_values[] = $tipo_id_filtro;
                $types_bind_string .= "i";
            }

            $sql_disponibles .= " AND h.id NOT IN (
                                   SELECT r.habitacion_id FROM reservas r
                                   WHERE r.habitacion_id IS NOT NULL AND r.estado = 'Confirmada'
                                   AND r.id != ? -- EXCLUIR LA RESERVA ACTUAL DE LA VERIFICACIÓN DE CONFLICTO
                                   AND (r.fecha_entrada < ? AND r.fecha_salida > ?)
                                 ) ORDER BY h.nombre";
            $params_bind_values[] = $reserva_id; // ID de la reserva actual
            $params_bind_values[] = $fecha_salida_sql;
            $params_bind_values[] = $fecha_entrada_sql;
            $types_bind_string .= "iss"; // i para capacidad, (opcional i para tipo), i para reserva_id, s, s para fechas

            $stmt_disp = $conn->prepare($sql_disponibles);
            if (!$stmt_disp) {
                $_SESSION['mensaje_error_mod'] = "Error al preparar consulta: " . $conn->error;
            } else {
                $stmt_disp->bind_param($types_bind_string, ...$params_bind_values);
                $stmt_disp->execute();
                $result_disp = $stmt_disp->get_result();
                $current_habitaciones_disponibles = [];
                while ($hab = $result_disp->fetch_assoc()) {
                    $current_habitaciones_disponibles[] = $hab;
                }
                $_SESSION['mod_habitaciones_disponibles'] = $current_habitaciones_disponibles;
                $stmt_disp->close();
                if (empty($current_habitaciones_disponibles)) {
                    $_SESSION['mensaje_info_mod'] = "No hay otras habitaciones disponibles. Puede mantener la actual si no hay conflicto.";
                } else {
                     $_SESSION['mensaje_exito_mod'] = count($current_habitaciones_disponibles) . " habitación(es) alternativa(s) encontrada(s).";
                }
            }
        }
        header("Location: modificar_reserva.php?id=" . $reserva_id); // Recargar la página
        exit();

    } elseif ($accion_post === 'actualizar_reserva') {
        // Recoger todos los datos del formulario
        $nueva_habitacion_id = (int)$_POST['habitacion_id_seleccionada']; // Puede ser la misma o una nueva
        $nueva_fecha_entrada_str = $_POST['fecha_entrada'];
        $nueva_fecha_salida_str = $_POST['fecha_salida'];
        $nuevo_modo_reserva = $_POST['modo_reserva'];
        $nuevo_metodo_pago = $_POST['metodo_pago'] ?? null;
        // El DNI del cliente no se cambia desde aquí directamente, se asume que se mantiene el de la reserva original.
        // Si se quisiera cambiar cliente, sería un flujo más complejo.

        if (empty($nueva_fecha_entrada_str) || empty($nueva_fecha_salida_str) || $nueva_fecha_salida_str <= $nueva_fecha_entrada_str) {
            $_SESSION['mensaje_error_mod'] = "Fechas inválidas para la actualización.";
        } else {
            $nueva_fecha_e_obj = new DateTime($nueva_fecha_entrada_str);
            $nueva_fecha_s_obj = new DateTime($nueva_fecha_salida_str);
            $diferencia = $nueva_fecha_s_obj->diff($nueva_fecha_e_obj);
            $nueva_estancia = (int)$diferencia->days;

            if ($nueva_estancia <= 0) {
                $_SESSION['mensaje_error_mod'] = "La estancia debe ser de al menos 1 día.";
            } else {
                $stmt_hab_info = $conn->prepare("SELECT precio FROM habitaciones WHERE id = ?");
                $stmt_hab_info->bind_param("i", $nueva_habitacion_id);
                $stmt_hab_info->execute();
                $result_hab_info = $stmt_hab_info->get_result();

                if ($result_hab_info->num_rows === 0) {
                    $_SESSION['mensaje_error_mod'] = "La habitación seleccionada no existe.";
                } else {
                    $hab_data = $result_hab_info->fetch_assoc();
                    $precio_noche_nuevo = $hab_data['precio'];
                    $nuevo_monto_total = $precio_noche_nuevo * $nueva_estancia;
                    $stmt_hab_info->close();

                    // Verificar disponibilidad de la NUEVA habitación para las NUEVAS fechas (excluyendo la reserva actual)
                    $nueva_fecha_entrada_sql = $nueva_fecha_entrada_str . " 14:00:00";
                    $nueva_fecha_salida_sql = $nueva_fecha_salida_str . " 12:00:00";

                    $stmt_check_conflict = $conn->prepare(
                        "SELECT COUNT(*) as count FROM reservas 
                         WHERE habitacion_id = ? AND estado = 'Confirmada' AND id != ? 
                         AND (fecha_entrada < ? AND fecha_salida > ?)"
                    );
                    $stmt_check_conflict->bind_param("iiss", $nueva_habitacion_id, $reserva_id, $nueva_fecha_salida_sql, $nueva_fecha_entrada_sql);
                    $stmt_check_conflict->execute();
                    $conflict_result = $stmt_check_conflict->get_result()->fetch_assoc();
                    $stmt_check_conflict->close();

                    if ($conflict_result['count'] > 0) {
                        $_SESSION['mensaje_error_mod'] = "Conflicto de reserva. La habitación y fechas seleccionadas ya están ocupadas por otra reserva.";
                    } else {
                        // Proceder con la actualización
                        $sql_update = "UPDATE reservas SET 
                                          habitacion_id = ?, fecha_entrada = ?, fecha_salida = ?, 
                                          modo_reserva = ?, metodo_pago = ?, estancia = ?, monto_total = ?,
                                          usuario_id = ? -- Actualizar el usuario que modifica
                                      WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $usuario_actualizador_id = $_SESSION['usuario_id'];

                        if (empty($nuevo_metodo_pago)) $nuevo_metodo_pago = null;

                        $stmt_update->bind_param("issssidii",
                            $nueva_habitacion_id, $nueva_fecha_entrada_sql, $nueva_fecha_salida_sql,
                            $nuevo_modo_reserva, $nuevo_metodo_pago, $nueva_estancia, $nuevo_monto_total,
                            $usuario_actualizador_id, $reserva_id
                        );

                        if ($stmt_update->execute()) {
                            $_SESSION['mensaje_exito'] = "Reserva ID $reserva_id actualizada correctamente.";
                            unset($_SESSION['mod_reserva_id_actual'], $_SESSION['mod_reserva_filtros'], $_SESSION['mod_habitaciones_disponibles']);
                            header("Location: gestionar_reservas.php");
                            exit();
                        } else {
                            $_SESSION['mensaje_error_mod'] = "Error al actualizar la reserva: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    }
                }
            }
        }
        header("Location: modificar_reserva.php?id=" . $reserva_id); // Recargar con error
        exit();
    }
}


// --- LÓGICA GET PARA CARGAR DATOS DE LA RESERVA POR PRIMERA VEZ ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($_SESSION['mod_habitaciones_disponibles']) || !empty($filtros_guardados_mod) ) {
    // Cargar datos de la reserva solo si no es un POST de actualización fallido
    // o si estamos volviendo de una búsqueda de habitaciones.
    $stmt_load = $conn->prepare(
        "SELECT r.*, c.nombres as cliente_nombres, c.apellidos as cliente_apellidos, c.dni as cliente_dni
         FROM reservas r 
         JOIN clientes c ON r.cliente_dni = c.dni
         WHERE r.id = ? AND r.estado = 'Confirmada'" // Solo modificar confirmadas
    );
    $stmt_load->bind_param("i", $reserva_id);
    $stmt_load->execute();
    $result_load = $stmt_load->get_result();
    if ($result_load->num_rows === 1) {
        $reserva_actual = $result_load->fetch_assoc();
        $cliente_info = [
            'dni' => $reserva_actual['cliente_dni'],
            'nombres' => $reserva_actual['cliente_nombres'],
            'apellidos' => $reserva_actual['cliente_apellidos']
        ];
        // Poblar filtros guardados si no hay una búsqueda activa
        if (empty($filtros_guardados_mod)) {
            $_SESSION['mod_reserva_filtros'] = [
                'fecha_entrada' => date('Y-m-d', strtotime($reserva_actual['fecha_entrada'])),
                'fecha_salida' => date('Y-m-d', strtotime($reserva_actual['fecha_salida'])),
                // No pre-seleccionamos tipo ni capacidad para que el usuario pueda buscar libremente
            ];
            $filtros_guardados_mod = $_SESSION['mod_reserva_filtros'];
        }

    } else {
        $_SESSION['mensaje_error'] = "Reserva no encontrada o no se puede modificar (ID: $reserva_id).";
        unset($_SESSION['mod_reserva_id_actual']);
        header("Location: gestionar_reservas.php");
        exit();
    }
    $stmt_load->close();
}


// Limpiar mensajes después de asignarlos
$mensaje_error_mod_disp = $_SESSION['mensaje_error_mod'] ?? null;
$mensaje_exito_mod_disp = $_SESSION['mensaje_exito_mod'] ?? null;
$mensaje_info_mod_disp = $_SESSION['mensaje_info_mod'] ?? null;
unset($_SESSION['mensaje_error_mod'], $_SESSION['mensaje_exito_mod'], $_SESSION['mensaje_info_mod']);

$conn->close();

if (!$reserva_actual) { // Si después de todo, no se cargó la reserva
    $_SESSION['mensaje_error'] = "No se pudieron cargar los datos de la reserva para modificar.";
    unset($_SESSION['mod_reserva_id_actual']);
    header("Location: gestionar_reservas.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Modificar Reserva - Hotel</title>
</head>
<body>
  <header>
    <h1>Modificar Reserva ID: <?php echo htmlspecialchars($reserva_id); ?></h1>
    <nav>
        <a href="gestionar_reservas.php">Volver a Gestionar Reservas</a>
    </nav>
  </header>
  <hr>
  <?php if ($mensaje_error_mod_disp): ?><p style="color:red; font-weight:bold;"><?php echo $mensaje_error_mod_disp; ?></p><?php endif; ?>
  <?php if ($mensaje_exito_mod_disp): ?><p style="color:green; font-weight:bold;"><?php echo $mensaje_exito_mod_disp; ?></p><?php endif; ?>
  <?php if ($mensaje_info_mod_disp): ?><p style="color:blue;"><?php echo $mensaje_info_mod_disp; ?></p><?php endif; ?>
  <hr>

  <section>
    <h2>Cliente</h2>
    <?php if ($cliente_info): ?>
        <p>
            <b>DNI:</b> <?php echo htmlspecialchars($cliente_info['dni']); ?><br>
            <b>Nombre:</b> <?php echo htmlspecialchars($cliente_info['nombres'] . " " . $cliente_info['apellidos']); ?>
        </p>
        <p><em>(La modificación de cliente asignado a la reserva no está implementada en este formulario).</em></p>
    <?php endif; ?>
  </section>
  <hr>

  <form action="modificar_reserva.php" method="POST">
    <input type="hidden" name="accion" value="actualizar_reserva">
    
    <h3>Fechas y Habitación</h3>
    <p><em>Si cambia las fechas o necesita otra habitación, use la búsqueda de abajo y luego seleccione una.</em></p>

    <label for="fecha_entrada">Fecha de Entrada Actual:</label><br>
    <input type="date" id="fecha_entrada" name="fecha_entrada" 
           value="<?php echo htmlspecialchars($filtros_guardados_mod['fecha_entrada'] ?? date('Y-m-d', strtotime($reserva_actual['fecha_entrada']))); ?>" required><br><br>

    <label for="fecha_salida">Fecha de Salida Actual:</label><br>
    <input type="date" id="fecha_salida" name="fecha_salida" 
           value="<?php echo htmlspecialchars($filtros_guardados_mod['fecha_salida'] ?? date('Y-m-d', strtotime($reserva_actual['fecha_salida']))); ?>" required><br><br>

    <p><b>Habitación Asignada Actualmente:</b> 
        <?php
        // Obtener nombre de la habitación actual para mostrar
        $conn_temp = new mysqli($host, $user, $password, $database); // Re-conectar para esta info
        $stmt_hab_actual_nombre = $conn_temp->prepare("SELECT nombre FROM habitaciones WHERE id = ?");
        $stmt_hab_actual_nombre->bind_param("i", $reserva_actual['habitacion_id']);
        $stmt_hab_actual_nombre->execute();
        $hab_actual_nombre_res = $stmt_hab_actual_nombre->get_result()->fetch_assoc();
        echo htmlspecialchars($hab_actual_nombre_res['nombre'] ?? 'N/D');
        $stmt_hab_actual_nombre->close();
        $conn_temp->close();
        ?>
        (ID: <?php echo htmlspecialchars($reserva_actual['habitacion_id']); ?>)
    </p>
    
    <input type="hidden" name="habitacion_id_original" value="<?php echo htmlspecialchars($reserva_actual['habitacion_id']); ?>">


    <h3>Buscar Habitaciones Alternativas (Opcional)</h3>
    <!-- Formulario anidado conceptualmente, pero se procesa por separado con JS o como acción diferente -->
    <!-- Por simplicidad, lo haremos con una acción POST separada -->
    <fieldset>
        <legend>Filtros para buscar otra habitación</legend>
        <label for="tipo_habitacion_filtro">Tipo de Habitación:</label><br>
        <select id="tipo_habitacion_filtro_busqueda" name="tipo_habitacion_filtro"> <!-- Nombre diferente para no chocar -->
            <option value="">Cualquiera</option>
            <?php foreach ($tipos_habitacion as $tipo): ?>
            <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($filtros_guardados_mod['tipo_habitacion_filtro']) && $filtros_guardados_mod['tipo_habitacion_filtro'] == $tipo['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tipo['nombre']); ?>
            </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="capacidad_filtro">Capacidad Mínima:</label><br>
        <input type="number" id="capacidad_filtro_busqueda" name="capacidad_filtro" 
               value="<?php echo htmlspecialchars($filtros_guardados_mod['capacidad_filtro'] ?? 1); ?>" min="1"><br><br>
        
        <button type="submit" formmethod="POST" formaction="modificar_reserva.php?id=<?php echo $reserva_id; ?>" name="accion" value="buscar_habitaciones_mod">Buscar Habitaciones con Fechas Arriba</button>
    </fieldset>
    <br>

    <?php if (!empty($habitaciones_disponibles_mod)): ?>
        <h4>Habitaciones Alternativas Disponibles (o la actual si sigue disponible)</h4>
        <p>Seleccione una habitación de la lista. Si no selecciona ninguna nueva, se intentará mantener la actual.</p>
        <table border="1" style="width:100%; border-collapse: collapse;">
            <thead><tr><th>Seleccionar</th><th>Nombre</th><th>Tipo</th><th>Capacidad</th><th>Precio/Noche</th></tr></thead>
            <tbody>
                <?php foreach ($habitaciones_disponibles_mod as $hab_disp): ?>
                <tr>
                    <td><input type="radio" name="habitacion_id_seleccionada_nueva" value="<?php echo $hab_disp['id']; ?>" 
                               <?php // echo ($hab_disp['id'] == $reserva_actual['habitacion_id'] ? 'checked' : ''); ?>> <!-- No pre-check para forzar elección si busca -->
                    </td>
                    <td><?php echo htmlspecialchars($hab_disp['nombre_habitacion']); ?></td>
                    <td><?php echo htmlspecialchars($hab_disp['tipo_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($hab_disp['capacidad']); ?></td>
                    <td>S/ <?php echo htmlspecialchars(number_format($hab_disp['precio'], 2)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <label for="habitacion_id_seleccionada_input">ID de Habitación a asignar (requerido si cambia):</label><br>
        <input type="number" name="habitacion_id_seleccionada" id="habitacion_id_seleccionada_input" value="<?php echo htmlspecialchars($reserva_actual['habitacion_id']); ?>" required>
        <p><small>Copie el ID de la tabla de arriba si elige una nueva, o deje el ID actual.</small></p>

    <?php else: ?>
        <input type="hidden" name="habitacion_id_seleccionada" value="<?php echo htmlspecialchars($reserva_actual['habitacion_id']); ?>">
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'buscar_habitaciones_mod'): ?>
        <p>No se encontraron otras habitaciones con los criterios. Puede intentar mantener la actual si las fechas no generan conflicto.</p>
        <?php endif; ?>
    <?php endif; ?>
    <br>

    <h3>Otros Detalles de la Reserva</h3>
    <label for="modo_reserva">Modo de Reserva:</label><br>
    <select id="modo_reserva" name="modo_reserva" required>
        <option value="Simple" <?php echo ($reserva_actual['modo_reserva'] == 'Simple' ? 'selected' : ''); ?>>Simple</option>
        <option value="Anticipada" <?php echo ($reserva_actual['modo_reserva'] == 'Anticipada' ? 'selected' : ''); ?>>Anticipada</option>
    </select><br><br>

    <label for="metodo_pago">Método de Pago (Opcional):</label><br>
    <select id="metodo_pago" name="metodo_pago">
        <option value="">-- Seleccionar --</option>
        <option value="Efectivo" <?php echo ($reserva_actual['metodo_pago'] == 'Efectivo' ? 'selected' : ''); ?>>Efectivo</option>
        <option value="Transferencia" <?php echo ($reserva_actual['metodo_pago'] == 'Transferencia' ? 'selected' : ''); ?>>Transferencia</option>
        <option value="Tarjeta" <?php echo ($reserva_actual['metodo_pago'] == 'Tarjeta' ? 'selected' : ''); ?>>Tarjeta</option>
    </select><br><br>

    <button type="submit" name="accion" value="actualizar_reserva">Guardar Cambios en la Reserva</button>
  </form>
  <hr>
  <footer>
    <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>

<script>
    // Pequeño script para facilitar la selección de habitación de la tabla
    document.querySelectorAll('input[name="habitacion_id_seleccionada_nueva"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('habitacion_id_seleccionada_input').value = this.value;
        });
    });
</script>

</body>
</html>