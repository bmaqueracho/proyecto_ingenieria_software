<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../conexion.php';
 // archivo de conexión a la BD

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = trim($_POST['dni']);
    $clave = trim($_POST['clave']);

    // Validaciones básicas
    if (strlen($dni) != 8 || !ctype_digit($dni)) {
        die("DNI inválido. Debe contener 8 dígitos.");
    }
    if (strlen($clave) < 6) {
        die("La contraseña debe tener al menos 6 caracteres.");
    }

    // Conexión y consulta
    $stmt = $conn->prepare("SELECT id, nombre, cargo, clave FROM usuarios WHERE dni = ? AND estado = 'Activo' LIMIT 1");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        // Comparación segura (usamos SHA-256)
        // Suponiendo que el campo clave contiene texto plano por ahora
            if ($clave === $usuario['clave']) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['cargo'] = $usuario['cargo'];

            if ($usuario['cargo'] === 'Administrador') {
                header("Location: ../dashboard/admin_dashboard.php");
            } else {
                header("Location: ../dashboard/recepcionista_dashboard.php");
            }
            exit;
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Usuario no encontrado o inactivo.";
    }

    $stmt->close();
    $conn->close();
}
?>