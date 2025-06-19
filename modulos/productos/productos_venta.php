<?php
// ARCHIVO DE VISTA
session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) { exit(); }

// Tu lógica para obtener $productos_para_venta y $reservas_activas
$ids_productos_venta_directa = [1, 2, 3, 6, 7, 16, 17, 18, 19, 20, 21];
$productos_para_venta = [];
if (!empty($ids_productos_venta_directa)) {
    $placeholders = implode(',', array_fill(0, count($ids_productos_venta_directa), '?'));
    $types_string = str_repeat('i', count($ids_productos_venta_directa));
    $stmt_prod = $conexion->prepare("SELECT id, nombre, stock FROM productos WHERE id IN ($placeholders) ORDER BY nombre");
    $stmt_prod->bind_param($types_string, ...$ids_productos_venta_directa);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();
    if ($result_prod) { while ($row = $result_prod->fetch_assoc()) { $productos_para_venta[] = $row; } }
    $stmt_prod->close();
}
$reservas_activas = [];
$sql_reservas_activas = "SELECT r.id, c.nombres, c.apellidos, h.nombre as nombre_habitacion FROM reservas r JOIN clientes c ON r.cliente_dni = c.dni JOIN habitaciones h ON r.habitacion_id = h.id WHERE r.estado IN ('Confirmada', 'Ocupada') AND r.fecha_salida >= CURDATE() ORDER BY c.apellidos, c.nombres";
$result_ra = $conexion->query($sql_reservas_activas);
if ($result_ra) { while($row_ra = $result_ra->fetch_assoc()){ $reservas_activas[] = $row_ra; } }

$mensaje = $_SESSION['mensaje_prod'] ?? null;
unset($_SESSION['mensaje_prod']);
$conexion->close();
?>

<section id="registro-venta-producto">
    <h2 class="section-title"><i class="fas fa-cash-register"></i> Registrar Venta de Productos</h2>
    <p class="text-white-50 mb-4">Seleccione un producto para vender, ingrese el precio y asócielo a una reserva o cliente si es necesario.</p>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $mensaje['tipo'] === 'exito' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($mensaje['texto']); ?></div>
    <?php endif; ?>

    <div class="card action-card p-4">
        <form action="../productos/productos_procesar.php" method="POST" oninput="calcularTotalVenta()">
            <input type="hidden" name="accion" value="registrar_venta">
            <div class="row">
                <div class="col-md-7"><div class="mb-3"><label for="producto_id_venta" class="form-label">Producto a Vender:</label><select name="producto_id_venta" id="producto_id_venta" class="form-select" required onchange="actualizarInfoProducto()"><option value="">-- Seleccione Producto --</option><?php foreach ($productos_para_venta as $prod_v): ?><option value="<?php echo $prod_v['id']; ?>" data-stock="<?php echo $prod_v['stock']; ?>"><?php echo htmlspecialchars($prod_v['nombre']); ?></option><?php endforeach; ?></select></div></div>
                <div class="col-md-5"><div class="mb-3"><label> </label><div id="stock_disponible_info" class="form-control" style="background-color: rgba(0,0,0,0.2);">Seleccione un producto</div></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="mb-3"><label for="cantidad_vendida" class="form-label">Cantidad:</label><input type="number" name="cantidad_vendida" id="cantidad_vendida" class="form-control" value="1" min="1" required></div></div>
                <div class="col-md-6"><div class="mb-3"><label for="precio_unitario_venta" class="form-label">Precio Unitario Venta (S/):</label><input type="number" name="precio_unitario_venta" id="precio_unitario_venta" class="form-control" step="0.01" min="0" required value="0.00"></div></div>
            </div>
            <div class="mb-4 text-end"><h4 id="monto_total_calculado" class="text-warning">Monto Total: S/ 0.00</h4></div>
            <div class="mb-3"><label for="reserva_id_venta" class="form-label">Asociar a Reserva Activa (Opcional):</label><select name="reserva_id_venta" id="reserva_id_venta" class="form-select"><option value="">-- Venta Directa (Sin Reserva) --</option><?php foreach ($reservas_activas as $ra): ?><option value="<?php echo $ra['id']; ?>">Reserva <?php echo $ra['id']; ?> - <?php echo htmlspecialchars($ra['apellidos'] . ", " . $ra['nombres']); ?> (Hab: <?php echo htmlspecialchars($ra['nombre_habitacion']); ?>)</option><?php endforeach; ?></select></div>
            <div class="mb-3"><label for="cliente_dni_directo_venta" class="form-label">DNI Cliente (si es venta directa):</label><input type="text" name="cliente_dni_directo_venta" id="cliente_dni_directo_venta" class="form-control" maxlength="10" pattern="\d{8,10}"></div>
            <div class="d-grid"><button type="submit" class="btn btn-lg btn-success">Registrar Venta</button></div>
        </form>
    </div>
</section>

<!-- Script de cálculo (mejorado para evitar errores si el módulo no está visible) -->
<script>
    function actualizarInfoProducto() {
        const selectProducto = document.getElementById('producto_id_venta');
        const stockDisponibleSpan = document.getElementById('stock_disponible_info');
        if (!selectProducto || !stockDisponibleSpan) return; // Si no encuentra los elementos, no hace nada.
        const productoSeleccionado = selectProducto.options[selectProducto.selectedIndex];
        if (productoSeleccionado.value) {
            const stock = productoSeleccionado.dataset.stock || 0;
            stockDisponibleSpan.textContent = "Stock Disponible: " + stock;
        } else {
            stockDisponibleSpan.textContent = "Seleccione un producto";
        }
        calcularTotalVenta();
    }
    function calcularTotalVenta() {
        const cantidadInput = document.getElementById('cantidad_vendida');
        const precioInput = document.getElementById('precio_unitario_venta');
        const montoTotalSpan = document.getElementById('monto_total_calculado');
        if (!cantidadInput || !precioInput || !montoTotalSpan) return; // Defensa
        const cantidad = parseInt(cantidadInput.value) || 0;
        const precioUnitario = parseFloat(precioInput.value) || 0;
        montoTotalSpan.textContent = "Monto Total: S/ " + (cantidad * precioUnitario).toFixed(2);
    }
    // Llama a la función una vez que el contenido se carga
    document.addEventListener('DOMContentLoaded', actualizarInfoProducto);
</script>