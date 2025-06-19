<?php
session_start();
// --- RUTA DE CONEXIÓN CORREGIDA ---
// Desde /modulos/reportes/, sube dos niveles (../..) para llegar a la raíz.
require_once '../../conexion.php'; 

// --- VERIFICACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    $conexion->close(); // Cerramos la conexión si el usuario no tiene permisos
    header("Location: ../auth/login.html");
    exit();
}

// --- RECOLECCIÓN Y LÓGICA DEL REPORTE (TU CÓDIGO ORIGINAL ÍNTEGRO) ---

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

$fecha_inicio_sql = null;
$fecha_fin_sql = null;

setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');

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
            $week = substr($semana_anio, 6);
            $fecha_obj = new DateTime();
            $fecha_obj->setISODate((int)$year, (int)$week, 1);
            $fecha_inicio_sql = $fecha_obj->format('Y-m-d 00:00:00');
            $fecha_obj->modify('+6 days');
            $fecha_fin_sql = $fecha_obj->format('Y-m-d 23:59:59');
            $subtitulo_reporte_final = "Semana " . $week . " del Año " . $year;
            break;
        case 'mensual':
            if (!$mes_anio) throw new Exception("Debe seleccionar un mes y año para el reporte mensual.");
            $fecha_obj = new DateTime($mes_anio . '-01');
            $fecha_inicio_sql = $fecha_obj->format('Y-m-01 00:00:00');
            $fecha_fin_sql = $fecha_obj->format('Y-m-t 23:59:59');
            $subtitulo_reporte_final = "Mes: " . ucfirst(strftime('%B de %Y', $fecha_obj->getTimestamp()));
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

    $sql = "";
    $params = [];
    $types = "";

    switch ($tipo_reporte) {
        case 'ingresos_totales':
            // ... (Toda tu lógica para ingresos_totales idéntica) ...
            $titulo_reporte_final = "Ingresos Totales";
            $columnas_reporte = ['Tipo Ingreso', 'Monto Total (S/)'];
            $sql_reservas_ing = "SELECT 'Reservas' as tipo_ingreso, SUM(monto_total) as monto FROM reservas WHERE estado = 'Completa' AND fecha_salida BETWEEN ? AND ?";
            $stmt_r = $conexion->prepare($sql_reservas_ing); $stmt_r->bind_param("ss", $fecha_inicio_sql, $fecha_fin_sql); $stmt_r->execute(); $res_r = $stmt_r->get_result()->fetch_assoc(); $stmt_r->close();
            if ($res_r && $res_r['monto'] !== null) $datos_reporte[] = $res_r;
            $sql_ventas_ing = "SELECT 'Venta de Productos' as tipo_ingreso, SUM(monto_total_venta) as monto FROM venta_productos WHERE fecha_consumo BETWEEN ? AND ?";
            $stmt_v = $conexion->prepare($sql_ventas_ing); $stmt_v->bind_param("ss", $fecha_inicio_sql, $fecha_fin_sql); $stmt_v->execute(); $res_v = $stmt_v->get_result()->fetch_assoc(); $stmt_v->close();
            if ($res_v && $res_v['monto'] !== null) $datos_reporte[] = $res_v;
            $gran_total = ($res_r['monto'] ?? 0) + ($res_v['monto'] ?? 0);
            $datos_reporte[] = ['tipo_ingreso' => 'GRAN TOTAL', 'monto' => $gran_total];
            break;

        case 'ocupacion_hotel':
             // ... (Toda tu lógica para ocupacion_hotel idéntica) ...
             $titulo_reporte_final = "Ocupación del Hotel";
             $columnas_reporte = ['Concepto', 'Valor'];
             $sql = "SELECT COUNT(id) as numero_reservas, SUM(estancia) AS total_noches_vendidas FROM reservas WHERE estado IN ('Confirmada', 'Completa') AND fecha_entrada BETWEEN ? AND ?";
             $params = [$fecha_inicio_sql, $fecha_fin_sql]; $types = "ss";
             $stmt_ocup = $conexion->prepare($sql); $stmt_ocup->bind_param($types, ...$params); $stmt_ocup->execute(); $res_ocup = $stmt_ocup->get_result()->fetch_assoc(); $stmt_ocup->close();
             $datos_reporte[] = ['Concepto' => 'Número de Reservas Iniciadas', 'Valor' => $res_ocup['numero_reservas'] ?? 0];
             $datos_reporte[] = ['Concepto' => 'Total Noches Vendidas', 'Valor' => $res_ocup['total_noches_vendidas'] ?? 0];
             break;

        case 'reservas_resumen':
             // ... (Toda tu lógica para reservas_resumen idéntica) ...
             $titulo_reporte_final = "Resumen de Reservas";
             $columnas_reporte = ['Estado Reserva', 'Cantidad', 'Promedio Estancia (días)', 'Monto Total (S/)'];
             $sql = "SELECT estado, COUNT(id) as cantidad, AVG(estancia) as promedio_estancia, SUM(monto_total) as monto_total FROM reservas WHERE fecha_reserva BETWEEN ? AND ? GROUP BY estado";
             $params = [$fecha_inicio_sql, $fecha_fin_sql]; $types = "ss";
             break;

        case 'ventas_productos_resumen':
            // ... (Toda tu lógica para ventas_productos_resumen idéntica) ...
            $titulo_reporte_final = "Resumen de Venta de Productos";
            $columnas_reporte = ['Producto', 'Categoría', 'Cantidad Vendida Total', 'Monto Total Venta (S/)'];
            $sql = "SELECT p.nombre as producto, p.categoria, SUM(vp.cantidad_vendida) as cantidad_total, SUM(vp.monto_total_venta) as monto_total FROM venta_productos vp JOIN productos p ON vp.producto_id = p.id WHERE vp.fecha_consumo BETWEEN ? AND ? GROUP BY p.id, p.nombre, p.categoria ORDER BY monto_total DESC";
            $params = [$fecha_inicio_sql, $fecha_fin_sql]; $types = "ss";
            break;

        case 'stock_productos_critico':
            // ... (Toda tu lógica para stock_productos_critico idéntica) ...
            $titulo_reporte_final = "Productos con Stock Crítico";
            $subtitulo_reporte_final = "Stock menor o igual a 10";
            $columnas_reporte = ['ID Producto', 'Nombre', 'Categoría', 'Stock Actual'];
            $stock_critico_umbral = 10;
            $sql = "SELECT id, nombre, categoria, stock FROM productos WHERE stock <= ? ORDER BY stock ASC, nombre ASC";
            $params = [$stock_critico_umbral]; $types = "i";
            break;

        default:
            throw new Exception("Tipo de reporte no implementado: " . htmlspecialchars($tipo_reporte));
    }

    if (!empty($sql) && !in_array($tipo_reporte, ['ingresos_totales', 'ocupacion_hotel'])) {
        $stmt = $conexion->prepare($sql);
        if (!$stmt) throw new Exception("Error al preparar la consulta: " . $conexion->error);
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) { while ($row = $result->fetch_assoc()) { $datos_reporte[] = $row; } }
        $stmt->close();
    }
} catch (Exception $e) {
    $error_reporte = $e->getMessage();
}

$conexion->close();

// --- RUTA DE VISTA CORREGIDA ---
// Como la vista está en la misma carpeta (/reportes/), la ruta es directa.
require 'vista_reporte_generico.html.php';
?>