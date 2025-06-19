<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['cargo'])) {
  header("Location: ../autch/login.html");
  exit();
}

if ($_SESSION['cargo'] !== 'Recepcionista' && $_SESSION['cargo'] !== 'Administrador') {
  echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Acceso Denegado</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='bg-dark text-white'>";
  echo "<div class='container d-flex justify-content-center align-items-center vh-100'>";
  echo "<div class='text-center p-5 rounded-3' style='background-color: rgba(0,0,0,0.7)'>";
  echo "<h1 class='mb-4'>Acceso Denegado</h1>";
  echo "<p class='mb-4'>No tiene permisos para esta área</p>";
  echo "<a href='../autch/logout.php' class='btn btn-warning'>Cerrar Sesión</a></div></div></body></html>";
  exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Recepción | Hotel Premium</title>
  <!-- Dependencias CSS (Rutas correctas según tu estructura) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Tu CSS Personalizado (Ruta correcta) -->
  <link rel="stylesheet" href="../../css/dashboard.css">

</head>

<body class="luxury-hotel-dashboard">
  <!-- Menú Hamburguesa para móvil (Tu código intacto) -->
  <button class="btn btn-warning d-lg-none mobile-menu-toggle position-fixed start-0 m-3" style="z-index: 1100;">
    <i class="fas fa-bars"></i>
  </button>

  <div class="d-flex">
    <!-- Sidebar (Tu código intacto) -->
    <aside class="sidebar p-3 d-flex flex-column">
      <div class="sidebar-header pb-3 mb-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
          <i class="fas fa-hotel me-3" style="color: var(--color-accento); font-size: 1.8rem;"></i>
          <span class="fs-4" style="font-family: var(--fuente-titulos);">Hotel Mediterraneo</span>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- ============== ÚNICO CAMBIO DE HTML ESTÁ AQUÍ DENTRO ============== -->
      <!-- ================================================================= -->
      <nav class="nav flex-column">
        <!-- Link de Inicio (sin cambios) -->
        <a href="recepcionista_dashboard.php" class="nav-link active">
          <i class="fas fa-home"></i>
          <span>Inicio</span>
        </a>

        <!-- ¡CAMBIO 1: El link de Reservas ahora llama a JavaScript! -->
        <!-- La ruta es relativa a este archivo, por eso es simple. -->
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('reservas_content.php', this);">
          <i class="fas fa-calendar-check"></i>
          <span>Reservas</span>
        </a>

        <!-- El resto de links se dejan como estaban hasta que adaptemos esos módulos -->
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php', this);">
          <i class="fas fa-users"></i>
          <span>Clientes</span>
        </a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../productos/productos_content.php', this);">
          <i class="fas fa-concierge-bell"></i>
          <span>Productos y Ventas</span>
        </a>
        <a href="#" class="nav-link" onclick="event.preventDefault(); cargarContenido('../reportes/reportes_content.php', this);">
          <i class="fas fa-chart-bar"></i>
          <span>Reportes</span>
        </a>

        <!-- Link de Cerrar Sesión (sin cambios) -->
        <a href="../autch/logout.php" class="nav-link logout-item mt-auto">
          <i class="fas fa-sign-out-alt"></i>
          <span>Cerrar Sesión</span>
        </a>
      </nav>
      <!-- ================================================================= -->
      <!-- ======================= FIN DE LA SECCIÓN MODIFICADA ======================= -->
      <!-- ================================================================= -->

    </aside>

    <!-- Contenido Principal (Tu código 100% intacto) -->
    <main class="flex-grow-1 p-3 main-content">
      <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
          <div class="avatar me-3">
            <?php echo substr(htmlspecialchars($_SESSION['nombre'] ?? 'U'), 0, 1); ?>
          </div>
          <div>
            <h2 class="mb-0">Bienvenido/a,</h2>
            <p class="mb-0 user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></p>
            <small class="user-role"><?php echo htmlspecialchars($_SESSION['cargo']); ?></small>
          </div>
        </div>
        <?php if ($_SESSION['cargo'] === 'Administrador'): ?>
          <a href="admin_dashboard.php" class="btn admin-access-btn">
            <i class="fas fa-user-shield me-2"></i> Panel Admin
          </a>
        <?php endif; ?>
      </header>

      <div id="contentArea">
        <section class="mb-4">
          <h2 class="section-title">
            <i class="fas fa-door-open"></i> Panel de Recepción
          </h2>
          <p class="text-white-50 mb-4">Gestión integral de operaciones hoteleras con información en tiempo real</p>
          <div class="row g-3">
            <div class="col-md-3"><a href="#nueva-reserva" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(233, 196, 106, 0.2);"><i class="fas fa-plus-circle"></i></div>
                <h3>Nueva Reserva</h3>
                <p class="text-white-50 mb-0">Registrar nueva estadía</p>
              </a></div>
            <div class="col-md-3"><a href="#checkin" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(106, 168, 79, 0.2);"><i class="fas fa-key"></i></div>
                <h3>Check-In</h3>
                <p class="text-white-50 mb-0">Registrar llegada</p>
              </a></div>
            <div class="col-md-3"><a href="#checkout" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(214, 93, 77, 0.2);"><i class="fas fa-sign-out-alt"></i></div>
                <h3>Check-Out</h3>
                <p class="text-white-50 mb-0">Procesar salida</p>
              </a></div>
            <div class="col-md-3"><a href="#clientes" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(108, 117, 125, 0.2);"><i class="fas fa-user-edit"></i></div>
                <h3>Registrar Cliente</h3>
                <p class="text-white-50 mb-0">Nuevo huésped</p>
              </a></div>
          </div>
        </section>
        <section>
          <h2 class="section-title"><i class="fas fa-chart-line"></i> Estadísticas Rápidas</h2>
          <div class="row g-3">
            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">24</div>
                <div class="stat-label">Check-Ins Hoy</div>
                <div class="stat-trend text-success small mt-2"><i class="fas fa-arrow-up"></i> 12%</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">18</div>
                <div class="stat-label">Check-Outs Hoy</div>
                <div class="stat-trend text-danger small mt-2"><i class="fas fa-arrow-down"></i> 5%</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">92%</div>
                <div class="stat-label">Ocupación</div>
                <div class="stat-trend text-success small mt-2"><i class="fas fa-arrow-up"></i> 8%</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">5</div>
                <div class="stat-label">VIP Hoy</div>
                <div class="stat-trend text-secondary small mt-2"><i class="fas fa-equals"></i></div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Dependencias JS (Ruta correcta) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../js/dashboard.js"></script>

  <!-- CAMBIO 2: Este pequeño script es nuevo y se necesita para la recarga automática -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const moduleToLoad = urlParams.get('load_module');

      if (moduleToLoad) {
        // moduleToLoad llega como, por ejemplo: 'modulos/reservas/gestionar_reservas'
        // La ruta relativa desde este dashboard para llegar a la raíz es '../../'
        const fullModulePath = `../../${moduleToLoad}.php`;

        // Buscamos un link en el sidebar que apunte al módulo principal para activarlo
        const linkSelector = `.sidebar .nav-link[onclick*="reservas_content"]`;
        const targetLink = document.querySelector(linkSelector);

        // Llamamos a la función para cargar el contenido
        cargarContenido(fullModulePath, targetLink);
      }
    });
  </script>

</body>

</html>