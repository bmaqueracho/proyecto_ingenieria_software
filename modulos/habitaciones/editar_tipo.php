<?php
session_start();
require_once '../../conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Administrador') { exit('Acceso denegado.'); }

$tipo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tipo_id) { exit('ID inválido.'); }

$stmt = $conexion->prepare("SELECT * FROM tipo_habitacion WHERE id = ?");
$stmt->bind_param("i", $tipo_id);
$stmt->execute();
$tipo = $stmt->get_result()->fetch_assoc();
if (!$tipo) { exit('Tipo de habitación no encontrado.'); }
$stmt->close();
$conexion->close();
?>
<section id="editar-tipo-form">
    <h2 class="section-title"><i class="fas fa-edit"></i> Editar Tipo de Habitación</h2>
    <div class="card action-card p-4">
        <form action="../habitaciones/habitaciones_procesar.php" method="POST">
            <input type="hidden" name="accion" value="actualizar_tipo">
            <input type="hidden" name="tipo_id" value="<?php echo $tipo['id']; ?>">
            <div class="mb-3"><label class="form-label">Nombre del Tipo</label><input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($tipo['nombre']); ?>" required></div>
            <div class="mb-3"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($tipo['descripcion']); ?></textarea></div>
            <div class="d-flex justify-content-end gap-2">
                <a href="#" onclick="event.preventDefault(); cargarContenido('../habitaciones/habitaciones_content.php');" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </div>
        </form>
    </div>
</section>