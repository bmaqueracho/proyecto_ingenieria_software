<?php
session_start();
// RUTA CORREGIDA PARA TU ESTRUCTURA
require_once '../../conexion.php'; 

// --- PARTE 1: LÓGICA DE PROCESAMIENTO (BACKEND) ---

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    // Si es una petición AJAX, respondemos con JSON, si no, es un acceso directo y no mostramos nada.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    }
    exit();
}

$reserva_id = $_SESSION['mod_reserva_id_actual'] = $_GET['id'] ?? $_SESSION['mod_reserva_id_actual'] ?? null;
if (!ctype_digit((string)$reserva_id)) {
    $_SESSION['mensaje_exito_global'] = "ID de reserva inválido."; // Usar global para la página de gestión
    echo json_encode(['status' => 'error', 'action' => 'redirect_to_gestionar']);
    exit();
}

// ---- MANEJO DE ACCIONES POST (VÍA AJAX) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion_post = $_POST['accion'] ?? '';
    $response = ['status' => 'error']; // Respuesta por defecto

    // ACCIÓN: BUSCAR HABITACIONES ALTERNATIVAS
    if ($accion_post === 'buscar_habitaciones_mod') {
        $fecha_entrada_str = $_POST['fecha_entrada'];
        $fecha_salida_str = $_POST['fecha_salida'];
        $tipo_id_filtro = $_POST['tipo_habitacion_filtro'] ?? null;
        $capacidad_filtro = $_POST['capacidad_filtro'] ?? 1;

        $_SESSION['mod_reserva_filtros'] = $_POST; // Guardar filtros
        unset($_SESSION['mod_habitaciones_disponibles']); // Limpiar resultados anteriores

        if (empty($fecha_entrada_str) || empty($fecha_salida_str) || $fecha_salida_str <= $fecha_entrada_str) {
            $_SESSION['mensaje_error_mod'] = "Fechas inválidas.";
        } else {
            // Toda la lógica de búsqueda de habitaciones se mantiene igual
            $fecha_entrada_sql = $fecha_entrada_str . " 14:00:00";
            $fecha_salida_sql = $fecha_salida_str . " 12:00:00";
            $sql_disponibles = "SELECT h.id, h.nombre AS nombre_habitacion, h.precio, h.capacidad, th.nombre AS tipo_nombre
                               FROM habitaciones h
                               JOIN tipo_habitacion th ON h.tipo_id = th.id
                               WHERE h.estado = 'Disponible' AND h.capacidad >= ? ";
            $params = [$capacidad_filtro];
            $types = "i";
            if (!empty($tipo_id_filtro)) {
                $sql_disponibles .= " AND h.tipo_id = ? ";
                $params[] = $tipo_id_filtro; $types .= "i";
            }
            $sql_disponibles .= " AND h.id NOT IN (
                                   SELECT r.habitacion_id FROM reservas r
                                   WHERE r.habitacion_id IS NOT NULL AND r.estado = 'Confirmada'
                                   AND r.id != ? AND (r.fecha_entrada < ? AND r.fecha_salida > ?)
                                 ) ORDER BY h.nombre";
            array_push($params, $reserva_id, $fecha_salida_sql, $fecha_entrada_sql);
            $types .= "iss";

            $stmt_disp = $conexion->prepare($sql_disponibles);
            $stmt_disp->bind_param($types, ...$params);
            $stmt_disp->execute();
            $result_disp = $stmt_disp->get_result();
            $habitaciones = [];
            while ($hab = $result_disp->fetch_assoc()) { $habitaciones[] = $hab; }
            $_SESSION['mod_habitaciones_disponibles'] = $habitaciones;
            if (empty($habitaciones)) {
                $_SESSION['mensaje_info_mod'] = "No hay otras habitaciones disponibles con esos criterios.";
            } else {
                $_SESSION['mensaje_exito_mod'] = count($habitaciones) . " habitación(es) alternativa(s) encontrada(s).";
            }
            $stmt_disp->close();
        }
        $response = ['status' => 'ok', 'action' => 'reload_form'];
    }

    // ACCIÓN: ACTUALIZAR LA RESERVA
    elseif ($accion_post === 'actualizar_reserva') {
        $nueva_habitacion_id = (int)$_POST['habitacion_id_seleccionada'];
        $nueva_fecha_entrada_str = $_POST['fecha_entrada'];
        $nueva_fecha_salida_str = $_POST['fecha_salida'];
        // ... (resto de variables POST)
        $nuevo_modo_reserva = $_POST['modo_reserva'];
        $nuevo_metodo_pago = empty($_POST['metodo_pago']) ? null : $_POST['metodo_pago'];

        // Lógica de validación y cálculo se mantiene igual...
        $diferencia = (new DateTime($nueva_fecha_salida_str))->diff(new DateTime($nueva_fecha_entrada_str));
        $nueva_estancia = max(1, (int)$diferencia->days);
        
        $stmt_hab_info = $conexion->prepare("SELECT precio FROM habitaciones WHERE id = ?");
        $stmt_hab_info->bind_param("i", $nueva_habitacion_id); $stmt_hab_info->execute();
        $hab_data = $stmt_hab_info->get_result()->fetch_assoc();
        $stmt_hab_info->close();

        if (!$hab_data) {
            $_SESSION['mensaje_error_mod'] = "La habitación seleccionada no existe.";
        } else {
            $nuevo_monto_total = $hab_data['precio'] * $nueva_estancia;
            $nueva_fecha_entrada_sql = $nueva_fecha_entrada_str . " 14:00:00";
            $nueva_fecha_salida_sql = $nueva_fecha_salida_str . " 12:00:00";
            
            // Lógica de comprobación de conflictos se mantiene igual...
            $stmt_check_conflict = $conexion->prepare("SELECT COUNT(*) as count FROM reservas WHERE habitacion_id = ? AND estado = 'Confirmada' AND id != ? AND (fecha_entrada < ? AND fecha_salida > ?)");
            $stmt_check_conflict->bind_param("iiss", $nueva_habitacion_id, $reserva_id, $nueva_fecha_salida_sql, $nueva_fecha_entrada_sql);
            $stmt_check_conflict->execute();
            $conflict_result = $stmt_check_conflict->get_result()->fetch_assoc();
            $stmt_check_conflict->close();

            if ($conflict_result['count'] > 0) {
                $_SESSION['mensaje_error_mod'] = "Conflicto de reserva. La habitación y fechas seleccionadas ya no están disponibles.";
            } else {
                // Lógica de actualización de la base de datos se mantiene igual
                $sql_update = "UPDATE reservas SET habitacion_id = ?, fecha_entrada = ?, fecha_salida = ?, modo_reserva = ?, metodo_pago = ?, estancia = ?, monto_total = ?, usuario_id = ? WHERE id = ?";
                $stmt_update = $conexion->prepare($sql_update);
                $usuario_actualizador_id = $_SESSION['usuario_id'];
                $stmt_update->bind_param("issssidii", $nueva_habitacion_id, $nueva_fecha_entrada_sql, $nueva_fecha_salida_sql, $nuevo_modo_reserva, $nuevo_metodo_pago, $nueva_estancia, $nuevo_monto_total, $usuario_actualizador_id, $reserva_id);

                if ($stmt_update->execute()) {
                    $_SESSION['mensaje_exito_global'] = "Reserva ID $reserva_id actualizada correctamente.";
                    unset($_SESSION['mod_reserva_id_actual'], $_SESSION['mod_reserva_filtros'], $_SESSION['mod_habitaciones_disponibles']);
                    $response = ['status' => 'success', 'action' => 'redirect_to_gestionar'];
                } else {
                    $_SESSION['mensaje_error_mod'] = "Error al actualizar la reserva: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
        }
    }
    
    // NO MÁS REDIRECCIONES, SOLO RESPUESTAS JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    $conexion->close();
    exit();
}


// --- PARTE 2: LÓGICA DE CARGA DE DATOS PARA LA VISTA (GET) ---

// Si no es un POST, es una carga de la vista. Limpiamos sesión si es la primera carga.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['id'])) {
    unset($_SESSION['mod_reserva_filtros'], $_SESSION['mod_habitaciones_disponibles']);
}

