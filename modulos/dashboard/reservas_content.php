<?php
?>
<section id="gestion-reservas">

  <h2 class="section-title">
    <i class="fas fa-calendar-check"></i>
    Gestión de Reservas
  </h2>
  <p class="section-subtitle">
    Desde aquí puede iniciar el proceso para crear una nueva reserva para un cliente o administrar las reservas existentes.
  </p>

  <!-- CAMBIO: La estructura ahora usa las tarjetas de bienvenida rediseñadas -->
  <div class="row g-4">
    <div class="col-lg-4 col-md-6">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/nueva_reserva.php', this);" class="welcome-card text-decoration-none">
        <div class="card-icon icon-reserva">
          <i class="fas fa-plus-circle"></i>
        </div>
        <h3>Nueva Reserva</h3>
        <p>Iniciar el proceso de reserva para un huésped.</p>
      </a>
    </div>
    <div class="col-lg-4 col-md-6">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/gestionar_reservas.php', this);" class="welcome-card text-decoration-none">
        <div class="card-icon icon-clientes">
            <i class="fas fa-tasks"></i>
        </div>
        <h3>Gestionar Reservas</h3>
        <p>Ver, modificar o cancelar las reservas existentes.</p>
      </a>
    </div>
  </div>

</section>