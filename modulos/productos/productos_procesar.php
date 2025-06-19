<?php
session_start();
require_once '../../conexion.php'; // Sube dos niveles a la raíz

// --- VERIFICACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    http_response_code(403);
    $_SESSION['mensaje_global'] = ['tipo' => 'error', 'texto' => 'Acceso denegado.'];
    header("Location: ../dashboard/recepcionista_dashboard.php");
    exit();
}

$accion = $_POST['accion'] ?? null;
if (empty($accion)) {
    header("Location: ../dashboard/recepcionista_dashboard.php");
    exit();
}

// Iniciar transacción para asegurar la integridad de los datos
$conn->begin_transaction();

try {
    // --- ACCIÓN: AGREGAR NUEVO PRODUCTO ---
    if ($accion === 'agregar_producto') {
        $redirect_url = '../dashboard/recepcionista_dashboard.php?load_module=modulos/productos/productos_gestion';
        $nombre = trim($_POST['nombre']);
        $stock = (int)$_POST['stock'];
        $categoria = $_POST['categoria'];

        if (empty($nombre) || $stock < 0) {
            throw new Exception("Datos inválidos para agregar producto.");
        }
        
        $stmt = $conn->prepare("INSERT INTO productos (nombre, stock, categoria) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $nombre, $stock, $categoria);
        if (!$stmt->execute()) { throw new Exception("Error al agregar producto: " . $stmt->error); }
        $stmt->close();
        $_SESSION['mensaje_prod'] = ['tipo' => 'exito', 'texto' => "Producto '$nombre' agregado exitosamente."];
    }
    
    // --- ACCIÓN: AJUSTAR STOCK DE PRODUCTO ---
    elseif ($accion === 'ajustar_stock') {
        $redirect_url = '../dashboard/recepcionista_dashboard.php?load_module=modulos/productos/productos_gestion';
        $producto_id_ajuste = (int)$_POST['producto_id_ajuste'];
        $cantidad_ajuste = (int)$_POST['cantidad_ajuste'];
        $tipo_ajuste = $_POST['tipo_ajuste'];

        if ($producto_id_ajuste <= 0 || $cantidad_ajuste <= 0) {
             throw new Exception("Datos inválidos para ajustar stock.");
        }

        // Si es merma, verificar que haya stock suficiente
        if ($tipo_ajuste === 'merma') {
            $stmt_check = $conn->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt_check->bind_param("i", $producto_id_ajuste);
            $stmt_check->execute();
            $stock_actual = $stmt_check->get_result()->fetch_assoc()['stock'] ?? 0;
            $stmt_check->close();
            if ($stock_actual < $cantidad_ajuste) {
                throw new Exception("No se puede ajustar. Stock actual ($stock_actual) es menor que la cantidad a mermar ($cantidad_ajuste).");
            }
        }
        
        $operacion_stock = ($tipo_ajuste === 'ingreso') ? '+' : '-';
        $stmt_update = $conn->prepare("UPDATE productos SET stock = stock $operacion_stock ? WHERE id = ?");
        $stmt_update->bind_param("ii", $cantidad_ajuste, $producto_id_ajuste);
        if (!$stmt_update->execute()) { throw new Exception("Error al ajustar stock: " . $stmt_update->error); }
        $stmt_update->close();
        $_SESSION['mensaje_prod'] = ['tipo' => 'exito', 'texto' => "Stock ajustado para producto ID $producto_id_ajuste."];
    }
    
    // --- ACCIÓN: REGISTRAR UNA VENTA ---
    elseif ($accion === 'registrar_venta') {
        $redirect_url = '../dashboard/recepcionista_dashboard.php?load_module=modulos/productos/productos_venta';
        $producto_id_venta = (int)$_POST['producto_id_venta'];
        $cantidad_vendida = (int)$_POST['cantidad_vendida'];
        $precio_unitario_venta = (float)$_POST['precio_unitario_venta'];
        $reserva_id_venta = !empty($_POST['reserva_id_venta']) ? (int)$_POST['reserva_id_venta'] : null;
        $cliente_dni_directo_venta = empty($reserva_id_venta) && !empty($_POST['cliente_dni_directo_venta']) ? trim($_POST['cliente_dni_directo_venta']) : null;
        $usuario_id_venta = $_SESSION['usuario_id'];

        if ($producto_id_venta <= 0 || $cantidad_vendida <= 0 || $precio_unitario_venta < 0) {
            throw new Exception("Datos de venta inválidos.");
        }
        
        // 1. Verificar y bloquear stock
        $stmt_check_stock = $conn->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
        $stmt_check_stock->bind_param("i", $producto_id_venta);
        $stmt_check_stock->execute();
        $stock_result = $stmt_check_stock->get_result();
        if ($stock_result->num_rows === 0) { throw new Exception("Producto no encontrado."); }
        $stock_actual_val = $stock_result->fetch_assoc()['stock'];
        $stmt_check_stock->close();

        if ($stock_actual_val < $cantidad_vendida) {
            throw new Exception("Stock insuficiente. Stock actual: $stock_actual_val.");
        }

        // 2. Insertar la venta
        $monto_total_la_venta = $cantidad_vendida * $precio_unitario_venta;
        $fecha_consumo_actual = date('Y-m-d H:i:s');
        $stmt_insert_venta = $conn->prepare("INSERT INTO venta_productos (fecha_consumo, producto_id, cantidad_vendida, precio_venta_unitario, monto_total_venta, reserva_id, cliente_dni_directo, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert_venta->bind_param("siiddisi", $fecha_consumo_actual, $producto_id_venta, $cantidad_vendida, $precio_unitario_venta, $monto_total_la_venta, $reserva_id_venta, $cliente_dni_directo_venta, $usuario_id_venta);
        if (!$stmt_insert_venta->execute()) { throw new Exception("Error al registrar la venta: " . $stmt_insert_venta->error); }
        $stmt_insert_venta->close();

        // 3. Descontar el stock
        $stmt_update_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $stmt_update_stock->bind_param("ii", $cantidad_vendida, $producto_id_venta);
        if (!$stmt_update_stock->execute()) { throw new Exception("Error al actualizar el stock: " . $stmt_update_stock->error); }
        $stmt_update_stock->close();

        $_SESSION['mensaje_prod'] = ['tipo' => 'exito', 'texto' => "Venta registrada exitosamente y stock actualizado."];
    }
    
    else {
        throw new Exception("Acción no válida o no reconocida.");
    }
    
    // Si todo fue bien, confirmar los cambios en la BD
    $conn->commit();

} catch (Exception $e) {
    // Si algo falló, revertir todos los cambios y guardar mensaje de error
    $conn->rollback();
    $_SESSION['mensaje_prod'] = ['tipo' => 'error', 'texto' => $e->getMessage()];
}

// Redirección final
$conn->close();
header("Location: " . ($redirect_url ?? '../dashboard/recepcionista_dashboard.php'));
exit();