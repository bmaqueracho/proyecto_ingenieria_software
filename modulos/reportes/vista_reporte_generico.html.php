<?php
// Lógica para los enlaces de "Volver"
$origen_final = $origen_reporte ?? 'recepcionista';
$dashboard_file = ($origen_final === 'admin') ? 'admin_dashboard.php' : 'recepcionista_dashboard.php';
$link_volver_seleccion = "../dashboard/{$dashboard_file}?load_module=modulos/reportes/reportes_content";
$link_volver_panel_principal = "../dashboard/{$dashboard_file}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($titulo_reporte_final ?? 'Reporte'); ?> - Hotel Mediterraneo</title>
  <style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
        margin: 0; 
        padding: 20px;
        color: #333;
        background-color: #f4f7f6;
    }
    .container {
        max-width: 980px;
        margin: 0 auto;
        padding: 25px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 5px;
    }
    header { 
        text-align: center; 
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    header h1 { 
        margin: 0 0 5px 0; 
        color: #2c3e50; 
        font-size: 1.8em; 
    }
    header h2 { 
        margin: 0; 
        font-weight: normal; 
        color: #7f8c8d; 
        font-size: 1.1em;
    }
    nav {
        margin: 20px 0;
        text-align: center;
    }
    nav a { 
        color: #3498db; 
        text-decoration: none;
        margin: 0 10px;
    }
    nav a:hover { 
        text-decoration: underline;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px 12px;
        text-align: left;
    }
    th {
        background-color: #34495e;
        color: white;
        font-weight: bold;
    }
    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    .error {
        color: #d9534f;
        background-color: #f9e2e2;
        border: 1px solid #ebccd1;
        padding: 15px;
        border-radius: 4px;
        text-align: center;
    }
    .no-data {
        text-align: center;
        padding: 30px;
        color: #777;
        font-style: italic;
        border: 1px dashed #ddd;
        background-color: #fafafa;
    }
    footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        text-align: center;
        font-size: 0.9em;
        color: #999;
    }

    /* Estilos para impresión */
    @media print {
        body { 
            margin: 0;
            padding: 0;
            background: none;
        }
        .container {
            box-shadow: none;
            border: none;
            padding: 10px;
            max-width: 100%;
        }
        nav {
            display: none;
        }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1><?php echo htmlspecialchars($titulo_reporte_final ?? 'Reporte del Sistema'); ?></h1>
      <?php if (!empty($subtitulo_reporte_final)): ?>
          <h2><?php echo htmlspecialchars($subtitulo_reporte_final); ?></h2>
      <?php endif; ?>
      <nav>
          <a href="<?php echo $link_volver_seleccion; ?>">Volver a Selección de Reportes</a> |
          <a href="<?php echo $link_volver_panel_principal; ?>">Volver al Panel Principal</a>
      </nav>
    </header>
    
    <main>
      <?php if (!empty($error_reporte)): ?>
          <div class="error">Error al generar el reporte: <?php echo htmlspecialchars($error_reporte); ?></div>
      
      <?php elseif (isset($datos_reporte) && !empty($datos_reporte)): ?>
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
                      <?php foreach ($fila as $valor): ?>
                          <td><?php echo htmlspecialchars($valor); ?></td>
                      <?php endforeach; ?>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      
      <?php else: ?>
          <div class="no-data">No se encontraron datos para este reporte</div>
      <?php endif; ?>
    </main>
    
    <footer>
      <p>© Hotel Mediterraneo <?php echo date('Y'); ?></p>
      <p>Reporte generado el: <?php echo date('d/m/Y \a \l\a\s H:i:s'); ?></p>
    </footer>
  </div>
</body>
</html>