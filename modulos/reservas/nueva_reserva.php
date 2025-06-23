<?php
session_start();
require_once '../../conexion.php';
if (!isset($_SESSION['usuario_id'])) {
    exit('Acceso denegado.');
}

// Limpiar sesión si se pide por URL (ej. botón "Reiniciar")
if (isset($_GET['limpiar_nr']) && $_GET['limpiar_nr'] == '1') {
    unset($_SESSION['nr_cliente_info'], $_SESSION['nr_filtros'], $_SESSION['nr_habitaciones'], $_SESSION['mensaje_reserva']);
}

// Recuperar datos de la sesión para poblar el formulario
$cliente_info = $_SESSION['nr_cliente_info'] ?? null;
$filtros_guardados = $_SESSION['nr_filtros'] ?? [];
$habitaciones_disponibles = $_SESSION['nr_habitaciones'] ?? [];
$tipos_habitacion = [];
$result_tipos = $conexion->query("SELECT id, nombre FROM tipo_habitacion ORDER BY nombre");
if ($result_tipos) {
    while ($tipo = $result_tipos->fetch_assoc()) {
        $tipos_habitacion[] = $tipo;
    }
}
$etapa = ($cliente_info) ? 'datos_reserva' : 'buscar_cliente';

$mensaje = $_SESSION['mensaje_reserva'] ?? null;
unset($_SESSION['mensaje_reserva']);

$conexion->close();
?>

