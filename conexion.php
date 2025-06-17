<?php
$host = "localhost";
$user = "root";
$password = ""; // Cambiar si tiene contraseña
$database = "hotel_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
