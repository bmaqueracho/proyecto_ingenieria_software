<?php
session_start(); // Siempre iniciar la sesión primero

// 1. Verificar si existe una sesión y el usuario está logueado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['cargo'])) {
    // Si no hay sesión, redirigir al login
    header("Location: ../autch/login.html");
    exit();
}

// 2. Verificar si el cargo del usuario es 'Administrador'
if ($_SESSION['cargo'] !== 'Administrador') {
    // Si no es Administrador, mostrar un mensaje de acceso denegado
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Acceso Denegado</title></head><body>";
    echo "<h1>Acceso Denegado</h1>";
    echo "<p>Usted no tiene los permisos necesarios para acceder a esta página.</p>";
    if (isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'Recepcionista') {
        echo "<p><a href='recepcionista_dashboard.html'>Ir al Panel de Recepcionista</a></p>";
    }
    echo "<p><a href='logout.php'>Cerrar Sesión</a></p>";
    echo "</body></html>";
    exit();
}

// Si llegamos aquí, el usuario es un Administrador y tiene una sesión activa.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Administrador</title>
  <!-- <link rel="stylesheet" href="estilos.css"> Futuro CSS -->
</head>
<body>

  <header>
    <h1>Panel de Administración del Hotel</h1>
  </header>
  <nav>
    <?php if (isset($_SESSION['nombre'])): ?>
        <span>Bienvenido, Administrador <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
    <?php else: ?>
        <span>Panel de Administrador</span>
    <?php endif; ?>
  </nav>
  <hr>

  <div>
    <div>
      <p>Bienvenido al panel de administración. Desde aquí puede gestionar la configuración y operativa clave del hotel.</p>
    </div>
    <hr>

    <div>
      <section>
        <h2>Gestión Central</h2>
        <ul>
          <li><a href="admin_gestion_usuarios.php">Gestión de Usuarios</a></li>
          <li><a href="admin_configuracion_hotel.php">Configuración General del Hotel</a></li>
        </ul>
      </section>
      <hr>

      <section>
        <h2>Gestión Operativa</h2>
        <ul>
          <li><a href="admin_gestion_habitaciones.php">Gestión de Tipos y Habitaciones</a></li>
          <li><a href="productos_gestion.php">Gestión de Productos</a></li> 
        </ul>
      </section>
      <hr>

      <section>
        <h2>Reportes y Análisis</h2>
        <ul>
          <li><a href="reportes_dashboard.html">Acceder al Módulo de Reportes</a></li>
          <li><a href="admin_auditoria.php">Logs y Auditoría del Sistema</a></li>
        </ul>
      </section>
      <hr>
      
      <section>
        <h2>Acceso Rápido a Recepción</h2>
        <ul>
            <li><a href="recepcionista_dashboard.php">Ir al Panel de Recepcionista</a></li>
        </ul>
      </section>
    </div>
    <hr>

    <div>
      <form action="logout.php" method="post">
        <button type="submit">Cerrar Sesión</button>
      </form>
    </div>

  </div>
  <hr>
  <footer>
      <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>

</body>
</html>