<section id="proceso-nueva-reserva">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-plus-circle"></i> Crear Nueva Reserva</h2>
        <!-- MODIFICACIÓN: Usa JS para reiniciar sin recargar toda la página -->
        <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/nueva_reserva.php?limpiar_nr=1');" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Reiniciar</a>
    </header>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $mensaje['tipo']; ?>" id="nr-alert"><?php echo htmlspecialchars($mensaje['texto']); ?></div>
    <?php endif; ?>

    <!-- PASO 1: CLIENTE -->
    <div class="card action-card p-4 mb-4">
        <h4 class="mb-3">Paso 1: Cliente</h4>
        <?php if ($cliente_info): ?>
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0"><b>Cliente:</b> <?php echo htmlspecialchars($cliente_info['nombres'] . " " . $cliente_info['apellidos']); ?> (DNI: <?php echo htmlspecialchars($cliente_info['dni']); ?>)</p>
                <!-- MODIFICACIÓN: Usa JS para cambiar de cliente -->
                <a href="#" onclick="event.preventDefault(); procesarAccionNuevaReserva('limpiar_cliente_nr');" class="btn btn-sm btn-outline-warning">Cambiar</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6 border-end">
                    <!-- MODIFICACIÓN: El formulario ahora es manejado por JS -->
                    <form onsubmit="event.preventDefault(); procesarAccionNuevaReserva(this);">
                        <input type="hidden" name="accion" value="buscar_cliente_dni">
                        <div class="mb-3"><label class="form-label">DNI</label><input type="text" name="dni_buscar" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form onsubmit="event.preventDefault(); procesarAccionNuevaReserva(this);" class="needs-validation" novalidate>
                        <input type="hidden" name="accion" value="registrar_cliente_reserva">

                        <div class="mb-2">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni_reg" class="form-control" required pattern="^\d{6,15}$">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombres_reg" class="form-control" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,}$">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos_reg" class="form-control" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{2,}$">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono_reg" class="form-control" pattern="^\d{6,15}$">
                            <div class="invalid-feedback"></div>
                        </div>

                        <button type="submit" class="btn btn-success">Registrar y Seleccionar</button>
                    </form>


                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- PASO 2 Y 3: FECHAS, HABITACIONES Y CONFIRMACIÓN -->
    <?php if ($etapa === 'datos_reserva' && $cliente_info): ?>
        <div class="card action-card p-4">
            <!-- MODIFICACIÓN: Todo el proceso ahora está dentro de un gran formulario para facilidad de JS -->
            <form id="form-datos-reserva" onsubmit="event.preventDefault();">
                <h4 class="mb-3">Paso 2: Fechas y Búsqueda</h4>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3"><label class="form-label">Entrada</label><input type="date" name="fecha_entrada" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados['fecha_entrada'] ?? date('Y-m-d')); ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Salida</label><input type="date" name="fecha_salida" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados['fecha_salida'] ?? date('Y-m-d', strtotime('+1 day'))); ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Tipo Hab.</label><select name="tipo_habitacion_filtro" class="form-select">
                            <option value="">Cualquiera</option><?php foreach ($tipos_habitacion as $tipo): ?><option value="<?php echo $tipo['id']; ?>" <?php echo (isset($filtros_guardados['tipo_habitacion_filtro']) && $filtros_guardados['tipo_habitacion_filtro'] == $tipo['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nombre']); ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-2"><label class="form-label">Capacidad</label><input type="number" name="capacidad_filtro" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados['capacidad_filtro'] ?? '1'); ?>" min="1"></div>
                    <div class="col-md-1"><button type="button" onclick="procesarAccionNuevaReserva('buscar_habitaciones');" class="btn btn-primary w-100"><i class="fas fa-search"></i></button></div>
                </div>

                <?php if (!empty($habitaciones_disponibles)): ?>
                    <hr class="my-4">
                    <h4 class="mt-4">Paso 3: Confirmar Reserva</h4>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Cap.</th>
                                    <th>Precio/Noche</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($habitaciones_disponibles as $hab): ?>
                                    <tr>
                                        <td><input type="radio" name="habitacion_id_seleccionada" value="<?php echo $hab['id']; ?>" class="form-check-input" required></td>
                                        <td><?php echo htmlspecialchars($hab['nombre_habitacion']); ?></td>
                                        <td><?php echo htmlspecialchars($hab['tipo_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($hab['capacidad']); ?></td>
                                        <td>S/ <?php echo htmlspecialchars(number_format($hab['precio'], 2)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6"><label class="form-label">Modo</label><select name="modo_reserva" class="form-select" required>
                                <option value="Simple">Simple</option>
                                <option value="Anticipada">Anticipada</option>
                            </select></div>
                        <div class="col-md-6"><label class="form-label">Pago</label><select name="metodo_pago" class="form-select">
                                <option value="">Opcional</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Tarjeta">Tarjeta</option>
                            </select></div>
                    </div>
                    <div class="d-flex justify-content-end mt-4"><button type="button" onclick="procesarAccionNuevaReserva('confirmar_reserva_final');" class="btn btn-lg btn-success"><i class="fas fa-check-circle"></i> Confirmar Reserva</button></div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</section>

<script>
    // Eliminar alerta después de unos segundos
    if (document.getElementById('nr-alert')) {
        setTimeout(() => {
            document.getElementById('nr-alert').style.display = 'none';
        }, 4000);
    }

    // Función central para todas las acciones de este formulario
    function procesarAccionNuevaReserva(source) {
        const url = '../reservas/reservas_procesar.php';
        let formData;

        // Determinar de dónde vienen los datos
        if (typeof source === 'string') { // Es un botón o enlace con una acción específica
            formData = new FormData(document.getElementById('form-datos-reserva')); // Tomar datos del formulario principal (fechas, etc.)
            formData.append('accion', source);
        } else { // Es un formulario (buscar o registrar cliente)
            formData = new FormData(source);
        }

        // Mostrar indicador de carga
        const container = document.getElementById('proceso-nueva-reserva');
        container.style.opacity = '0.5';

        fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.action === 'reload_form') {
                    cargarContenido('../reservas/nueva_reserva.php');
                } else if (data.action === 'redirect_to_gestionar') {
                    // El mensaje de éxito lo mostrará la página de gestión
                    cargarContenido('../reservas/gestionar_reservas.php');
                } else {
                    // Recargar por si acaso, para mostrar cualquier mensaje de error inesperado
                    cargarContenido('../reservas/nueva_reserva.php');
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                container.style.opacity = '1';
                alert('Ocurrió un error de red. Inténtelo de nuevo.');
            });
    }
    // Validación estilo Bootstrap para formularios personalizados
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                console.log('Form submit attempt'); // >>> diagnóstico
                if (!form.checkValidity()) {
                    console.log('Invalid form:', form.name);
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>