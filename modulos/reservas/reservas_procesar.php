<?php
session_start();
require_once '../../conexion.php';

// Respuesta por defecto
$response = ['status' => 'error', 'message' => 'Acción no reconocida.'];

// Verificar permisos de acceso
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    $response['message'] = 'Acceso denegado.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Solo procesamos peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$accion = $_POST['accion'] ?? null;

// Un switch es más limpio para manejar múltiples acciones
switch ($accion) {
    case 'limpiar_cliente_nr':
        unset($_SESSION['nr_cliente_info'], $_SESSION['nr_filtros'], $_SESSION['nr_habitaciones']);
        $response = ['status' => 'ok', 'action' => 'reload_form'];
        break;

    case 'buscar_cliente_dni':
        $dni_buscar = trim($_POST['dni_buscar']);
        $stmt = $conexion->prepare("SELECT dni, nombres, apellidos FROM clientes WHERE dni = ?");
        $stmt->bind_param("s", $dni_buscar);
        $stmt->execute();
        $result_cliente = $stmt->get_result();
        if ($result_cliente->num_rows > 0) {
            $cliente_data = $result_cliente->fetch_assoc();
            $_SESSION['nr_cliente_info'] = $cliente_data;
            $_SESSION['mensaje_reserva'] = ['tipo' => 'success', 'texto' => "Cliente " . htmlspecialchars($cliente_data['nombres']) . " seleccionado."];
        } else {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'warning', 'texto' => "Cliente con DNI " . htmlspecialchars($dni_buscar) . " no encontrado. Puede registrarlo."];
        }
        $stmt->close();
        $response = ['status' => 'ok', 'action' => 'reload_form'];
        break;

    case 'registrar_cliente_reserva':
        $dni_reg = trim($_POST['dni_reg'] ?? '');
        $nombres_reg = trim($_POST['nombres_reg'] ?? '');
        $apellidos_reg = trim($_POST['apellidos_reg'] ?? '');
        $telefono_reg = trim($_POST['telefono_reg'] ?? '');

        // Validaciones básicas
        if (!preg_match('/^[0-9]{6,15}$/', $dni_reg)) {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "El DNI debe contener solo dígitos (6 a 15 caracteres)."];
            $response = ['status' => 'error', 'action' => 'reload_form'];
            break;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,50}$/u', $nombres_reg)) {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Los nombres solo deben contener letras y espacios (mín. 3 caracteres)."];
            $response = ['status' => 'error', 'action' => 'reload_form'];
            break;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,50}$/u', $apellidos_reg)) {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Los apellidos solo deben contener letras y espacios (mín. 3 caracteres)."];
            $response = ['status' => 'error', 'action' => 'reload_form'];
            break;
        }
        if (!empty($telefono_reg) && !preg_match('/^[0-9]{6,15}$/', $telefono_reg)) {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "El teléfono debe contener solo dígitos (mín. 6 caracteres si se proporciona)."];
            $response = ['status' => 'error', 'action' => 'reload_form'];
            break;
        }

        // Verificar duplicado
        $stmt_check = $conexion->prepare("SELECT dni FROM clientes WHERE dni = ?");
        $stmt_check->bind_param("s", $dni_reg);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $stmt_insert = $conexion->prepare("INSERT INTO clientes (dni, nombres, apellidos, telefono, observacion) VALUES (?, ?, ?, ?, 'Registrado durante reserva')");
            $stmt_insert->bind_param("ssss", $dni_reg, $nombres_reg, $apellidos_reg, $telefono_reg);
            if ($stmt_insert->execute()) {
                $_SESSION['nr_cliente_info'] = ['dni' => $dni_reg, 'nombres' => $nombres_reg, 'apellidos' => $apellidos_reg];
                $_SESSION['mensaje_reserva'] = ['tipo' => 'success', 'texto' => "Cliente " . htmlspecialchars($nombres_reg) . " registrado y seleccionado."];
            } else {
                $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Error al registrar cliente."];
            }
            $stmt_insert->close();
        } else {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'warning', 'texto' => "Cliente con DNI " . htmlspecialchars($dni_reg) . " ya existe. Búsquelo."];
        }
        $stmt_check->close();
        $response = ['status' => 'ok', 'action' => 'reload_form'];
        break;


    case 'buscar_habitaciones':
        if (!isset($_SESSION['nr_cliente_info'])) {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Primero debe seleccionar un cliente."];
        } else {
            $fecha_entrada_str = $_POST['fecha_entrada'];
            $fecha_salida_str = $_POST['fecha_salida'];
            //... (resto de variables de filtro)
            $tipo_id_filtro = $_POST['tipo_habitacion_filtro'] ?? null;
            $capacidad_filtro = $_POST['capacidad_filtro'] ?? 1;

            $_SESSION['nr_filtros'] = $_POST;
            unset($_SESSION['nr_habitaciones']);

            if (empty($fecha_entrada_str) || empty($fecha_salida_str) || $fecha_salida_str <= $fecha_entrada_str) {
                $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Fechas inválidas."];
            } else {
                // Lógica de búsqueda de habitaciones (copiada de tu original)
                $fecha_entrada_sql = $fecha_entrada_str . " 14:00:00";
                $fecha_salida_sql = $fecha_salida_str . " 12:00:00";
                $sql_disponibles = "SELECT h.id, h.nombre AS nombre_habitacion, h.precio, h.capacidad, th.nombre AS tipo_nombre FROM habitaciones h JOIN tipo_habitacion th ON h.tipo_id = th.id WHERE h.estado = 'Disponible' AND h.capacidad >= ?";
                $params = [$capacidad_filtro];
                $types = "i";
                if (!empty($tipo_id_filtro)) {
                    $sql_disponibles .= " AND h.tipo_id = ?";
                    $params[] = $tipo_id_filtro;
                    $types .= "i";
                }
                $sql_disponibles .= " AND h.id NOT IN (SELECT r.habitacion_id FROM reservas r WHERE r.habitacion_id IS NOT NULL AND r.estado = 'Confirmada' AND (r.fecha_entrada < ? AND r.fecha_salida > ?)) ORDER BY h.nombre";
                array_push($params, $fecha_salida_sql, $fecha_entrada_sql);
                $types .= "ss";

                $stmt_disp = $conexion->prepare($sql_disponibles);
                $stmt_disp->bind_param($types, ...$params);
                $stmt_disp->execute();
                $habitaciones = $stmt_disp->get_result()->fetch_all(MYSQLI_ASSOC);
                $_SESSION['nr_habitaciones'] = $habitaciones;
                if (empty($habitaciones)) {
                    $_SESSION['mensaje_reserva'] = ['tipo' => 'info', 'texto' => "No se encontraron habitaciones con los criterios especificados."];
                } else {
                    $_SESSION['mensaje_reserva'] = ['tipo' => 'success', 'texto' => count($habitaciones) . " habitación(es) encontrada(s)."];
                }
                $stmt_disp->close();
            }
        }
        $response = ['status' => 'ok', 'action' => 'reload_form'];
        break;

    case 'confirmar_reserva_final':
        if (!isset($_SESSION['nr_cliente_info']) || !isset($_POST['habitacion_id_seleccionada']) || !isset($_SESSION['nr_filtros'])) {
            $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Faltan datos. Por favor, reinicie el proceso."];
            $response = ['status' => 'error', 'action' => 'reload_form'];
        } else {
            // Lógica de confirmación de reserva (copiada de tu original)
            $cliente_dni = $_SESSION['nr_cliente_info']['dni'];
            $habitacion_id = (int)$_POST['habitacion_id_seleccionada'];
            $filtros = $_SESSION['nr_filtros'];
            $estancia = max(1, (new DateTime($filtros['fecha_salida']))->diff(new DateTime($filtros['fecha_entrada']))->days);

            // Obtener precio y calcular monto
            $stmt_hab = $conexion->prepare("SELECT precio FROM habitaciones WHERE id = ?");
            $stmt_hab->bind_param("i", $habitacion_id);
            $stmt_hab->execute();
            $precio_noche = $stmt_hab->get_result()->fetch_assoc()['precio'];
            $monto_total = $precio_noche * $estancia;
            $stmt_hab->close();

            // Doble verificación de disponibilidad
            $fecha_e = $filtros['fecha_entrada'] . " 14:00:00";
            $fecha_s = $filtros['fecha_salida'] . " 12:00:00";
            $stmt_check = $conexion->prepare("SELECT COUNT(*) as count FROM reservas WHERE habitacion_id = ? AND estado = 'Confirmada' AND (fecha_entrada < ? AND fecha_salida > ?)");
            $stmt_check->bind_param("iss", $habitacion_id, $fecha_s, $fecha_e);
            $stmt_check->execute();
            $conflict_count = $stmt_check->get_result()->fetch_assoc()['count'];
            $stmt_check->close();

            if ($conflict_count > 0) {
                $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Conflicto de reserva. La habitación ya no está disponible."];
                unset($_SESSION['nr_habitaciones']);
                $response = ['status' => 'error', 'action' => 'reload_form'];
            } else {
                // Insertar reserva
                $sql = "INSERT INTO reservas (fecha_reserva, modo_reserva, metodo_pago, estancia, monto_total, fecha_entrada, fecha_salida, cliente_dni, habitacion_id, usuario_id, estado) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmada')";
                $stmt_insert = $conexion->prepare($sql);
                $modo_reserva = $_POST['modo_reserva'];
                $metodo_pago = empty($_POST['metodo_pago']) ? null : $_POST['metodo_pago'];
                $usuario_id = $_SESSION['usuario_id'];
                $stmt_insert->bind_param("sssdsssii", $modo_reserva, $metodo_pago, $estancia, $monto_total, $fecha_e, $fecha_s, $cliente_dni, $habitacion_id, $usuario_id);

                if ($stmt_insert->execute()) {
                    $_SESSION['mensaje_exito_global'] = "Reserva ID " . $stmt_insert->insert_id . " creada exitosamente.";
                    unset($_SESSION['nr_cliente_info'], $_SESSION['nr_filtros'], $_SESSION['nr_habitaciones']);
                    $response = ['status' => 'success', 'action' => 'redirect_to_gestionar'];
                } else {
                    $_SESSION['mensaje_reserva'] = ['tipo' => 'danger', 'texto' => "Error al guardar la reserva en la base de datos."];
                    $response = ['status' => 'error', 'action' => 'reload_form'];
                }
                $stmt_insert->close();
            }
        }
        break;
}

$conexion->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();
