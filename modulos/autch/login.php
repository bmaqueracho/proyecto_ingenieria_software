<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = trim($_POST['dni']);
    $clave = trim($_POST['clave']);
    $error_login = "";

    // Validaciones básicas (se mantienen)
    if (empty($dni) || empty($clave)) {
        $error_login = "El DNI y la contraseña no pueden estar vacíos.";
    }

    if (empty($error_login)) {
        // --- CAMBIO 1: PEDIMOS MÁS DATOS DEL USUARIO ---
        $stmt = $conexion->prepare("SELECT id, nombre, apellido, cargo, clave FROM usuarios WHERE dni = ? AND estado = 'Activo' LIMIT 1");
        
        if ($stmt === false) { die("Error al preparar la consulta: " . htmlspecialchars($conexion->error)); }

        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();

            // --- CAMBIO 2: LÓGICA DE VERIFICACIÓN DE CONTRASEÑA MODERNA ---
            // Primero intentamos con el método seguro password_verify()
            if (password_verify($clave, $usuario['clave'])) {
                // Si la verificación es exitosa, es una contraseña hasheada y segura.
                $esClaveCorrecta = true;
            } else {
                // Si falla, como plan B, comprobamos si es una contraseña antigua en texto plano.
                // Esto es solo para mantener la compatibilidad con tus usuarios existentes.
                $esClaveCorrecta = ($clave === $usuario['clave']);
            }

            if ($esClaveCorrecta) {
                session_regenerate_id(true); // Previene ataques de fijación de sesión

                // Guardar datos en la sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['apellido'] = $usuario['apellido']; // Útil para mostrar nombre completo
                $_SESSION['cargo'] = $usuario['cargo'];

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