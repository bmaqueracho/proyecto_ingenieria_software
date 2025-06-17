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
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --color-primario: rgba(107, 79, 61, 0.74);
      --color-accento: #E9C46A;
      --color-fondo-oscuro: #2A3D45;
      --color-texto-claro: #f8f9fa;
      --fuente-titulos: 'Cormorant Garamond', serif;
      --fuente-texto: 'Montserrat', sans-serif;
    }

    body {
      background: url('https://i.pinimg.com/736x/cf/fc/06/cffc0687363cf919da1e1b7ff3ad0bc6.jpg') no-repeat center center fixed;
      background-size: cover;
      color: var(--color-texto-claro);
      font-family: var(--fuente-texto);
      min-height: 100vh;
    }

    .sidebar {
      width: 280px;
      background: rgba(42, 61, 69, 0.7);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border-right: 1px solid rgba(255, 255, 255, 0.18);
    }

    .sidebar .nav-link {
      color: var(--color-texto-claro);
      border-radius: 8px;
      margin-bottom: 0.5rem;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      background: rgba(233, 197, 106, 0);
    }

    .sidebar .nav-link i {
      width: 24px;
      text-align: center;
      margin-right: 1rem;
    }

    .logout-item .nav-link {
      color: #ff6b6b !important;
    }

    .main-content {
      background: rgba(250, 237, 205, 0.3);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .section-title {
      font-family: var(--fuente-titulos);
      position: relative;
      padding-bottom: 10px;
    }

    .section-title:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 2px;
      background: var(--color-accento);
    }

    .section-title i {
      color: var(--color-accento);
      margin-right: 0.75rem;
    }

    .action-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      border: 1px solid transparent;
      transition: all 0.3s ease;
      height: 100%;
    }

    .action-card:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: var(--color-accento);
      transform: translateY(-5px);
    }

    .card-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.5rem;
    }

    .avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background-color: var(--color-accento);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--color-fondo-oscuro);
      font-weight: bold;
      font-size: 1.2rem;
    }

    .user-name {
      font-family: var(--fuente-titulos);
    }

    .user-role {
      color: var(--color-accento);
      font-size: 0.85rem;
    }

    .admin-access-btn {
      background: rgba(233, 196, 106, 0.2);
      color: var(--color-accento);
      border: 1px solid var(--color-accento);
    }

    .admin-access-btn:hover {
      background: rgba(233, 196, 106, 0.3);
    }

    .loading-overlay {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 300px;
    }

    .loader {
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top: 4px solid var(--color-accento);
      width: 40px;
      height: 40px;
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

    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        z-index: 1000;
        left: -280px;
        top: 0;
        height: 100vh;
        transition: all 0.3s ease;
      }

      .sidebar.active {
        left: 0;
      }
    }
  </style>
</head>

