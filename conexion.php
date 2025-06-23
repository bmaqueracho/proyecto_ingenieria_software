<?php
// conexion.php

// --- URL BASE DEL PROYECTO ---
// Define la ruta base para construir URLs absolutas en todo el sistema.
// Esto hace que los enlaces y formularios sean robustos y no se rompan.
// El formato es: http://tudominio/tu_carpeta
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/HOTELBASIC');

// --- PARÁMETROS DE CONEXIÓN ---
$servidor = "localhost";
$usuario_db = "root";
$contrasena_db = "";
$nombre_db = "hotel_db";

// --- CREAR LA CONEXIÓN ---
$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

// --- VERIFICAR LA CONEXIÓN ---
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// --- CONFIGURAR EL JUEGO DE CARACTERES ---
if (!$conexion->set_charset("utf8")) {
    // printf("Error cargando el conjunto de caracteres utf8: %s\n", $conexion->error);
}

// ¡Listo!
?>