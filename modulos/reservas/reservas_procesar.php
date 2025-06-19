<?php
session_start();
require_once '../../conexion.php'; // Correcto: Sube dos niveles para llegar a la raíz.

// --- VERIFICACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['usuario_id']) || ($_SESSION['cargo'] !== 'Recepcionista' && $_SESSION['cargo'] !== 'Administrador')) {
    header("Location: ../auth/login.html");
    exit();
}

$accion_post = $_POST['accion'] ?? null;
$accion_get = $_GET['accion'] ?? null;

// --- PROCESAMIENTO DE ACCIONES GET (Desde links) ---
if (!empty($accion_get)) {
    if ($accion_get === 'limpiar_todo') {
        unset($_SESSION['reserva_dni_cliente'], $_SESSION['reserva_cliente_info'], $_SESSION['reserva_filtros'], $_SESSION['reserva_habitaciones_disponibles']);
    } elseif ($accion_get === 'limpiar_cliente') {
        unset($_SESSION['reserva_dni_cliente'], $_SESSION['reserva_cliente_info']);
    }
    // Después de limpiar, volvemos al formulario de nueva reserva
    header("Location: ../dashboard/recepcionista_dashboard.php?load_module=modulos/reservas/nueva_reserva");
    exit();
}

// --- PROCESAMIENTO DE ACCIONES POST (Desde formularios) ---
if (!empty($accion_post)) {
    // La URL por defecto para recargar es el formulario donde estábamos.
    $redirect_url = '../dashboard/recepcionista_dashboard.php?load_module=modulos/reservas/nueva_reserva';

    // He movido aquí TODA la lógica POST que tenías en nueva_reserva.php
    if ($accion_post === 'buscar_cliente_dni') {
        $dni_buscar = trim($_POST['dni_buscar']);
        $stmt = $conn->prepare("SELECT dni, nombres, apellidos FROM clientes WHERE dni = ?");
        $stmt->bind_param("s", $dni_buscar);
        $stmt->execute();
        $result_cliente = $stmt->get_result();
        if ($result_cliente->num_rows > 0) {
            $cliente_data = $result_cliente->fetch_assoc();
            $_SESSION['reserva_dni_cliente'] = $cliente_data['dni'];
            $_SESSION['reserva_cliente_info'] = $cliente_data;
            $_SESSION['mensaje_exito'] = "Cliente " . htmlspecialchars($cliente_data['nombres']) . " seleccionado.";
        } else {
            $_SESSION['mensaje_error'] = "Cliente con DNI " . htmlspecialchars($dni_buscar) . " no encontrado. Puede registrarlo.";
        }
        $stmt->close();
    }
    elseif ($accion_post === 'registrar_cliente_reserva') {
        $dni_reg = trim($_POST['dni_reg']);
        $nombres_reg = trim($_POST['nombres_reg']);
        // ... (toda tu lógica de validación y registro de cliente idéntica) ...
        // ...
        if ($stmt_insert->execute()) {
            $_SESSION['reserva_dni_cliente'] = $dni_reg;
            $_SESSION['reserva_cliente_info'] = ['dni' => $dni_reg, 'nombres' => $nombres_reg, 'apellidos' => $apellidos_reg];
            $_SESSION['mensaje_exito'] = "Cliente " . htmlspecialchars($nombres_reg) . " registrado y seleccionado.";
        } else {
            $_SESSION['mensaje_error'] = "Error al registrar cliente: " . $stmt_insert->error;
        }
    }
    elseif ($accion_post === 'buscar_habitaciones') {
        // ... (toda tu lógica de búsqueda de habitaciones idéntica) ...
        // ...
    }
    elseif ($accion_post === 'confirmar_reserva_final') {
        // ... (toda tu lógica de confirmación de reserva idéntica) ...
        // ...
        if ($stmt_insert_res->execute()) {
            $_SESSION['mensaje_exito_global'] = "¡Éxito! Reserva confirmada con ID: " . $stmt_insert_res->insert_id;
            unset($_SESSION['reserva_dni_cliente'], $_SESSION['reserva_cliente_info'], $_SESSION['reserva_filtros'], $_SESSION['reserva_habitaciones_disponibles']);
            // ¡CAMBIO CLAVE! Si la reserva tiene éxito, redirigimos a la tabla de gestión.
            $redirect_url = '../dashboard/recepcionista_dashboard.php?load_module=modulos/reservas/gestionar_reservas';
        } else {
            $_SESSION['mensaje_error'] = "Error al confirmar la reserva: " . $stmt_insert_res->error;
        }
        $stmt_insert_res->close();
    }

    $conn->close();
    header("Location: " . $redirect_url);
    exit();
}

// Si se accede a este archivo sin una acción, simplemente redirigir al dashboard.
header("Location: ../dashboard/recepcionista_dashboard.php");
exit();
?>