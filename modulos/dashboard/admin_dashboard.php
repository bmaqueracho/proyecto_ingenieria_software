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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    /* ... (Todo tu CSS profesional existente se mantiene igual) ... */
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
      --color-exito: #10b981;
      --color-error: #ef4444;
      --color-aviso: #f59e0b;
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
      overflow-x: hidden;
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
      background: linear-gradient(45deg, var(--color-acento), var(--color-sidebar-link-active-bg));
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

    #contentArea {
      transition: opacity 0.3s ease;
    }

    /* 3. ESTILOS DE CONTENIDO */
    .section-title {
      font-family: var(--fuente-titulos);
      font-weight: 600;
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .section-title i {
      color: var(--color-sidebar-link-active-bg);
      margin-right: 1rem;
    }

    .section-subtitle {
      color: var(--color-texto-secundario);
      margin-bottom: 2.5rem;
      max-width: 600px;
    }

    .card,
    .action-card {
      background: var(--color-card-bg);
      border-radius: var(--radius-grande);
      border: 1px solid var(--color-borde);
      transition: var(--transicion-suave);
      box-shadow: var(--sombra-sutil);
      height: 100%;
      color: var(--color-texto-principal);
    }

    .card:hover,
    .action-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--sombra-media);
    }

    /* 4. RESPONSIVIDAD Y ANIMACIONES */
    .mobile-menu-toggle {
      z-index: 1100;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      background-color: var(--color-sidebar-link-active-bg);
      border: none;
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
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .loading-overlay {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 400px;
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
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* ====================================================================== */
    /* ============== CÓDIGO CSS PARA AÑADIR AL ADMIN DASHBOARD ============== */
    /* ====================================================================== */

    /* 3. ESTILOS DE CONTENIDO DEL DASHBOARD INICIAL (TARJETAS DE BIENVENIDA) */
    .welcome-card {
      background: var(--color-card-bg);
      border-radius: var(--radius-grande);
      border: 1px solid var(--color-borde);
      transition: var(--transicion-suave);
      box-shadow: var(--sombra-sutil);
      text-decoration: none;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 2rem 1rem;
      height: 100%;
    }

    .welcome-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--sombra-media);
      /* El color de acento puede ser el mismo que el del link activo */
      border-color: var(--color-sidebar-link-active-bg);
    }

    .welcome-card .card-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      font-size: 1.5rem;
      transition: var(--transicion-suave);
    }

    .welcome-card h3 {
      color: var(--color-texto-principal);
      font-family: var(--fuente-textos);
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0 0 0.25rem 0;
    }

    .welcome-card p {
      color: var(--color-texto-secundario);
      font-size: 0.9rem;
      margin: 0;
    }

    /* Colores de los iconos (puedes personalizarlos si quieres) */
    .icon-reserva {
      background-color: rgba(52, 152, 219, 0.1);
      color: #3498db;
      /* Azul */
    }

    .icon-checkin {
      background-color: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
      /* Verde */
    }

    .icon-checkout {
      background-color: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
      /* Rojo */
    }

    .icon-clientes {
      background-color: rgba(155, 89, 182, 0.1);
      color: #9b59b6;
      /* Púrpura */
    }

    .welcome-card:hover .card-icon {
      transform: scale(1.1) rotate(5deg);
    }


    /* ESTILOS ADICIONALES PARA FORMULARIOS Y TABLAS (PARA CONSISTENCIA FUTURA) */
    #contentArea .form-label {
      font-weight: 500;
      color: var(--color-texto-secundario);
      font-size: 0.9rem;
    }

    #contentArea .form-control,
    #contentArea .form-select {
      background-color: var(--color-content-bg);
      border: 1px solid var(--color-borde);
      border-radius: var(--radius-suave);
      padding: 0.75rem 1rem;
      transition: var(--transicion-suave);
    }

    #contentArea .form-control:focus,
    #contentArea .form-select:focus {
      background-color: #fff;
      border-color: var(--color-sidebar-link-active-bg);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    #contentArea .table-responsive {
      border: 1px solid var(--color-borde);
      border-radius: var(--radius-grande);
      overflow: hidden;
    }

    #contentArea .table {
      margin-bottom: 0;
    }

    #contentArea .table thead th {
      background-color: #f9fafb;
      border-bottom: 2px solid var(--color-borde);
      color: var(--color-texto-secundario);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
    }

    #contentArea .table td {
      border-top: 1px solid var(--color-borde);
      vertical-align: middle;
    }

    #contentArea .table-hover tbody tr:hover {
      background-color: #eff6ff;
    }

    #contentArea .btn {
      border-radius: var(--radius-suave);
      font-weight: 600;
      padding: 0.6rem 1.2rem;
      transition: var(--transicion-suave);
    }

    #contentArea .btn-primary {
      background-color: var(--color-sidebar-link-active-bg);
      border-color: var(--color-sidebar-link-active-bg);
    }

    #contentArea .btn-primary:hover {
      background-color: #4338ca;
      /* Un poco más oscuro que el índigo */
      border-color: #4338ca;
      transform: translateY(-2px);
    }

    #contentArea .badge {
      padding: 0.5em 0.75em;
      font-weight: 600;
      font-size: 0.75rem;
    }
  </style>
