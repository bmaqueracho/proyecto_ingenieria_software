<?php
// ARCHIVO DE VISTA. La lógica POST ha sido movida a reservas_procesar.php
session_start();
require_once '../../conexion.php'; // Correcto: Sube dos niveles.

// Lógica para OBTENER datos para mostrar en la vista
$cliente_info = $_SESSION['reserva_cliente_info'] ?? null;
$filtros_guardados = $_SESSION['reserva_filtros'] ?? [];
$habitaciones_disponibles = $_SESSION['reserva_habitaciones_disponibles'] ?? [];
$tipos_habitacion = [];
$result_tipos = $conn->query("SELECT id, nombre FROM tipo_habitacion ORDER BY nombre");
if ($result_tipos) { while ($tipo = $result_tipos->fetch_assoc()) { $tipos_habitacion[] = $tipo; } }
$etapa = ($cliente_info) ? 'datos_reserva' : 'buscar_cliente';

// Mostrar y luego limpiar mensajes de la sesión para que solo se vean una vez.
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_info = $_SESSION['mensaje_info'] ?? null;
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito'], $_SESSION['mensaje_info']);
$conn->close();
?>

<!-- Esto es solo el HTML, sin <html>, <head> o <body> -->
<section id="proceso-nueva-reserva">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0"><i class="fas fa-plus-circle"></i> Crear Nueva Reserva</h2>
        <!-- Este link ahora apunta al procesador -->
        <a href="reservas_procesar.php?accion=limpiar_todo" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-trash"></i> Limpiar y Empezar de Nuevo
        </a>
    </header>

    <?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo $mensaje_error; ?></div><?php endif; ?>
    <?php if ($mensaje_exito): ?><div class="alert alert-success"><?php echo $mensaje_exito; ?></div><?php endif; ?>
    <?php if ($mensaje_info): ?><div class="alert alert-info"><?php echo $mensaje_info; ?></div><?php endif; ?>

    <!-- PASO 1: CLIENTE (El HTML que ya tenías, pero con las acciones apuntando al procesador) -->
    <div class="card action-card p-4 mb-4">
        <h4 class="mb-3">Paso 1: Cliente</h4>
        <?php if ($cliente_info): ?>
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0"><b>Cliente:</b> <?php echo htmlspecialchars($cliente_info['nombres'] . " " . $cliente_info['apellidos']); ?> (DNI: <?php echo htmlspecialchars($cliente_info['dni']); ?>)</p>
                <a href="reservas_procesar.php?accion=limpiar_cliente" class="btn btn-sm btn-outline-warning">Cambiar Cliente</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6 border-end">
                    <form action="reservas_procesar.php" method="POST">
                        <h5>Buscar Cliente</h5>
                        <input type="hidden" name="accion" value="buscar_cliente_dni">
                        <div class="mb-3"><label for="dni_buscar" class="form-label">DNI</label><input type="text" id="dni_buscar" name="dni_buscar" class="form-control" pattern="\d{8,10}" required></div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="reservas_procesar.php" method="POST">
                        <h5>Registrar Nuevo Cliente</h5>
                        <input type="hidden" name="accion" value="registrar_cliente_reserva">
                        <div class="mb-2"><label for="dni_reg" class="form-label">DNI</label><input type="text" id="dni_reg" name="dni_reg" class="form-control" required></div>
                        <div class="mb-2"><label for="nombres_reg" class="form-label">Nombres</label><input type="text" id="nombres_reg" name="nombres_reg" class="form-control" required></div>
                        <div class="mb-2"><label for="apellidos_reg" class="form-label">Apellidos</label><input type="text" id="apellidos_reg" name="apellidos_reg" class="form-control" required></div>
                        <button type="submit" class="btn btn-success">Registrar y Seleccionar</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- PASO 2: FECHAS Y HABITACIÓN (Solo se muestra si hay cliente) -->
    <?php if ($etapa === 'datos_reserva' && $cliente_info): ?>
    <div class="card action-card p-4">
        <h4 class="mb-3">Paso 2: Fechas y Preferencias</h4>
        <form action="reservas_procesar.php" method="POST">
            <input type="hidden" name="accion" value="buscar_habitaciones">
            <div class="row g-3">
                <div class="col-md-3"><label>Entrada</label><input type="date" name="fecha_entrada" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados['fecha_entrada'] ?? date('Y-m-d')); ?>" required></div>
                <div class="col-md-3"><label>Salida</label><input type="date" name="fecha_salida" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados['fecha_salida'] ?? date('Y-m-d', strtotime('+1 day'))); ?>" required></div>
                <div class="col-md-3"><label>Tipo Hab.</label><select name="tipo_habitacion_filtro" class="form-select"><option value="">Cualquiera</option><?php foreach ($tipos_habitacion as $tipo): ?><option value="<?php echo $tipo['id']; ?>" <?php echo (isset($filtros_guardados['tipo_habitacion_filtro']) && $filtros_guardados['tipo_habitacion_filtro'] == $tipo['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label>Capacidad</label><input type="number" name="capacidad_filtro" class="form-control" value="<?php echo htmlspecialchars($filtros_guardados['capacidad_filtro'] ?? 1); ?>" min="1" required></div>
                <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button></div>
            </div>
        </form>

        <?php if (!empty($habitaciones_disponibles)): ?>
        <hr>
        <h4 class="mt-4">Paso 3: Seleccionar Habitación y Confirmar</h4>
        <form action="reservas_procesar.php" method="POST">
            <input type="hidden" name="accion" value="confirmar_reserva_final">
            <table class="table table-dark table-striped table-hover">
                <thead><tr><th></th><th>Nombre</th><th>Tipo</th><th>Cap.</th><th>Precio/Noche</th></tr></thead>
                <tbody>
                    <?php foreach ($habitaciones_disponibles as $hab): ?>
                    <tr>
                        <td><input type="radio" name="habitacion_id_seleccionada" value="<?php echo $hab['id']; ?>" class="form-check-input" required></td>
                        <td><?php echo htmlspecialchars($hab['nombre_habitacion']); ?></td>
                        <td><?php echo htmlspecialchars($hab['tipo_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($hab['capacidad']); ?></td>
                        <td>S/ <?php echo htmlspecialchars(number_format($hab['precio'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="row g-3 mt-3">
                <div class="col-md-6"><label>Modo</label><select name="modo_reserva" class="form-select" required><option value="Simple">Simple</option><option value="Anticipada">Anticipada</option></select></div>
                <div class="col-md-6"><label>Pago</label><select name="metodo_pago" class="form-select"><option value="">-- Opcional --</option><option value="Efectivo">Efectivo</option><option value="Transferencia">Transferencia</option><option value="Tarjeta">Tarjeta</option></select></div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-lg btn-success"><i class="fas fa-check-circle"></i> Confirmar Reserva</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>