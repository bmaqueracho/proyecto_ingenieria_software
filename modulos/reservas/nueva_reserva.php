<?php
session_start();
require_once '../../conexion.php'; // $conn

// Verificar sesión y rol (Recepcionista o Administrador pueden crear reservas)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['cargo'] !== 'Recepcionista' && $_SESSION['cargo'] !== 'Administrador')) {
    header("Location: ../autch/login.html");
    exit();
}

// Acción para limpiar toda la sesión de reserva
if (isset($_GET['accion_limpiar']) && $_GET['accion_limpiar'] == '1') {
    unset($_SESSION['reserva_dni_cliente']);
    unset($_SESSION['reserva_cliente_info']);
    unset($_SESSION['reserva_filtros']);
    unset($_SESSION['reserva_habitaciones_disponibles']);
    unset($_SESSION['reserva_habitacion_seleccionada_info']); // Si la usas
    header("Location: nueva_reserva.php?etapa=buscar_cliente");
    exit();
}

// Acción para limpiar solo el cliente seleccionado y volver a buscar/registrar
if (isset($_GET['limpiar_cliente']) && $_GET['limpiar_cliente'] == '1') {
    unset($_SESSION['reserva_dni_cliente']);
    unset($_SESSION['reserva_cliente_info']);
    // Opcional: podrías querer limpiar también los filtros si cambiar de cliente implica nueva búsqueda de habitaciones
    // unset($_SESSION['reserva_filtros']);
    // unset($_SESSION['reserva_habitaciones_disponibles']);
    header("Location: nueva_reserva.php?etapa=buscar_cliente");
    exit();
}


$etapa = $_GET['etapa'] ?? ($_SESSION['reserva_dni_cliente'] ? 'datos_reserva' : 'buscar_cliente');
$cliente_info = $_SESSION['reserva_cliente_info'] ?? null;
$habitaciones_disponibles = $_SESSION['reserva_habitaciones_disponibles'] ?? [];
$tipos_habitacion = [];

// Cargar tipos de habitación para el filtro
$result_tipos = $conn->query("SELECT id, nombre FROM tipo_habitacion ORDER BY nombre");
if ($result_tipos) {
    while ($tipo = $result_tipos->fetch_assoc()) {
        $tipos_habitacion[] = $tipo;
    }
}

