<?php
// --- INICIO DEL SCRIPT PHP (SIN CAMBIOS EN LA FUNCIONALIDAD) ---
session_start();
require_once '../../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') { exit('Acceso denegado.'); }
$usuario_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$usuario_id) { exit('ID de usuario inválido.'); }
$stmt = $conexion->prepare("SELECT dni, nombre, apellido, correo, telefono, cargo, turno, salario FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { exit('Usuario no encontrado.'); }
$usuario_actual = $result->fetch_assoc();
$stmt->close();
$conexion->close();
// --- FIN DEL SCRIPT PHP ---
?>

<!-- ========================================================== -->
<!-- ========= INICIO DEL HTML REDISEÑADO (ESTILO WIZARD) ===== -->
<!-- ========================================================== -->

<style>
    /* Estilos para el efecto "wizard" */
    .wizard-step {
        display: flex;
        align-items: flex-start;
        margin-bottom: 2.5rem;
    }
    .wizard-step-icon {
        flex-shrink: 0;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--color-sidebar-link-active-bg);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 600;
        margin-right: 1.5rem;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
    }
    .wizard-step-content {
        flex-grow: 1;
        padding-top: 0.25rem;
    }
    .wizard-step-content h5 {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .wizard-step-content p {
        color: var(--color-texto-secundario);
        margin-bottom: 1.5rem;
    }
</style>

<section id="wizard-editar-usuario">

    <!-- 1. Encabezado principal de la página -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="section-title mb-0"><i class="fas fa-magic"></i> Asistente de Edición de Usuario</h2>
            <p class="section-subtitle mb-0 mt-1">Modificando el perfil de <strong><?php echo htmlspecialchars($usuario_actual['nombre']); ?></strong>.</p>
        </div>
        <a href="#" onclick="event.preventDefault(); cargarContenido('../usuarios/usuarios_content.php');" class="btn btn-light border">
            <i class="fas fa-times me-2"></i>Cancelar
        </a>
    </div>

    <!-- 2. Formulario principal -->
    <form action="../usuarios/usuarios_procesar.php" method="POST">
        <input type="hidden" name="accion" value="actualizar_usuario">
        <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">

        <!-- PASO 1: DATOS PERSONALES -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="wizard-step">
                    <div class="wizard-step-icon">01</div>
                    <div class="wizard-step-content">
                        <h5>Datos Personales</h5>
                        <p>Información básica para la identificación y contacto del usuario.</p>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario_actual['nombre']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario_actual['apellido']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" id="correo" name="correo" class="form-control" value="<?php echo htmlspecialchars($usuario_actual['correo']); ?>" required>
                            </div>
                             <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario_actual['telefono']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- PASO 2: DATOS LABORALES -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4 p-md-5">
                <div class="wizard-step">
                    <div class="wizard-step-icon">02</div>
                    <div class="wizard-step-content">
                        <h5>Datos Laborales</h5>
                        <p>Define el rol, turno y compensación del usuario dentro del sistema.</p>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="cargo" class="form-label">Cargo en el Sistema</label>
                                <select id="cargo" name="cargo" class="form-select" required>
                                    <option value="Recepcionista" <?php echo ($usuario_actual['cargo'] === 'Recepcionista') ? 'selected' : ''; ?>>Recepcionista</option>
                                    <option value="Administrador" <?php echo ($usuario_actual['cargo'] === 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="turno" class="form-label">Turno de Trabajo</label>
                                <select id="turno" name="turno" class="form-select">
                                    <option value="DIA" <?php echo ($usuario_actual['turno'] === 'DIA') ? 'selected' : ''; ?>>Día</option>
                                    <option value="NOCHE" <?php echo ($usuario_actual['turno'] === 'NOCHE') ? 'selected' : ''; ?>>Noche</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="salario" class="form-label">Salario (S/)</label>
                                <input type="number" id="salario" name="salario" class="form-control" step="0.01" value="<?php echo htmlspecialchars($usuario_actual['salario']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PASO 3: SEGURIDAD -->
        <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="wizard-step">
                    <div class="wizard-step-icon">03</div>
                    <div class="wizard-step-content">
                        <h5>Seguridad y Acceso</h5>
                        <p>Gestiona las credenciales de acceso. El DNI no es modificable por seguridad.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="dni" class="form-label">DNI (No modificable)</label>
                                <input type="text" id="dni" name="dni" class="form-control bg-light" value="<?php echo htmlspecialchars($usuario_actual['dni']); ?>" readonly>
                            </div>
                             <div class="col-md-6">
                                <label for="clave" class="form-label">Nueva Contraseña</label>
                                <input type="password" id="clave" name="clave" class="form-control" placeholder="Dejar en blanco para no cambiar">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción Finales -->
        <div class="text-end mt-4">
             <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-check-circle me-2"></i>Confirmar y Guardar Cambios
            </button>
        </div>
    </form>
</section>