<?php
// Este archivo solo es HTML por ahora. Más adelante añadiremos PHP para guardar o actualizar datos.
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Cliente</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>

  <header class="header">
    <h1 class="titulo-pagina">Registro de Clientes</h1>
  </header>

  <main class="contenedor">
    <!-- Formulario de búsqueda para actualizar -->
    <section class="seccion-buscar-cliente">
      <h2 class="subtitulo">Buscar Cliente para Actualizar</h2>
      <form action="buscar_cliente.php" method="get" class="formulario-busqueda">
        <label for="dni_buscar">DNI del Cliente:</label>
        <input type="text" id="dni_buscar" name="dni_buscar" minlength="8" maxlength="10" required>
        <button type="submit">Buscar</button>
      </form>
    </section>

    <hr>

    <!-- Formulario de registro / actualización -->
    <section class="seccion-formulario-cliente">
      <h2 class="subtitulo">Registrar / Actualizar Cliente</h2>
      <form action="guardar_cliente.php" method="post" class="formulario-cliente">
        <label for="dni">DNI:</label>
        <input type="text" id="dni" name="dni" minlength="8" maxlength="10" required>

        <label for="nombres">Nombres:</label>
        <input type="text" id="nombres" name="nombres" maxlength="45" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" id="apellidos" name="apellidos" maxlength="45" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" maxlength="15">

        <label for="observacion">Observación:</label>
        <textarea id="observacion" name="observacion" maxlength="255" rows="3" placeholder="Sin Observación"></textarea>

        <div class="botones-formulario">
          <button type="submit" name="accion" value="registrar">Registrar</button>
          <button type="submit" name="accion" value="actualizar">Actualizar</button>
        </div>
      </form>
    </section>
  </main>

  <footer class="footer">
    <p>© HotelApp 2025 - Todos los derechos reservados</p>
  </footer>

</body>
</html>