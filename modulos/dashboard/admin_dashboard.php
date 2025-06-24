<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['cargo'])) {
  header("Location: ../../index.php");
  exit();
}
if ($_SESSION['cargo'] !== 'Administrador') {
  header("Location: recepcionista_dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel | Hotel Mediterraneo</title>

  <!-- Dependencias de Estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    /* 1. CONFIGURACIÓN GLOBAL Y VARIABLES */
    :root {
      --fuente-titulos: 'Playfair Display', serif;
      --fuente-textos: 'Montserrat', sans-serif;
      --color-sidebar-bg: #111827;
      --color-sidebar-link: #9ca3af;
      --color-sidebar-link-hover: #ffffff;
      --color-sidebar-link-active-bg: #4f46e5;
      --color-content-bg: #f9fafb;
      --color-card-bg: #ffffff;
      --color-texto-principal: #1f2937;
      --color-texto-secundario: #6b7280;
      --color-borde: #e5e7eb;
      --color-acento: #4f46e5;
      --radius-suave: 8px;
      --radius-grande: 12px;
      --sombra-sutil: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --sombra-media: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
      --transicion-suave: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background-color: var(--color-content-bg);
      color: var(--color-texto-principal);
      font-family: var(--fuente-textos);
      min-height: 100vh;
    }

    /* 2. LAYOUT PRINCIPAL */
    .dashboard-wrapper {
      display: flex;
    }

    .sidebar {
      width: 260px;
      background: var(--color-sidebar-bg);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      position: fixed;
      height: 100%;
      transition: transform 0.3s ease;
      z-index: 1050;
    }

    .sidebar-header {
      padding: 1.5rem;
      font-family: var(--fuente-titulos);
      font-size: 1.5rem;
      font-weight: 600;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header i {
      color: var(--color-sidebar-link-active-bg);
    }

    .sidebar nav {
      padding: 1rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .sidebar .nav-link {
      color: var(--color-sidebar-link);
      border-radius: var(--radius-suave);
      margin-bottom: 0.25rem;
      transition: var(--transicion-suave);
      padding: 0.75rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none;
      font-weight: 500;
    }

    .sidebar .nav-link:hover {
      background: rgba(255, 255, 255, 0.05);
      color: var(--color-sidebar-link-hover);
    }

    .sidebar .nav-link.active {
      background: var(--color-sidebar-link-active-bg);
      color: #fff;
      font-weight: 600;
    }

    .sidebar .logout-item {
      margin-top: auto;
    }

    .main-wrapper {
      flex-grow: 1;
      padding: 2.5rem;
      margin-left: 260px;
      transition: margin-left 0.3s ease;
      animation: fadeIn 0.6s ease-out;
    }

    .main-header {
      padding-bottom: 1.5rem;
      margin-bottom: 2.5rem;
      border-bottom: 1px solid var(--color-borde);
    }

    .main-header .avatar {
      background: linear-gradient(45deg, var(--color-acento), #6366f1);
      color: #fff;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      font-weight: 600;
    }

    .main-header .user-name {
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0;
    }

    .main-header .user-role {
      font-size: 0.85rem;
      color: var(--color-texto-secundario);
      margin: 0;
    }

    /* 3. ESTILOS DE CONTENIDO */
    .section-title {
      font-family: var(--fuente-titulos);
      font-weight: 600;
      font-size: 2rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .section-title i {
      color: var(--color-acento);
    }

    .section-subtitle {
      color: var(--color-texto-secundario);
      margin-bottom: 2.5rem;
      max-width: 600px;
    }
    
    .card {
      background: var(--color-card-bg);
      border-radius: var(--radius-grande);
      border: 1px solid var(--color-borde);
      transition: var(--transicion-suave);
      box-shadow: var(--sombra-sutil);
      height: 100%;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: var(--sombra-media);
    }

    .card-header {
      background-color: transparent;
      border-bottom: 1px solid var(--color-borde);
      padding: 1.25rem 1.5rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    /* 4. MEJORAS ESPECÍFICAS AL DASHBOARD */

    /* Tarjetas KPI */
    .kpi-card {
      padding: 1.5rem;
    }
    .kpi-card:hover {
      border-color: var(--color-acento);
    }
    .kpi-card .card-icon {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
      transition: var(--transicion-suave);
    }
    .kpi-card:hover .card-icon {
        transform: scale(1.1);
    }
    .kpi-card-content h6 {
      font-size: 0.9rem;
      color: var(--color-texto-secundario);
      font-weight: 500;
      margin-bottom: 0.25rem;
    }
    .kpi-card-content .h4 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--color-texto-principal);
    }
    .kpi-card-content .comparison {
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    /* Colores de Iconos KPI */
    .icon-revenue { background-color: rgba(79, 70, 229, 0.1); color: #4f46e5; }
    .icon-occupancy { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .icon-bookings { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .icon-clients { background-color: rgba(249, 115, 22, 0.1); color: #f97316; }

    /* Tabla de Actividad Reciente */
    .activity-feed-table tbody tr:hover {
        background-color: #eff6ff;
    }
    .activity-feed-table td {
        padding: 1rem 1.5rem !important;
        border-top: 1px solid var(--color-borde);
    }

    /* Gráfico */
    #graficoIngresos {
        min-height: 320px;
    }

    /* 5. RESPONSIVIDAD Y ANIMACIONES */
    .mobile-menu-toggle {
      z-index: 1100;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      background-color: var(--color-acento);
      color: white;
      border: none;
      position: fixed;
      bottom: 20px;
      left: 20px;
    }

    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }
      .sidebar.active {
        transform: translateX(0);
        box-shadow: 0 0 50px rgba(0, 0, 0, 0.3);
      }
      .main-wrapper {
        margin-left: 0;
        padding: 1.5rem;
      }
      .main-header {
        margin-bottom: 1.5rem;
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .loading-overlay {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 400px;
    }

    .loader {
      border: 5px solid #f3f3f3;
      border-top: 5px solid var(--color-acento);
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
  <div class="dashboard-wrapper">
    <aside class="sidebar d-flex flex-column">
      <div class="sidebar-header">
        <i class="fas fa-user-shield"></i>
        <span>Panel Admin</span>
      </div>
      <nav class="nav flex-column flex-grow-1">
        <a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt fa-fw"></i><span>Inicio Admin</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../usuarios/usuarios_content.php', this);"><i class="fas fa-users-cog fa-fw"></i><span>Gestión de Usuarios</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('reservas_content.php', this);"><i class="fas fa-calendar-check fa-fw"></i><span>Reservas</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php?from=admin', this);"><i class="fas fa-users fa-fw"></i><span>Clientes</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../habitaciones/habitaciones_content.php', this);"><i class="fas fa-bed fa-fw"></i><span>Habitaciones</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../productos/productos_content.php', this);"><i class="fas fa-concierge-bell fa-fw"></i><span>Productos y Ventas</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../reportes/reportes_content.php', this);"><i class="fas fa-chart-bar fa-fw"></i><span>Reportes</span></a>
        <a href="recepcionista_dashboard.php?from_admin=1" class="nav-link mt-3"><i class="fas fa-door-open fa-fw"></i><span>Ir a Panel Recepción</span></a>
        <a href="../autch/logout.php" class="nav-link logout-item"><i class="fas fa-sign-out-alt fa-fw"></i><span>Cerrar Sesión</span></a>
      </nav>
    </aside>

    <div class="main-wrapper">
      <header class="main-header d-flex justify-content-end align-items-center">
        <div class="d-flex align-items-center">
          <div class="text-end me-3">
            <p class="user-name mb-0"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Admin'); ?></p>
            <p class="user-role mb-0"><?php echo htmlspecialchars($_SESSION['cargo']); ?></p>
          </div>
          <div class="avatar"><?php echo substr(htmlspecialchars($_SESSION['nombre'] ?? 'A'), 0, 1); ?></div>
        </div>
      </header>

      <?php
      // ----- INICIO DE LA LÓGICA DE DATOS -----
      require_once '../../conexion.php';
      
      // KPI 1: Ingresos Hoy vs Ayer
      $hoy_inicio = date('Y-m-d 00:00:00');
      $hoy_fin = date('Y-m-d 23:59:59');
      $stmt = $conexion->prepare("SELECT SUM(monto_total) as total FROM reservas WHERE estado = 'Completa' AND fecha_salida BETWEEN ? AND ?");
      $stmt->bind_param("ss", $hoy_inicio, $hoy_fin);
      $stmt->execute();
      $ingresos_hoy = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
      
      $ayer_inicio = date('Y-m-d 00:00:00', strtotime('-1 day'));
      $ayer_fin = date('Y-m-d 23:59:59', strtotime('-1 day'));
      $stmt->bind_param("ss", $ayer_inicio, $ayer_fin);
      $stmt->execute();
      $ingresos_ayer = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
      $stmt->close();
      
      $porcentaje_cambio = 0;
      if ($ingresos_ayer > 0) {
          $porcentaje_cambio = (($ingresos_hoy - $ingresos_ayer) / $ingresos_ayer) * 100;
      } elseif ($ingresos_hoy > 0) {
          $porcentaje_cambio = 100;
      }
      $clase_texto = $porcentaje_cambio >= 0 ? 'text-success' : 'text-danger';
      $icono = $porcentaje_cambio >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
      
      // KPI 2: Tasa de Ocupación
      $result_total_hab = $conexion->query("SELECT COUNT(*) as total FROM habitaciones");
      $total_habitaciones = $result_total_hab->fetch_assoc()['total'] ?? 1;
      $stmt = $conexion->prepare("SELECT COUNT(DISTINCT habitacion_id) as ocupadas FROM reservas WHERE NOW() BETWEEN fecha_entrada AND fecha_salida AND estado IN ('Confirmada', 'Completa')");
      $stmt->execute();
      $habitaciones_ocupadas = $stmt->get_result()->fetch_assoc()['ocupadas'] ?? 0;
      $stmt->close();
      $tasa_ocupacion = ($total_habitaciones > 0) ? ($habitaciones_ocupadas / $total_habitaciones) * 100 : 0;
      
      // KPI 3: Reservas Nuevas Hoy
      $hoy_fecha = date('Y-m-d');
      $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE DATE(fecha_reserva) = ?");
      $stmt->bind_param("s", $hoy_fecha);
      $stmt->execute();
      $reservas_nuevas_hoy = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
      $stmt->close();
      
      // KPI 4: Clientes Totales
      $result_clientes = $conexion->query("SELECT COUNT(*) as total FROM clientes");
      $clientes_totales = $result_clientes->fetch_assoc()['total'] ?? 0;
      
      // Tabla de Últimas Reservas
      $ultimas_reservas = $conexion->query(
          "SELECT r.id, c.nombres, c.apellidos, h.nombre as nombre_habitacion, r.fecha_entrada, r.monto_total, r.estado 
           FROM reservas r 
           JOIN clientes c ON r.cliente_dni = c.dni 
           JOIN habitaciones h ON r.habitacion_id = h.id 
           ORDER BY r.id DESC 
           LIMIT 5"
      )->fetch_all(MYSQLI_ASSOC);
      
      // Datos para Gráfico
      $datos_grafico = [];
      $labels_grafico = [];
      for ($i = 6; $i >= 0; $i--) {
          $fecha = date('Y-m-d', strtotime("-$i days"));
          $labels_grafico[] = date('d/m', strtotime($fecha));
          $fecha_inicio = $fecha . ' 00:00:00';
          $fecha_fin = $fecha . ' 23:59:59';
          $stmt = $conexion->prepare("SELECT SUM(monto_total) as total FROM reservas WHERE estado = 'Completa' AND fecha_salida BETWEEN ? AND ?");
          $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
          $stmt->execute();
          $datos_grafico[] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
          $stmt->close();
      }
      
      $conexion->close();
      // ----- FIN DE LA LÓGICA DE DATOS -----
      ?>

      <main id="contentArea">
        <div class="container-fluid px-0">
          <!-- 1. Encabezado de la Sección -->
          <div>
            <h1 class="section-title"><i class="fas fa-chart-line"></i> Resumen del Sistema</h1>
            <p class="section-subtitle">Vista general del estado del hotel y rendimiento del negocio en tiempo real.</p>
          </div>

          <!-- 2. Tarjetas de KPIs (Rediseñadas) -->
          <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="card-icon icon-revenue"><i class="fas fa-dollar-sign"></i></div>
                  <div class="kpi-card-content">
                    <h6>Ingresos de Hoy</h6>
                    <h4 class="h4 mb-0">S/ <?php echo number_format($ingresos_hoy, 2); ?></h4>
                    <small class="comparison <?php echo $clase_texto; ?>"><i class="fas <?php echo $icono; ?>"></i> <?php echo number_format(abs($porcentaje_cambio), 0); ?>% vs ayer</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="card-icon icon-occupancy"><i class="fas fa-door-open"></i></div>
                  <div class="kpi-card-content">
                    <h6>Ocupación Actual</h6>
                    <h4 class="h4 mb-0"><?php echo number_format($tasa_ocupacion, 1); ?>%</h4>
                    <small class="comparison text-muted"><?= $habitaciones_ocupadas ?> de <?= $total_habitaciones ?> hab.</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="card-icon icon-bookings"><i class="fas fa-calendar-plus"></i></div>
                  <div class="kpi-card-content">
                    <h6>Reservas Nuevas (Hoy)</h6>
                    <h4 class="h4 mb-0"><?php echo $reservas_nuevas_hoy; ?></h4>
                    <small class="comparison text-muted"> </small> <!-- Placeholder for alignment -->
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-md-6">
              <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="card-icon icon-clients"><i class="fas fa-users"></i></div>
                  <div class="kpi-card-content">
                    <h6>Clientes Totales</h6>
                    <h4 class="h4 mb-0"><?php echo $clientes_totales; ?></h4>
                     <small class="comparison text-muted"> </small> <!-- Placeholder for alignment -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Contenedor para Gráfico y Tabla -->
          <div class="row g-4">
            <!-- Columna Izquierda: Gráfico de Ingresos -->
            <div class="col-lg-7">
              <div class="card">
                <div class="card-header">
                  <i class="fas fa-chart-area"></i> Ingresos de la Última Semana
                </div>
                <div class="card-body p-4">
                  <canvas id="graficoIngresos"></canvas>
                </div>
              </div>
            </div>

            <!-- Columna Derecha: Tabla de Actividad Reciente (Rediseñada) -->
            <div class="col-lg-5">
              <div class="card">
                <div class="card-header">
                  <i class="fas fa-history"></i> Actividad Reciente
                </div>
                <div class="table-responsive">
                  <table class="table table-borderless activity-feed-table mb-0">
                    <tbody>
                      <?php if (empty($ultimas_reservas)): ?>
                        <tr><td class="text-center text-muted py-5">No hay actividad reciente.</td></tr>
                      <?php else: ?>
                        <?php foreach ($ultimas_reservas as $reserva): ?>
                          <tr>
                            <td>
                              <div class="d-flex justify-content-between align-items-center">
                                <div>
                                  <div class="fw-bold text-dark"><?php echo htmlspecialchars($reserva['nombres'] . ' ' . $reserva['apellidos']); ?></div>
                                  <small class="text-muted">Hab. <?php echo htmlspecialchars($reserva['nombre_habitacion']); ?> • Entrada: <?php echo date('d/m/y', strtotime($reserva['fecha_entrada'])); ?></small>
                                </div>
                                <div class="text-end">
                                  <div class="fw-bold text-success">S/ <?php echo number_format($reserva['monto_total'], 2); ?></div>
                                  <span class="badge rounded-pill text-bg-<?php
                                    switch ($reserva['estado']) {
                                      case 'Completa': echo 'success'; break;
                                      case 'Confirmada': echo 'primary'; break;
                                      case 'Cancelada': echo 'danger'; break;
                                      default: echo 'secondary';
                                    } ?>"><?php echo htmlspecialchars($reserva['estado']); ?></span>
                                </div>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  
  <button class="btn d-lg-none mobile-menu-toggle">
      <i class="fas fa-bars"></i>
  </button>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // --- Lógica de Carga de Módulos ---
      const contentArea = document.getElementById('contentArea');
      const loaderHTML = `<div class="loading-overlay"><div class="loader"></div></div>`;

      window.cargarContenido = async function(pathModulo, clickedLink) {
        if (clickedLink) {
          document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
          clickedLink.classList.add('active');
        }
        contentArea.style.opacity = '0.5';
        contentArea.innerHTML = loaderHTML;

        try {
          const response = await fetch(pathModulo);
          if (!response.ok) throw new Error(`Error ${response.status}: No se pudo cargar ${pathModulo}`);
          const html = await response.text();
          
          // Usamos un delay mínimo para que la transición sea perceptible
          setTimeout(() => {
            contentArea.innerHTML = html;
            contentArea.style.opacity = '1';

            // Re-ejecutar scripts del contenido cargado
            Array.from(contentArea.querySelectorAll("script")).forEach(oldScript => {
              const newScript = document.createElement("script");
              Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
              newScript.appendChild(document.createTextNode(oldScript.innerHTML));
              oldScript.parentNode.replaceChild(newScript, oldScript);
            });
          }, 150);

        } catch (error) {
          console.error("Error al cargar módulo:", error);
          contentArea.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`;
          contentArea.style.opacity = '1';
        }
      }
      
      const mobileToggle = document.querySelector('.mobile-menu-toggle');
      const sidebar = document.querySelector('.sidebar');
      if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
      }

      // --- Inicialización del Gráfico ---
      const ctx = document.getElementById('graficoIngresos');
      if (ctx) {
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: <?php echo json_encode($labels_grafico); ?>,
            datasets: [{
              label: 'Ingresos (S/)',
              data: <?php echo json_encode($datos_grafico); ?>,
              fill: true,
              backgroundColor: 'rgba(79, 70, 229, 0.1)',
              borderColor: 'rgba(79, 70, 229, 1)',
              tension: 0.4,
              pointBackgroundColor: 'rgba(79, 70, 229, 1)',
              pointBorderColor: '#fff',
              pointHoverRadius: 7,
              pointHoverBorderWidth: 2,
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) { return 'S/ ' + value.toLocaleString(); }
                }
              },
              x: {
                grid: { display: false }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                 callbacks: {
                     label: function(context) {
                         let label = context.dataset.label || '';
                         if (label) { label += ': '; }
                         if (context.parsed.y !== null) {
                             label += new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(context.parsed.y);
                         }
                         return label;
                     }
                 }
              }
            }
          }
        });
      }

      // --- Función para generación de reportes ---
      window.generarReporteConIframe = function(form) {
        // 1. Construir la URL del reporte desde los datos del formulario
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        const urlReporte = `../reportes/generar_reporte.php?${params}`;

        // 2. Comprobar si ya existe un iframe de reporte y eliminarlo para evitar duplicados
        const iframeExistente = document.getElementById('iframe-impresion-oculto');
        if (iframeExistente) {
          iframeExistente.remove();
        }

        // 3. Crear el nuevo iframe oculto
        const iframe = document.createElement('iframe');
        iframe.id = 'iframe-impresion-oculto';
        iframe.style.display = 'none'; // Completamente invisible
        iframe.src = urlReporte;

        // 4. Añadir el iframe al cuerpo de la página para que empiece a cargar
        document.body.appendChild(iframe);

        // 5. Definir qué hacer cuando el contenido del iframe haya cargado COMPLETAMENTE
        iframe.onload = function() {
          try {
            // Enfocar el contenido del iframe y llamar al diálogo de impresión del navegador
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
          } catch (e) {
            console.error("Error al intentar imprimir el reporte:", e);
            alert("No se pudo abrir el diálogo de impresión. Verifique la consola del navegador.");
          }

          // Opcional: Eliminar el iframe después de un tiempo para mantener limpio el DOM
          setTimeout(() => {
            document.body.removeChild(iframe);
          }, 1000);
        };

        iframe.onerror = function() {
          alert("Error: No se pudo cargar el contenido del reporte. Verifique la consola (F12) para posibles errores en el script PHP.");
          document.body.removeChild(iframe);
        }
      }
    });
  </script>
</body>
</html>