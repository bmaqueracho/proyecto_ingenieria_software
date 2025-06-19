<?php
// ARCHIVO DE VISTA. Corregido y mejorado.
session_start();
require_once '../../conexion.php';

// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'Recepcionista') { 
    exit(); 
}

// Lógica para obtener datos para la vista (sin cambios)
$cliente_encontrado = $_SESSION['cliente_encontrado'] ?? null;
unset($_SESSION['cliente_encontrado']);
$mensaje = $_SESSION['mensaje_cliente'] ?? null;
unset($_SESSION['mensaje_cliente']);

$lista_clientes = [];
$resultado_lista = $conexion->query("SELECT * FROM clientes ORDER BY apellidos ASC, nombres ASC");
if ($resultado_lista) {
    while ($fila = $resultado_lista->fetch_assoc()) {
        $lista_clientes[] = $fila;
    }
}
$conexion->close();
?>

<section id="gestion-clientes">
    <h2 class="section-title"><i class="fas fa-users"></i> Gestión de Clientes</h2>
    <p class="text-white-50 mb-4">Añada nuevos clientes o busque existentes para actualizar sus datos.</p>
    
    <!-- Sección para mostrar mensajes de éxito o error -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo ($mensaje['tipo'] === 'exito' ? 'success' : 'danger'); ?>">
            <?php echo htmlspecialchars($mensaje['texto']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Columna Izquierda: Acciones (Buscar, Registrar, Actualizar) -->
        <div class="col-lg-5">
            <div class="card action-card p-4">
                
                <?php // --- LÓGICA DE VISUALIZACIÓN ---
                      // Si se encontró un cliente, mostramos el formulario de ACTUALIZACIÓN.
                      if ($cliente_encontrado): 
                ?>
                    <h4 class="mb-3">Actualizar Datos del Cliente</h4>
                    <form action="../clientes/clientes_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar">
                        <div class="mb-3">
                            <label class="form-label">DNI</label>
                            <!-- El DNI no se puede editar, por eso es 'readonly' -->
                            <input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['dni']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" name="nombres" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['nombres']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['apellidos']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($cliente_encontrado['telefono']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <textarea name="observacion" class="form-control"><?php echo htmlspecialchars($cliente_encontrado['observacion']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 mb-2">Actualizar Datos</button>
                        <a href="#" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php');" class="btn btn-secondary w-100">Cancelar y Limpiar</a>
                    </form>

                <?php // Si NO se encontró un cliente, mostramos los formularios de BÚSQUEDA y REGISTRO.
                      else: 
                ?>
                    <h4 class="mb-3">Buscar Cliente</h4>
                    <form action="../clientes/clientes_procesar.php" method="POST" class="mb-4">
                        <input type="hidden" name="accion" value="buscar">
                        <label class="form-label">Buscar por DNI para Actualizar</label>
                        <div class="input-group">
                            <input type="text" name="dni" class="form-control" placeholder="Ingresar DNI..." required>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                    
                    <hr class="my-4">

                    <h4 class="mb-3">Registrar Nuevo Cliente</h4>
                    <form action="../clientes/clientes_procesar.php" method="POST">
                        <input type="hidden" name="accion" value="registrar">
                        <div class="mb-3"><label class="form-label">DNI</label><input type="text" name="dni" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Nombres</label><input type="text" name="nombres" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Apellidos</label><input type="text" name="apellidos" class="form-control" required></div>
                        <!-- CAMPOS RESTAURADOS -->
                        <div class="mb-3"><label class="form-label">Teléfono (Opcional)</label><input type="text" name="telefono" class="form-control" placeholder="Ej: 987654321"></div>
                        <div class="mb-3"><label class="form-label">Observación (Opcional)</label><textarea name="observacion" class="form-control" rows="2" placeholder="Ej: Cliente frecuente"></textarea></div>
                        <button type="submit" class="btn btn-success w-100">Registrar Cliente</button>
                    </form>
                <?php endif; ?>
                
            </div>
        </div>

        <!-- Columna Derecha: Lista de Clientes (sin cambios) -->
        <div class="col-lg-7">
            <div class="card action-card p-4">
                <h4 class="mb-3">Lista de Clientes Registrados</h4>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-dark table-striped table-hover">
                        <thead>
                            <tr><th>DNI</th><th>Nombres</th><th>Apellidos</th><th>Teléfono</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['dni']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>