$reserva_actual = null;
$cliente_info = null;
$tipos_habitacion = [];
$habitaciones_disponibles_mod = $_SESSION['mod_habitaciones_disponibles'] ?? [];
$filtros_guardados_mod = $_SESSION['mod_reserva_filtros'] ?? [];

// Cargar datos de la reserva actual
$stmt_load = $conexion->prepare("SELECT r.*, c.nombres as cliente_nombres, c.apellidos as cliente_apellidos, c.dni as cliente_dni FROM reservas r JOIN clientes c ON r.cliente_dni = c.dni WHERE r.id = ? AND (r.estado = 'Confirmada' OR r.estado = 'Pendiente')");
$stmt_load->bind_param("i", $reserva_id);
$stmt_load->execute();
$result_load = $stmt_load->get_result();
if ($result_load->num_rows === 1) {
    $reserva_actual = $result_load->fetch_assoc();
    $cliente_info = ['dni' => $reserva_actual['cliente_dni'], 'nombres' => $reserva_actual['cliente_nombres'], 'apellidos' => $reserva_actual['cliente_apellidos']];
    if (empty($filtros_guardados_mod)) {
        $filtros_guardados_mod = [
            'fecha_entrada' => date('Y-m-d', strtotime($reserva_actual['fecha_entrada'])),
            'fecha_salida' => date('Y-m-d', strtotime($reserva_actual['fecha_salida'])),
        ];
    }
} else {
    $_SESSION['mensaje_exito_global'] = "La reserva (ID: $reserva_id) no se puede modificar o no existe.";
    // Esto se manejará en el JS para redirigir
    // (no se puede redirigir con header aquí porque ya se va a imprimir HTML)
}
$stmt_load->close();