<body class="luxury-hotel-dashboard">
  <!-- Menú Hamburguesa para móvil -->
  <button class="btn btn-warning d-lg-none mobile-menu-toggle position-fixed start-0 m-3" style="z-index: 1100;">
    <i class="fas fa-bars"></i>
  </button>

  <div class="d-flex">
    <!-- Sidebar -->
    <aside class="sidebar p-3 d-flex flex-column">
      <div class="sidebar-header pb-3 mb-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
          <i class="fas fa-hotel me-3" style="color: var(--color-accento); font-size: 1.8rem;"></i>
          <span class="fs-4" style="font-family: var(--fuente-titulos);">Hotel Mediterraneo</span>
        </div>
      </div>

      <nav class="nav flex-column">
        <a href="recepcionista_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'recepcionista_dashboard.php' ? 'active' : ''; ?>">
          <i class="fas fa-home"></i>
          <span>Inicio</span>
        </a>
        <a href="#" class="nav-link" onclick="cargarModuloReservas()">
          <i class="fas fa-calendar-check"></i>
          <span>Reservas</span>
        </a>
        <a href="../otros/cliente.html" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == '../otros/cliente.html' ? 'active' : ''; ?>">
          <i class="fas fa-users"></i>
          <span>Clientes</span>
        </a>
        <a href="../productos/productos_dashboard.html" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == '../productos/productos_dashboard.html' ? 'active' : ''; ?>">
          <i class="fas fa-concierge-bell"></i>
          <span>Productos y Ventas</span>
        </a>
        <a href="reportes_dashboard.html" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes_dashboard.html' ? 'active' : ''; ?>">
          <i class="fas fa-chart-bar"></i>
          <span>Reportes</span>
        </a>
        <a href="../autch/logout.php" class="nav-link logout-item mt-auto">
          <i class="fas fa-sign-out-alt"></i>
          <span>Cerrar Sesión</span>
        </a>
      </nav>
    </aside>

    <!-- Contenido Principal -->
    <main class="flex-grow-1 p-3 main-content">
      <!-- Barra Superior -->
      <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
          <div class="avatar me-3">
            <?php echo substr(htmlspecialchars($_SESSION['nombre'] ?? 'U'), 0, 1); ?>
          </div>
          <div>
            <h2 class="mb-0">Bienvenid<?php echo ($_SESSION['genero'] ?? 'a') === 'Masculino' ? 'o' : 'a'; ?>,</h2>
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

      <!-- Contenido Dinámico -->
      <div id="contentArea">
        <section class="mb-4">
          <h2 class="section-title">
            <i class="fas fa-door-open"></i> Panel de Recepción
          </h2>
          <p class="text-white-50 mb-4">Gestión integral de operaciones hoteleras con información en tiempo real</p>

          <div class="row g-3">
            <div class="col-md-3">
              <a href="#nueva-reserva" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(233, 196, 106, 0.2);">
                  <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Nueva Reserva</h3>
                <p class="text-white-50 mb-0">Registrar nueva estadía</p>
              </a>
            </div>

            <div class="col-md-3">
              <a href="#checkin" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(106, 168, 79, 0.2);">
                  <i class="fas fa-key"></i>
                </div>
                <h3>Check-In</h3>
                <p class="text-white-50 mb-0">Registrar llegada</p>
              </a>
            </div>

            <div class="col-md-3">
              <a href="#checkout" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(214, 93, 77, 0.2);">
                  <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3>Check-Out</h3>
                <p class="text-white-50 mb-0">Procesar salida</p>
              </a>
            </div>

            <div class="col-md-3">
              <a href="#clientes" class="card action-card text-decoration-none text-white text-center p-4">
                <div class="card-icon" style="background-color: rgba(108, 117, 125, 0.2);">
                  <i class="fas fa-user-edit"></i>
                </div>
                <h3>Registrar Cliente</h3>
                <p class="text-white-50 mb-0">Nuevo huésped</p>
              </a>
            </div>
          </div>
        </section>

        <section>
          <h2 class="section-title">
            <i class="fas fa-chart-line"></i> Estadísticas Rápidas
          </h2>

          <div class="row g-3">
            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">24</div>
                <div class="stat-label">Check-Ins Hoy</div>
                <div class="stat-trend text-success small mt-2">
                  <i class="fas fa-arrow-up"></i> 12%
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">18</div>
                <div class="stat-label">Check-Outs Hoy</div>
                <div class="stat-trend text-danger small mt-2">
                  <i class="fas fa-arrow-down"></i> 5%
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">92%</div>
                <div class="stat-label">Ocupación</div>
                <div class="stat-trend text-success small mt-2">
                  <i class="fas fa-arrow-up"></i> 8%
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card action-card text-center p-4">
                <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">5</div>
                <div class="stat-label">VIP Hoy</div>
                <div class="stat-trend text-secondary small mt-2">
                  <i class="fas fa-equals"></i>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle sidebar en móviles
    document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('active');
    });

    function cargarModuloReservas() {
      // Mostrar loader
      document.getElementById('contentArea').innerHTML = `
        <div class="loading-overlay">
          <div class="loader"></div>
        </div>
      `;

      // Simular carga (reemplaza esto con tu AJAX real)
      setTimeout(() => {
        document.getElementById('contentArea').innerHTML = `
          <section class="mb-4">
            <h2 class="section-title">
              <i class="fas fa-calendar-check"></i> Gestión de Reservas
            </h2>
            
            <div class="row g-3">
              <div class="col-md-4">
                <a href="../reservas/nueva_reserva.php?accion_limpiar=1" class="card action-card text-decoration-none text-white text-center p-4">
                  <div class="card-icon" style="background-color: rgba(233, 196, 106, 0.2);">
                    <i class="fas fa-plus-circle"></i>
                  </div>
                  <h3>Nueva Reserva</h3>
                  <p class="text-white-50 mb-0">Crear nueva reserva para huéspedes</p>
                </a>
              </div>
              
              <div class="col-md-4">
                <a href="../reservas/gestionar_reservas.php" class="card action-card text-decoration-none text-white text-center p-4">
                  <div class="card-icon" style="background-color: rgba(106, 168, 79, 0.2);">
                    <i class="fas fa-tasks"></i>
                  </div>
                  <h3>Gestionar Reservas</h3>
                  <p class="text-white-50 mb-0">Consultar y modificar reservas existentes</p>
                </a>
              </div>
            </div>
          </section>
          
          <section>
            <h3 class="section-title">
              <i class="fas fa-chart-pie"></i> Estadísticas
            </h3>
            
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card action-card text-center p-4">
                  <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">12</div>
                  <div class="stat-label">Reservas Hoy</div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card action-card text-center p-4">
                  <div class="stat-value fs-1 fw-bold" style="color: var(--color-accento);">85%</div>
                  <div class="stat-label">Ocupación</div>
                </div>
              </div>
            </div>
          </section>
        `;
      }, 500);
    }
  </script>
</body>

</html>