<?php
session_start();
require_once '../../conexion.php'; // Esto crea la variable $conexion

// --- RESPUESTA POR DEFECTO ---
// Preparar un array para la respuesta JSON.
// Por defecto, asumimos que habrá un error.
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];
http_response_code(500); // Código de error de servidor por defecto

// --- VERIFICACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    http_response_code(403); // Forbidden
    $response['message'] = 'Acceso denegado.';
} 
// --- VALIDACIÓN DE DATOS ---
elseif (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    http_response_code(400); // Bad Request
    $response['message'] = 'ID de reserva no válido.';
} 
else {
    // --- LÓGICA DE NEGOCIO ---
    $reserva_id = (int)$_GET['id'];

    try {
        // Verificar que la reserva existe y está 'Confirmada'
        // *** CORRECCIÓN: Usar $conexion ***
        $stmt_check = $conexion->prepare("SELECT estado FROM reservas WHERE id = ?");
        $stmt_check->bind_param("i", $reserva_id);
        $stmt_check->execute();
        $reserva_actual = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$reserva_actual) {
            http_response_code(404); // Not Found
            $response['message'] = "Reserva con ID $reserva_id no encontrada.";
        } elseif ($reserva_actual['estado'] !== 'Confirmada' && $reserva_actual['estado'] !== 'Pendiente') {
            http_response_code(409); // Conflict
            $response['message'] = "Solo se pueden cancelar reservas 'Confirmadas' o 'Pendientes'.";
        } else {
            // Proceder con la cancelación
            // *** CORRECCIÓN: Usar $conexion ***
            $stmt = $conexion->prepare("UPDATE reservas SET estado = 'Cancelada' WHERE id = ?");
            $stmt->bind_param("i", $reserva_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                http_response_code(200); // OK
                $response = ['status' => 'success', 'message' => "Reserva ID $reserva_id cancelada exitosamente."];
                
                // Guardamos el mensaje de éxito en la sesión para mostrarlo al recargar la tabla
                $_SESSION['mensaje_exito_global'] = $response['message'];

            } else {
                $response['message'] = "La reserva ID $reserva_id no se pudo cancelar (puede que su estado haya cambiado).";
            }
            $stmt->close();
        }

    } catch (Exception $e) {
        // En caso de un error de base de datos no esperado
        $response['message'] = "Error del servidor: " . $e->getMessage();
    }
}

// --- RESPUESTA FINAL ---
// Cerramos la conexión y enviamos la respuesta JSON al cliente (JavaScript)
$conexion->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>