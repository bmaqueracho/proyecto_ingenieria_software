<?php
// ARCHIVO DE VISTA. Menú principal del módulo.
session_start();
// No necesita conexión a BD. Es solo HTML estático.
?>
<section id="gestion-productos-ventas">
  <h2 class="section-title"><i class="fas fa-boxes"></i> Gestión de Productos y Ventas</h2>
  <p class="text-white-50 mb-4">Administra el inventario de productos o registra ventas a huéspedes.</p>
  <div class="row g-3">
    <div class="col-md-6">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/productos_gestion.php');" class="card action-card text-decoration-none text-white text-center p-4">
        <div class="card-icon" style="background-color: rgba(108, 117, 125, 0.2);"><i class="fas fa-cogs"></i></div>
        <h3>Administrar Productos</h3>
        <p class="text-white-50 mb-0">Ver stock, añadir, ajustar inventario.</p>
      </a>
    </div>
    <div class="col-md-6">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../productos/productos_venta.php');" class="card action-card text-decoration-none text-white text-center p-4">
        <div class="card-icon" style="background-color: rgba(40, 167, 69, 0.2);"><i class="fas fa-cash-register"></i></div>
        <h3>Registrar Venta</h3>
        <p class="text-white-50 mb-0">Venta de productos a clientes.</p>
      </a>
    </div>
  </div>
</section>