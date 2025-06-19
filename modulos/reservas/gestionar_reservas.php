<?php
// ARCHIVO DE VISTA. Solo muestra la tabla.
session_start();
require_once '../../conexion.php'; // Correcto: Sube dos niveles.

if (!isset($_SESSION['usuario_id']) || ($_SESSION['cargo'] !== 'Recepcionista' && $_SESSION['cargo'] !== 'Administrador')) {
    // Si la sesión se pierde, no mostramos nada. El JS mostrará un error.
    exit();
}

$reservas = [];
$sql = "SELECT r.id AS reserva_id, r.fecha_reserva, r.fecha_entrada, r.fecha_salida,
               r.monto_total, r.estado AS estado_reserva, r.modo_reserva, r.metodo_pago,
               c.dni AS cliente_dni, c.nombres AS cliente_nombres, c.apellidos AS cliente_apellidos,
               h.nombre AS nombre_habitacion,
               u.nombre AS recepcionista_nombre, u.apellido AS recepcionista_apellido
        FROM reservas r
        LEFT JOIN clientes c ON r.cliente_dni = c.dni
        LEFT JOIN habitaciones h ON r.habitacion_id = h.id
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        ORDER BY r.fecha_entrada DESC, r.id DESC";

$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { $reservas[] = $row; } }

// Mensaje de éxito global (p.ej. después de crear una reserva)
$mensaje_exito_global = $_SESSION['mensaje_exito_global'] ?? null;
unset($_SESSION['mensaje_exito_global']); // Limpiar para que se muestre solo una vez
$conn->close();
?>

<!-- HTML de la tabla, sin <html>, <head>, etc. -->
<section id="tabla-gestion-reservas">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-tasks"></i> Gestionar Reservas Existentes</h2>
        <!-- Botón para ir a crear nueva reserva, usando la misma función JS -->
        <button class="btn btn-success" onclick="cargarContenido('../reservas/nueva_reserva.php', this)">
            <i class="fas fa-plus"></i> Crear Nueva Reserva
        </button>
    </div>

    <?php if ($mensaje_exito_global): ?><div class="alert alert-success"><?php echo $mensaje_exito_global; ?></div><?php endif; ?>
    
    <div class="table-responsive card action-card p-3">
        <?php if (!empty($reservas)): ?>
        <table class="table table-dark table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Habitación</th>
                    <th>Entrada/Salida</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $reserva): 
                    $fecha_e = new DateTime($reserva['fecha_entrada']);
                    $fecha_s = new DateTime($reserva['fecha_salida']);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                    <td><?php echo htmlspecialchars($reserva['cliente_nombres'] . " " . $reserva['cliente_apellidos']); ?></td>
                    <td><?php echo htmlspecialchars($reserva['nombre_habitacion'] ?? 'N/A'); ?></td>
                    <td><?php echo $fecha_e->format('d/m/y H:i'); ?> → <?php echo $fecha_s->format('d/m/y H:i'); ?></td>
                    <td>S/ <?php echo htmlspecialchars(number_format($reserva['monto_total'], 2)); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            $color_map = ['Confirmada' => 'success', 'Cancelada' => 'danger', 'Completa' => 'primary'];
                            echo $color_map[$reserva['estado_reserva']] ?? 'secondary';
                        ?>">
                            <?php echo htmlspecialchars($reserva['estado_reserva']); ?>
                        </span>
                    </td>
                    <td>
                        <!-- Estos links todavía recargarán la página. Se pueden adaptar después. -->
                        <?php if ($reserva['estado_reserva'] === 'Confirmada'): ?>
                            <a href="../reservas/procesar_cancelacion.php?id=<?php echo $reserva['reserva_id']; ?>" onclick="return confirm('¿Cancelar Reserva ID: <?php echo $reserva['reserva_id']; ?>?');" class="btn btn-sm btn-danger">X</a>
                            <a href="../reservas/modificar_reserva.php?id=<?php echo $reserva['reserva_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-center p-4">No hay reservas registradas.</p>
        <?php endif; ?>
    </div>
</section>