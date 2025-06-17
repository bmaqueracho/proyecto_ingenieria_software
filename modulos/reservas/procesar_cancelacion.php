<?php
session_start();
require_once '../../conexion.php'; // $conn

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || ($_SESSION['cargo'] !== 'Recepcionista' && $_SESSION['cargo'] !== 'Administrador')) {
    $_SESSION['mensaje_error'] = "Acceso denegado.";
    header("Location: ../autch/login.html");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de reserva no válido.";
    header("Location: gestionar_reservas.php");
    exit();
}

$reserva_id = (int)$_GET['id'];

// Verificar que la reserva existe y está 'Confirmada' antes de cancelar
$stmt_check = $conn->prepare("SELECT estado FROM reservas WHERE id = ?");
$stmt_check->bind_param("i", $reserva_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $_SESSION['mensaje_error'] = "Reserva con ID $reserva_id no encontrada.";
    $stmt_check->close();
    header("Location: gestionar_reservas.php");
    exit();
}

$reserva_actual = $result_check->fetch_assoc();
$stmt_check->close();

if ($reserva_actual['estado'] !== 'Confirmada') {
    $_SESSION['mensaje_error'] = "Solo se pueden cancelar reservas que estén 'Confirmadas'. Esta reserva está '{$reserva_actual['estado']}'.";
    header("Location: gestionar_reservas.php");
    exit();
}

// Proceder con la cancelación
$stmt = $conn->prepare("UPDATE reservas SET estado = 'Cancelada' WHERE id = ? AND estado = 'Confirmada'");
$stmt->bind_param("i", $reserva_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['mensaje_exito'] = "Reserva ID $reserva_id cancelada exitosamente.";
        // Aquí NO actualizamos habitaciones.estado, según tu último requerimiento.
        // El trigger `actualizar_estado_habitacion_v2` (si lo creaste y mantuviste) podría manejar
        // el cambio de estado de la habitación a 'Disponible' si estaba en 'Mantenimiento' por la reserva.
        // Pero como dijiste que no usaríamos triggers para esto, la habitación simplemente
        // vuelve a estar disponible para ser reservada en ese rango de fechas.
    } else {
        $_SESSION['mensaje_info'] = "La reserva ID $reserva_id no se pudo cancelar (quizás ya estaba cancelada o su estado cambió).";
    }
} else {
    $_SESSION['mensaje_error'] = "Error al cancelar la reserva ID $reserva_id: " . $stmt->error;
}

$stmt->close();
$conn->close();
header("Location: gestionar_reservas.php");
exit();
?>