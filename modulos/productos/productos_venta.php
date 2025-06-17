<?php
session_start();
require_once 'conexion.php'; // $conn

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    header("Location: login.html");
    exit();
}

// IDs de productos permitidos para la venta directa desde esta interfaz
$ids_productos_venta_directa = [1, 2, 3, 6, 7, 16, 17, 18, 19, 20, 21];
$productos_para_venta = [];

if (!empty($ids_productos_venta_directa)) {
    $placeholders = implode(',', array_fill(0, count($ids_productos_venta_directa), '?'));
    // Asegurarse de que str_repeat genere el tipo correcto para cada placeholder
    $types_string = str_repeat('i', count($ids_productos_venta_directa));

    $stmt_prod = $conn->prepare("SELECT id, nombre, stock FROM productos WHERE id IN ($placeholders) ORDER BY nombre");
    // Pasar los IDs como argumentos individuales a bind_param
    $stmt_prod->bind_param($types_string, ...$ids_productos_venta_directa);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();
    if ($result_prod) {
        while ($row = $result_prod->fetch_assoc()) {
            $productos_para_venta[] = $row;
        }
    }
    $stmt_prod->close();
}

// Para seleccionar una reserva activa (opcional)
$reservas_activas = [];
$sql_reservas_activas = "SELECT r.id, c.nombres, c.apellidos, h.nombre as nombre_habitacion
                         FROM reservas r
                         JOIN clientes c ON r.cliente_dni = c.dni
                         JOIN habitaciones h ON r.habitacion_id = h.id
                         WHERE r.estado IN ('Confirmada', 'Ocupada') AND (r.fecha_salida IS NULL OR r.fecha_salida >= CURDATE())
                         ORDER BY c.apellidos, c.nombres";
$result_ra = $conn->query($sql_reservas_activas);
if ($result_ra) {
    while($row_ra = $result_ra->fetch_assoc()){
        $reservas_activas[] = $row_ra;
    }
}


// --- LÓGICA POST PARA REGISTRAR VENTA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id_venta = (int)$_POST['producto_id_venta'];
    $cantidad_vendida = (int)$_POST['cantidad_vendida'];
    $precio_unitario_venta = (float)$_POST['precio_unitario_venta']; // Ingresado por el usuario
    $reserva_id_venta = !empty($_POST['reserva_id_venta']) ? (int)$_POST['reserva_id_venta'] : null;
    $cliente_dni_directo_venta = empty($reserva_id_venta) && !empty($_POST['cliente_dni_directo_venta']) ? trim($_POST['cliente_dni_directo_venta']) : null;
    $usuario_id_venta = $_SESSION['usuario_id'];

    if (!in_array($producto_id_venta, $ids_productos_venta_directa)) {
        $_SESSION['mensaje_error_venta'] = "Producto no permitido para esta venta o ID inválido.";
    } elseif ($cantidad_vendida <= 0) {
        $_SESSION['mensaje_error_venta'] = "La cantidad vendida debe ser mayor a cero.";
    } elseif ($precio_unitario_venta < 0) { 
        $_SESSION['mensaje_error_venta'] = "El precio unitario no puede ser negativo.";
    } else {
        $stmt_check_stock = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt_check_stock->bind_param("i", $producto_id_venta);
        $stmt_check_stock->execute();
        $stock_actual_res = $stmt_check_stock->get_result();
        if ($stock_actual_res->num_rows > 0) {
            $stock_actual_val = $stock_actual_res->fetch_assoc()['stock'];
            if ($stock_actual_val < $cantidad_vendida) {
                $_SESSION['mensaje_error_venta'] = "Stock insuficiente para '".htmlspecialchars($_POST['nombre_producto_seleccionado'] ?? 'el producto')."'. Stock actual: $stock_actual_val.";
            } else {
                $monto_total_la_venta = $cantidad_vendida * $precio_unitario_venta;
                $fecha_consumo_actual = date('Y-m-d H:i:s');

                $stmt_insert_venta = $conn->prepare(
                    "INSERT INTO venta_productos (fecha_consumo, producto_id, cantidad_vendida, precio_venta_unitario, monto_total_venta, reserva_id, cliente_dni_directo, usuario_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                // Asegurar que cliente_dni_directo_venta sea NULL si está vacío
                if (empty($cliente_dni_directo_venta)) $cliente_dni_directo_venta = null;
                
                $stmt_insert_venta->bind_param("siiddisi", 
                    $fecha_consumo_actual, $producto_id_venta, $cantidad_vendida, $precio_unitario_venta, 
                    $monto_total_la_venta, $reserva_id_venta, $cliente_dni_directo_venta, $usuario_id_venta
                );

                if ($stmt_insert_venta->execute()) {
                    $_SESSION['mensaje_exito_venta'] = "Venta registrada exitosamente. ID Venta: " . $stmt_insert_venta->insert_id;
                } else {
                    $_SESSION['mensaje_error_venta'] = "Error al registrar la venta: " . $stmt_insert_venta->error;
                }
                $stmt_insert_venta->close();
            }
        } else {
            $_SESSION['mensaje_error_venta'] = "Producto no encontrado para verificar stock.";
        }
        $stmt_check_stock->close();
    }
    header("Location: productos_venta.php"); 
    exit();
}


