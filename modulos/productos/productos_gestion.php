<?php
session_start();
require_once '../../conexion.php';

// Seguridad básica
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    exit();
}

// Obtener productos
$productos = [];
$result = $conexion->query("SELECT id, nombre, stock, categoria FROM productos ORDER BY categoria, nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Categorías ENUM simuladas
$categorias_enum = ['Aseo', 'General', 'Bebidas'];

// Manejo de mensajes
$mensaje = $_SESSION['mensaje_prod'] ?? null;
unset($_SESSION['mensaje_prod']);

$conexion->close();
?>

<style>
    #admin-productos .card {
        border: 1px solid var(--color-borde);
        border-radius: var(--radius-grande);
        box-shadow: var(--sombra-sutil);
        height: 100%;
    }
    #admin-productos .card-header {
        background-color: #f9fafb;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--color-borde);
    }
    #admin-productos .card-header h5 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--color-texto-principal);
    }
    #admin-productos .card-header h5 i {
        color: var(--color-acento);
    }
    .table-productos {
        margin-bottom: 0;
    }
    .table-productos thead th {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #fff;
        border-bottom: 1px solid var(--color-borde);
        padding: 1rem 1.5rem;
    }
    .table-productos tbody td {
        vertical-align: middle;
        padding: 1rem 1.5rem;
    }
    .table-productos .stock-badge {
        font-size: 0.85rem;
        font-weight: 600;
        color:rgb(17, 22, 27);
        padding: 0.4em 0.8em;
        border-radius: 20px;
        min-width: 45px;
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
        font-weight: 500;
    }
    #admin-productos .accordion-item {
        border: none;
        border-bottom: 1px solid var(--color-borde);
    }
    #admin-productos .accordion-item:last-child {
        border-bottom: none;
    }
    #admin-productos .accordion-button {
        font-weight: 600;
        color: var(--color-texto-principal);
    }
    #admin-productos .accordion-button:not(.collapsed) {
        background-color: rgba(79, 70, 229, 0.05);
        color: var(--color-acento);
        box-shadow: none;
    }
    #admin-productos .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }
    #admin-productos .accordion-body {
        padding: 1.5rem;
    }
    .btn-icon-text {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
</style>

<section id="admin-productos">
    <h2 class="section-title">
        <i class="fas fa-dolly"></i> Gestión de Productos y Stock
    </h2>
    <p class="section-subtitle">
        Visualice el inventario y realice altas o ajustes de stock de forma centralizada.
    </p>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $mensaje['tipo'] === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($mensaje['texto']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sección izquierda: tabla de inventario -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-boxes-stacked"></i> Inventario Actual</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-productos">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted p-5">No hay productos registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                            $stock = (int)$producto['stock'];
                                            $stock_class = $stock <= 10 ? 'stock-critical' : ($stock <= 25 ? 'stock-low' : 'stock-ok');
                                            $cat_color = match($producto['categoria']) {
                                                'Aseo' => 'text-bg-primary',
                                                'Bebidas' => 'text-bg-danger',
                                                default => 'text-bg-secondary',
                                            };
                                        ?>
                                        <tr>
                                            <td><strong>#<?= $producto['id'] ?></strong></td>
                                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                            <td><span class="badge <?= $cat_color ?> category-badge"><?= htmlspecialchars($producto['categoria']) ?></span></td>
                                            <td class="text-center">
                                                <span class="stock-badge <?= $stock_class ?>"><?= $stock ?></span>
                                            </td>
                                            <td>
                                                <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/editar_producto.php?id=<?= $producto['id'] ?>');" class="btn btn-sm btn-outline-primary" title="Editar Producto">
                                                    <i class="fas fa-pen"></i>
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
        </div>

        <!-- Sección derecha: acciones -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-cogs"></i> Panel de Acciones</h5>
                </div>
                <div class="card-body p-0">
                    <div class="accordion accordion-flush" id="accionesProductoAccordion">
                        <!-- Agregar producto -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAgregar">
                                    Agregar Nuevo Producto
                                </button>
                            </h2>
                            <div id="collapseAgregar" class="accordion-collapse collapse show" data-bs-parent="#accionesProductoAccordion">
                                <div class="accordion-body">
                                    <form action="../productos/productos_procesar.php" method="POST">
                                        <input type="hidden" name="return_url" value="producto_gestion.php">
                                        <input type="hidden" name="accion" value="agregar_producto">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre</label>
                                            <input type="text" name="nombre" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stock Inicial</label>
                                            <input type="number" name="stock" class="form-control" required min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Categoría</label>
                                            <select name="categoria" class="form-select" required>
                                                <?php foreach ($categorias_enum as $cat): ?>
                                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 mt-2 btn-icon-text">
                                            <i class="fas fa-plus-circle"></i>Agregar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Ajustar stock -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAjustar">
                                    Ajustar Stock
                                </button>
                            </h2>
                            <div id="collapseAjustar" class="accordion-collapse collapse" data-bs-parent="#accionesProductoAccordion">
                                <div class="accordion-body">
                                    <form action="../productos/productos_procesar.php" method="POST">
                                        <input type="hidden" name="return_url" value="producto_gestion.php">
                                        <input type="hidden" name="accion" value="ajustar_stock">
                                        <div class="mb-3">
                                            <label class="form-label">Producto</label>
                                            <select name="producto_id_ajuste" class="form-select" required>
                                                <option value="">-- Elija --</option>
                                                <?php foreach ($productos as $p): ?>
                                                    <option value="<?= $p['id'] ?>"><?= $p['nombre'] . " (Act: " . $p['stock'] . ")" ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tipo</label>
                                            <select name="tipo_ajuste" class="form-select" required>
                                                <option value="ingreso">Ingreso (+)</option>
                                                <option value="merma">Merma (-)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Cantidad</label>
                                            <input type="number" name="cantidad_ajuste" class="form-control" required min="1">
                                        </div>
                                        <button type="submit" class="btn btn-warning w-100 mt-2 btn-icon-text">
                                            <i class="fas fa-sync-alt"></i>Ajustar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /.accordion -->
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div>
    </div>
</section>
