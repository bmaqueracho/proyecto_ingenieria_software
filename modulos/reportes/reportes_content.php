<?php
// ==================================================================
// ESTA PARTE ES LA CLAVE DE LA SOLUCIÓN
// ==================================================================
// Como este archivo se carga vía AJAX (fetch), debe iniciar su
// propio entorno en cada llamada.

// 1. Iniciar la sesión para poder verificar los permisos.
session_start();

// 2. Incluir la conexión para tener acceso a la constante BASE_URL.
// La ruta es relativa a ESTE archivo (reportes_content.php).
// Desde /modulos/reportes/, subimos dos niveles (a /) para encontrarlo.
require_once '../../conexion.php'; 

// 3. Verificar los permisos de nuevo. Es una buena práctica de seguridad.
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) { 
    // Si la seguridad falla, muestra un error claro en lugar de una página en blanco.
    exit('<div class="alert alert-danger"><strong>Error:</strong> Acceso no autorizado a este módulo.</div>'); 
}
?>

<!-- El resto del archivo es el formulario que ya teníamos, ahora funcionará -->
<section id="generador-reportes">
    <h2 class="section-title"><i class="fas fa-chart-bar"></i> Generación de Reportes</h2>
    <p class="section-subtitle">Seleccione un tipo de reporte y el rango de fechas para generar una vista detallada.</p>

    <div class="card action-card p-4">
        <!-- 
            Ahora esta línea funcionará, porque BASE_URL está definida
            gracias al require_once de arriba.
        -->
        <form action="<?php echo BASE_URL; ?>/modulos/reportes/generar_reporte.php" method="GET" target="_blank">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tipo_reporte" class="form-label">Tipo de Reporte:</label>
                    <select name="tipo_reporte" id="tipo_reporte" class="form-select" required>
                        <option value="ingresos_totales">Ingresos Totales (Reservas + Ventas)</option>
                        <option value="ocupacion_hotel">Ocupación del Hotel (Noches vendidas)</option>
                        <option value="reservas_resumen">Resumen de Reservas (Cantidad, Promedio Estancia)</option>
                        <option value="ventas_productos_resumen">Resumen de Venta de Productos (Cantidad, Monto)</option>
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
                <label class="form-label">Seleccione Fecha:</label>
                <input type="date" name="fecha_especifica" id="fecha_especifica" class="form-control">
            </div>
            <div id="controles_fecha_semanal" class="mb-3" style="display:none;">
                <label class="form-label">Seleccione Semana y Año:</label>
                <input type="week" name="semana_anio" id="semana_anio" class="form-control">
            </div>
            <div id="controles_fecha_mensual" class="mb-3" style="display:none;">
                <label class="form-label">Seleccione Mes y Año:</label>
                <input type="month" name="mes_anio" id="mes_anio" class="form-control">
            </div>
            <div id="controles_fecha_anual" class="mb-3" style="display:none;">
                <label class="form-label">Seleccione Año:</label>
                <input type="number" name="anio" id="anio" class="form-control" min="2020" placeholder="<?php echo date('Y'); ?>">
            </div>
            
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-lg btn-primary">
                    <i class="fas fa-download me-2"></i>Generar y Descargar 
                </button>
            </div>
        </form>
    </div>
</section>

<!-- El script JS para mostrar/ocultar campos se queda igual -->
<script>
    // Tu script para manejar los controles de fecha ya estaba bien.
    (function() {
        const tipoReporteSelect = document.getElementById('tipo_reporte');
        const periodicidadSelect = document.getElementById('periodicidad');
        const periodicidadContainer = periodicidadSelect.closest('.col-md-6');
        const controles = {
            diario: document.getElementById('controles_fecha_diario'),
            semanal: document.getElementById('controles_fecha_semanal'),
            mensual: document.getElementById('controles_fecha_mensual'),
            anual: document.getElementById('controles_fecha_anual')
        };
        const inputs = {
            diario: document.getElementById('fecha_especifica'),
            semanal: document.getElementById('semana_anio'),
            mensual: document.getElementById('mes_anio'),
            anual: document.getElementById('anio')
        };
        
        function actualizarControles() {
            const esStockCritico = tipoReporteSelect.value === 'stock_productos_critico';
            
            periodicidadContainer.style.display = esStockCritico ? 'none' : 'block';
            Object.values(controles).forEach(el => el.style.display = 'none');
            Object.values(inputs).forEach(el => el.required = false);
            periodicidadSelect.required = !esStockCritico;

            if (!esStockCritico) {
                const seleccion = periodicidadSelect.value;
                if (controles[seleccion]) {
                    controles[seleccion].style.display = 'block';
                    if (inputs[seleccion]) {
                        inputs[seleccion].required = true;
                    }
                }
            }
        }
        
        tipoReporteSelect.addEventListener('change', actualizarControles);
        periodicidadSelect.addEventListener('change', actualizarControles);
        actualizarControles();
    })();
</script>