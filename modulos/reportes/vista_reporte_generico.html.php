<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($titulo_reporte_final); ?> - Hotel</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    header, footer { text-align: center; margin-bottom: 20px; }
    nav a { margin: 0 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .no-data { color: #777; font-style: italic; text-align: center; padding: 20px; }
    .error { color: red; font-weight: bold; text-align: center; padding: 20px; }
    caption { font-size: 1.2em; font-weight: bold; margin-bottom: 10px; text-align: left;}
  </style>
</head>
<body>
  <header>
    <h1><?php echo htmlspecialchars($titulo_reporte_final); ?></h1>
    <?php if (!empty($subtitulo_reporte_final)): ?>
        <h2><?php echo htmlspecialchars($subtitulo_reporte_final); ?></h2>
    <?php endif; ?>
    <nav>
        <!-- ¡RUTAS CORREGIDAS AQUÍ! -->
        <a href="../dashboard/recepcionista_dashboard.php?load_module=modulos/reportes/reportes_content">Volver a Selección de Reportes</a> |
        <a href="../dashboard/recepcionista_dashboard.php">Volver al Panel Principal</a>
    </nav>
  </header>
  <hr>
  <main>
    <?php if ($error_reporte): ?>
        <p class="error"><?php echo htmlspecialchars($error_reporte); ?></p>
    <?php elseif (!empty($datos_reporte)): ?>
    <table>
        <thead>
            <tr>
                <?php foreach ($columnas_reporte as $columna): ?>
                    <th><?php echo htmlspecialchars($columna); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($datos_reporte as $fila): ?>
            <tr>
                <?php foreach ($fila as $valor_celda): ?>
                    <td><?php echo htmlspecialchars(is_numeric($valor_celda) && strpos($valor_celda, '.') !== false ? number_format((float)$valor_celda, 2) : $valor_celda); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p class="no-data">No se encontraron datos para este reporte con los criterios seleccionados.</p>
    <?php endif; ?>
  </main>
  <hr>
  <footer>
    <p>© HotelApp 2025 - Todos los derechos reservados. Generado: <?php echo date('d/m/Y H:i:s'); ?></p>
  </footer>
</body>
</html>