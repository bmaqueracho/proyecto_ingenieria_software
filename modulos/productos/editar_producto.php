<?php
session_start();
require_once '../../conexion.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    exit('Acceso denegado.');
}

// Validar y obtener el ID del producto de la URL
$producto_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$producto_id) {
    exit('ID de producto inválido.');
}

// Obtener los datos actuales del producto para el formulario
$stmt = $conexion->prepare("SELECT id, nombre, categoria FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit('Producto no encontrado.');
}
$producto_actual = $result->fetch_assoc();
$stmt->close();

// Lista de categorías para el selector
$categorias_enum = ['Aseo', 'General', 'Bebidas'];

$conexion->close();
?>

<section id="editar-producto-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-edit"></i> Editar Producto</h2>
    </div>

    <div class="card action-card p-4">
        <h4 class="mb-4">Modificando: <?php echo htmlspecialchars($producto_actual['nombre']); ?></h4>
        <form action="../productos/productos_procesar.php" method="POST">
            <input type="hidden" name="accion" value="actualizar_producto">
            <input type="hidden" name="producto_id" value="<?php echo $producto_actual['id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto_actual['nombre']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-select" required>
                    <?php foreach ($categorias_enum as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($producto_actual['categoria'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="text-muted small mt-4">Nota: El stock solo se puede modificar desde la pantalla de "Ajustar Stock".</p>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/productos_gestion.php');" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </div>
        </form>
    </div>
</section>