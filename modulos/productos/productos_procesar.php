<?php
session_start();
require_once '../../conexion.php';

// Seguridad de acceso
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Parámetros iniciales
$accion = $_POST['accion'] ?? null;
$return_url = $_POST['return_url'] ?? 'producto_gestion.php';

// Validación mínima
if (empty($accion)) {
    header("Location: ../../index.php");
    exit();
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    switch ($accion) {
        case 'agregar_producto':
            $nombre = trim($_POST['nombre']);
            $stock = (int)$_POST['stock'];
            $categoria = $_POST['categoria'];

            if (empty($nombre) || $stock < 0) {
                throw new Exception("Datos inválidos para agregar producto.");
            }

            $stmt = $conexion->prepare("INSERT INTO productos (nombre, stock, categoria) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $nombre, $stock, $categoria);
            if (!$stmt->execute()) {
                throw new Exception("Error al agregar: " . $stmt->error);
            }
            $stmt->close();

            $_SESSION['mensaje_prod'] = [
                'tipo' => 'exito',
                'texto' => "Producto '$nombre' agregado correctamente."
            ];
            break;

        case 'ajustar_stock':
            $producto_id_ajuste = (int)$_POST['producto_id_ajuste'];
            $cantidad_ajuste = (int)$_POST['cantidad_ajuste'];
            $tipo_ajuste = $_POST['tipo_ajuste'];

            if ($producto_id_ajuste <= 0 || $cantidad_ajuste <= 0) {
                throw new Exception("Datos inválidos para ajuste.");
            }

            if ($tipo_ajuste === 'merma') {
                $stmt_check = $conexion->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
                $stmt_check->bind_param("i", $producto_id_ajuste);
                $stmt_check->execute();
                $stock_actual = $stmt_check->get_result()->fetch_assoc()['stock'] ?? 0;
                $stmt_check->close();

                if ($stock_actual < $cantidad_ajuste) {
                    throw new Exception("Stock insuficiente (actual: $stock_actual).");
                }
            }

            $op = ($tipo_ajuste === 'ingreso') ? '+' : '-';
            $stmt = $conexion->prepare("UPDATE productos SET stock = stock $op ? WHERE id = ?");
            $stmt->bind_param("ii", $cantidad_ajuste, $producto_id_ajuste);
            if (!$stmt->execute()) {
                throw new Exception("Error al ajustar stock: " . $stmt->error);
            }
            $stmt->close();

            $_SESSION['mensaje_prod'] = [
                'tipo' => 'exito',
                'texto' => "Stock ajustado correctamente."
            ];
            break;

        case 'actualizar_producto':
            $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
            $nombre = trim($_POST['nombre']);
            $categoria = $_POST['categoria'];

            if (!$producto_id || empty($nombre) || empty($categoria)) {
                throw new Exception("Datos inválidos para actualización.");
            }

            if (!in_array($categoria, ['Aseo', 'General', 'Bebidas'])) {
                throw new Exception("Categoría no permitida.");
            }

            $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, categoria = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $categoria, $producto_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar: " . $stmt->error);
            }
            $stmt->close();

            $_SESSION['mensaje_prod'] = [
                'tipo' => 'exito',
                'texto' => "Producto '$nombre' actualizado correctamente."
            ];
            break;

        case 'eliminar_producto':
            $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
            if (!$producto_id) {
                throw new Exception("ID inválido para eliminación.");
            }

            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->bind_param("i", $producto_id);
            if (!$stmt->execute()) {
                if ($conexion->errno == 1451) {
                    throw new Exception("No se puede eliminar: producto asociado a ventas.");
                } else {
                    throw new Exception("Error al eliminar: " . $stmt->error);
                }
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("Producto no encontrado para eliminar.");
            }

            $stmt->close();

            $_SESSION['mensaje_prod'] = [
                'tipo' => 'exito',
                'texto' => "Producto eliminado correctamente."
            ];
            break;

        case 'registrar_venta':
            $producto_id = (int)$_POST['producto_id_venta'];
            $cantidad = (int)$_POST['cantidad_vendida'];
            $precio_unitario = (float)$_POST['precio_unitario_venta'];
            $reserva_id = !empty($_POST['reserva_id_venta']) ? (int)$_POST['reserva_id_venta'] : null;
            $cliente_dni = empty($reserva_id) && !empty($_POST['cliente_dni_directo_venta']) ? trim($_POST['cliente_dni_directo_venta']) : null;
            $usuario_id = $_SESSION['usuario_id'];

            if ($producto_id <= 0 || $cantidad <= 0 || $precio_unitario < 0) {
                throw new Exception("Datos inválidos para venta.");
            }

            if (empty($reserva_id) && empty($cliente_dni)) {
                throw new Exception("Debe asociar la venta a una reserva o a un cliente (DNI).");
            }

            $stmt_check = $conexion->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt_check->bind_param("i", $producto_id);
            $stmt_check->execute();
            $res = $stmt_check->get_result();
            if ($res->num_rows === 0) {
                throw new Exception("Producto no encontrado.");
            }
            $stock_actual = $res->fetch_assoc()['stock'];
            $stmt_check->close();

            if ($stock_actual < $cantidad) {
                throw new Exception("Stock insuficiente: $stock_actual.");
            }

            $monto_total = $cantidad * $precio_unitario;
            $fecha_venta = date('Y-m-d H:i:s');

            $stmt_insert = $conexion->prepare("
                INSERT INTO venta_productos (
                    fecha_consumo, producto_id, cantidad_vendida, 
                    precio_venta_unitario, monto_total_venta, 
                    reserva_id, cliente_dni_directo, usuario_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert->bind_param("siiddisi", $fecha_venta, $producto_id, $cantidad, $precio_unitario, $monto_total, $reserva_id, $cliente_dni, $usuario_id);
            if (!$stmt_insert->execute()) {
                throw new Exception("Error al registrar la venta: " . $stmt_insert->error);
            }
            $stmt_insert->close();

            $stmt_update = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt_update->bind_param("ii", $cantidad, $producto_id);
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar stock tras venta: " . $stmt_update->error);
            }
            $stmt_update->close();

            $_SESSION['mensaje_prod'] = [
                'tipo' => 'exito',
                'texto' => "Venta registrada correctamente."
            ];
            break;

        default:
            throw new Exception("Acción no reconocida.");
    }

    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje_prod'] = ['tipo' => 'error', 'texto' => $e->getMessage()];
}

$conexion->close();

// Redirección dinámica según cargo del usuario
$dashboard = ($_SESSION['cargo'] === 'Administrador') ? 'admin_dashboard.php' : 'recepcionista_dashboard.php';
header("Location: ../dashboard/{$dashboard}?load_module=modulos/productos/{$return_url}");
exit();
