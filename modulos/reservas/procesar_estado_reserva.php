<?php
session_start();
require_once '../../conexion.php'; // $conn

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    $_SESSION['mensaje_error'] = "Acceso denegado.";
    header("Location: ../autch/login.html");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id']) || !isset($_GET['nuevo_estado'])) {
    $_SESSION['mensaje_error'] = "Datos no válidos para cambiar estado de reserva.";
    header("Location: gestionar_reservas.php");
    exit();
}

$reserva_id = (int)$_GET['id'];
$nuevo_estado = $_GET['nuevo_estado'];
// Solo permitimos cambiar a 'Completa' o 'Confirmada' (si necesitaras revertir, por ejemplo)
$estados_permitidos_para_cambio = ['Completa', 'Confirmada']; 

if (!in_array($nuevo_estado, $estados_permitidos_para_cambio)) {
    $_SESSION['mensaje_error'] = "Cambio de estado no permitido a: " . htmlspecialchars($nuevo_estado);
    header("Location: gestionar_reservas.php");
    exit();
}

// Opcional: Verificar el estado actual antes de cambiar
$stmt_check = $conn->prepare("SELECT estado FROM reservas WHERE id = ?");
$stmt_check->bind_param("i", $reserva_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    $_SESSION['mensaje_error'] = "Reserva no encontrada.";
    $stmt_check->close();
    header("Location: gestionar_reservas.php");
    exit();
}
$reserva_actual = $result_check->fetch_assoc();
$stmt_check->close();

if ($reserva_actual['estado'] === $nuevo_estado) {
    $_SESSION['mensaje_info'] = "La reserva ya estaba en estado '" . htmlspecialchars($nuevo_estado) . "'.";
    header("Location: gestionar_reservas.php");
    exit();
}

// Lógica adicional para evitar cambios no deseados (ej. de Cancelada a Completa)
if ($nuevo_estado === 'Completa' && $reserva_actual['estado'] !== 'Confirmada') {
     $_SESSION['mensaje_error'] = "Solo se puede marcar como 'Completa' una reserva que esté 'Confirmada'. Estado actual: {$reserva_actual['estado']}.";
     header("Location: gestionar_reservas.php");
     exit();
}
// Podrías añadir más reglas si es necesario


$stmt = $conn->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
$stmt->bind_param("si", $nuevo_estado, $reserva_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['mensaje_exito'] = "Reserva ID $reserva_id actualizada a estado '$nuevo_estado'.";
        // El trigger trg_consumo_aseo_reserva_completa se encargará del descuento si $nuevo_estado es 'Completa'
    } else {
        $_SESSION['mensaje_info'] = "No se realizaron cambios en la reserva ID $reserva_id (quizás ya estaba en ese estado o no cumplió condiciones).";
    }
} else {
    $_SESSION['mensaje_error'] = "Error al actualizar estado de la reserva ID $reserva_id: " . $stmt->error;
}

$stmt->close();
$conn->close();
header("Location: gestionar_reservas.php");
exit();
?>