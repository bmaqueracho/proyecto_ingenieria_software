<?php
session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') { 
    exit('Acceso denegado.'); 
}

// Obtener datos para ambas tablas
$tipos_habitacion = $conexion->query("SELECT * FROM tipo_habitacion ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$habitaciones = $conexion->query("
    SELECT h.id, h.nombre, h.capacidad, h.precio, h.estado, th.nombre as tipo_nombre 
    FROM habitaciones h 
    JOIN tipo_habitacion th ON h.tipo_id = th.id 
    ORDER BY h.nombre
")->fetch_all(MYSQLI_ASSOC);

$mensaje = $_SESSION['mensaje_habitacion'] ?? null;
unset($_SESSION['mensaje_habitacion']);

$conexion->close();
?>

<section id="gestion-habitaciones">
    <h2 class="section-title"><i class="fas fa-bed"></i> Gestión de Habitaciones y Tipos</h2>
    <p class="section-subtitle mb-4">Defina los tipos de habitación y administre cada una de las habitaciones del hotel.</p> <!-- Quitado text-white-50 para que herede el color correcto -->

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $mensaje['tipo'] === 'exito' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensaje['texto']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN PARA TIPOS DE HABITACIÓN (Diseño mejorado) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Tipos de Habitación</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h6>Crear Nuevo Tipo</h6>
                    <form action="../habitaciones/habitaciones_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="crear_tipo">
                        <div class="mb-3"><label class="form-label">Nombre del Tipo</label><input type="text" name="nombre" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" rows="2"></textarea></div>
                        <button type="submit" class="btn btn-primary w-100">Crear Tipo</button>
                    </form>
                </div>
                <div class="col-lg-8">
                    <h6>Tipos Existentes</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th class="text-end">Acciones</th></tr></thead>
                            <tbody>
                                <?php if (empty($tipos_habitacion)): ?>
                                    <tr><td colspan="4" class="text-center text-muted p-4">No hay tipos de habitación creados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($tipos_habitacion as $tipo): ?>
                                    <tr>
                                        <td><?php echo $tipo['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($tipo['nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($tipo['descripcion']); ?></td>
                                        <td class="text-end"><a href="#" onclick="event.preventDefault(); cargarContenido('../habitaciones/editar_tipo.php?id=<?php echo $tipo['id']; ?>');" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN PARA HABITACIONES INDIVIDUALES (Diseño mejorado) -->
    <div class="card shadow-sm">
         <div class="card-header bg-light py-3">
            <h5 class="mb-0"><i class="fas fa-door-closed me-2"></i>Habitaciones Individuales</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h6>Crear Nueva Habitación</h6>
                    <form action="../habitaciones/habitaciones_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="crear_habitacion">
                        <div class="mb-3"><label class="form-label">Nombre/Número</label><input type="text" name="nombre" class="form-control" placeholder="Ej: 101, Suite Presidencial" required></div>
                        <div class="mb-3"><label class="form-label">Tipo de Habitación</label><select name="tipo_id" class="form-select" required><option value="" disabled selected>-- Seleccione --</option><?php foreach ($tipos_habitacion as $tipo): ?><option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option><?php endforeach; ?></select></div>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6"><label class="form-label">Capacidad</label><input type="number" name="capacidad" class="form-control" required min="1"></div>
                            <div class="col-sm-6"><label class="form-label">Precio (S/)</label><input type="number" name="precio" class="form-control" step="0.01" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Estado Inicial</label><select name="estado" class="form-select" required><option value="Disponible">Disponible</option><option value="Mantenimiento">Mantenimiento</option></select></div>
                        <button type="submit" class="btn btn-primary w-100">Añadir Habitación</button>
                    </form>
                </div>
                <div class="col-lg-8">
                    <h6>Listado de Habitaciones</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                             <thead><tr><th>Nombre</th><th>Tipo</th><th>Precio</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                            <!-- ========================================================== -->
                            <!-- ============== LA CORRECCIÓN ESTÁ AQUÍ ABAJO ============== -->
                            <!-- ========================================================== -->
                            <tbody>
                                <?php if (empty($habitaciones)): ?>
                                    <tr><td colspan="5" class="text-center p-5 text-muted">No hay habitaciones registradas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($habitaciones as $hab): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($hab['nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($hab['tipo_nombre']); ?></td>
                                        <td>S/ <?php echo number_format($hab['precio'], 2); ?></td>
                                        <td style="min-width: 150px;">
                                            <form action="../habitaciones/habitaciones_procesar.php" method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="cambiar_estado_habitacion">
                                                <input type="hidden" name="habitacion_id" value="<?php echo $hab['id']; ?>">
                                                <select name="nuevo_estado" 
                                                        class="form-select form-select-sm fw-bold <?php 
                                                            switch($hab['estado']) {
                                                                case 'Disponible': echo 'border-success text-success'; break;
                                                                case 'Ocupada': echo 'border-danger text-danger'; break;
                                                                case 'Mantenimiento': echo 'border-warning text-warning'; break;
                                                                default: echo 'border-secondary';
                                                            }
                                                        ?>" 
                                                        onchange="this.form.submit()"
                                                        <?php echo $hab['estado'] === 'Ocupada' ? 'disabled' : ''; // Deshabilitar si está ocupada ?>
                                                        >
                                                    <option value="Disponible" <?php echo $hab['estado'] === 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                                                    <option value="Mantenimiento" <?php echo $hab['estado'] === 'Mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                                                    
                                                    <?php if ($hab['estado'] === 'Ocupada'): // Si está ocupada, mostrar la opción pero mantenerla seleccionada ?>
                                                    <option value="Ocupada" selected>Ocupada</option>
                                                    <?php endif; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" onclick="event.preventDefault(); cargarContenido('../habitaciones/editar_habitacion.php?id=<?php echo $hab['id']; ?>');" class="btn btn-sm btn-outline-primary" title="Editar Habitación">
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
        </div>
    </div>
</section>