


<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
  header("Location: ../../index.php");
  exit('Acceso denegado.');
}

// LÓGICA PARA EL NUEVO DASHBOARD OPERATIVO
require_once '../../conexion.php';
$hoy = date('Y-m-d');

// KPI 1: Check-ins para hoy
$stmt_checkins = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE fecha_entrada = ? AND estado = 'Confirmada'");
$stmt_checkins->bind_param("s", $hoy);
$stmt_checkins->execute();
$checkins_hoy = $stmt_checkins->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_checkins->close();

// KPI 2: Check-outs para hoy
$stmt_checkouts = $conexion->prepare("SELECT COUNT(*) as total FROM reservas WHERE fecha_salida = ? AND estado IN ('Confirmada', 'Completa')");
$stmt_checkouts->bind_param("s", $hoy);
$stmt_checkouts->execute();
$checkouts_hoy = $stmt_checkouts->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_checkouts->close();

// KPI 3: Habitaciones Disponibles
$result_disponibles = $conexion->query("SELECT COUNT(*) as total FROM habitaciones WHERE estado = 'Disponible'");
$habitaciones_disponibles = $result_disponibles->fetch_assoc()['total'] ?? 0;

// Feed de Próximas Llegadas
$stmt_llegadas = $conexion->prepare(
  "SELECT c.nombres, c.apellidos, h.nombre as nombre_habitacion, r.fecha_entrada 
   FROM reservas r
   JOIN clientes c ON r.cliente_dni = c.dni
   JOIN habitaciones h ON r.habitacion_id = h.id
   WHERE r.fecha_entrada >= ? AND r.estado = 'Confirmada'
   ORDER BY r.fecha_entrada ASC, r.id ASC
   LIMIT 3"
);
$stmt_llegadas->bind_param("s", $hoy);
$stmt_llegadas->execute();
$proximas_llegadas = $stmt_llegadas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_llegadas->close();

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recepción | Hotel Mediterraneo</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ... (Todo tu CSS profesional existente se mantiene igual) ... */
    :root {
      --fuente-titulos: 'Playfair Display', serif;
      --fuente-textos: 'Montserrat', sans-serif;
      --color-sidebar-bg: #111827;
      --color-sidebar-link: #9ca3af;
      --color-sidebar-link-hover: #ffffff;
      --color-acento: #4f46e5; /* Color de acento unificado */
      --color-sidebar-link-active-bg: var(--color-acento); /* Usar el color de acento */
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
      --sombra-grande: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
      --transicion-suave: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background-color: var(--color-content-bg);
      color: var(--color-texto-principal);
      font-family: var(--fuente-textos);
      min-height: 100vh;
      /* Eliminado: overflow-x: hidden; para permitir scroll horizontal si un elemento lo requiere */
    }

    /* 2. LAYOUT PRINCIPAL (Tus estilos originales para la estructura) */
    .dashboard-wrapper { display: flex; }
    .sidebar { width: 260px; background: var(--color-sidebar-bg); display: flex; flex-direction: column; flex-shrink: 0; position: fixed; height: 100%; transition: transform 0.3s ease; z-index: 1050; }
    .sidebar-header { padding: 1.5rem; font-family: var(--fuente-titulos); font-size: 1.5rem; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    .sidebar-header i { color: var(--color-acento); }
    .sidebar nav { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; }
    .sidebar .nav-link { color: var(--color-sidebar-link); border-radius: var(--radius-suave); margin-bottom: 0.25rem; transition: var(--transicion-suave); padding: 0.75rem 1.25rem; display: flex; align-items: center; gap: 15px; text-decoration: none; font-weight: 500; }
    .sidebar .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: var(--color-sidebar-link-hover); }
    .sidebar .nav-link.active { background: var(--color-sidebar-link-active-bg); color: #fff; font-weight: 600; }
    .sidebar .logout-item { margin-top: auto; }
    .main-wrapper { flex-grow: 1; padding: 2.5rem; margin-left: 260px; transition: margin-left 0.3s ease; animation: fadeIn 0.6s ease-out; }
    .main-header { padding-bottom: 1.5rem; margin-bottom: 2.5rem; border-bottom: 1px solid var(--color-borde); }
    .main-header .avatar { background: linear-gradient(45deg, var(--color-acento), #6366f1); color: #fff; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 600; }
    .main-header .user-name { font-size: 1.1rem; font-weight: 600; margin: 0; }
    .main-header .user-role { font-size: 0.85rem; color: var(--color-texto-secundario); margin: 0; }

    /* 3. ESTILOS PARA TU CONTENIDO DINÁMICO (Tus estilos originales preservados) */
    /* Aquí se mantienen tus estilos para forms, tables, etc., dentro de #contentArea */
    #contentArea { transition: opacity 0.3s ease; }
    #contentArea .form-label { font-weight: 500; color: var(--color-texto-secundario); font-size: 0.9rem; }
    #contentArea .form-control, #contentArea .form-select { background-color: var(--color-content-bg); border: 1px solid var(--color-borde); border-radius: var(--radius-suave); padding: 0.75rem 1rem; transition: var(--transicion-suave); }
    #contentArea .form-control:focus, #contentArea .form-select:focus { background-color: #fff; border-color: var(--color-acento); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    #contentArea .table-responsive { border: 1px solid var(--color-borde); border-radius: var(--radius-grande); }
    #contentArea .table { border: none; margin-bottom: 0; background-color: #fff; }
    #contentArea .table thead th { background-color: #f9fafb; border-bottom: 2px solid var(--color-borde); color: var(--color-texto-secundario); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
    #contentArea .table td { border-top: 1px solid var(--color-borde); vertical-align: middle; }
    #contentArea .table-hover tbody tr:hover { background-color: #eff6ff; }
    #contentArea .table> :not(caption)>*>* { background-color: inherit; }
    #contentArea .btn { border-radius: var(--radius-suave); font-weight: 600; padding: 0.6rem 1.2rem; transition: var(--transicion-suave); }
    #contentArea .btn-primary { background-color: var(--color-acento); border-color: var(--color-acento); }
    
    /* 4. NUEVOS ESTILOS - SOLO PARA EL DASHBOARD DE INICIO (LO QUE TE GUSTÓ) */
    .section-title { font-family: var(--fuente-titulos); font-weight: 600; font-size: 2rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 1rem; }
    .section-title i { color: var(--color-acento); }
    .section-subtitle { color: var(--color-texto-secundario); margin-bottom: 2.5rem; max-width: 600px; }
    
    .kpi-widget { background: var(--color-card-bg); border-radius: var(--radius-grande); padding: 1.5rem; display: flex; align-items: center; gap: 1rem; border: 1px solid var(--color-borde); box-shadow: var(--sombra-sutil); transition: var(--transicion-suave); }
    .kpi-widget:hover { transform: translateY(-5px); box-shadow: var(--sombra-media); border-color: var(--color-acento); }
    .kpi-widget .icon { font-size: 1.75rem; width: 50px; height: 50px; display: grid; place-items: center; border-radius: 50%; }
    .kpi-widget .text .value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .kpi-widget .text .label { font-size: 0.9rem; color: var(--color-texto-secundario); }
    .icon-checkin { color: #2ecc71; background-color: rgba(46, 204, 113, 0.1); }
    .icon-checkout { color: #e74c3c; background-color: rgba(231, 76, 60, 0.1); }
    .icon-available { color: #3498db; background-color: rgba(52, 152, 219, 0.1); }

    .action-card { display: flex; flex-direction: column; justify-content: space-between; text-decoration: none; color: var(--color-texto-principal); background: var(--color-card-bg); border: 1px solid var(--color-borde); border-radius: var(--radius-grande); padding: 1.5rem; box-shadow: var(--sombra-sutil); transition: var(--transicion-suave); height: 100%; }
    .action-card:hover { transform: translateY(-5px); box-shadow: var(--sombra-media); border-color: var(--color-acento); }
    .action-card .icon { font-size: 2rem; color: var(--color-acento); margin-bottom: 1rem; }
    .action-card h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
    .action-card p { color: var(--color-texto-secundario); font-size: 0.9rem; flex-grow: 1; }
    .action-card .action-link { font-weight: 600; color: var(--color-acento); text-decoration: none; }
    .action-card .action-link i { margin-left: 0.25rem; transition: transform 0.2s ease; }
    .action-card:hover .action-link i { transform: translateX(4px); }

    .feed-card { background: var(--color-card-bg); border-radius: var(--radius-grande); border: 1px solid var(--color-borde); box-shadow: var(--sombra-sutil); height: 100%; }
    .feed-card .card-header { font-weight: 600; background: transparent; padding: 1rem 1.5rem; border-bottom: 1px solid var(--color-borde); }
    .feed-list .feed-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--color-borde); transition: background-color 0.2s ease; }
    .feed-list .feed-item:last-child { border-bottom: none; }
    .feed-list .feed-item:hover { background-color: #f9fafb; }
    .feed-item .avatar-initials { width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-acento); color: white; display: grid; place-items: center; font-weight: 600; flex-shrink: 0; }
    .feed-item .info .name { font-weight: 600; }
    .feed-item .info .details { font-size: 0.85rem; color: var(--color-texto-secundario); }

    /* 5. RESPONSIVIDAD Y ANIMACIONES (Incluye el nuevo botón de menú) */
    .mobile-menu-toggle { z-index: 1100; border-radius: 50%; width: 50px; height: 50px; background-color: var(--color-acento); color: white; border: none; position: fixed; bottom: 20px; right: 20px; box-shadow: var(--sombra-grande); }
    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); box-shadow: 0 0 50px rgba(0, 0, 0, 0.3); }
      .main-wrapper { margin-left: 0; padding: 1.5rem; }
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .loading-overlay { display: flex; justify-content: center; align-items: center; height: 400px; }
    .loader { border: 5px solid #f3f3f3; border-top: 5px solid var(--color-acento); border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

  </style>
</head>

<body>
  <div class="dashboard-wrapper">
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
      <header class="main-header d-flex justify-content-end align-items-center">
        <div class="d-flex align-items-center">
          <div class="text-end me-3">
            <p class="user-name mb-0"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></p>
            <p class="user-role mb-0"><?php echo htmlspecialchars($_SESSION['cargo']); ?></p>
          </div>
          <div class="avatar"><?php echo substr(htmlspecialchars($_SESSION['nombre'] ?? 'R'), 0, 1); ?></div>
        </div>
      </header>
      
      <main id="contentArea">
        <div class="container-fluid px-0">
          <section>
            <h1 class="section-title"><i class="fas fa-door-open"></i> Panel de Recepción</h1>
            <p class="section-subtitle">Centro de operaciones para la gestión diaria del hotel.</p>
          </section>

          <!-- KPIs Operativos -->
          <section class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
              <div class="kpi-widget">
                <div class="icon icon-checkin"><i class="fas fa-right-to-bracket"></i></div>
                <div class="text">
                  <div class="value"><?= $checkins_hoy ?></div>
                  <div class="label">Check-ins para Hoy</div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="kpi-widget">
                <div class="icon icon-checkout"><i class="fas fa-right-from-bracket"></i></div>
                <div class="text">
                  <div class="value"><?= $checkouts_hoy ?></div>
                  <div class="label">Check-outs para Hoy</div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-12">
              <div class="kpi-widget">
                <div class="icon icon-available"><i class="fas fa-bed"></i></div>
                <div class="text">
                  <div class="value"><?= $habitaciones_disponibles ?></div>
                  <div class="label">Habitaciones Disponibles</div>
                </div>
              </div>
            </div>
          </section>

          <!-- Acciones Principales y Feed de Actividad -->
          <section class="row g-4">
            <div class="col-lg-8">
                <div class="row g-4 h-100">
                    <div class="col-md-6">
                        <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/nueva_reserva.php', this);" class="action-card">
                            <div>
                                <div class="icon"><i class="fas fa-calendar-plus"></i></div>
                                <h3>Nueva Reserva</h3>
                                <p>Crea una nueva reserva para un cliente existente o nuevo.</p>
                            </div>
                            <span class="action-link">Acceder <i class="fas fa-arrow-right"></i></span>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="#" onclick="event.preventDefault(); cargarContenido('../clientes/clientes_content.php', this);" class="action-card">
                            <div>
                                <div class="icon"><i class="fas fa-user-plus"></i></div>
                                <h3>Gestionar Clientes</h3>
                                <p>Busca, registra o actualiza la información de los huéspedes.</p>
                            </div>
                            <span class="action-link">Acceder <i class="fas fa-arrow-right"></i></span>
                        </a>
                    </div>
                </div>
            </div>

            <aside class="col-lg-4">
              <div class="feed-card">
                <div class="card-header"><i class="fas fa-bell me-2"></i>Próximas Llegadas</div>
                <div class="list-group list-group-flush feed-list">
                  <?php if (empty($proximas_llegadas)): ?>
                    <div class="feed-item text-muted justify-content-center">No hay llegadas programadas.</div>
                  <?php else: ?>
                    <?php foreach ($proximas_llegadas as $llegada): ?>
                      <div class="feed-item">
                        <div class="avatar-initials"><?= htmlspecialchars(substr($llegada['nombres'], 0, 1) . substr($llegada['apellidos'], 0, 1)) ?></div>
                        <div class="info">
                          <div class="name"><?= htmlspecialchars($llegada['nombres'] . ' ' . $llegada['apellidos']) ?></div>
                          <div class="details">Hab. <?= htmlspecialchars($llegada['nombre_habitacion']) ?> • <?= (date('Y-m-d', strtotime($llegada['fecha_entrada'])) == $hoy) ? 'Hoy' : 'el ' . date('d/m', strtotime($llegada['fecha_entrada'])) ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </aside>
          </section>
        </div>
      </main>
    </div>
  </div>

  <button class="btn d-lg-none mobile-menu-toggle">
    <i class="fas fa-bars"></i>
  </button>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const contentArea = document.getElementById('contentArea');
      const loaderHTML = `<div class="loading-overlay"><div class="loader"></div></div>`;

      window.cargarContenido = async function(pathModulo, clickedLink = null) {
        if (clickedLink) {
          document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
          clickedLink.classList.add('active');
        } else {
            // Si no se pasa un link, se asegura que el de "Inicio" no esté activo si se carga un módulo
            document.querySelector('.sidebar .nav-link.active')?.classList.remove('active');
        }
        
        contentArea.style.opacity = '0.5';
        contentArea.innerHTML = loaderHTML;

        try {
          const response = await fetch(pathModulo);
          if (!response.ok) throw new Error(`Error ${response.status}: No se pudo cargar el recurso.`);
          const html = await response.text();
          
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
          contentArea.innerHTML = `<div class="alert alert-danger m-4"><strong>Error:</strong> ${error.message}</div>`;
          contentArea.style.opacity = '1';
        }
      }

      const mobileToggle = document.querySelector('.mobile-menu-toggle');
      const sidebar = document.querySelector('.sidebar');
      if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
      }

      // Función de reportes (sin cambios)
      window.generarReporteConIframe = function(form) {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        const urlReporte = `../reportes/generar_reporte.php?${params}`;
        const iframeExistente = document.getElementById('iframe-impresion-oculto');
        if (iframeExistente) iframeExistente.remove();
        
        const iframe = document.createElement('iframe');
        iframe.id = 'iframe-impresion-oculto';
        iframe.style.display = 'none';
        iframe.src = urlReporte;
        document.body.appendChild(iframe);

        iframe.onload = function() {
          try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
          } catch (e) {
            console.error("Error al intentar imprimir el reporte:", e);
            alert("No se pudo abrir el diálogo de impresión.");
          }
          setTimeout(() => document.body.removeChild(iframe), 1000);
        };
        iframe.onerror = () => {
          alert("Error: No se pudo cargar el contenido del reporte.");
          document.body.removeChild(iframe);
        }
      }
    });
  </script>
</body>
</html>