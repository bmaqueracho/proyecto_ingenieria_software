<?php
// Muestra todos los errores para facilitar la depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia la sesión
session_start();

// Ruta a la conexión (verificada)
require_once '../../conexion.php'; 

// Solo procesar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = trim($_POST['dni']);
    $clave = trim($_POST['clave']);
    $error_login = "";

    // Validaciones básicas
    if (empty($dni) || empty($clave)) {
        $error_login = "El DNI y la contraseña no pueden estar vacíos.";
    } elseif (strlen($dni) != 8 || !ctype_digit($dni)) {
        $error_login = "DNI inválido. Debe contener exactamente 8 dígitos.";
    } elseif (strlen($clave) < 6) {
        $error_login = "La contraseña debe tener al menos 6 caracteres.";
    }

    if (empty($error_login)) {
        // --- CORRECCIÓN: SE HA QUITADO LA COLUMNA 'genero' DE LA CONSULTA ---
        $stmt = $conexion->prepare("SELECT id, nombre, cargo, clave FROM usuarios WHERE dni = ? AND estado = 'Activo' LIMIT 1");
        
        if ($stmt === false) {
            die("Error al preparar la consulta: " . htmlspecialchars($conexion->error));
        }

        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();

            // Comparación de contraseña. Si no usas hashing, esta es la forma.
            if ($clave === $usuario['clave']) {
                session_regenerate_id(true);

                // Guardar datos en la sesión (sin 'genero')
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['cargo'] = $usuario['cargo'];
                // La línea $_SESSION['genero'] = $usuario['genero']; se ha eliminado.

                $stmt->close();
                $conexion->close();

                // Redirigir según el cargo
                if ($usuario['cargo'] === 'Administrador') {
                    header("Location: ../dashboard/admin_dashboard.php");
                } else {
                    header("Location: ../dashboard/recepcionista_dashboard.php");
                }
                exit;
            } else {
                $error_login = "Contraseña incorrecta.";
            }
        } else {
            $error_login = "Usuario no encontrado, inactivo o DNI incorrecto.";
        }
        $stmt->close();
    }
    
    $conexion->close();

    $_SESSION['login_error'] = $error_login;
    header("Location: login.html");
    exit();
} else {
    header("Location: login.html");
    exit();
}
?>