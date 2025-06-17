<?php
session_start();

if (!isset($_SESSION['cargo'])) {
    // No hay sesión iniciada
    header("Location: ../autch/login.php");
    exit();
}

// Verificar el rol según la página
if ($_SESSION['cargo'] !== 'Recepcionista') {
    // Si no es Recepcionista, redirige o bloquea
    echo "Acceso denegado.";
    exit();
}
$conexion = new mysqli("localhost", "root", "", "hotel_db");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$dni = $_POST['dni'];

$sql = "SELECT * FROM clientes WHERE dni = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $dni);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $cliente = $resultado->fetch_assoc();
    ?>
    <form action="guardar_cliente.php" method="post">
      <input type="hidden" name="accion" value="actualizar">
      <label>DNI:</label>
      <input type="text" name="dni" value="<?php echo $cliente['dni']; ?>" readonly>

      <label>Nombres:</label>
      <input type="text" name="nombres" value="<?php echo $cliente['nombres']; ?>" required>

      <label>Apellidos:</label>
      <input type="text" name="apellidos" value="<?php echo $cliente['apellidos']; ?>" required>

      <label>Teléfono:</label>
      <input type="text" name="telefono" value="<?php echo $cliente['telefono']; ?>">

      <label>Observación:</label>
      <textarea name="observacion"><?php echo $cliente['observacion']; ?></textarea>

      <button type="submit">Actualizar</button>
    </form>
    <?php
} else {
    echo "Cliente no encontrado.";
}

$stmt->close();
$conexion->close();
?>