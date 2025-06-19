<?php
// modulos/dashboard/reservas_content.php
// Este es el reemplazo de tu reservas_dashboard.html
?>
<section id="gestion-reservas">
  <h2 class="section-title"><i class="fas fa-calendar-check"></i> Gestión de Reservas</h2>
  <p class="text-white-50 mb-4">Crea nuevas reservas o administra las existentes.</p>
  <div class="row g-3">
    <div class="col-md-4">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/nueva_reserva.php', this);" class="card action-card text-decoration-none text-white text-center p-4">
        <div class="card-icon" style="background-color: rgba(233, 196, 106, 0.2);"><i class="fas fa-plus-circle"></i></div>
        <h3>Nueva Reserva</h3>
        <p class="text-white-50 mb-0">Iniciar el proceso de reserva.</p>
      </a>
    </div>
    <div class="col-md-4">
      <a href="#" onclick="event.preventDefault(); cargarContenido('../reservas/gestionar_reservas.php', this);" class="card action-card text-decoration-none text-white text-center p-4">
        <div class="card-icon" style="background-color: rgba(106, 168, 79, 0.2);"><i class="fas fa-tasks"></i></div>
        <h3>Gestionar Reservas</h3>
        <p class="text-white-50 mb-0">Ver, modificar o cancelar.</p>
      </a>
    </div>
  </div>
</section>