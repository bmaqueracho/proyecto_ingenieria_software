<?php
session_start();
require_once '../../conexion.php'; // $conn

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    header("Location: ../autch/login.html");
    exit();
}

$tipo_reporte = $_GET['tipo_reporte'] ?? '';
$periodicidad = $_GET['periodicidad'] ?? '';
$fecha_especifica = $_GET['fecha_especifica'] ?? null;
$semana_anio = $_GET['semana_anio'] ?? null; // Formato YYYY-Www, ej: 2023-W30
$mes_anio = $_GET['mes_anio'] ?? null;       // Formato YYYY-MM, ej: 2023-07
$anio_input = $_GET['anio'] ?? null;         // Formato YYYY

$datos_reporte = [];
$titulo_reporte_final = "Reporte";
$subtitulo_reporte_final = "";
$columnas_reporte = [];
$error_reporte = null;

// --- Lógica para determinar el rango de fechas según periodicidad ---
$fecha_inicio_sql = null;
$fecha_fin_sql = null;

try {
    switch ($periodicidad) {
        case 'diario':
            if (!$fecha_especifica) throw new Exception("Debe seleccionar una fecha para el reporte diario.");
            $fecha_obj = new DateTime($fecha_especifica);
            $fecha_inicio_sql = $fecha_obj->format('Y-m-d 00:00:00');
            $fecha_fin_sql = $fecha_obj->format('Y-m-d 23:59:59');
            $subtitulo_reporte_final = "Día: " . $fecha_obj->format('d/m/Y');
            break;
        case 'semanal':
            if (!$semana_anio) throw new Exception("Debe seleccionar una semana y año para el reporte semanal.");
            $year = substr($semana_anio, 0, 4);
            $week = substr($semana_anio, 6); // Www -> ww
            $fecha_obj = new DateTime();
            $fecha_obj->setISODate((int)$year, (int)$week, 1); // Lunes de esa semana
            $fecha_inicio_sql = $fecha_obj->format('Y-m-d 00:00:00');
            $fecha_obj->modify('+6 days'); // Domingo de esa semana
            $fecha_fin_sql = $fecha_obj->format('Y-m-d 23:59:59');
            $subtitulo_reporte_final = "Semana " . $week . " del Año " . $year;
            break;
        case 'mensual':
            if (!$mes_anio) throw new Exception("Debe seleccionar un mes y año para el reporte mensual.");
            $fecha_obj = new DateTime($mes_anio . '-01');
            $fecha_inicio_sql = $fecha_obj->format('Y-m-01 00:00:00');
            $fecha_fin_sql = $fecha_obj->format('Y-m-t 23:59:59'); // 't' da el último día del mes
            $subtitulo_reporte_final = "Mes: " . $fecha_obj->format('F Y');
            break;
        case 'anual':
            if (!$anio_input) throw new Exception("Debe seleccionar un año para el reporte anual.");
            $fecha_obj = new DateTime($anio_input . '-01-01');
            $fecha_inicio_sql = $fecha_obj->format('Y-01-01 00:00:00');
            $fecha_fin_sql = $fecha_obj->format('Y-12-31 23:59:59');
            $subtitulo_reporte_final = "Año: " . $anio_input;
            break;
        default:
            throw new Exception("Periodicidad no válida.");
    }

    // --- Lógica para construir la consulta SQL según el tipo de reporte ---
    $sql = "";
    $params = [];
    $types = "";

    switch ($tipo_reporte) {
        case 'ingresos_totales':
            $titulo_reporte_final = "Ingresos Totales";
            $columnas_reporte = ['Tipo Ingreso', 'Monto Total (S/)'];
            // Ingresos por Reservas (estado 'Completa', fecha_salida en rango)
            $sql_reservas_ing = "SELECT 'Reservas' as tipo_ingreso, SUM(monto_total) as monto 
                                 FROM reservas 
                                 WHERE estado = 'Completa' AND fecha_salida BETWEEN ? AND ?";
            $stmt_r = $conn->prepare($sql_reservas_ing);
            $stmt_r->bind_param("ss", $fecha_inicio_sql, $fecha_fin_sql);
            $stmt_r->execute();
            $res_r = $stmt_r->get_result()->fetch_assoc();
            $stmt_r->close();
            if ($res_r && $res_r['monto'] !== null) $datos_reporte[] = $res_r;

            // Ingresos por Venta de Productos (fecha_consumo en rango)
            $sql_ventas_ing = "SELECT 'Venta de Productos' as tipo_ingreso, SUM(monto_total_venta) as monto 
                               FROM venta_productos 
                               WHERE fecha_consumo BETWEEN ? AND ?";
            $stmt_v = $conn->prepare($sql_ventas_ing);
            $stmt_v->bind_param("ss", $fecha_inicio_sql, $fecha_fin_sql);
            $stmt_v->execute();
            $res_v = $stmt_v->get_result()->fetch_assoc();
            $stmt_v->close();
            if ($res_v && $res_v['monto'] !== null) $datos_reporte[] = $res_v;
            
            // Calcular total general para este reporte específico
            $gran_total = ($res_r['monto'] ?? 0) + ($res_v['monto'] ?? 0);
            $datos_reporte[] = ['tipo_ingreso' => 'GRAN TOTAL', 'monto' => $gran_total];
            break;

        case 'ocupacion_hotel':
            $titulo_reporte_final = "Ocupación del Hotel";
            // Para la ocupación, la fecha relevante es la fecha_entrada para contar inicios de reserva,
            // o un análisis más complejo para noches-habitación ocupadas.
            // Simplificaremos: Total de noches vendidas cuyas reservas estuvieron activas en el periodo.
            // Se considera una noche vendida si la reserva está 'Confirmada' o 'Completa'.
            $columnas_reporte = ['Concepto', 'Valor'];
            $sql = "SELECT SUM(estancia) AS total_noches_vendidas
                    FROM reservas
                    WHERE estado IN ('Confirmada', 'Completa') 
                      AND fecha_entrada <= ?  -- La reserva debe haber iniciado antes o durante el fin del periodo
                      AND fecha_salida >= ?   -- Y terminado después o durante el inicio del periodo
                                            -- (Esto cuenta reservas que se solapan con el periodo)
                      "; 
            // Esto es una simplificación. Un cálculo exacto de "noches-habitación" dentro del periodo es más complejo.
            // Por ejemplo, una reserva de 5 noches que empieza 2 días antes del periodo y termina 1 día dentro,
            // contribuiría con 1 noche al periodo.
            // La consulta más precisa sería similar a la que intentamos en reporte_ocupacion.php,
            // distribuyendo las noches de cada reserva dentro del rango.
            // Para un resumen rápido, SUM(estancia) de las reservas que caen DENTRO del periodo es una aproximación.
            // Tomaremos fecha_entrada DENTRO del periodo para este ejemplo.
            $sql = "SELECT COUNT(id) as numero_reservas, SUM(estancia) AS total_noches_vendidas
                    FROM reservas
                    WHERE estado IN ('Confirmada', 'Completa') 
                      AND fecha_entrada BETWEEN ? AND ? 
                      ";
            $params = [$fecha_inicio_sql, $fecha_fin_sql];
            $types = "ss";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res_ocup = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $datos_reporte[] = ['Concepto' => 'Número de Reservas Iniciadas en Periodo', 'Valor' => $res_ocup['numero_reservas'] ?? 0];
            $datos_reporte[] = ['Concepto' => 'Total Noches Vendidas (de esas reservas)', 'Valor' => $res_ocup['total_noches_vendidas'] ?? 0];
            break;

        case 'reservas_resumen':
            $titulo_reporte_final = "Resumen de Reservas";
            $columnas_reporte = ['Estado Reserva', 'Cantidad', 'Promedio Estancia (días)', 'Monto Total (S/)'];
            $sql = "SELECT estado, COUNT(id) as cantidad, AVG(estancia) as promedio_estancia, SUM(monto_total) as monto_total
                    FROM reservas 
                    WHERE fecha_reserva BETWEEN ? AND ? 
                    GROUP BY estado"; // Agrupado por estado, fecha de reserva en rango
            $params = [$fecha_inicio_sql, $fecha_fin_sql];
            $types = "ss";
            break;

        case 'ventas_productos_resumen':
            $titulo_reporte_final = "Resumen de Venta de Productos";
            $columnas_reporte = ['Producto', 'Categoría', 'Cantidad Vendida Total', 'Monto Total Venta (S/)'];
            $sql = "SELECT p.nombre as producto, p.categoria, SUM(vp.cantidad_vendida) as cantidad_total, SUM(vp.monto_total_venta) as monto_total
                    FROM venta_productos vp
                    JOIN productos p ON vp.producto_id = p.id
                    WHERE vp.fecha_consumo BETWEEN ? AND ?
                    GROUP BY p.id, p.nombre, p.categoria
                    ORDER BY monto_total DESC";
            $params = [$fecha_inicio_sql, $fecha_fin_sql];
            $types = "ss";
            break;

        case 'stock_productos_critico':
            $titulo_reporte_final = "Productos con Stock Crítico";
            $subtitulo_reporte_final = "Stock menor o igual a 10 (configurable)"; // No usa fechas
            $columnas_reporte = ['ID Producto', 'Nombre', 'Categoría', 'Stock Actual'];
            $stock_critico_umbral = 10;
            $sql = "SELECT id, nombre, categoria, stock FROM productos WHERE stock <= ? ORDER BY stock ASC, nombre ASC";
            $params = [$stock_critico_umbral];
            $types = "i";
            // Este reporte no usa el rango de fechas, así que lo sobreescribimos.
            $fecha_inicio_sql = null; $fecha_fin_sql = null;
            break;

        default:
            throw new Exception("Tipo de reporte no implementado: " . htmlspecialchars($tipo_reporte));
    }

    // Ejecutar consulta principal si no se manejó ya (como en ingresos_totales)
    if (!empty($sql) && $tipo_reporte !== 'ingresos_totales' && $tipo_reporte !== 'ocupacion_hotel' /* ya se ejecutó arriba */) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error al preparar la consulta: " . $conn->error);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $datos_reporte[] = $row;
            }
        }
        $stmt->close();
    }

} catch (Exception $e) {
    $error_reporte = $e->getMessage();
}

$conn->close();

// Incluir la vista genérica para mostrar la tabla del reporte
require 'vista_reporte_generico.html.php';
?>