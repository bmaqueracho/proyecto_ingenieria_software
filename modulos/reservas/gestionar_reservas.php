<?php
session_start();
require_once '../../conexion.php'; // $conn

if (!isset($_SESSION['usuario_id']) || ($_SESSION['cargo'] !== 'Recepcionista' && $_SESSION['cargo'] !== 'Administrador')) {
    header("Location: ../autch/login.html");
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
        ORDER BY r.fecha_entrada DESC, r.id DESC"; // O r.id DESC para las más nuevas primero

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reservas[] = $row;
    }
}

$mensaje_error_gest = $_SESSION['mensaje_error'] ?? null;
$mensaje_exito_gest = $_SESSION['mensaje_exito'] ?? null;
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']); // Limpia mensajes generales

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestionar Reservas - Hotel</title>
  <!-- <link rel="stylesheet" href="estilos.css"> Futuro CSS -->
</head>
<body>
  <header>
    <h1>Gestionar Reservas Existentes</h1>
    <nav>
        <a href="../dashboard/recepcionista_dashboard.php">Volver al Dashboard de Reservas</a> |
        <a href="nueva_reserva.php?accion_limpiar=1">Crear Nueva Reserva</a>
    </nav>
  </header>
  <hr>
  <?php if ($mensaje_error_gest): ?><p style="color:red; font-weight:bold;"><?php echo $mensaje_error_gest; ?></p><?php endif; ?>
  <?php if ($mensaje_exito_gest): ?><p style="color:green; font-weight:bold;"><?php echo $mensaje_exito_gest; ?></p><?php endif; ?>
  <hr>
  <main>
    <?php if (!empty($reservas)): ?>
    <table border="1" style="width:100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Cliente (DNI)</th>
          <th>Habitación</th>
          <th>Entrada</th>
          <th>Salida</th>
          <th>Estancia (días)</th>
          <th>Monto</th>
          <th>Modo</th>
          <th>Pago</th>
          <th>Estado</th>
          <th>Hecha por</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservas as $reserva): ?>
        <?php
            $fecha_e = new DateTime($reserva['fecha_entrada']);
            $fecha_s = new DateTime($reserva['fecha_salida']);
            $estancia_dias = $fecha_s->diff($fecha_e)->days;
        ?>
        <tr>
          <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
          <td><?php echo htmlspecialchars($reserva['cliente_nombres'] . " " . $reserva['cliente_apellidos'] . " (" . $reserva['cliente_dni'] . ")"); ?></td>
          <td><?php echo htmlspecialchars($reserva['nombre_habitacion'] ?? 'N/A'); ?></td>
          <td><?php echo htmlspecialchars(date("d-m-Y H:i", strtotime($reserva['fecha_entrada']))); ?></td>
          <td><?php echo htmlspecialchars(date("d-m-Y H:i", strtotime($reserva['fecha_salida']))); ?></td>
          <td><?php echo $estancia_dias; ?></td>
          <td>S/ <?php echo htmlspecialchars(number_format($reserva['monto_total'], 2)); ?></td>
          <td><?php echo htmlspecialchars($reserva['modo_reserva']); ?></td>
          <td><?php echo htmlspecialchars($reserva['metodo_pago'] ?? 'Pendiente'); ?></td>
          <td style="font-weight:bold; color:<?php 
              // Lógica de color para el estado
              $color_estado = 'orange'; // Default color
              if ($reserva['estado_reserva'] === 'Confirmada') $color_estado = 'green';
              elseif ($reserva['estado_reserva'] === 'Cancelada') $color_estado = 'red';
              elseif ($reserva['estado_reserva'] === 'Completa') $color_estado = 'blue';
              echo $color_estado; 
          ?>;">
              <?php echo htmlspecialchars($reserva['estado_reserva']); ?>
          </td>
          <td><?php echo htmlspecialchars($reserva['recepcionista_nombre'] . " " . $reserva['recepcionista_apellido']); ?></td>
          <td>
            <?php if ($reserva['estado_reserva'] === 'Confirmada'): ?>
                <a href="procesar_cancelacion.php?id=<?php echo $reserva['reserva_id']; ?>" onclick="return confirm('¿Cancelar Reserva ID: <?php echo $reserva['reserva_id']; ?>?');">Cancelar</a> <br>
                <a href="procesar_estado_reserva.php?id=<?php echo $reserva['reserva_id']; ?>&nuevo_estado=Completa" onclick="return confirm('¿Marcar Reserva ID: <?php echo $reserva['reserva_id']; ?> como COMPLETA? Esto descontará productos de aseo.');">Marcar Completa</a>
                <br><a href="modificar_reserva.php?id=<?php echo $reserva['reserva_id']; ?>">Modificar</a>

            <?php elseif ($reserva['estado_reserva'] === 'Completa'): ?>
                (Reserva Completa)
            <?php elseif ($reserva['estado_reserva'] === 'Cancelada'): ?>
                (Reserva Cancelada)
            <?php else: ?>
                (Sin acciones definidas para estado: <?php echo htmlspecialchars($reserva['estado_reserva']); ?>)
            <?php endif; ?>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p>No hay reservas registradas.</p>
    <?php endif; ?>
  </main>
  <hr>
  <footer>
    <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>
</body>
</html>