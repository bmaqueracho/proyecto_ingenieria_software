<?php
session_start();
require_once '../../conexion.php'; // Correcto: Sube dos niveles a la raíz

// --- VERIFICACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Recepcionista') {
    // Usamos un array para devolver un error JSON en caso de acceso no autorizado
    // ya que este script no debería ser accedido directamente por un usuario.
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$accion = $_POST['accion'] ?? null;

if (empty($accion)) {
    header("Location: ../dashboard/recepcionista_dashboard.php");
    exit();
}

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
} 
elseif ($accion === 'registrar') {
    // Lógica copiada de tu guardar_cliente.php
    $dni = trim($_POST['dni']);
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : 'Sin Telefono';
    $observacion = !empty($_POST['observacion']) ? trim($_POST['observacion']) : 'Sin Observación';
    
    // ... (Tus validaciones) ...
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
}
elseif ($accion === 'actualizar') {
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
header("Location: ../dashboard/recepcionista_dashboard.php?load_module=modulos/clientes/clientes_content");
exit();
?>