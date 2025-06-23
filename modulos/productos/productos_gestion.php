<?php
session_start();
require_once '../../conexion.php';

// La lógica PHP inicial se mantiene, es correcta.
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) { exit(); }

$productos = [];
$result = $conexion->query("SELECT id, nombre, stock, categoria FROM productos ORDER BY categoria, nombre");
if ($result) { while ($row = $result->fetch_assoc()) { $productos[] = $row; } }
$categorias_enum = ['Aseo', 'General', 'Bebidas'];

$mensaje = $_SESSION['mensaje_prod'] ?? null;
unset($_SESSION['mensaje_prod']);
$conexion->close();
?>

<!-- ========================================================================== -->
<!-- ============== ¡NUEVO CSS PROFESIONAL INCRUSTADO! ============== -->
<!-- ========================================================================== -->
<style>
    /* Estilos específicos para la tabla y formularios de productos */
    .table-productos-wrapper {
        border: 1px solid var(--color-borde);
        border-radius: var(--radius-grande);
        overflow: hidden;
        box-shadow: var(--sombra-sutil);
    }
    .table-productos {
        margin-bottom: 0;
    }
    .table-productos thead th {
        background-color: #f9fafb;
        border-bottom: 2px solid var(--color-borde);
        color: var(--color-texto-secundario);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem 1.5rem;
    }
    .table-productos tbody td {
        vertical-align: middle;
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--color-borde);
    }
    .table-productos tbody tr:hover {
        background-color: #eff6ff;
    }
    .table-productos .stock-badge {
        font-size: 0.9rem;
        padding: 0.4em 0.8em;
        border-radius: 20px;
        color: #fff;
        min-width: 40px;
        display: inline-block;
        text-align: center;
    }
    .stock-ok { background-color: var(--color-exito); }
    .stock-low { background-color: var(--color-aviso); }
    .stock-critical { background-color: var(--color-error); }
    
    .category-badge {
        font-size: 0.8rem;
        padding: 0.3em 0.7em;
        border-radius: var(--radius-suave);
        color: #fff;
    }
    .btn-icon-text {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
</style>

<section id="admin-productos">
    <h2 class="section-title"><i class="fas fa-cogs"></i> Administrar Productos y Stock</h2>
    <p class="section-subtitle">Añada nuevos productos al inventario o realice ajustes de stock por ingresos o mermas.</p>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $mensaje['tipo'] === 'exito' ? 'success' : 'danger'; ?> mb-4"><?php echo htmlspecialchars($mensaje['texto']); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card action-card p-4">
                <h4 class="mb-4">Inventario Actual</h4>
                <div class="table-wrapper">
                    <table class="table table-hover table-productos">
                        <thead>
                            <tr>
                                <th>ID</th><th>Nombre</th><th>Categoría</th><th class="text-center">Stock</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr><td colspan="5" class="text-center text-muted p-4">No hay productos registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td><strong><?php echo $producto['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td>
                                        <?php 
                                            $cat_color = '#34495e'; // Gris por defecto
                                            if ($producto['categoria'] === 'Aseo') $cat_color = '#3498db'; // Azul
                                            if ($producto['categoria'] === 'Bebidas') $cat_color = '#e74c3c'; // Rojo
                                        ?>
                                        <span class="category-badge" style="background-color: <?php echo $cat_color; ?>;">
                                            <?php echo htmlspecialchars($producto['categoria']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            $stock = (int)$producto['stock'];
                                            $stock_class = 'stock-ok'; // Verde por defecto
                                            if ($stock <= 10) $stock_class = 'stock-critical';
                                            elseif ($stock <= 25) $stock_class = 'stock-low';
                                        ?>
                                        <span class="stock-badge <?php echo $stock_class; ?>"><?php echo $stock; ?></span>
                                    </td>
                                    <td>
                                        <!-- ========================================================== -->
                                        <!-- ============== ¡ESTE ES EL BOTÓN MODIFICADO! ============== -->
                                        <!-- ========================================================== -->
                                        <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/editar_producto.php?id=<?php echo $producto['id']; ?>');" class="btn btn-sm btn-outline-primary" title="Editar Producto">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Columna para Formularios (Ahora a la derecha) -->
        <div class="col-lg-4">
            <div class="card action-card p-4 mb-4">
                <h4 class="mb-4">Agregar Producto</h4>
                <form action="../productos/productos_procesar.php" method="POST">
                    <input type="hidden" name="accion" value="agregar_producto">
                    <div class="mb-3"><label class="form-label">Nombre del Producto</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Stock Inicial</label><input type="number" name="stock" class="form-control" required min="0"></div>
                    <div class="mb-3"><label class="form-label">Categoría</label><select name="categoria" class="form-select" required><?php foreach ($categorias_enum as $cat): ?><option value="<?php echo $cat; ?>"><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
                    <button type="submit" class="btn btn-success w-100 mt-2"><span class="btn-icon-text"><i class="fas fa-plus-circle"></i>Agregar Producto</span></button>
                </form>
            </div>
            <div class="card action-card p-4">
                <h4 class="mb-4">Ajustar Stock</h4>
                <form action="../productos/productos_procesar.php" method="POST">
                    <input type="hidden" name="accion" value="ajustar_stock">
                    <div class="mb-3"><label class="form-label">Producto</label><select name="producto_id_ajuste" class="form-select" required><option value="">-- Elija un producto --</option><?php foreach ($productos as $prod_item): ?><option value="<?php echo $prod_item['id']; ?>"><?php echo htmlspecialchars($prod_item['nombre']) . " (Actual: " . $prod_item['stock'] . ")"; ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Tipo de Ajuste</label><select name="tipo_ajuste" class="form-select" required><option value="ingreso">Ingreso (+)</option><option value="merma">Merma (-)</option></select></div>
                    <div class="mb-3"><label class="form-label">Cantidad</label><input type="number" name="cantidad_ajuste" class="form-control" required min="1"></div>
                    <button type="submit" class="btn btn-warning w-100 mt-2"><span class="btn-icon-text"><i class="fas fa-sync-alt"></i>Realizar Ajuste</span></button>
                </form>
            </div>
        </div>
    </div>
</section>