<?php
session_start();
require_once '../../conexion.php';

// Verificación de seguridad (solo administradores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') {
    http_response_code(403);
    exit('Acceso denegado.');
}

$accion = $_POST['accion'] ?? null;
$redirect_url = '../dashboard/admin_dashboard.php?load_module=modulos/habitaciones/habitaciones_content';

try {
    switch ($accion) {
        // --- ACCIÓN: CREAR TIPO DE HABITACIÓN ---
        case 'crear_tipo':
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            if (empty($nombre)) { throw new Exception("El nombre del tipo es obligatorio."); }
            $stmt = $conexion->prepare("INSERT INTO tipo_habitacion (nombre, descripcion) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $descripcion);
            if (!$stmt->execute()) { throw new Exception("Error al crear tipo: " . $stmt->error); }
            $stmt->close();
            $_SESSION['mensaje_habitacion'] = ['tipo' => 'exito', 'texto' => "Tipo '$nombre' creado."];
            break;
        
        // --- ACCIÓN: ACTUALIZAR TIPO DE HABITACIÓN ---
        case 'actualizar_tipo':
            $tipo_id = (int)$_POST['tipo_id'];
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            if ($tipo_id <= 0 || empty($nombre)) { throw new Exception("Datos inválidos."); }
            $stmt = $conexion->prepare("UPDATE tipo_habitacion SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $descripcion, $tipo_id);
            if (!$stmt->execute()) { throw new Exception("Error al actualizar tipo: " . $stmt->error); }
            $stmt->close();
            $_SESSION['mensaje_habitacion'] = ['tipo' => 'exito', 'texto' => "Tipo de habitación actualizado."];
            break;

        // ==========================================================
        // ============== ¡LÓGICA RESTAURADA Y COMPLETA! ==============
        // ==========================================================
        case 'crear_habitacion':
            $nombre = trim($_POST['nombre']);
            $tipo_id = (int)$_POST['tipo_id'];
            $capacidad = (int)$_POST['capacidad'];
            $precio = (float)$_POST['precio'];
            $estado = $_POST['estado'];

            if (empty($nombre) || $tipo_id <= 0 || $capacidad <= 0 || $precio <= 0) {
                throw new Exception("Todos los campos para crear la habitación son obligatorios.");
            }
            if (!in_array($estado, ['Disponible', 'Mantenimiento'])) {
                throw new Exception("Estado inicial no válido.");
            }

            $stmt = $conexion->prepare("INSERT INTO habitaciones (nombre, tipo_id, capacidad, precio, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siids", $nombre, $tipo_id, $capacidad, $precio, $estado);
            if (!$stmt->execute()) { throw new Exception("Error al crear la habitación: " . $stmt->error); }
            $stmt->close();
            $_SESSION['mensaje_habitacion'] = ['tipo' => 'exito', 'texto' => "Habitación '$nombre' creada exitosamente."];
            break;

        // --- ACCIÓN: ACTUALIZAR HABITACIÓN ---
        case 'actualizar_habitacion':
            $habitacion_id = (int)$_POST['habitacion_id'];
            $nombre = trim($_POST['nombre']);
            $tipo_id = (int)$_POST['tipo_id'];
            $capacidad = (int)$_POST['capacidad'];
            $precio = (float)$_POST['precio'];
            if ($habitacion_id <= 0 || empty($nombre) || $tipo_id <= 0 || $capacidad <= 0 || $precio <= 0) { throw new Exception("Datos inválidos."); }
            $stmt = $conexion->prepare("UPDATE habitaciones SET nombre = ?, tipo_id = ?, capacidad = ?, precio = ? WHERE id = ?");
            $stmt->bind_param("siidi", $nombre, $tipo_id, $capacidad, $precio, $habitacion_id);
            if (!$stmt->execute()) { throw new Exception("Error al actualizar habitación: " . $stmt->error); }
            $stmt->close();
            $_SESSION['mensaje_habitacion'] = ['tipo' => 'exito', 'texto' => "Habitación '$nombre' actualizada."];
            break;
            
        // --- ACCIÓN: CAMBIAR ESTADO DE HABITACIÓN ---
        case 'cambiar_estado_habitacion':
            $habitacion_id = (int)$_POST['habitacion_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            if ($habitacion_id <= 0 || !in_array($nuevo_estado, ['Disponible', 'Mantenimiento'])) { throw new Exception("Datos inválidos."); }
            $stmt = $conexion->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_estado, $habitacion_id);
            if (!$stmt->execute()) { throw new Exception("Error al cambiar estado: " . $stmt->error); }
            $stmt->close();
            $_SESSION['mensaje_habitacion'] = ['tipo' => 'exito', 'texto' => "Estado de la habitación actualizado."];
            break;

        default:
            throw new Exception("Acción no reconocida.");
            break;
    }
} catch (Exception $e) {
    $_SESSION['mensaje_habitacion'] = ['tipo' => 'error', 'texto' => $e->getMessage()];
}

$conexion->close();
header("Location: " . $redirect_url);
exit();
?>