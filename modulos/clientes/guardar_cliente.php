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
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "hotel_db");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener los datos del formulario
$dni = trim($_POST['dni']);
$nombres = trim($_POST['nombres']);
$apellidos = trim($_POST['apellidos']);
$telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : 'Sin Telefono';
$observacion = !empty($_POST['observacion']) ? trim($_POST['observacion']) : 'Sin Observación';
$accion = $_POST['accion'] ?? 'registrar';

// Validaciones
$errores = [];

if (!preg_match('/^\d{8}$/', $dni)) {
    $errores[] = "El DNI debe tener exactamente 8 dígitos.";
}
if (strlen($nombres) < 2) {
    $errores[] = "El nombre es demasiado corto.";
}
if (strlen($apellidos) < 2) {
    $errores[] = "El apellido es demasiado corto.";
}

if (!empty($errores)) {
    foreach ($errores as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<p>Redireccionando en 3 segundos...</p>";
    header("Refresh: 3; URL=clientes.html");
    exit();
}

if ($accion === 'registrar') {
    // Verificar si ya existe un cliente con ese DNI
    $verificar = $conexion->prepare("SELECT dni FROM clientes WHERE dni = ?");
    $verificar->bind_param("s", $dni);
    $verificar->execute();
    $verificar->store_result();

    if ($verificar->num_rows > 0) {
        echo "<p style='color:red;'>El cliente con DNI $dni ya está registrado.</p>";
    } else {
        $stmt = $conexion->prepare("INSERT INTO clientes (dni, Nombres, Apellidos, Telefono, Observacion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $dni, $nombres, $apellidos, $telefono, $observacion);

        if ($stmt->execute()) {
            echo "<p style='color:green;'>Cliente registrado exitosamente.</p>";
        } else {
            echo "<p style='color:red;'>Error al registrar cliente: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    $verificar->close();

} elseif ($accion === 'actualizar') {
    $stmt = $conexion->prepare("UPDATE clientes SET Nombres = ?, Apellidos = ?, Telefono = ?, Observacion = ? WHERE dni = ?");
    $stmt->bind_param("sssss", $nombres, $apellidos, $telefono, $observacion, $dni);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Datos del cliente actualizados correctamente.</p>";
    } else {
        echo "<p style='color:red;'>Error al actualizar: " . $stmt->error . "</p>";
    }

    $stmt->close();
} else {
    echo "<p style='color:red;'>Acción no válida.</p>";
}

// Cierre de conexión
$conexion->close();

// Redireccionar después de 2 segundos
echo "<p>Redireccionando a ../otros/cliente.html...</p>";
header("Refresh: 2; URL=cliente.html");
exit();
?>