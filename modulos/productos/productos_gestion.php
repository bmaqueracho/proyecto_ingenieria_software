<?php
// ARCHIVO DE VISTA
session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) { exit(); }

$productos = [];
$result = $conexion->query("SELECT id, nombre, stock, categoria FROM productos ORDER BY categoria, nombre");
if ($result) { while ($row = $result->fetch_assoc()) { $productos[] = $row; } }
$categorias_enum = ['Aseo', 'General', 'Bebidas'];

$mensaje = $_SESSION['mensaje_prod'] ?? null;
unset($_SESSION['mensaje_prod']);
$conexion->close();
?>

<section id="admin-productos">
    <h2 class="section-title"><i class="fas fa-cogs"></i> Administrar Productos y Stock</h2>
    <p class="text-white-50 mb-4">Añada nuevos productos al inventario o realice ajustes de stock por ingresos o mermas.</p>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $mensaje['tipo'] === 'exito' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($mensaje['texto']); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Columna para Formularios -->
        <div class="col-lg-4">
            <div class="card action-card p-4 mb-4">
                <h4 class="mb-3">Agregar Producto</h4>
                <form action="../productos/productos_procesar.php" method="POST">
                    <input type="hidden" name="accion" value="agregar_producto">
                    <div class="mb-3"><label for="nombre" class="form-label">Nombre del Producto:</label><input type="text" id="nombre" name="nombre" class="form-control" required maxlength="45"></div>
                    <div class="mb-3"><label for="stock" class="form-label">Stock Inicial:</label><input type="number" id="stock" name="stock" class="form-control" required min="0"></div>
                    <div class="mb-3"><label for="categoria" class="form-label">Categoría:</label><select id="categoria" name="categoria" class="form-select" required><?php foreach ($categorias_enum as $cat): ?><option value="<?php echo $cat; ?>"><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
                    <button type="submit" class="btn btn-success w-100">Agregar Producto</button>
                </form>
            </div>
            <div class="card action-card p-4">
                <h4 class="mb-3">Ajustar Stock</h4>
                <form action="../productos/productos_procesar.php" method="POST">
                    <input type="hidden" name="accion" value="ajustar_stock">
                    <div class="mb-3"><label for="producto_id_ajuste" class="form-label">Producto:</label><select name="producto_id_ajuste" id="producto_id_ajuste" class="form-select" required><option value="">-- Elija un producto --</option><?php foreach ($productos as $prod_item): ?><option value="<?php echo $prod_item['id']; ?>"><?php echo htmlspecialchars($prod_item['nombre']) . " (Actual: " . $prod_item['stock'] . ")"; ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label for="tipo_ajuste" class="form-label">Tipo de Ajuste:</label><select name="tipo_ajuste" id="tipo_ajuste" class="form-select" required><option value="ingreso">Ingreso (+)</option><option value="merma">Merma (-)</option></select></div>
                    <div class="mb-3"><label for="cantidad_ajuste" class="form-label">Cantidad:</label><input type="number" name="cantidad_ajuste" id="cantidad_ajuste" class="form-control" required min="1"></div>
                    <button type="submit" class="btn btn-warning w-100">Realizar Ajuste</button>
                </form>
            </div>
        </div>
        <!-- Columna para la Tabla -->
        <div class="col-lg-8">
            <div class="card action-card p-4">
                <h4 class="mb-3">Inventario Actual</h4>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-dark table-striped table-hover">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Stock</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['id']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($producto['stock']); ?></td>
                                <td><a href="#" class="btn btn-sm btn-outline-light disabled">Editar</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>