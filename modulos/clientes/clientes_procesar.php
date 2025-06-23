<?php
session_start();
require_once '../../conexion.php'; // Correcto: Sube dos niveles a la raíz

// --- VERIFICACIÓN DE SEGURIDAD --
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. No tiene los permisos necesarios.']);
    exit();
}
$accion = $_POST['accion'] ?? null;

if (empty($accion)) {
    // Si no hay acción, redirigir al index principal
    header("Location: ../../index.php");
    exit();
}
$redirect_url = $_POST['redirect_url'] ?? '../../index.php';
// --- LÓGICA DE PROCESAMIENTO POST ---

if ($accion === 'buscar') {
    $dni = $_POST['dni'] ?? '';
    if (!empty($dni)) {
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE dni = ?");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows > 0) {
            $_SESSION['cliente_encontrado'] = $resultado->fetch_assoc();
            $_SESSION['mensaje_cliente'] = ['tipo' => 'info', 'texto' => 'Cliente encontrado. Puede actualizar sus datos.'];
        } else {
            $_SESSION['mensaje_cliente'] = ['tipo' => 'error', 'texto' => 'No se encontró ningún cliente con ese DNI.'];
        }
        $stmt->close();
    } else {
        $_SESSION['mensaje_cliente'] = ['tipo' => 'error', 'texto' => 'Debe proporcionar un DNI para buscar.'];
    }
} elseif ($accion === 'registrar') {
    $dni = trim($_POST['dni']);
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : 'Sin Telefono';
    $observacion = !empty($_POST['observacion']) ? trim($_POST['observacion']) : 'Sin Observación';

    // Validaciones
    $errores = [];

    if (!preg_match('/^\d{6,15}$/', $dni)) {
        $errores[] = "El DNI debe contener solo números (entre 6 y 15 dígitos).";
    }

    if (!preg_match('/^[\p{L} ]+$/u', $nombres)) {
        $errores[] = "El nombre solo debe contener letras y espacios.";
    }

    if (!preg_match('/^[\p{L} ]+$/u', $apellidos)) {
        $errores[] = "El apellido solo debe contener letras y espacios.";
    }

    if (!empty($_POST['telefono']) && !preg_match('/^\d{6,15}$/', $telefono)) {
        $errores[] = "El teléfono debe contener solo números (entre 6 y 15 dígitos).";
    }

    // Si hay errores, redirigir con mensaje
    if (!empty($errores)) {
        $_SESSION['mensaje_cliente'] = [
            'tipo' => 'error',
            'texto' => implode('<br>', $errores)
        ];
        header("Location: $redirect_url");
        exit();
    }

    // Verificar duplicidad de DNI
    $verificar = $conexion->prepare("SELECT dni FROM clientes WHERE dni = ?");
    $verificar->bind_param("s", $dni);
    $verificar->execute();

    if ($verificar->get_result()->num_rows > 0) {
        $_SESSION['mensaje_cliente'] = ['tipo' => 'error', 'texto' => "El cliente con DNI $dni ya está registrado."];
    } else {
        $stmt = $conexion->prepare("INSERT INTO clientes (dni, Nombres, Apellidos, Telefono, Observacion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $dni, $nombres, $apellidos, $telefono, $observacion);

        if ($stmt->execute()) {
            $_SESSION['mensaje_cliente'] = ['tipo' => 'exito', 'texto' => 'Cliente registrado exitosamente.'];
        } else {
            $_SESSION['mensaje_cliente'] = ['tipo' => 'error', 'texto' => 'Error al registrar cliente: ' . $stmt->error];
        }

        $stmt->close();
    }
    $verificar->close();
} elseif ($accion === 'actualizar') {
    // Lógica copiada de tu guardar_cliente.php
    $dni = trim($_POST['dni']);
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : 'Sin Telefono';
    $observacion = !empty($_POST['observacion']) ? trim($_POST['observacion']) : 'Sin Observación';

    $stmt = $conexion->prepare("UPDATE clientes SET Nombres = ?, Apellidos = ?, Telefono = ?, Observacion = ? WHERE dni = ?");
    $stmt->bind_param("sssss", $nombres, $apellidos, $telefono, $observacion, $dni);
    if ($stmt->execute()) {
        $_SESSION['mensaje_cliente'] = ['tipo' => 'exito', 'texto' => 'Datos del cliente actualizados correctamente.'];
    } else {
        $_SESSION['mensaje_cliente'] = ['tipo' => 'error', 'texto' => 'Error al actualizar: ' . $stmt->error];
    }
    $stmt->close();
}

$conexion->close();

// Al final, siempre redirigimos de vuelta al dashboard para que cargue el módulo de clientes.
header("Location: " . $redirect_url);
exit();