// --- LÓGICA DE PROCESAMIENTO POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion_post = $_POST['accion'] ?? '';

    if ($accion_post === 'buscar_cliente_dni') {
        $dni_buscar = trim($_POST['dni_buscar']);
        $stmt = $conn->prepare("SELECT dni, nombres, apellidos FROM clientes WHERE dni = ?");
        $stmt->bind_param("s", $dni_buscar);
        $stmt->execute();
        $result_cliente = $stmt->get_result();
        if ($result_cliente->num_rows > 0) {
            $cliente_data = $result_cliente->fetch_assoc();
            $_SESSION['reserva_dni_cliente'] = $cliente_data['dni'];
            $_SESSION['reserva_cliente_info'] = $cliente_data;
            $_SESSION['mensaje_exito'] = "Cliente " . htmlspecialchars($cliente_data['nombres']) . " seleccionado.";
            $etapa = 'datos_reserva';
        } else {
            $_SESSION['mensaje_error'] = "Cliente con DNI " . htmlspecialchars($dni_buscar) . " no encontrado. Puede registrarlo.";
            $etapa = 'buscar_cliente';
        }
        $stmt->close();
        header("Location: nueva_reserva.php?etapa=" . $etapa);
        exit();
    }
    elseif ($accion_post === 'registrar_cliente_reserva') {
        $dni_reg = trim($_POST['dni_reg']);
        $nombres_reg = trim($_POST['nombres_reg']);
        $apellidos_reg = trim($_POST['apellidos_reg']);
        $telefono_reg = !empty($_POST['telefono_reg']) ? trim($_POST['telefono_reg']) : 'Sin Telefono';

        if (!preg_match('/^\d{8,10}$/', $dni_reg)) { // Asumiendo DNI de 8 a 10 dígitos
            $_SESSION['mensaje_error'] = "DNI inválido. Debe contener de 8 a 10 dígitos.";
        } elseif (empty($nombres_reg) || empty($apellidos_reg)) {
            $_SESSION['mensaje_error'] = "Nombres y Apellidos son obligatorios.";
        } else {
            $stmt_check = $conn->prepare("SELECT dni FROM clientes WHERE dni = ?");
            $stmt_check->bind_param("s", $dni_reg);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows === 0) {
                $stmt_insert = $conn->prepare("INSERT INTO clientes (dni, nombres, apellidos, telefono, observacion) VALUES (?, ?, ?, ?, 'Registrado durante reserva')");
                $stmt_insert->bind_param("ssss", $dni_reg, $nombres_reg, $apellidos_reg, $telefono_reg);
                if ($stmt_insert->execute()) {
                    $_SESSION['reserva_dni_cliente'] = $dni_reg;
                    $_SESSION['reserva_cliente_info'] = ['dni' => $dni_reg, 'nombres' => $nombres_reg, 'apellidos' => $apellidos_reg];
                    $_SESSION['mensaje_exito'] = "Cliente " . htmlspecialchars($nombres_reg) . " registrado y seleccionado.";
                    $etapa = 'datos_reserva';
                } else {
                    $_SESSION['mensaje_error'] = "Error al registrar cliente: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                 $_SESSION['mensaje_error'] = "Cliente con DNI " . htmlspecialchars($dni_reg) . " ya existe. Búsquelo.";
            }
            $stmt_check->close();
        }
        if (!isset($_SESSION['reserva_dni_cliente'])) { // Si hubo error y no se seleccionó
            $etapa = 'buscar_cliente';
        }
        header("Location: nueva_reserva.php?etapa=" . $etapa);
        exit();
    }
    elseif ($accion_post === 'buscar_habitaciones') {
        if (!isset($_SESSION['reserva_dni_cliente'])) {
            $_SESSION['mensaje_error'] = "Primero debe seleccionar o registrar un cliente.";
            header("Location: nueva_reserva.php?etapa=buscar_cliente");
            exit();
        }
        $fecha_entrada_str = $_POST['fecha_entrada']; // YYYY-MM-DD
        $fecha_salida_str = $_POST['fecha_salida'];   // YYYY-MM-DD
        $tipo_id_filtro = $_POST['tipo_habitacion_filtro'] ?? null;
        $capacidad_filtro = $_POST['capacidad_filtro'] ?? 1;

        $_SESSION['reserva_filtros'] = $_POST; // Guardar filtros para repoblar
        unset($_SESSION['reserva_habitaciones_disponibles']); // Limpiar resultados anteriores

        if (empty($fecha_entrada_str) || empty($fecha_salida_str) || $fecha_salida_str <= $fecha_entrada_str) {
            $_SESSION['mensaje_error'] = "Fechas inválidas. La fecha de salida debe ser posterior a la de entrada.";
        } else {
            // Check-in 14:00, Check-out 12:00
            $fecha_entrada_sql = $fecha_entrada_str . " 14:00:00";
            $fecha_salida_sql = $fecha_salida_str . " 12:00:00";

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
                                   AND (r.fecha_entrada < ? AND r.fecha_salida > ?)
                                 ) ORDER BY h.nombre";
            $params_bind_values[] = $fecha_salida_sql;
            $params_bind_values[] = $fecha_entrada_sql;
            $types_bind_string .= "ss";

            $stmt_disp = $conn->prepare($sql_disponibles);
            if (!$stmt_disp) {
                $_SESSION['mensaje_error'] = "Error al preparar la consulta de disponibilidad: " . $conn->error;
            } else {
                $stmt_disp->bind_param($types_bind_string, ...$params_bind_values);
                $stmt_disp->execute();
                $result_disp = $stmt_disp->get_result();
                $current_habitaciones_disponibles = [];
                while ($hab = $result_disp->fetch_assoc()) {
                    $current_habitaciones_disponibles[] = $hab;
                }
                $_SESSION['reserva_habitaciones_disponibles'] = $current_habitaciones_disponibles; // Guardar en sesión
                $stmt_disp->close();

                if (empty($current_habitaciones_disponibles)) {
                    $_SESSION['mensaje_info'] = "No hay habitaciones disponibles con los criterios y fechas seleccionados.";
                } else {
                    $_SESSION['mensaje_exito'] = count($current_habitaciones_disponibles) . " habitación(es) encontrada(s).";
                }
            }
        }
        $etapa = 'datos_reserva';
        header("Location: nueva_reserva.php?etapa=" . $etapa);
        exit();
    }
    elseif ($accion_post === 'confirmar_reserva_final') {
        if (!isset($_SESSION['reserva_dni_cliente']) || !isset($_POST['habitacion_id_seleccionada']) || !isset($_SESSION['reserva_filtros'])) {
            $_SESSION['mensaje_error'] = "Faltan datos para confirmar la reserva. Por favor, comience de nuevo.";
            header("Location: nueva_reserva.php?accion_limpiar=1");
            exit();
        }

        $cliente_dni = $_SESSION['reserva_dni_cliente'];
        $habitacion_id = (int)$_POST['habitacion_id_seleccionada'];
        $filtros_guardados = $_SESSION['reserva_filtros'];
        $fecha_entrada_str_confirm = $filtros_guardados['fecha_entrada']; // YYYY-MM-DD
        $fecha_salida_str_confirm = $filtros_guardados['fecha_salida'];   // YYYY-MM-DD

        $modo_reserva = $_POST['modo_reserva'];
        $metodo_pago = $_POST['metodo_pago'] ?? null;

        $fecha_e_obj = new DateTime($fecha_entrada_str_confirm);
        $fecha_s_obj = new DateTime($fecha_salida_str_confirm);
        $diferencia = $fecha_s_obj->diff($fecha_e_obj);
        $estancia = (int)$diferencia->days;

        if ($estancia <= 0) {
            $_SESSION['mensaje_error'] = "La estancia debe ser de al menos 1 día.";
            header("Location: nueva_reserva.php?etapa=datos_reserva");
            exit();
        }

        $stmt_hab_info = $conn->prepare("SELECT precio FROM habitaciones WHERE id = ? AND estado = 'Disponible'");
        $stmt_hab_info->bind_param("i", $habitacion_id);
        $stmt_hab_info->execute();
        $result_hab_info = $stmt_hab_info->get_result();

        if ($result_hab_info->num_rows === 0) {
            $_SESSION['mensaje_error'] = "La habitación seleccionada ya no está disponible o no existe. Por favor, busque de nuevo.";
            unset($_SESSION['reserva_habitaciones_disponibles']); // Limpiar para forzar nueva búsqueda
            header("Location: nueva_reserva.php?etapa=datos_reserva");
            exit();
        }
        $hab_data = $result_hab_info->fetch_assoc();
        $precio_noche = $hab_data['precio'];
        $monto_total = $precio_noche * $estancia;
        $stmt_hab_info->close();

        // Doble verificación de disponibilidad (contra condición de carrera)
        $fecha_entrada_confirm_sql = $fecha_entrada_str_confirm . " 14:00:00";
        $fecha_salida_confirm_sql = $fecha_salida_str_confirm . " 12:00:00";
        $stmt_check_again = $conn->prepare("SELECT COUNT(*) as count FROM reservas WHERE habitacion_id = ? AND estado = 'Confirmada' AND (fecha_entrada < ? AND fecha_salida > ?)");
        $stmt_check_again->bind_param("iss", $habitacion_id, $fecha_salida_confirm_sql, $fecha_entrada_confirm_sql);
        $stmt_check_again->execute();
        $check_result = $stmt_check_again->get_result()->fetch_assoc();
        $stmt_check_again->close();

        if ($check_result['count'] > 0) {
             $_SESSION['mensaje_error'] = "Lo sentimos, la habitación seleccionada acaba de ser reservada por otra persona. Por favor, elija otra o intente con fechas diferentes.";
             unset($_SESSION['reserva_habitaciones_disponibles']);
             header("Location: nueva_reserva.php?etapa=datos_reserva");
             exit();
        }

        $usuario_id = $_SESSION['usuario_id'];
        $fecha_reserva_actual = date('Y-m-d');

        $sql_insert_reserva = "INSERT INTO reservas (fecha_reserva, modo_reserva, metodo_pago, estancia, monto_total, fecha_entrada, fecha_salida, cliente_dni, habitacion_id, usuario_id, estado)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmada')"; // 10 placeholders + 'Confirmada'
        $stmt_insert_res = $conn->prepare($sql_insert_reserva);
        
        // Verifica que $metodo_pago sea NULL si está vacío, para que coincida con la BBDD si permite NULL
        if (empty($metodo_pago)) {
            $metodo_pago = null;
        }

        $stmt_insert_res->bind_param(
            "sssidsssii", // 10 caracteres: s s s i d s s s i i
            $fecha_reserva_actual,
            $modo_reserva,
            $metodo_pago,
            $estancia,
            $monto_total,
            $fecha_entrada_confirm_sql,
            $fecha_salida_confirm_sql,
            $cliente_dni,
            $habitacion_id,
            $usuario_id
        );

        if ($stmt_insert_res->execute()) {
            $_SESSION['mensaje_exito'] = "Reserva confirmada exitosamente. ID de Reserva: " . $stmt_insert_res->insert_id;
            // Limpiar variables de sesión de reserva
            unset($_SESSION['reserva_dni_cliente'], $_SESSION['reserva_cliente_info'], $_SESSION['reserva_filtros'], $_SESSION['reserva_habitaciones_disponibles']);
            header("Location: gestionar_reservas.php");
            exit();
        } else {
            $_SESSION['mensaje_error'] = "Error al confirmar la reserva: " . $stmt_insert_res->error . ". Por favor, intente de nuevo.";
            // No redirigir para que pueda ver los datos y el error, o redirigir a la etapa de datos
            header("Location: nueva_reserva.php?etapa=datos_reserva");
            exit();
        }
        $stmt_insert_res->close();
    }
} // Fin de if ($_SERVER['REQUEST_METHOD'] === 'POST')