$mensaje_error_venta = $_SESSION['mensaje_error_venta'] ?? null;
$mensaje_exito_venta = $_SESSION['mensaje_exito_venta'] ?? null;
unset($_SESSION['mensaje_error_venta'], $_SESSION['mensaje_exito_venta']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Venta de Productos - Hotel</title>
  <script>
    function actualizarInfoProducto() {
        const selectProducto = document.getElementById('producto_id_venta');
        const productoSeleccionado = selectProducto.options[selectProducto.selectedIndex];
        const stockDisponibleSpan = document.getElementById('stock_disponible_info');
        // El input para el nombre del producto, para usarlo en mensajes de error si es necesario
        const nombreProductoInput = document.getElementById('nombre_producto_seleccionado');


        if (productoSeleccionado.value) {
            const stock = productoSeleccionado.dataset.stock || 0;
            stockDisponibleSpan.textContent = "Stock Disponible: " + stock;
            if (nombreProductoInput) { // Si existe el input oculto
                nombreProductoInput.value = productoSeleccionado.text;
            }
        } else {
            stockDisponibleSpan.textContent = "Seleccione un producto";
            if (nombreProductoInput) {
                nombreProductoInput.value = "";
            }
        }
        // El precio ya no se carga automáticamente, el usuario lo ingresa.
        // Pero aún llamamos a calcularTotalVenta por si ya hay un precio ingresado.
        calcularTotalVenta(); 
    }

    function calcularTotalVenta() {
        const cantidad = parseInt(document.getElementById('cantidad_vendida').value) || 0;
        const precioUnitario = parseFloat(document.getElementById('precio_unitario_venta').value) || 0;
        const montoTotalSpan = document.getElementById('monto_total_calculado');
        const total = cantidad * precioUnitario;
        montoTotalSpan.textContent = "Monto Total: S/ " + total.toFixed(2);
    }
  </script>
</head>
<body onload="actualizarInfoProducto()">
  <header>
    <h1>Registrar Venta de Productos</h1>
    <nav>
        <a href="productos_dashboard.html">Volver a Productos y Ventas</a> |
        <a href="recepcionista_dashboard.php">Volver al Panel Principal</a>
    </nav>
  </header>
  <hr>
  <?php if ($mensaje_error_venta): ?><p style="color:red; font-weight:bold;"><?php echo $mensaje_error_venta; ?></p><?php endif; ?>
  <?php if ($mensaje_exito_venta): ?><p style="color:green; font-weight:bold;"><?php echo $mensaje_exito_venta; ?></p><?php endif; ?>
  <hr>

  <form action="productos_venta.php" method="POST" oninput="calcularTotalVenta()">
    <!-- Input oculto para pasar el nombre del producto seleccionado al backend, útil para mensajes de error -->
    <input type="hidden" name="nombre_producto_seleccionado" id="nombre_producto_seleccionado" value="">

    <label for="producto_id_venta">Producto a Vender:</label><br>
    <select name="producto_id_venta" id="producto_id_venta" required onchange="actualizarInfoProducto()">
        <option value="">-- Seleccione Producto --</option>
        <?php foreach ($productos_para_venta as $prod_v): ?>
        <option value="<?php echo $prod_v['id']; ?>" data-stock="<?php echo $prod_v['stock']; ?>">
            <?php echo htmlspecialchars($prod_v['nombre']); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <span id="stock_disponible_info" style="margin-left: 10px;">Seleccione un producto</span>
    <br><br>

    <label for="cantidad_vendida">Cantidad:</label><br>
    <input type="number" name="cantidad_vendida" id="cantidad_vendida" value="1" min="1" required><br><br>

    <label for="precio_unitario_venta">Precio Unitario de Venta (S/):</label><br>
    <input type="number" name="precio_unitario_venta" id="precio_unitario_venta" step="0.01" min="0" required value="0.00"> 
    <em><small>(Ingrese el precio de venta para este producto).</small></em><br><br>

    <p id="monto_total_calculado" style="font-weight:bold;">Monto Total: S/ 0.00</p>

    <label for="reserva_id_venta">Asociar a Reserva Activa (Opcional):</label><br>
    <select name="reserva_id_venta" id="reserva_id_venta">
        <option value="">-- Ninguna / Venta Directa --</option>
        <?php foreach ($reservas_activas as $ra): ?>
        <option value="<?php echo $ra['id']; ?>">
            Reserva <?php echo $ra['id']; ?> - <?php echo htmlspecialchars($ra['apellidos'] . ", " . $ra['nombres']); ?> (Hab: <?php echo htmlspecialchars($ra['nombre_habitacion']); ?>)
        </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="cliente_dni_directo_venta">DNI Cliente (si no se asocia a reserva y es venta directa):</label><br>
    <input type="text" name="cliente_dni_directo_venta" id="cliente_dni_directo_venta" maxlength="10" pattern="\d{8,10}"><br><br>

    <button type="submit">Registrar Venta</button>
  </form>
  <hr>
  <footer>
    <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>
</body>
</html>