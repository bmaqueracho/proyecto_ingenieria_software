<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['cargo'])) {
    header("Location: ../auth/login.html");
    exit();
}

// --- CAMBIO 1: PERMITIR ACCESO A AMBOS ROLES ---
if (!in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    echo "Acceso denegado. No tiene los permisos necesarios.";
    exit();
}

// --- CAMBIO 2: DETERMINAR LA URL DE REDIRECCIÓN DINÁMICAMENTE ---
$dashboard_url = '';
if ($_SESSION['cargo'] === 'Administrador') {
    $dashboard_url = '../dashboard/admin_dashboard.php';
} else {
    $dashboard_url = '../dashboard/recepcionista_dashboard.php';
}

// Se mantiene la conexión y lógica original del archivo
$conexion = new mysqli("localhost", "root", "", "hotel_db");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener los datos del formulario (se mantiene igual)
$dni = trim($_POST['dni']);
$nombres = trim($_POST['nombres']);
$apellidos = trim($_POST['apellidos']);
$telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : 'Sin Telefono';
$observacion = !empty($_POST['observacion']) ? trim($_POST['observacion']) : 'Sin Observación';
$accion = $_POST['accion'] ?? 'registrar';

// Validaciones (se mantienen igual)
$errores = [];
if (!preg_match('/^\d{8,10}$/', $dni)) { // Ajustado a 8-10 dígitos por consistencia
    $errores[] = "El DNI debe tener entre 8 y 10 dígitos.";
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
    // --- CAMBIO 3: REDIRIGIR AL DASHBOARD CORRECTO EN CASO DE ERROR ---
    echo "<p>Redireccionando en 3 segundos...</p>";
    header("Refresh: 3; URL=" . $dashboard_url);
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
echo "<p>Operación completada. Redireccionando al dashboard...</p>";
header("Refresh: 2; URL=" . $dashboard_url);
exit();
?>