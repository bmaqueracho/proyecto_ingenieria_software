<?php
// conexion.php

// --- PARÁMETROS DE CONEXIÓN ---
$servidor = "localhost";
$usuario_db = "root";
$contrasena_db = "";
$nombre_db = "hotel_db"; // Asumo que este es el nombre de tu base deatos por tus otros archivos

// --- CREAR LA CONEXIÓN ---
// Se crea la variable $conexion que estará disponible en todos los archivos que incluyan este.
$conexion = new mysqli($servidor, $usuario_db, $contrasena_db, $nombre_db);

// --- VERIFICAR LA CONEXIÓN ---
if ($conexion->connect_error) {
    // Si la conexión falla, el script se detiene y muestra un error claro.
    // Esto es crucial para la depuración y evita errores crípticos más adelante.
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// --- CONFIGURAR EL JUEGO DE CARACTERES ---
// Esto asegura que los acentos y caracteres especiales (ñ) se guarden y muestren correctamente.
if (!$conexion->set_charset("utf8")) {
    // Si falla, se puede seguir, pero es bueno saberlo.
    // printf("Error cargando el conjunto de caracteres utf8: %s\n", $conexion->error);
}

// ¡Listo! El archivo termina aquí. 
// No necesita una etiqueta de cierre de PHP (?>