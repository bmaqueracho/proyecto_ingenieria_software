<?php
session_start();
require_once '../../conexion.php';

// Preparamos la respuesta JSON por defecto
$response = ['status' => 'error', 'message' => 'Petición inválida o error inesperado.'];
http_response_code(400); // Bad Request por defecto

// --- VERIFICACIÓN DE SEGURIDAD Y DATOS ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido. Se esperaba POST.';

} elseif (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    http_response_code(403); // Forbidden
    $response['message'] = 'Acceso denegado.';

} elseif (!isset($_POST['id']) || !ctype_digit((string)$_POST['id']) || !isset($_POST['nuevo_estado'])) {
    $response['message'] = 'Faltan parámetros requeridos (ID de reserva o nuevo estado).';

} else {
    $reserva_id = (int)$_POST['id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    // Simplificamos los estados válidos según tu petición
    $estados_validos = ['Completa']; // Solo permitimos esta acción desde este script

    if (!in_array($nuevo_estado, $estados_validos)) {
        $response['message'] = 'La acción para el estado "' . htmlspecialchars($nuevo_estado) . '" no es válida aquí.';
    } else {
        // --- LÓGICA DE NEGOCIO ---
        try {
            // 1. Obtener el estado actual de la reserva
            $stmt_check = $conexion->prepare("SELECT estado FROM reservas WHERE id = ?");
            $stmt_check->bind_param("i", $reserva_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows === 0) {
                http_response_code(404); // Not Found
                $response['message'] = "Reserva con ID $reserva_id no encontrada.";
            } else {
                $reserva_actual = $result_check->fetch_assoc();
                $estado_actual = $reserva_actual['estado'];
                $permitido = false;
                $error_msg = '';

                // 2. Aplicar la regla de negocio para 'Completa'
                switch ($nuevo_estado) {
                    case 'Completa':
                        // === ¡AQUÍ ESTÁ LA CORRECCIÓN! ===
                        // Antes se requería 'Check-In'. Ahora permitimos completar desde 'Confirmada'.
                        if (in_array($estado_actual, ['Confirmada'])) {
                            $permitido = true;
                        } else {
                            $error_msg = "Solo se puede completar una reserva que esté 'Confirmada'.";
                        }
                        break;
                }

                // 3. Si la regla se cumple, actualizar la base de datos
                if ($permitido) {
                    $stmt_update = $conexion->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
                    $stmt_update->bind_param("si", $nuevo_estado, $reserva_id);

                    if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
                        http_response_code(200); // OK
                        $success_message = "Reserva ID $reserva_id marcada como 'Completa' correctamente.";
                        $response = ['status' => 'success', 'message' => $success_message];
                        $_SESSION['mensaje_exito_global'] = $success_message;
                    } else {
                        $response['message'] = "Error al ejecutar la actualización en la base de datos.";
                    }
                    $stmt_update->close();
                } else {
                    http_response_code(409); // Conflict (la acción no es permitida)
                    $response['message'] = $error_msg;
                }
            }
            $stmt_check->close();

        } catch (Exception $e) {
            http_response_code(500);
            $response['message'] = "Error del servidor: " . $e->getMessage();
        }
    }
}

// --- RESPUESTA FINAL ---
$conexion->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>