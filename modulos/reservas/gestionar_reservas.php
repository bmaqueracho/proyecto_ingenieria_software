<?php
session_start();
require_once '../../conexion.php'; 

// ... (toda la lógica PHP de arriba se mantiene igual) ...
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    exit();
}
$reservas = [];
$sql = "SELECT r.id AS reserva_id, r.fecha_entrada, r.fecha_salida,
               r.monto_total, r.estado AS estado_reserva,
               c.nombres AS cliente_nombres, c.apellidos AS cliente_apellidos,
               h.nombre AS nombre_habitacion
        FROM reservas r
        LEFT JOIN clientes c ON r.cliente_dni = c.dni
        LEFT JOIN habitaciones h ON r.habitacion_id = h.id
        ORDER BY r.fecha_entrada DESC, r.id DESC";
$result = $conexion->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { $reservas[] = $row; } }
$mensaje_global = $_SESSION['mensaje_exito_global'] ?? null;
unset($_SESSION['mensaje_exito_global']);
$conexion->close();
?>
<style>
    .estado-badge { font-size: 0.8rem; padding: 0.4em 0.8em; border-radius: 20px; font-weight: 600; color: #fff; text-transform: uppercase; }
    .estado-confirmada { background-color: #28a745; }
    .estado-cancelada { background-color: #dc3545; }
    .estado-completa { background-color: #17a2b8; }
    
    /* === CSS MEJORADO PARA EL DISEÑO MINIMALISTA === */
    .actions-cell .dropdown-toggle {
        background-color: transparent !important; /* Quita el fondo del botón */
        border: none !important; /* Quita el borde */
        box-shadow: none !important; /* Quita la sombra al hacer clic */
        color: #6c757d; /* Color gris para el icono */
        padding: 0.25rem 0.5rem;
    }

    .actions-cell .dropdown-toggle::after {
        display: none; /* Oculta la flecha por defecto de Bootstrap */
    }

    .dropdown-menu {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border: 1px solid #dee2e6;
        padding-top: 0.5rem; /* Pequeño espacio arriba */
        padding-bottom: 0.5rem; /* Pequeño espacio abajo */
    }

    /* ESTE ES EL CAMBIO CLAVE para quitar los puntos negros */
    .dropdown-menu li {
        list-style-type: none; /* Oculta los puntos de la lista */
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    .dropdown-item i.fa, .dropdown-item i.fas {
        width: 16px;
        text-align: center;
        color: #6c757d;
    }
    .dropdown-item:hover i {
        color: inherit;
    }
</style>

<section id="tabla-gestion-reservas">
    <!-- ... (el encabezado de la sección no cambia) ... -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-1"><i class="fas fa-tasks"></i> Gestionar Reservas</h2>
            <p class="section-subtitle mb-0">Visualice y administre todas las reservas del hotel.</p>
        </div>
        <a href="#" class="btn btn-success" onclick="event.preventDefault(); cargarContenido('../reservas/nueva_reserva.php?limpiar_nr=1');">
            <i class="fas fa-plus me-2"></i> Nueva Reserva
        </a>
    </div>

    <!-- ... (alertas no cambian) ... -->
    <div id="gest-alert-container">
        <?php if ($mensaje_global): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje_global); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <!-- ... (thead no cambia) ... -->
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4">Habitación</th>
                            <th class="py-3 px-4">Fechas</th>
                            <th class="py-3 px-4">Monto</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reservas)): ?>
                            <?php foreach ($reservas as $reserva): ?>
                                <tr>
                                    <!-- ... (otros <td> no cambian) ... -->
                                    <td class="py-3 px-4"><strong>#<?php echo htmlspecialchars($reserva['reserva_id']); ?></strong></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($reserva['cliente_nombres'] . " " . $reserva['cliente_apellidos']); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($reserva['nombre_habitacion'] ?? 'N/A'); ?></td>
                                    <td class="py-3 px-4"><?php echo date("d/m/y", strtotime($reserva['fecha_entrada'])) . ' - ' . date("d/m/y", strtotime($reserva['fecha_salida'])); ?></td>
                                    <td class="py-3 px-4">S/ <?php echo htmlspecialchars(number_format($reserva['monto_total'], 2)); ?></td>
                                    <td class="py-3 px-4">
                                        <?php $estado_clase = strtolower($reserva['estado_reserva']); ?>
                                        <span class="estado-badge estado-<?php echo $estado_clase; ?>">
                                            <?php echo htmlspecialchars($reserva['estado_reserva']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center actions-cell">
                                        <?php if ($reserva['estado_reserva'] === 'Confirmada'): ?>
                                            <div class="btn-group">
                                                <!-- === CAMBIO CLAVE EN EL HTML === -->
                                                <!-- El botón ahora solo contiene un icono -->
                                                <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="#" onclick="cambiarEstadoReserva(event, <?php echo $reserva['reserva_id']; ?>, 'Completa')"><i class="fas fa-check-double"></i> Marcar Completa</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="cargarContenido('../reservas/modificar_reserva.php?id=<?php echo $reserva['reserva_id']; ?>')"><i class="fas fa-edit"></i> Modificar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelarReserva(event, <?php echo $reserva['reserva_id']; ?>)"><i class="fas fa-times-circle"></i> Cancelar Reserva</a></li>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">--</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center p-5 text-muted">No hay reservas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<!-- 3. JAVASCRIPT (Controlador de acciones para esta vista) -->
<!-- ======================================================== -->
<script>
    // Desvanecer la alerta de éxito después de 5 segundos
    const gestAlert = document.querySelector('#gest-alert-container .alert');
    if (gestAlert) {
        setTimeout(() => { new bootstrap.Alert(gestAlert).close(); }, 5000);
    }
    
    /**
     * Cambia el estado de una reserva a 'Completa' usando AJAX.
     * Llama a procesar_estado_reserva.php que ya tienes.
     */
    function cambiarEstadoReserva(event, reservaId, nuevoEstado) {
        event.preventDefault();
        if (!confirm(`¿Estás seguro de que quieres marcar la reserva #${reservaId} como '${nuevoEstado}'?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('id', reservaId);
        formData.append('nuevo_estado', nuevoEstado);
        
        document.getElementById('contentArea').style.opacity = '0.5';

        fetch('../reservas/procesar_estado_reserva.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                cargarContenido('../reservas/gestionar_reservas.php');
            } else {
                alert('Error: ' + data.message);
                document.getElementById('contentArea').style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error de conexión.');
            document.getElementById('contentArea').style.opacity = '1';
        });
    }

    /**
     * Cancela una reserva usando AJAX.
     * Llama a procesar_cancelacion.php que ya tienes.
     */
    function cancelarReserva(event, reservaId) {
        event.preventDefault();
        if (!confirm(`¡ATENCIÓN! ¿Realmente deseas CANCELAR la reserva #${reservaId}? Esta acción es irreversible.`)) {
            return;
        }

        document.getElementById('contentArea').style.opacity = '0.5';

        fetch(`../reservas/procesar_cancelacion.php?id=${reservaId}`)
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.message); });
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                cargarContenido('../reservas/gestionar_reservas.php');
            } else {
                alert('Error: ' + data.message);
                document.getElementById('contentArea').style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Error al cancelar:', error);
            alert('Ocurrió un error: ' + error.message);
            document.getElementById('contentArea').style.opacity = '1';
        });
    }
</script>
