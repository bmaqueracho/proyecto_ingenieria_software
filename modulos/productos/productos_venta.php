<?php
session_start();
require_once '../../conexion.php';

// Verificación de sesión y permisos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    exit();
}

// Obtener productos disponibles con stock > 0
$ids_productos_venta_directa = [1, 2, 3, 6, 7, 16, 17, 18, 19, 20, 21];
$productos_para_venta = [];

if (!empty($ids_productos_venta_directa)) {
    $placeholders = implode(',', array_fill(0, count($ids_productos_venta_directa), '?'));
    $types_string = str_repeat('i', count($ids_productos_venta_directa));
    $stmt = $conexion->prepare("SELECT id, nombre, stock FROM productos WHERE id IN ($placeholders) AND stock > 0 ORDER BY nombre");
    if ($stmt) {
        $stmt->bind_param($types_string, ...$ids_productos_venta_directa);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $productos_para_venta[] = $row;
        }
        $stmt->close();
    }
}

// Obtener reservas activas en curso
$reservas_activas = [];
$sql = "SELECT r.id, c.nombres, c.apellidos, h.nombre AS nombre_habitacion
        FROM reservas r
        JOIN clientes c ON r.cliente_dni = c.dni
        JOIN habitaciones h ON r.habitacion_id = h.id
        WHERE r.estado IN ('Confirmada', 'Completa') 
          AND NOW() BETWEEN r.fecha_entrada AND r.fecha_salida
        ORDER BY c.apellidos, c.nombres";
$result = $conexion->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reservas_activas[] = $row;
    }
}

// Manejo de mensajes
$mensaje = $_SESSION['mensaje_prod'] ?? null;
unset($_SESSION['mensaje_prod']);
$conexion->close();
?>

<section id="registro-venta-producto">
    <h2 class="section-title"><i class="fas fa-cash-register"></i> Registrar Venta de Productos</h2>
    <p class="section-subtitle">Seleccione un producto, especifique cantidad, precio y relacione con una reserva o cliente.</p>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $mensaje['tipo'] === 'exito' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje['texto']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="card action-card p-4">
        <form action="../productos/productos_procesar.php" method="POST">
            <input type="hidden" name="accion" value="registrar_venta">
            <input type="hidden" name="return_url" value="producto_venta.php">

            <div class="mb-3">
                <label for="producto_id_venta" class="form-label">Producto a Vender:</label>
                <select name="producto_id_venta" id="producto_id_venta" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($productos_para_venta as $p): ?>
                        <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['nombre']) . " (Stock: " . $p['stock'] . ")" ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cantidad_vendida" class="form-label">Cantidad:</label>
                    <input type="number" name="cantidad_vendida" id="cantidad_vendida" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="precio_unitario_venta" class="form-label">Precio Unitario (S/):</label>
                    <input type="number" name="precio_unitario_venta" id="precio_unitario_venta" class="form-control" step="0.01" min="0" value="0.00" required>
                </div>
            </div>

            <div class="mb-4 text-end">
                <h5 id="monto_total_calculado" class="text-primary fw-bold">Monto Total: S/ 0.00</h5>
            </div>

            <div class="mb-3">
                <label for="reserva_id_venta" class="form-label">Asociar a Reserva (opcional):</label>
                <select name="reserva_id_venta" id="reserva_id_venta" class="form-select">
                    <option value="">-- Venta Directa --</option>
                    <?php foreach ($reservas_activas as $r): ?>
                        <option value="<?= $r['id']; ?>">
                            Reserva <?= $r['id']; ?> - <?= htmlspecialchars($r['apellidos'] . ', ' . $r['nombres']) ?> (Hab: <?= htmlspecialchars($r['nombre_habitacion']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="cliente_dni_directo_venta" class="form-label">DNI Cliente (si es venta directa):</label>
                <input type="text" name="cliente_dni_directo_venta" id="cliente_dni_directo_venta" class="form-control" pattern="\d{8,11}" maxlength="11">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check-circle me-2"></i>Registrar Venta
                </button>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cantidad = document.getElementById('cantidad_vendida');
    const precio = document.getElementById('precio_unitario_venta');
    const total = document.getElementById('monto_total_calculado');
    const reserva = document.getElementById('reserva_id_venta');
    const dni = document.getElementById('cliente_dni_directo_venta');

    function actualizarTotal() {
        const c = parseInt(cantidad.value) || 0;
        const p = parseFloat(precio.value) || 0;
        total.textContent = "Monto Total: S/ " + (c * p).toFixed(2);
    }

    function alternarDNI() {
        if (reserva.value) {
            dni.disabled = true;
            dni.required = false;
            dni.value = '';
        } else {
            dni.disabled = false;
            dni.required = true;
        }
    }

    cantidad.addEventListener('input', actualizarTotal);
    precio.addEventListener('input', actualizarTotal);
    reserva.addEventListener('change', alternarDNI);

    actualizarTotal();
    alternarDNI();
});
</script>
