<?php
session_start();

if (!isset($_SESSION['cargo'])) {
    // No hay sesión iniciada
    header("Location: ../autch/login.php");
    exit();
}

// Verificar el rol según la página
if ($_SESSION['cargo'] !== 'Recepcionista') {
    // Si no es Recepcionista, redirige o bloquea
    echo "Acceso denegado.";
    exit();
}
$conexion = new mysqli("localhost", "root", "", "hotel_db");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$sql = "SELECT * FROM clientes ORDER BY apellidos ASC";
$resultado = $conexion->query($sql);

// Mostrar tabla
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>DNI</th><th>Nombres</th><th>Apellidos</th><th>Teléfono</th><th>Observación</th></tr>";

while ($fila = $resultado->fetch_assoc()) {
    echo "<tr>
        <td>{$fila['dni']}</td>
        <td>{$fila['nombres']}</td>
        <td>{$fila['apellidos']}</td>
        <td>{$fila['telefono']}</td>
        <td>{$fila['observacion']}</td>
    </tr>";
}

echo "</table>";

$conexion->close();
?>