// Recuperar datos de sesión para mostrar en el formulario
$cliente_info = $_SESSION['reserva_cliente_info'] ?? null; // Actualizar después de POST
$filtros_guardados = $_SESSION['reserva_filtros'] ?? [];
$habitaciones_disponibles = $_SESSION['reserva_habitaciones_disponibles'] ?? []; // Actualizar después de POST

// Determinar la etapa actual para la vista (después de la lógica POST)
if ($cliente_info && $etapa === 'buscar_cliente') {
    $etapa = 'datos_reserva';
}

// Limpiar mensajes después de asignarlos a variables locales para mostrarlos una vez
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_info = $_SESSION['mensaje_info'] ?? null;
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito'], $_SESSION['mensaje_info']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nueva Reserva - Hotel</title>
  <!-- <link rel="stylesheet" href="estilos.css"> Futuro CSS -->
</head>
<body>
  <header>
    <h1>Crear Nueva Reserva</h1>
    <nav>
        <a href="../dashboard/recepcionista_dashboard.php">Volver al Dashboard de Reservas</a> |
        <a href="nueva_reserva.php?accion_limpiar=1">Iniciar Nueva Reserva (Limpiar Todo)</a>
    </nav>
  </header>

  <hr>
  <?php if ($mensaje_error): ?><p style="color:red; font-weight:bold;"><?php echo $mensaje_error; ?></p><?php endif; ?>
  <?php if ($mensaje_exito): ?><p style="color:green; font-weight:bold;"><?php echo $mensaje_exito; ?></p><?php endif; ?>
  <?php if ($mensaje_info): ?><p style="color:blue;"><?php echo $mensaje_info; ?></p><?php endif; ?>
  <hr>

  <!-- ETAPA 1: BUSCAR O REGISTRAR CLIENTE -->
  <section id="seccion-cliente">
    <h2>Paso 1: Cliente</h2>
    <?php if ($cliente_info): ?>
        <p><b>Cliente Seleccionado:</b> <?php echo htmlspecialchars($cliente_info['nombres'] . " " . $cliente_info['apellidos']); ?> (DNI: <?php echo htmlspecialchars($cliente_info['dni']); ?>)</p>
        <p><a href="nueva_reserva.php?limpiar_cliente=1">Cambiar Cliente</a></p>
    <?php else: ?>
        <form action="nueva_reserva.php?etapa=buscar_cliente" method="POST">
            <h3>Buscar Cliente Existente</h3>
            <label for="dni_buscar">DNI del Cliente:</label><br>
            <input type="text" id="dni_buscar" name="dni_buscar" maxlength="10" pattern="\d{8,10}" title="DNI de 8 a 10 dígitos"><br><br>
            <button type="submit" name="accion" value="buscar_cliente_dni">Buscar Cliente</button>
        </form>
        <hr>
        <form action="nueva_reserva.php?etapa=buscar_cliente" method="POST">
            <h3>Registrar Nuevo Cliente para la Reserva</h3>
            <input type="hidden" name="accion" value="registrar_cliente_reserva">
            <label for="dni_reg">DNI:</label><br>
            <input type="text" id="dni_reg" name="dni_reg" minlength="8" maxlength="10"><br>
            <label for="nombres_reg">Nombres:</label><br>
            <input type="text" id="nombres_reg" name="nombres_reg" maxlength="45"><br>
            <label for="apellidos_reg">Apellidos:</label><br>
            <input type="text" id="apellidos_reg" name="apellidos_reg" maxlength="45"><br>
            <label for="telefono_reg">Teléfono (Opcional):</label><br>
            <input type="text" id="telefono_reg" name="telefono_reg" maxlength="15"><br><br>
            <button type="submit">Registrar y Seleccionar Cliente</button>
        </form>
    <?php endif; ?>
  </section>
  <hr>

  <!-- ETAPA 2: DATOS DE RESERVA Y BÚSQUEDA DE HABITACIONES -->
  <?php if ($etapa === 'datos_reserva' && $cliente_info): ?>
  <section id="seccion-datos-reserva">
    <h2>Paso 2: Fechas y Preferencias de Habitación</h2>
    <form action="nueva_reserva.php?etapa=datos_reserva" method="POST">
        <input type="hidden" name="accion" value="buscar_habitaciones">
        
        <label for="fecha_entrada">Fecha de Entrada:</label><br>
        <input type="date" id="fecha_entrada" name="fecha_entrada" value="<?php echo htmlspecialchars($filtros_guardados['fecha_entrada'] ?? date('Y-m-d')); ?>" required><br><br>

        <label for="fecha_salida">Fecha de Salida:</label><br>
        <input type="date" id="fecha_salida" name="fecha_salida" value="<?php echo htmlspecialchars($filtros_guardados['fecha_salida'] ?? date('Y-m-d', strtotime('+1 day'))); ?>" required><br><br>

        <label for="tipo_habitacion_filtro">Tipo de Habitación (Opcional):</label><br>
        <select id="tipo_habitacion_filtro" name="tipo_habitacion_filtro">
            <option value="">Cualquiera</option>
            <?php foreach ($tipos_habitacion as $tipo): ?>
            <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($filtros_guardados['tipo_habitacion_filtro']) && $filtros_guardados['tipo_habitacion_filtro'] == $tipo['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tipo['nombre']); ?>
            </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="capacidad_filtro">Capacidad Mínima:</label><br>
        <input type="number" id="capacidad_filtro" name="capacidad_filtro" value="<?php echo htmlspecialchars($filtros_guardados['capacidad_filtro'] ?? 1); ?>" min="1" required><br><br>
        
        <button type="submit">Buscar Habitaciones Disponibles</button>
    </form>
    <br>

    <?php if (!empty($habitaciones_disponibles)): ?>
        <h3>Habitaciones Disponibles</h3>
        <form action="nueva_reserva.php?etapa=confirmar_reserva" method="POST"> <!-- La etapa en GET no es necesaria aquí si la lógica POST la maneja -->
            <input type="hidden" name="accion" value="confirmar_reserva_final">
            <table border="1" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th>Precio/Noche</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habitaciones_disponibles as $hab): ?>
                    <tr>
                        <td><input type="radio" name="habitacion_id_seleccionada" value="<?php echo $hab['id']; ?>" required></td>
                        <td><?php echo htmlspecialchars($hab['nombre_habitacion']); ?></td>
                        <td><?php echo htmlspecialchars($hab['tipo_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($hab['capacidad']); ?></td>
                        <td>S/ <?php echo htmlspecialchars(number_format($hab['precio'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <h4>Detalles Adicionales de la Reserva</h4>
            <label for="modo_reserva">Modo de Reserva:</label><br>
            <select id="modo_reserva" name="modo_reserva" required>
                <option value="Simple" selected>Simple</option>
                <option value="Anticipada">Anticipada</option>
            </select><br><br>

            <label for="metodo_pago">Método de Pago (Opcional):</label><br>
            <select id="metodo_pago" name="metodo_pago">
                <option value="">-- Seleccionar --</option>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Tarjeta">Tarjeta</option>
            </select><br><br>
            <button type="submit">Confirmar Reserva</button>
        </form>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($accion_post ?? '') === 'buscar_habitaciones' && empty($habitaciones_disponibles) && !($mensaje_info ?? null)): ?>
        <!-- Este mensaje se muestra si la búsqueda se hizo pero no hubo resultados Y no hay un mensaje_info ya establecido -->
        <p>No se encontraron habitaciones con los criterios especificados para las fechas seleccionadas.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <hr>
  <footer>
    <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>
</body>
</html>