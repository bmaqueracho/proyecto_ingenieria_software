<?php
session_start();
require_once '../../conexion.php';

// La lógica PHP inicial se mantiene intacta.
if (!isset($_SESSION['usuario_id'])) {
    exit('Acceso denegado.');
}

$origen_dashboard = ($_GET['from'] ?? '') === 'admin' ? 'admin' : 'recepcionista';
$url_dashboard_retorno = "../dashboard/{$origen_dashboard}_dashboard.php?load_module=modulos/clientes/clientes_content";

$cliente_encontrado = $_SESSION['cliente_encontrado'] ?? null;
unset($_SESSION['cliente_encontrado']);
$mensaje = $_SESSION['mensaje_cliente'] ?? null;
unset($_SESSION['mensaje_cliente']);

$lista_clientes = [];
$resultado_lista = $conexion->query("SELECT dni, nombres, apellidos, telefono, observacion FROM clientes ORDER BY apellidos ASC, nombres ASC");
if ($resultado_lista) {
    while ($fila = $resultado_lista->fetch_assoc()) {
        $lista_clientes[] = $fila;
    }
}
$conexion->close();
?>

<!-- ========================================================================== -->
<!-- ============== ¡NUEVO CSS PROFESIONAL INCRUSTADO! ============== -->
<!-- ========================================================================== -->
<style>
    /* Estilos específicos para el módulo de Gestión de Clientes */
    .gestion-clientes-container .card {
        padding: 2rem;
        height: 100%;
    }

    .form-card h4,
    .table-card h4 {
        font-family: var(--fuente-titulos);
        font-weight: 600;
        color: var(--color-texto-principal);
        margin-bottom: 1.5rem;
    }

    /* Formularios de la izquierda */
    .form-section {
        background-color: var(--color-card-bg);
        border: 1px solid var(--color-borde);
        border-radius: var(--radius-grande);
        padding: 2rem;
        box-shadow: var(--sombra-sutil);
        transition: var(--transicion-suave);
    }

    .form-section:hover {
        box-shadow: var(--sombra-media);
    }

    /* Tabla de la derecha */
    .table-wrapper {
        border: 1px solid var(--color-borde);
        border-radius: var(--radius-grande);
        overflow: hidden;
        /* Clave para que el radius afecte a la tabla */
        box-shadow: var(--sombra-sutil);
    }

    .table-wrapper .table {
        margin-bottom: 0;
    }

    .table-wrapper thead th {
        background-color: #f9fafb;
        /* Un gris muy claro para la cabecera */
        border-bottom: 2px solid var(--color-borde);
        color: var(--color-texto-secundario);
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-wrapper tbody td {
        vertical-align: middle;
        padding: 1rem;
        color: var(--color-texto-principal);
    }

    .table-wrapper tbody tr {
        transition: background-color 0.2s ease-in-out;
    }

    .table-wrapper tbody tr:hover {
        background-color: #eff6ff;
        /* Un azul muy claro al pasar el mouse */
    }

    .table-wrapper tbody tr:not(:last-child) td {
        border-bottom: 1px solid var(--color-borde);
    }

    .table-wrapper td.text-center {
        padding: 2rem;
    }

    /* Botones */
    .btn-icon-text {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    #wrapper {
      max-width: 90%;
      margin: 0 auto;
    }
</style>

<section id="gestion-clientes">
    <h2 class="section-title"><i class="fas fa-users"></i> Gestión de Clientes</h2>
    <p class="section-subtitle">Añada nuevos clientes o busque existentes para actualizar sus datos.</p>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo ($mensaje['tipo'] === 'exito' ? 'success' : 'danger'); ?> mb-4">
            <?php echo htmlspecialchars($mensaje['texto']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Columna Izquierda: Formularios de Acciones -->
        <div class="col-lg-5">
            <?php if ($cliente_encontrado): ?>
                <!-- VISTA DE ACTUALIZACIÓN -->
                <div class="form-section">
                    <h4>Actualizar Datos del Cliente</h4>
                    <form action="../clientes/clientes_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="redirect_url" value="<?php echo $url_dashboard_retorno; ?>">
                        <div class="mb-3"><label class="form-label">DNI</label><input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['dni']); ?>" readonly></div>
                        <div class="mb-3"><label class="form-label">Nombres</label><input type="text" name="nombres" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['nombres']); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Apellidos</label><input type="text" name="apellidos" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['apellidos']); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['telefono']); ?>"></div>
                        <div class="mb-3"><label class="form-label">Observación</label><textarea name="observacion" class="form-control" rows="3"><?php echo htmlspecialchars($cliente_encontrado['observacion']); ?></textarea></div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="#" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php?from=<?php echo $origen_dashboard; ?>');" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-warning">Actualizar Datos</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- VISTA DE BÚSQUEDA Y REGISTRO -->
                <div class="form-section mb-4">
                    <h4>Buscar Cliente</h4>
                    <form action="../clientes/clientes_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="buscar">
                        <input type="hidden" name="redirect_url" value="<?php echo $url_dashboard_retorno; ?>">
                        <label class="form-label">Buscar por DNI para ver o actualizar</label>
                        <div class="input-group">
                            <input type="text" name="dni" class="form-control" placeholder="Ingresar DNI..." required>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="form-section">
                    <h4>Registrar Nuevo Cliente</h4>
                    <form action="../clientes/clientes_procesar.php" method="POST" novalidate id="formRegistroCliente">
                        <input type="hidden" name="accion" value="registrar">
                        <input type="hidden" name="redirect_url" value="<?php echo $url_dashboard_retorno; ?>">

                        <!-- DNI -->
                        <div class="mb-3">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control" pattern="^\d{6,15}$" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Nombres -->
                        <div class="mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombres" class="form-control" pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,}$" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Apellidos -->
                        <div class="mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,}$" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Teléfono -->
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" pattern="^\d{6,15}$">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Observación -->
                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <textarea name="observacion" class="form-control" rows="2" placeholder="Opcional"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mt-2">
                            <span class="btn-icon-text"><i class="fas fa-plus-circle"></i>Registrar Cliente</span>
                        </button>
                    </form>

                </div>
            <?php endif; ?>
        </div>

        <!-- Columna Derecha: Lista de Clientes -->
        <div class="col-lg-7">
            <div class="card action-card">
                <h4 class="mb-3">Lista de Clientes Registrados</h4>
                <div class="table-wrapper">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Teléfono</th>
                                <th>observacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lista_clientes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay clientes registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lista_clientes as $cliente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cliente['dni']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['nombres']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['apellidos']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['observacion']); ?></td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    // Activar validación Bootstrap
    (function() {
        'use strict';
        const form = document.getElementById('formRegistroCliente');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>