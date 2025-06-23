<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
  exit('Acceso denegado.');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recepción | Hotel Mediterraneo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    /* ... (Todo tu CSS profesional existente se mantiene igual) ... */
    :root {
      --fuente-titulos: 'Playfair Display', serif;
      --fuente-textos: 'Montserrat', sans-serif;

      --color-sidebar-bg: #111827;
      /* Negro azulado muy oscuro */
      --color-sidebar-link: #9ca3af;
      /* Gris claro para texto inactivo */
      --color-sidebar-link-hover: #ffffff;
      --color-sidebar-link-active-bg: #4f46e5;
      /* Púrpura/índigo vibrante */

      --color-content-bg: #f9fafb;
      /* Fondo principal casi blanco */
      --color-card-bg: #ffffff;

      --color-texto-principal: #1f2937;
      /* Negro suave */
      --color-texto-secundario: #6b7280;
      /* Gris medio */
      --color-borde: #e5e7eb;

      --color-exito: #10b981;
      --color-error: #ef4444;
      --color-aviso: #f59e0b;

      --radius-suave: 8px;
      --radius-grande: 12px;
      --sombra-sutil: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --sombra-media: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
      --sombra-grande: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
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

    /* 2. LAYOUT PRINCIPAL (Sidebar Fijo + Contenido) */
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

    /* 3. ESTILOS DE CONTENIDO DEL DASHBOARD INICIAL */
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
      border-color: var(--color-acento);
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

    .icon-reserva {
      background-color: rgba(52, 152, 219, 0.1);
      color: #3498db;
    }

    .icon-checkin {
      background-color: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }

    .icon-checkout {
      background-color: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
    }

    .icon-clientes {
      background-color: rgba(155, 89, 182, 0.1);
      color: #9b59b6;
    }

    .welcome-card:hover .card-icon {
      transform: scale(1.1) rotate(5deg);
    }

    /* 4. ESTILOS PARA EL CONTENIDO DINÁMICO (MÓDULOS) */
    #contentArea .section-title {
      font-family: var(--fuente-titulos);
      font-weight: 600;
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: var(--color-texto-principal);
    }

    #contentArea .section-title i {
      color: var(--color-sidebar-link-active-bg);
      margin-right: 1rem;
    }

    #contentArea .section-subtitle,
    #contentArea p.text-white-50 {
      color: var(--color-texto-secundario) !important;
      margin-bottom: 2.5rem;
      max-width: 700px;
    }

    #contentArea .card,
    #contentArea .action-card {
      background: var(--color-card-bg);
      border-radius: var(--radius-grande);
      border: 1px solid var(--color-borde);
      box-shadow: var(--sombra-sutil);
      color: var(--color-texto-principal);
      padding: 2.5rem;
    }

    #contentArea .card h3,
    #contentArea .action-card h3,
    #contentArea .card h4,
    #contentArea .action-card h4,
    #contentArea .card h5,
    #contentArea .action-card h5 {
      font-family: var(--fuente-textos);
      font-weight: 600;
      color: var(--color-texto-principal);
    }

    /* Formularios profesionales dentro de los módulos */
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
      border-color: var(--color-acento);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    /* Tablas elegantes dentro de los módulos */
    #contentArea .table-responsive {
      border: 1px solid var(--color-borde);
      border-radius: var(--radius-grande);
    }

    #contentArea .table {
      border: none;
      margin-bottom: 0;
      background-color: #fff;
    }

    #contentArea .table thead th {
      background-color: #f9fafb;
      border-bottom: 2px solid var(--color-borde);
      color: var(--color-texto-secundario);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
    }

    #contentArea .table td {
      border-top: 1px solid var(--color-borde);
      vertical-align: middle;
    }

    #contentArea .table-hover tbody tr:hover {
      background-color: #eff6ff;
    }

    #contentArea .table> :not(caption)>*>* {
      background-color: inherit;
    }

    /* Override Bootstrap */

    /* Botones y Badges dentro de los módulos */
    #contentArea .btn {
      border-radius: var(--radius-suave);
      font-weight: 600;
      padding: 0.6rem 1.2rem;
      transition: var(--transicion-suave);
    }

    #contentArea .btn-primary {
      background-color: var(--color-acento);
      border-color: var(--color-acento);
    }

    #contentArea .btn-primary:hover {
      background-color: var(--color-acento-hover);
      border-color: var(--color-acento-hover);
      transform: translateY(-2px);
    }

    #contentArea .badge {
      padding: 0.5em 0.75em;
      font-weight: 600;
      font-size: 0.75rem;
    }

    #contentArea .bg-success {
      background-color: var(--color-exito) !important;
    }

    #contentArea .bg-danger {
      background-color: var(--color-error) !important;
    }

    /* 5. RESPONSIVIDAD Y ANIMACIONES */
    .mobile-menu-toggle {
      z-index: 1100;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      background-color: var(--color-acento);
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

    .report-modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.6);
      animation: fadeIn 0.3s ease;
    }

    .report-modal-content {
      background-color: #fefefe;
      margin: 2% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 90%;
      height: 95%;
      max-width: 1200px;
      border-radius: var(--radius-grande);
      display: flex;
      flex-direction: column;
    }

    .report-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--color-borde);
    }

    .report-modal-header h4 {
      font-family: var(--fuente-titulos);
      margin: 0;
    }

    .report-modal-close {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .report-modal-body {
      flex-grow: 1;
      padding-top: 15px;
    }

    .report-modal-body iframe {
      width: 100%;
      height: 100%;
      border: none;
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
        <i class="fas fa-hotel"></i>
        <span>Hotel Mediterraneo</span>
      </div>
      <nav class="nav flex-column flex-grow-1">
        <a href="recepcionista_dashboard.php" class="nav-link active"><i class="fas fa-home fa-fw"></i><span>Inicio</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('reservas_content.php', this);"><i class="fas fa-calendar-check fa-fw"></i><span>Reservas</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php', this);"><i class="fas fa-users fa-fw"></i><span>Clientes</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../productos/productos_content.php', this);"><i class="fas fa-concierge-bell fa-fw"></i><span>Productos y Ventas</span></a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../reportes/reportes_content.php', this);"><i class="fas fa-chart-bar fa-fw"></i><span>Reportes</span></a>

        <?php
        // ==========================================================
        // ============== CÓDIGO PARA EL BOTÓN DE VOLVER ==============
        // ==========================================================
        // Comprobamos si el usuario es Administrador Y si viene del panel de admin.
        if (isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'Administrador' && isset($_GET['from_admin'])) {
          echo '<a href="admin_dashboard.php" class="nav-link mt-3" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b;">
            <i class="fas fa-user-shield fa-fw" style="color: #f59e0b;"></i>
            <span style="color: #f59e0b; font-weight: bold;">Volver a Panel Admin</span>
          </a>';
        }
        ?>

        <a href="../autch/logout.php" class="nav-link logout-item"><i class="fas fa-sign-out-alt fa-fw"></i><span>Cerrar Sesión</span></a>
      </nav>
    </aside>
    <div class="main-wrapper">
      <header class="main-header d-flex justify-content-between align-items-center">
        <div><!-- Espacio en blanco para empujar el contenido a la derecha --></div>
        <div class="d-flex align-items-center">
          <div class="text-end me-3">
            <p class="user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></p>
            <p class="user-role"><?php echo htmlspecialchars($_SESSION['cargo']); ?></p>
          </div>
          <div class="avatar"><?php echo substr(htmlspecialchars($_SESSION['nombre'] ?? 'R'), 0, 1); ?></div>
        </div>
      </header>
      <main id="contentArea">
        <section class="mb-5">
          <h2 class="section-title"><i class="fas fa-door-open"></i> Panel de Recepción</h2>
          <p class="section-subtitle">Gestión integral de operaciones hoteleras con información en tiempo real.</p>
          <div class="row g-4">
            <div class="col-lg-3 col-md-6"><a href="#" onclick="event.preventDefault(); cargarContenido('reservas_content.php');" class="welcome-card">
                <div class="card-icon icon-reserva"><i class="fas fa-plus-circle"></i></div>
                <h3>Nueva Reserva</h3>
                <p>Registrar nueva estadía</p>
              </a></div>
            <div class="col-lg-3 col-md-6"><a href="#" class="welcome-card">
                <div class="card-icon icon-checkin"><i class="fas fa-key"></i></div>
                <h3>Check-In</h3>
                <p>Registrar llegada</p>
              </a></div>
            <div class="col-lg-3 col-md-6"><a href="#" class="welcome-card">
                <div class="card-icon icon-checkout"><i class="fas fa-sign-out-alt"></i></div>
                <h3>Check-Out</h3>
                <p>Procesar salida</p>
              </a></div>
            <div class="col-lg-3 col-md-6"><a href="#" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php');" class="welcome-card">
                <div class="card-icon icon-clientes"><i class="fas fa-user-edit"></i></div>
                <h3>Registrar Cliente</h3>
                <p>Añadir nuevo huésped</p>
              </a></div>
          </div>
        </section>
      </main>
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
            if (!response.ok) throw new Error(`Error ${response.status}: No se pudo cargar el recurso.`);

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
</body>

</html>