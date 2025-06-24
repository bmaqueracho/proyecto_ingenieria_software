<?php
// ARCHIVO DE VISTA: modulos/productos/productos_content.php
session_start();
?>

<section id="gestion-productos-ventas">
  <!-- Título principal -->
  <h2 class="section-title">
    <i class="fas fa-boxes"></i> Gestión de Productos y Ventas
  </h2>

  <!-- Subtítulo descriptivo -->
  <p class="section-subtitle">
    Desde aquí puede administrar el inventario de productos o registrar nuevas ventas a los huéspedes del hotel.
  </p>

  <!-- Tarjetas de navegación del módulo -->
  <div class="row g-4">
    <!-- Card: Gestión de Productos -->
    <div class="col-lg-4 col-md-6">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/productos_gestion.php');" class="welcome-card text-decoration-none">
        <div class="card-icon" style="background-color: rgba(243, 156, 18, 0.1); color: #f39c12;">
          <i class="fas fa-cogs"></i>
        </div>
        <h3>Administrar Productos</h3>
        <p>Ver stock, añadir y ajustar inventario.</p>
      </a>
    </div>

    <!-- Card: Registrar Venta -->
    <div class="col-lg-4 col-md-6">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/productos_venta.php');" class="welcome-card text-decoration-none">
        <div class="card-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;">
          <i class="fas fa-cash-register"></i>
        </div>
        <h3>Registrar Venta</h3>
        <p>Venta de productos y servicios a clientes.</p>
      </a>
    </div>
  </div>
</section>
