<?php
// ARCHIVO DE VISTA: Muestra el formulario para generar reportes.
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    exit(); 
}
?>

<section id="generador-reportes">
    <h2 class="section-title"><i class="fas fa-chart-bar"></i> Generación de Reportes</h2>
    <p class="text-white-50 mb-4">Seleccione un tipo de reporte y el rango de fechas para generar una vista detallada.</p>

    <div class="card action-card p-4">
        <form action="../reportes/generar_reporte.php" method="GET" target="_blank">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tipo_reporte" class="form-label">Tipo de Reporte:</label>
                    <select name="tipo_reporte" id="tipo_reporte" class="form-select" required>
                        <option value="ingresos_totales">Ingresos Totales (Reservas + Ventas)</option>
                        <option value="ocupacion_hotel">Ocupación del Hotel</option>
                        <option value="reservas_resumen">Resumen de Reservas</option>
                        <option value="ventas_productos_resumen">Resumen de Venta de Productos</option>
                        <option value="stock_productos_critico">Productos con Stock Crítico</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="periodicidad" class="form-label">Periodicidad:</label>
                    <select name="periodicidad" id="periodicidad" class="form-select" required>
                        <option value="diario">Diario</option>
                        <option value="semanal">Semanal</option>
                        <option value="mensual">Mensual</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
            </div>

            <div id="controles_fecha_diario" class="mb-3" style="display:none;">
                <label for="fecha_especifica" class="form-label">Seleccione Fecha:</label>
                <input type="date" name="fecha_especifica" id="fecha_especifica" class="form-control">
            </div>
            <div id="controles_fecha_semanal" class="mb-3" style="display:none;">
                <label for="semana_anio" class="form-label">Seleccione Semana y Año:</label>
                <input type="week" name="semana_anio" id="semana_anio" class="form-control">
            </div>
            <div id="controles_fecha_mensual" class="mb-3" style="display:none;">
                <label for="mes_anio" class="form-label">Seleccione Mes y Año:</label>
                <input type="month" name="mes_anio" id="mes_anio" class="form-control">
            </div>
            <div id="controles_fecha_anual" class="mb-3" style="display:none;">
                <label for="anio" class="form-label">Seleccione Año:</label>
                <input type="number" name="anio" id="anio" class="form-control" min="2020" placeholder="<?php echo date('Y'); ?>">
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-lg btn-primary"><i class="fas fa-file-alt"></i> Generar Reporte</button>
            </div>
        </form>
    </div>
</section>

<!-- El script para los controles de fecha -->
<script>
    // Se definen los elementos una vez que el script se ejecuta
    const periodicidadSelect = document.getElementById('periodicidad');
    const controlesDiario = document.getElementById('controles_fecha_diario');
    const controlesSemanal = document.getElementById('controles_fecha_semanal');
    const controlesMensual = document.getElementById('controles_fecha_mensual');
    const controlesAnual = document.getElementById('controles_fecha_anual');

    // La función que muestra u oculta los controles
    function actualizarControlesFecha() {
        if (!periodicidadSelect) return; // Si el elemento no existe, no hacer nada
        
        // Ocultar todos los controles primero
        controlesDiario.style.display = 'none';
        controlesSemanal.style.display = 'none';
        controlesMensual.style.display = 'none';
        controlesAnual.style.display = 'none';

        // Desactivar 'required' para todos los inputs
        document.getElementById('fecha_especifica').required = false;
        document.getElementById('semana_anio').required = false;
        document.getElementById('mes_anio').required = false;
        document.getElementById('anio').required = false;

        // Mostrar y configurar el control correcto
        switch (periodicidadSelect.value) {
            case 'diario':
                controlesDiario.style.display = 'block';
                document.getElementById('fecha_especifica').required = true;
                if (!document.getElementById('fecha_especifica').value) { document.getElementById('fecha_especifica').valueAsDate = new Date(); }
                break;
            case 'semanal':
                controlesSemanal.style.display = 'block';
                document.getElementById('semana_anio').required = true;
                break;
            case 'mensual':
                controlesMensual.style.display = 'block';
                document.getElementById('mes_anio').required = true;
                if (!document.getElementById('mes_anio').value) { const hoy = new Date(); document.getElementById('mes_anio').value = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2); }
                break;
            case 'anual':
                controlesAnual.style.display = 'block';
                document.getElementById('anio').required = true;
                if (!document.getElementById('anio').value) { document.getElementById('anio').value = new Date().getFullYear(); }
                break;
        }
    }
    
    // Se añade el 'listener' al selector de periodicidad
    if (periodicidadSelect) {
        periodicidadSelect.addEventListener('change', actualizarControlesFecha);
        // Se ejecuta la función una vez para establecer el estado inicial correcto
        actualizarControlesFecha();
    }
</script>