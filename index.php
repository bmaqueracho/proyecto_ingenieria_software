<?php
session_start();

// Define la ruta base manualmente.
// Asegúrate de que no tenga una barra al final.
define('RUTA_PROYECTO', '/HOTELBASIC');

if (isset($_SESSION['usuario_id']) && isset($_SESSION['cargo'])) {
    $cargo = $_SESSION['cargo'];

    if ($cargo === 'Administrador') {
        header("Location: " . RUTA_PROYECTO . "/modulos/dashboard/admin_dashboard.php");
        exit();
    } elseif ($cargo === 'Recepcionista') {
        header("Location: " . RUTA_PROYECTO . "/modulos/dashboard/recepcionista_dashboard.php");
        exit();
    } else {
        session_unset();
        session_destroy();
        header("Location: " . RUTA_PROYECTO . "/modulos/auth/login.html");
        exit();
    }
} else {
    header("Location: " . RUTA_PROYECTO . "/modulos/autch/login.html");
    exit();
}
?>