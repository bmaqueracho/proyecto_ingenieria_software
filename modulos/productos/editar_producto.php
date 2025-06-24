<?php
session_start();
require_once '../../conexion.php';

// Seguridad de sesión
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    exit('Acceso denegado.');
}

// Validar ID del producto
$producto_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$producto_id) {
    exit('ID de producto inválido.');
}

// Obtener datos actuales del producto
$stmt = $conexion->prepare("SELECT id, nombre, categoria FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit('Producto no encontrado.');
}
$producto_actual = $result->fetch_assoc();
$stmt->close();

// Listado de categorías
$categorias_enum = ['Aseo', 'General', 'Bebidas'];
$conexion->close();
?>

<!-- Estilo adicional -->
<style>
    .btn-delete {
        margin-right: auto;
    }
</style>

<section id="editar-producto-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-edit"></i> Editar Producto</h2>
        <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/producto_gestion.php');" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver al Listado
        </a>
    </div>

    <div class="card action-card p-4">
        <h4 class="mb-4">
            Modificando: <span class="text-primary"><?= htmlspecialchars($producto_actual['nombre']) ?></span>
        </h4>

        <!-- Formulario de edición -->
        <form action="../productos/productos_procesar.php" method="POST">
            <input type="hidden" name="return_url" value="producto_gestion.php">
            <input type="hidden" name="accion" value="actualizar_producto">
            <input type="hidden" name="producto_id" value="<?= $producto_actual['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto_actual['nombre']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-select" required>
                    <?php foreach ($categorias_enum as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($producto_actual['categoria'] === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p class="text-muted small mt-4">
                Nota: El stock solo se puede modificar desde la pantalla de <strong>"Ajustar Stock"</strong>.
            </p>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-danger btn-delete" data-bs-toggle="modal" data-bs-target="#confirmarEliminacionModal">
                    <i class="fas fa-trash-alt me-2"></i>Eliminar
                </button>
                <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/producto_gestion.php');" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</section>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="confirmarEliminacionModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar <strong>"<?= htmlspecialchars($producto_actual['nombre']) ?>"</strong>?</p>
                <p class="text-danger"><strong>Esta acción es irreversible y eliminará permanentemente el producto de la base de datos.</strong></p>
            </div>
            <div class="modal-footer">
                <form action="../productos/productos_procesar.php" method="POST" class="w-100 d-flex justify-content-between">
                    <input type="hidden" name="return_url" value="producto_gestion.php">
                    <input type="hidden" name="accion" value="eliminar_producto">
                    <input type="hidden" name="producto_id" value="<?= $producto_actual['id'] ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
