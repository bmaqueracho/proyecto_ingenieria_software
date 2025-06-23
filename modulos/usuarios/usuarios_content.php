<!-- ========================================== -->
<!-- ========== USUARIO_CONTENT.PHP (DISEÑO) ========== -->
<!-- ========================================== -->

<?php
session_start();
require_once '../../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') { exit('Acceso denegado.'); }
$usuarios = $conexion->query("SELECT id, dni, nombre, apellido, cargo, estado FROM usuarios ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$mensaje = $_SESSION['mensaje_usuario'] ?? null;
unset($_SESSION['mensaje_usuario']);
$conexion->close();
?>

<!-- =================== ESTILOS MODERNOS =================== -->
<style>
    .card {
        border-radius: 1rem;
        border: 1px solid #e4e6ef;
    }
    .card-header {
        background-color: #f5f8fa !important;
        border-bottom: 1px solid #e4e6ef;
    }
    .card-title {
        font-weight: 600;
        font-size: 1.125rem;
        color: #3f4254;
    }
    .table th {
        color: #5e6278;
        font-size: 0.95rem;
        font-weight: 600;
    }
    .table td {
        font-size: 0.94rem;
        vertical-align: middle;
        color: #3f4254;
    }
    .table-hover > tbody > tr:hover {
        background-color: #f1faff;
    }
    .badge {
        font-size: 0.8rem;
        padding: 0.4em 0.75em;
    }
    .btn-group .btn {
        transition: all 0.2s ease-in-out;
    }
    .btn:hover {
        transform: scale(1.05);
    }
    .form-control, .form-select {
        border-radius: 0.5rem;
        font-size: 0.60rem;
        padding: 0.22rem 0.50rem;
    }
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    .btn-primary:hover {
        background-color: #2e59d9;
    }
</style>

<section id="gestion-usuarios-fiel">
    <div class="mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
        <p class="section-subtitle mb-0 mt-1">Cree, edite o cambie el estado de las cuentas de usuario del sistema.</p>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo ($mensaje['tipo'] === 'exito' ? 'success' : 'danger'); ?> alert-dismissible fade show mb-4" role="alert">
        <?php echo htmlspecialchars($mensaje['texto']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-list-ul me-2"></i>Usuarios del Sistema</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 px-4">Nombre Completo</th>
                                <th class="py-3 px-4">Cargo</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr><td colspan="4" class="text-center p-5 text-muted">No hay usuarios registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></div>
                                        <small class="text-muted">DNI: <?php echo htmlspecialchars($usuario['dni']); ?></small>
                                    </td>
                                    <td class="px-4"><?php echo htmlspecialchars($usuario['cargo']); ?></td>
                                    <td class="px-4 text-center">
                                        <span class="badge rounded-pill text-bg-<?php echo $usuario['estado'] === 'Activo' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($usuario['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 text-center">
                                        <div class="btn-group" role="group">
                                            <a href="#" onclick="event.preventDefault(); cargarContenido('../usuarios/editar_usuario.php?id=<?php echo $usuario['id']; ?>');" class="btn btn-sm btn-outline-primary" title="Editar Usuario">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="../usuarios/usuarios_procesar.php" method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="cambiar_estado">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <?php if ($usuario['estado'] === 'Activo'): ?>
                                                    <input type="hidden" name="nuevo_estado" value="Inactivo">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Inactivar Usuario"><i class="fas fa-user-slash"></i></button>
                                                <?php else: ?>
                                                    <input type="hidden" name="nuevo_estado" value="Activo">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Activar Usuario"><i class="fas fa-user-check"></i></button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario</h5>
                </div>
                <div class="card-body p-4">
                    <form action="../usuarios/usuarios_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="crear_usuario">
                        <h6 class="text-muted small text-uppercase mb-3">Datos Personales</h6>
                        <div class="mb-3">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control" required pattern="\d{8,10}" title="DNI de 8 a 10 dígitos">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Correo <small>(Opcional)</small></label>
                                <input type="email" name="correo" class="form-control">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Teléfono <small>(Opcional)</small></label>
                                <input type="tel" name="telefono" class="form-control">
                            </div>
                        </div>
                        <hr class="my-4">
                        <h6 class="text-muted small text-uppercase mb-3">Datos Laborales y Acceso</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Cargo</label>
                                <select name="cargo" class="form-select" required>
                                    <option value="Recepcionista">Recepcionista</option>
                                    <option value="Administrador">Administrador</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Turno</label>
                                <select name="turno" class="form-select">
                                    <option value="DIA">DÍA</option>
                                    <option value="NOCHE">NOCHE</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salario (S/)</label>
                            <input type="number" name="salario" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="clave" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-bold">
                           <i class="fas fa-plus-circle me-2"></i>Añadir Usuario al Sistema
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