// Cargar tipos de habitación
$result_tipos_q = $conexion->query("SELECT id, nombre FROM tipo_habitacion ORDER BY nombre");
if ($result_tipos_q) { while ($tipo = $result_tipos_q->fetch_assoc()) { $tipos_habitacion[] = $tipo; } }

// Recuperar y limpiar mensajes de sesión
$mensaje_error = $_SESSION['mensaje_error_mod'] ?? null;
$mensaje_exito = $_SESSION['mensaje_exito_mod'] ?? null;
$mensaje_info = $_SESSION['mensaje_info_mod'] ?? null;
unset($_SESSION['mensaje_error_mod'], $_SESSION['mensaje_exito_mod'], $_SESSION['mensaje_info_mod']);

$conexion->close();

if (!$reserva_actual) {
    echo "<section><div class='alert alert-danger'>Error: La reserva no se puede cargar para modificar. Puede que ya haya sido cancelada o completada.</div>
    <a href='#' onclick=\"event.preventDefault(); cargarContenido('../reservas/gestionar_reservas.php');\" class='btn btn-primary'>Volver a Gestión de Reservas</a></section>";
    exit();
}

// --- PARTE 3: HTML DE LA VISTA (FRONTEND) ---
?>
<section id="modificar-reserva-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-edit"></i> Modificar Reserva ID: <?php echo htmlspecialchars($reserva_id); ?></h2>
        <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/gestionar_reservas.php');" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <div id="mod-alert-container">
        <?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div><?php endif; ?>
        <?php if ($mensaje_exito): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div><?php endif; ?>
        <?php if ($mensaje_info): ?><div class="alert alert-info"><?php echo htmlspecialchars($mensaje_info); ?></div><?php endif; ?>
    </div>

    <div class="card action-card p-4 mb-4">
        <h4 class="mb-3">Datos del Cliente</h4>
        <p class="mb-0"><b>Cliente:</b> <?php echo htmlspecialchars($cliente_info['nombres'] . " " . $cliente_info['apellidos']); ?> (DNI: <?php echo htmlspecialchars($cliente_info['dni']); ?>)</p>
        <small class="text-white-50">La reasignación de la reserva a otro cliente no está disponible en esta pantalla.</small>
    </div>

    <form id="form-modificar-reserva" onsubmit="event.preventDefault();">
        <div class="card action-card p-4">
            <h4 class="mb-3">Buscar Nuevas Fechas o Habitación</h4>
            <div class="row g-3 align-items-end">
                <div class="col-md-3"><label class="form-label">Entrada</label><input type="date" name="fecha_entrada" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados_mod['fecha_entrada']); ?>" required></div>
                <div class="col-md-3"><label class="form-label">Salida</label><input type="date" name="fecha_salida" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados_mod['fecha_salida']); ?>" required></div>
                <div class="col-md-3"><label class="form-label">Tipo Hab.</label><select name="tipo_habitacion_filtro" class="form-select"><option value="">Cualquiera</option><?php foreach ($tipos_habitacion as $tipo): ?><option value="<?php echo $tipo['id']; ?>" <?php echo (isset($filtros_guardados_mod['tipo_habitacion_filtro']) && $filtros_guardados_mod['tipo_habitacion_filtro'] == $tipo['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label">Capacidad</label><input type="number" name="capacidad_filtro" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados_mod['capacidad_filtro'] ?? 1); ?>" min="1"></div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-primary w-100" onclick="procesarAccionModificar('buscar_habitaciones_mod');"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <?php if (!empty($habitaciones_disponibles_mod)): ?>
            <hr class="my-4">
            <h5 class="mt-4">Habitaciones Alternativas Disponibles</h5>
            <div class="table-responsive"><table class="table table-dark table-striped table-hover"><thead><tr><th></th><th>Nombre</th><th>Tipo</th><th>Cap.</th><th>Precio/Noche</th></tr></thead><tbody>
                <?php foreach ($habitaciones_disponibles_mod as $hab): ?>
                <tr><td><input type="radio" name="habitacion_id_seleccionada" value="<?php echo $hab['id']; ?>" class="form-check-input"></td><td><?php echo htmlspecialchars($hab['nombre_habitacion']); ?></td><td><?php echo htmlspecialchars($hab['tipo_nombre']); ?></td><td><?php echo htmlspecialchars($hab['capacidad']); ?></td><td>S/ <?php echo htmlspecialchars(number_format($hab['precio'], 2)); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>

            <hr class="my-4">
            <h4 class="mb-3">Confirmar Cambios</h4>
            <div class="row g-3">
                 <div class="col-md-4">
                    <label class="form-label">Habitación Asignada</label>
                    <input type="number" name="habitacion_id_seleccionada" id="habitacion_id_final" class="form-control" value="<?php echo htmlspecialchars($reserva_actual['habitacion_id']); ?>" required>
                    <small class="text-white-50">Seleccione una de la tabla de arriba o mantenga la actual.</small>
                </div>
                <div class="col-md-4"><label class="form-label">Modo</label><select name="modo_reserva" class="form-select" required><option value="Simple" <?php echo ($reserva_actual['modo_reserva'] == 'Simple' ? 'selected' : ''); ?>>Simple</option><option value="Anticipada" <?php echo ($reserva_actual['modo_reserva'] == 'Anticipada' ? 'selected' : ''); ?>>Anticipada</option></select></div>
                <div class="col-md-4"><label class="form-label">Pago</label><select name="metodo_pago" class="form-select"><option value="">Opcional</option><option value="Efectivo" <?php echo ($reserva_actual['metodo_pago'] == 'Efectivo' ? 'selected' : ''); ?>>Efectivo</option><option value="Transferencia" <?php echo ($reserva_actual['metodo_pago'] == 'Transferencia' ? 'selected' : ''); ?>>Transferencia</option><option value="Tarjeta" <?php echo ($reserva_actual['metodo_pago'] == 'Tarjeta' ? 'selected' : ''); ?>>Tarjeta</option></select></div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-lg btn-success" onclick="procesarAccionModificar('actualizar_reserva');">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</section>

<script>
    // Script para autocompletar el ID de la habitación al seleccionarla
    document.querySelectorAll('input[name="habitacion_id_seleccionada"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Hay dos inputs con este nombre, el radio y el final. Buscamos el de tipo number.
            document.getElementById('habitacion_id_final').value = this.value;
        });
    });

    // Función central para manejar las acciones del formulario vía AJAX
    function procesarAccionModificar(accion) {
        const form = document.getElementById('form-modificar-reserva');
        const formData = new FormData(form);
        formData.append('accion', accion); // Añadimos la acción específica que se está ejecutando

        // Muestra un indicador de carga
        const container = document.getElementById('modificar-reserva-form');
        container.style.opacity = '0.5'; // Atenuar el formulario actual

        fetch(`../reservas/modificar_reserva.php?id=<?php echo $reserva_id; ?>`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.action === 'reload_form') {
                // Si la acción fue buscar, simplemente recargamos el formulario de modificación
                // para que muestre los resultados (habitaciones) y mensajes de la sesión.
                cargarContenido(`../reservas/modificar_reserva.php?id=<?php echo $reserva_id; ?>`);
            } else if (data.action === 'redirect_to_gestionar') {
                // Si la actualización fue exitosa o hubo un error de ID, vamos a la tabla de gestión.
                // El mensaje de éxito/error se mostrará allí.
                cargarContenido('../reservas/gestionar_reservas.php');
            } else {
                // Si hubo un error en la actualización pero debemos permanecer en el formulario
                // (ej. validación fallida), recargamos el formulario para ver el mensaje de error.
                cargarContenido(`../reservas/modificar_reserva.php?id=<?php echo $reserva_id; ?>`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.style.opacity = '1';
            alert('Ocurrió un error de conexión al procesar la solicitud.');
        });
    }
</script>