</head>

<body>
  <div class="dashboard-wrapper">
    <button class="btn d-lg-none mobile-menu-toggle position-fixed start-0 m-3">
      <i class="fas fa-bars"></i>
    </button>
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
            <p class="user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Admin'); ?></p>
            <p class="user-role"><?php echo htmlspecialchars($_SESSION['cargo']); ?></p>
          </div>
          <div class="avatar"><?php echo substr(htmlspecialchars($_SESSION['nombre'] ?? 'A'), 0, 1); ?></div>
        </div>
      </header>
      <?php
      // Incluimos la conexión aquí para poder hacer las consultas
      require_once '../../conexion.php';

      // --- CONSULTAS PARA LOS KPIs ---

      // 1. Ingresos del Día
      $hoy_inicio = date('Y-m-d 00:00:00');
      $hoy_fin = date('Y-m-d 23:59:59');
      $result_ingresos_hoy = $conexion->query("SELECT SUM(monto_total) as total FROM reservas WHERE estado = 'Completa' AND fecha_salida BETWEEN '$hoy_inicio' AND '$hoy_fin'");
      $ingresos_hoy = $result_ingresos_hoy->fetch_assoc()['total'] ?? 0;

      // 2. Tasa de Ocupación General
      $result_total_hab = $conexion->query("SELECT COUNT(*) as total FROM habitaciones");
      $total_habitaciones = $result_total_hab->fetch_assoc()['total'] ?? 1; // Evitar división por cero
      $result_ocupadas = $conexion->query("SELECT COUNT(*) as ocupadas FROM habitaciones WHERE estado = 'Ocupada'");
      $habitaciones_ocupadas = $result_ocupadas->fetch_assoc()['ocupadas'] ?? 0;
      $tasa_ocupacion = ($total_habitaciones > 0) ? ($habitaciones_ocupadas / $total_habitaciones) * 100 : 0;

      // 3. Reservas Nuevas Hoy
      $result_reservas_hoy = $conexion->query("SELECT COUNT(*) as total FROM reservas WHERE fecha_reserva BETWEEN '$hoy_inicio' AND '$hoy_fin'");
      $reservas_nuevas_hoy = $result_reservas_hoy->fetch_assoc()['total'] ?? 0;

      // 4. Clientes Totales
      $result_clientes = $conexion->query("SELECT COUNT(*) as total FROM clientes");
      $clientes_totales = $result_clientes->fetch_assoc()['total'] ?? 0;

      // --- CONSULTAS PARA LA TABLA DE ÚLTIMAS RESERVAS ---
      $ultimas_reservas = $conexion->query(
        "SELECT r.id, c.nombres, c.apellidos, h.nombre as nombre_habitacion, r.fecha_entrada, r.monto_total, r.estado 
     FROM reservas r 
     JOIN clientes c ON r.cliente_dni = c.dni 
     JOIN habitaciones h ON r.habitacion_id = h.id 
     ORDER BY r.fecha_reserva DESC 
     LIMIT 5"
      )->fetch_all(MYSQLI_ASSOC);

      // --- CONSULTA PARA EL GRÁFICO (Ingresos de los últimos 7 días) ---
      $datos_grafico = [];
      $labels_grafico = [];
      for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $labels_grafico[] = date('d/m', strtotime($fecha)); // Formato '21/06'

        $fecha_inicio = $fecha . ' 00:00:00';
        $fecha_fin = $fecha . ' 23:59:59';
        $result = $conexion->query("SELECT SUM(monto_total) as total FROM reservas WHERE estado = 'Completa' AND fecha_salida BETWEEN '$fecha_inicio' AND '$fecha_fin'");
        $datos_grafico[] = $result->fetch_assoc()['total'] ?? 0;
      }

      $conexion->close();
      ?>

      <main id="contentArea">
        <!-- 1. Encabezado de la Sección -->
        <div class="mb-5">
          <h2 class="section-title"><i class="fas fa-chart-line"></i> Resumen del Sistema</h2>
          <p class="section-subtitle">Vista general del estado del hotel y rendimiento del negocio en tiempo real.</p>
        </div>

        <!-- 2. Tarjetas de KPIs -->
        <div class="row g-4 mb-5">
          <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="card-icon icon-reserva me-3"><i class="fas fa-dollar-sign"></i></div>
                <div>
                  <h6 class="card-subtitle text-muted mb-1">Ingresos de Hoy</h6>
                  <h4 class="card-title mb-0 fw-bold">S/ <?php echo number_format($ingresos_hoy, 2); ?></h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="card-icon icon-checkin me-3"><i class="fas fa-door-open"></i></div>
                <div>
                  <h6 class="card-subtitle text-muted mb-1">Ocupación Actual</h6>
                  <h4 class="card-title mb-0 fw-bold"><?php echo number_format($tasa_ocupacion, 1); ?>%</h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="card-icon icon-checkout me-3"><i class="fas fa-calendar-plus"></i></div>
                <div>
                  <h6 class="card-subtitle text-muted mb-1">Reservas Nuevas (Hoy)</h6>
                  <h4 class="card-title mb-0 fw-bold"><?php echo $reservas_nuevas_hoy; ?></h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="card-icon icon-clientes me-3"><i class="fas fa-users"></i></div>
                <div>
                  <h6 class="card-subtitle text-muted mb-1">Clientes Totales</h6>
                  <h4 class="card-title mb-0 fw-bold"><?php echo $clientes_totales; ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Contenedor para Gráfico y Tabla -->
        <div class="row g-4">
          <!-- Columna Izquierda: Gráfico de Ingresos -->
          <div class="col-lg-7">
            <div class="card shadow-sm">
              <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-chart-area me-2"></i>Ingresos de la Última Semana</h5>
              </div>
              <div class="card-body">
                <canvas id="graficoIngresos"></canvas>
              </div>
            </div>
          </div>

          <!-- Columna Derecha: Tabla de Últimas Reservas -->
          <div class="col-lg-5">
            <div class="card shadow-sm">
              <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Últimas 5 Reservas</h5>
              </div>
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <tbody>
                    <?php if (empty($ultimas_reservas)): ?>
                      <tr>
                        <td class="text-center p-4 text-muted">No hay reservas recientes.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($ultimas_reservas as $reserva): ?>
                        <tr>
                          <td class="p-3">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($reserva['nombres'] . ' ' . $reserva['apellidos']); ?></div>
                                <small class="text-muted">Hab. <?php echo htmlspecialchars($reserva['nombre_habitacion']); ?> - Entrada: <?php echo date('d/m/y', strtotime($reserva['fecha_entrada'])); ?></small>
                              </div>
                              <div class="text-end">
                                <div class="fw-bold text-success">S/ <?php echo number_format($reserva['monto_total'], 2); ?></div>
                                <span class="badge rounded-pill text-bg-<?php
                                                                        switch ($reserva['estado']) {
                                                                          case 'Completa':
                                                                            echo 'success';
                                                                            break;
                                                                          case 'Confirmada':
                                                                            echo 'primary';
                                                                            break;
                                                                          case 'Cancelada':
                                                                            echo 'danger';
                                                                            break;
                                                                          default:
                                                                            echo 'secondary';
                                                                        }
                                                                        ?>"><?php echo htmlspecialchars($reserva['estado']); ?></span>
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
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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

          contentArea.innerHTML = await response.text();
          contentArea.style.opacity = '1';

          Array.from(contentArea.querySelectorAll("script")).forEach(oldScript => {
            const newScript = document.createElement("script");
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
          });
        } catch (error) {
          console.error("Error al cargar módulo:", error);
          contentArea.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`;
        }
      }

      const urlParams = new URLSearchParams(window.location.search);
      const moduleToLoad = urlParams.get('load_module');
      if (moduleToLoad) {
        cargarContenido(`../../${moduleToLoad}.php`, document.querySelector(`.nav-link[onclick*="${moduleToLoad}"]`));
      }

      const mobileToggle = document.querySelector('.mobile-menu-toggle');
      const sidebar = document.querySelector('.sidebar');
      if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
      }

      // ======================================================================
      // ============== ¡NUEVA FUNCIÓN PARA REPORTES PROFESIONALES! ==============
      // ======================================================================
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

  <!-- ... tu script de cargarContenido ... -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- ========================================================== -->
  <!-- ======== SCRIPT PARA EL GRÁFICO DEL DASHBOARD ============ -->
  <!-- ========================================================== -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // ... (todo tu script existente de cargarContenido, etc. se queda como está) ...

      // Código para inicializar el gráfico de ingresos
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
              tension: 0.3,
              pointBackgroundColor: 'rgba(79, 70, 229, 1)',
              pointBorderColor: '#fff',
              pointHoverRadius: 7
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value, index, values) {
                    return 'S/ ' + value;
                  }
                }
              }
            },
            plugins: {
              legend: {
                display: false
              }
            }
          }
        });
      }
    });
  </script>
</body>

</html>