<?php
session_start();
require_once '../../conexion.php'; // Incluir la conexión a la base de datos

/**
 * Este archivo procesa todas las acciones del módulo de gestión de usuarios.
 * Solo es accesible para el rol de 'Administrador'.
 * Acciones soportadas:
 * - crear_usuario: Registra un nuevo usuario en la base de datos.
 * - actualizar_usuario: Modifica los datos de un usuario existente.
 * - cambiar_estado: Cambia el estado de un usuario entre 'Activo' e 'Inactivo'.
 */

// --- VERIFICACIÓN DE SEGURIDAD ---
// Asegurarse de que el usuario ha iniciado sesión y es un Administrador.
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') {
    http_response_code(403); // Código de respuesta "Forbidden"
    // Es mejor no mostrar un mensaje HTML, ya que este script no debería ser accedido directamente.
    exit('Acceso denegado.'); 
}

// Obtener la acción del formulario POST. Si no hay acción, no hay nada que hacer.
$accion = $_POST['accion'] ?? null;
if (!$accion) {
    header("Location: ../../index.php"); // Redirigir a la página principal si se accede sin acción
    exit();
}

// URL a la que se redirigirá al finalizar, para mostrar los resultados y mensajes.
$redirect_url = '../dashboard/admin_dashboard.php?load_module=modulos/usuarios/usuarios_content';

// Usamos un bloque try-catch para manejar errores de forma centralizada.
try {
    // Usar un 'switch' es una forma limpia de manejar múltiples acciones.
    switch ($accion) {
        
        // --- CASO 1: CREAR UN NUEVO USUARIO ---
        case 'crear_usuario':
            $dni = trim($_POST['dni']);
            $nombre = trim($_POST['nombre']);
            $apellido = trim($_POST['apellido']);
            $clave = $_POST['clave']; // Contraseña en texto plano del formulario
            $cargo = $_POST['cargo'];
            $salario = (float)$_POST['salario'];

            // Validaciones básicas
            if (empty($dni) || empty($nombre) || empty($apellido) || empty($clave) || empty($cargo)) {
                throw new Exception("Todos los campos principales son obligatorios.");
            }
            if (strlen($clave) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres.");
            }
            
            // Verificar que el DNI no esté ya registrado para evitar duplicados.
            $stmt_check = $conexion->prepare("SELECT id FROM usuarios WHERE dni = ?");
            $stmt_check->bind_param("s", $dni);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("El DNI '$dni' ya está registrado en el sistema.");
            }
            $stmt_check->close();

            // NUNCA guardar contraseñas en texto plano. Usamos password_hash.
            $clave_hashed = password_hash($clave, PASSWORD_BCRYPT);

            $stmt = $conexion->prepare("INSERT INTO usuarios (dni, nombre, apellido, clave, cargo, salario, estado) VALUES (?, ?, ?, ?, ?, ?, 'Activo')");
            $stmt->bind_param("sssssd", $dni, $nombre, $apellido, $clave_hashed, $cargo, $salario);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear el usuario: " . $stmt->error);
            }
            $stmt->close();
            $_SESSION['mensaje_usuario'] = ['tipo' => 'exito', 'texto' => "Usuario '$nombre $apellido' creado exitosamente."];
            break;

        // --- CASO 2: ACTUALIZAR UN USUARIO EXISTENTE ---
        case 'actualizar_usuario':
            $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
            $nombre = trim($_POST['nombre']);
            $apellido = trim($_POST['apellido']);
            $cargo = $_POST['cargo'];
            $salario = (float)$_POST['salario'];
            $clave_nueva = $_POST['clave']; // La nueva contraseña (puede estar vacía)

            if (!$usuario_id || empty($nombre) || empty($apellido) || empty($cargo)) {
                throw new Exception("Faltan datos obligatorios para la actualización.");
            }
            
            $clave_hashed = null;
            // Solo actualizamos la contraseña si el usuario escribió una nueva.
            if (!empty($clave_nueva)) {
                if (strlen($clave_nueva) < 6) {
                    throw new Exception("La nueva contraseña debe tener al menos 6 caracteres.");
                }
                $clave_hashed = password_hash($clave_nueva, PASSWORD_BCRYPT);
            }

            // La consulta SQL cambia dependiendo de si se actualiza la contraseña o no.
            if ($clave_hashed !== null) {
                // Consulta para actualizar todos los campos, INCLUIDA la contraseña.
                $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, cargo = ?, salario = ?, clave = ? WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssdsi", $nombre, $apellido, $cargo, $salario, $clave_hashed, $usuario_id);
            } else {
                // Consulta para actualizar los campos EXCEPTO la contraseña.
                $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, cargo = ?, salario = ? WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssdi", $nombre, $apellido, $cargo, $salario, $usuario_id);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el usuario: " . $stmt->error);
            }
            $stmt->close();
            $_SESSION['mensaje_usuario'] = ['tipo' => 'exito', 'texto' => "Usuario '$nombre $apellido' (ID: $usuario_id) actualizado correctamente."];
            break;

        // --- CASO 3: CAMBIAR EL ESTADO (ACTIVO/INACTIVO) ---
        case 'cambiar_estado':
            $usuario_id = (int)$_POST['usuario_id'];
            $nuevo_estado = $_POST['nuevo_estado'];

            // Regla de negocio para impedir que un administrador se inactive a sí mismo.
            if ($usuario_id === $_SESSION['usuario_id']) {
                throw new Exception("Operación no permitida: No puede cambiar su propio estado de actividad.");
            }

            // Validar que el nuevo estado sea uno de los permitidos.
            if (!in_array($nuevo_estado, ['Activo', 'Inactivo'])) {
                throw new Exception("El estado proporcionado no es válido.");
            }

            $stmt = $conexion->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_estado, $usuario_id);

            if (!$stmt->execute()) {
                throw new Exception("Error al cambiar el estado del usuario.");
            }
            $stmt->close();
            $_SESSION['mensaje_usuario'] = ['tipo' => 'exito', 'texto' => "El estado del usuario ID $usuario_id ha sido cambiado a '$nuevo_estado'."];
            break;

        // --- CASO POR DEFECTO ---
        default:
            throw new Exception("La acción solicitada no es reconocida.");
            break;
    }
} catch (Exception $e) {
    // Si en cualquier punto del 'try' se lanza una excepción, se captura aquí.
    // Guardamos el mensaje de error en la sesión para mostrarlo en la página.
    $_SESSION['mensaje_usuario'] = ['tipo' => 'error', 'texto' => $e->getMessage()];
}

// Cerramos la conexión a la base de datos y redirigimos al usuario.
$conexion->close();
header("Location: " . $redirect_url);
exit();
?>