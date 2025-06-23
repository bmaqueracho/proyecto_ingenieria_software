<?php
session_start();
require_once '../../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') { exit('Acceso denegado.'); }

$habitacion_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$habitacion_id) { exit('ID inválido.'); }

// Necesitamos la habitación Y la lista de todos los tipos para el <select>
$stmt_hab = $conexion->prepare("SELECT * FROM habitaciones WHERE id = ?");
$stmt_hab->bind_param("i", $habitacion_id);
$stmt_hab->execute();
$habitacion = $stmt_hab->get_result()->fetch_assoc();
if (!$habitacion) { exit('Habitación no encontrada.'); }
$stmt_hab->close();
$tipos_habitacion = $conexion->query("SELECT * FROM tipo_habitacion ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$conexion->close();
?>
<section id="editar-habitacion-form">
    <h2 class="section-title"><i class="fas fa-edit"></i> Editar Habitación: <?php echo htmlspecialchars($habitacion['nombre']); ?></h2>
    <div class="card action-card p-4">
        <form action="../habitaciones/habitaciones_procesar.php" method="POST">
            <input type="hidden" name="accion" value="actualizar_habitacion">
            <input type="hidden" name="habitacion_id" value="<?php echo $habitacion['id']; ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre/Número</label><input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($habitacion['nombre']); ?>" required></div>
                <div class="col-md-6"><label class="form-label">Tipo</label><select name="tipo_id" class="form-select" required><?php foreach ($tipos_habitacion as $tipo): ?><option value="<?php echo $tipo['id']; ?>" <?php echo ($habitacion['tipo_id'] == $tipo['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Capacidad</label><input type="number" name="capacidad" class="form-control" value="<?php echo htmlspecialchars($habitacion['capacidad']); ?>" required min="1"></div>
                <div class="col-md-6"><label class="form-label">Precio (S/)</label><input type="number" name="precio" class="form-control" step="0.01" value="<?php echo htmlspecialchars($habitacion['precio']); ?>" required></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="#" onclick="event.preventDefault(); cargarContenido('../habitaciones/habitaciones_content.php');" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </div>
        </form>
    </div>
</section>