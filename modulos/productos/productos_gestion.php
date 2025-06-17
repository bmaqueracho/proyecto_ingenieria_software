<?php
session_start();
require_once 'conexion.php'; // $conn

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    header("Location: ../autch/login.html");
    exit();
}

$productos = [];
$categorias_enum = ['Aseo', 'General', 'Bebidas']; // Definido en tu tabla productos.categoria

// --- LÓGICA POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar_producto') {
        $nombre = trim($_POST['nombre']);
        $stock = (int)$_POST['stock'];
        $categoria = $_POST['categoria'];

        if (!empty($nombre) && $stock >= 0 && in_array($categoria, $categorias_enum)) {
            $stmt = $conn->prepare("INSERT INTO productos (nombre, stock, categoria) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $nombre, $stock, $categoria);
            if ($stmt->execute()) {
                $_SESSION['mensaje_exito_prod'] = "Producto '$nombre' agregado exitosamente.";
            } else {
                $_SESSION['mensaje_error_prod'] = "Error al agregar producto: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensaje_error_prod'] = "Datos inválidos para agregar producto.";
        }
        header("Location: productos_gestion.php");
        exit();
    }
    elseif ($accion === 'ajustar_stock') {
        $producto_id_ajuste = (int)$_POST['producto_id_ajuste'];
        $cantidad_ajuste = (int)$_POST['cantidad_ajuste']; // Puede ser positiva (ingreso) o negativa (merma)
        $tipo_ajuste = $_POST['tipo_ajuste']; // 'ingreso' o 'merma'

        if ($producto_id_ajuste > 0) {
            // Determinar si sumar o restar
            $operacion_stock = ($tipo_ajuste === 'ingreso') ? '+' : '-';
            // Asegurar que la cantidad sea positiva para la operación
            $cantidad_abs = abs($cantidad_ajuste);

            if ($tipo_ajuste === 'merma' && $cantidad_abs < 0) $cantidad_abs = -$cantidad_abs; // si es merma y negativo, hacerlo positivo

            $stmt = $conn->prepare("UPDATE productos SET stock = stock $operacion_stock ? WHERE id = ?");
            $stmt->bind_param("ii", $cantidad_abs, $producto_id_ajuste);

            // Opcional: Verificar que el stock no quede negativo si es merma
            if ($tipo_ajuste === 'merma') {
                $check_stock_stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
                $check_stock_stmt->bind_param("i", $producto_id_ajuste);
                $check_stock_stmt->execute();
                $current_stock_res = $check_stock_stmt->get_result();
                if ($current_stock_res->num_rows > 0) {
                    $current_stock_val = $current_stock_res->fetch_assoc()['stock'];
                    if ($current_stock_val < $cantidad_abs) {
                        $_SESSION['mensaje_error_prod'] = "No se puede ajustar merma. Stock actual ($current_stock_val) es menor que la cantidad a mermar ($cantidad_abs).";
                        $check_stock_stmt->close();
                        header("Location: productos_gestion.php");
                        exit();
                    }
                }
                $check_stock_stmt->close();
            }

            if ($stmt->execute()) {
                $_SESSION['mensaje_exito_prod'] = "Stock ajustado para producto ID $producto_id_ajuste.";
            } else {
                $_SESSION['mensaje_error_prod'] = "Error al ajustar stock: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['mensaje_error_prod'] = "Datos inválidos para ajustar stock.";
        }
        header("Location: productos_gestion.php");
        exit();
    }
    // Aquí podrías añadir lógica para editar producto (nombre, categoría)
}

// Cargar productos para mostrar
$result = $conn->query("SELECT id, nombre, stock, categoria FROM productos ORDER BY categoria, nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}

$mensaje_error_prod = $_SESSION['mensaje_error_prod'] ?? null;
$mensaje_exito_prod = $_SESSION['mensaje_exito_prod'] ?? null;
unset($_SESSION['mensaje_error_prod'], $_SESSION['mensaje_exito_prod']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrar Productos - Hotel</title>
</head>
<body>
  <header>
    <h1>Administrar Productos y Stock General</h1>
    <nav>
        <a href="productos_dashboard.html">Volver a Productos y Ventas</a> |
        <a href="../dashboard/recepcionista_dashboard.php">Volver al Panel Principal</a>
    </nav>
  </header>
  <hr>
  <?php if ($mensaje_error_prod): ?><p style="color:red; font-weight:bold;"><?php echo $mensaje_error_prod; ?></p><?php endif; ?>
  <?php if ($mensaje_exito_prod): ?><p style="color:green; font-weight:bold;"><?php echo $mensaje_exito_prod; ?></p><?php endif; ?>
  <hr>

  <section id="agregar-producto">
    <h2>Agregar Nuevo Producto</h2>
    <form action="productos_gestion.php" method="POST">
      <input type="hidden" name="accion" value="agregar_producto">
      <label for="nombre">Nombre del Producto:</label><br>
      <input type="text" id="nombre" name="nombre" required maxlength="45"><br><br>
      <label for="stock">Stock Inicial:</label><br>
      <input type="number" id="stock" name="stock" required min="0"><br><br>
      <label for="categoria">Categoría:</label><br>
      <select id="categoria" name="categoria" required>
        <?php foreach ($categorias_enum as $cat): ?>
        <option value="<?php echo $cat; ?>"><?php echo htmlspecialchars($cat); ?></option>
        <?php endforeach; ?>
      </select><br><br>
      <button type="submit">Agregar Producto</button>
    </form> 
  </section>
  <hr>

  <section id="ajustar-stock">
    <h2>Ajustar Stock (Ingreso/Merma)</h2>
    <form action="productos_gestion.php" method="POST">
        <input type="hidden" name="accion" value="ajustar_stock">
        <label for="producto_id_ajuste">Seleccionar Producto:</label><br>
        <select name="producto_id_ajuste" id="producto_id_ajuste" required>
            <option value="">-- Elija un producto --</option>
            <?php foreach ($productos as $prod_item): ?>
            <option value="<?php echo $prod_item['id']; ?>">
                <?php echo htmlspecialchars($prod_item['nombre']) . " (Actual: " . $prod_item['stock'] . ")"; ?>
            </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="tipo_ajuste">Tipo de Ajuste:</label><br>
        <select name="tipo_ajuste" id="tipo_ajuste" required>
            <option value="ingreso">Ingreso de Mercadería (+)</option>
            <option value="merma">Merma / Pérdida (-)</option>
        </select><br><br>

        <label for="cantidad_ajuste">Cantidad a Ajustar:</label><br>
        <input type="number" name="cantidad_ajuste" id="cantidad_ajuste" required min="1"><br><br>
        <button type="submit">Realizar Ajuste de Stock</button>
    </form>
  </section>
  <hr>

  <section id="lista-productos">
    <h2>Lista de Productos y Stock Actual</h2>
    <?php if (!empty($productos)): ?>
    <table border="1" style="width:100%; border-collapse: collapse;">
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Stock Actual</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $producto): ?>
        <tr>
          <td><?php echo htmlspecialchars($producto['id']); ?></td>
          <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
          <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
          <td><?php echo htmlspecialchars($producto['stock']); ?></td>
          <td>
            <a href="productos_editar.php?id=<?php echo $producto['id']; ?>">Editar</a>
            <?php // Botón de eliminar con cuidado, o solo desactivar ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p>No hay productos registrados.</p>
    <?php endif; ?>
  </section>
  <hr>
  <footer>
    <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>
</body>
</html>