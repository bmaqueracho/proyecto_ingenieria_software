<?php
// Incluir FPDF manualmente
require_once '../../libraries/fpdf.php';

session_start();
require_once '../../conexion.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['cargo'], ['Recepcionista', 'Administrador'])) {
    header("Location: ../autch/login.html");
    exit();
}

// --- CLASE PERSONALIZADA PARA HEADER Y FOOTER DEL PDF ---
class PDF extends FPDF
{
    private $ReportTitle = '';
    private $ReportSubtitle = '';

    function setReportTitles($title, $subtitle) {
        $this->ReportTitle = $title;
        $this->ReportSubtitle = $subtitle;
    }

    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 7, mb_convert_encoding($this->ReportTitle, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0, 7, mb_convert_encoding($this->ReportSubtitle, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetX(10);
        $this->Cell(0, 10, mb_convert_encoding('© Hotel Mediterraneo ' . date('Y'), 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(0, 10, 'Generado: ' . date('d/m/Y H:i:s'), 0, 0, 'R');
    }
}

// Obtener parámetros del formulario
$tipo_reporte = $_GET['tipo_reporte'] ?? '';
$periodicidad = $_GET['periodicidad'] ?? '';
$fecha_especifica = $_GET['fecha_especifica'] ?? null;
$semana_anio = $_GET['semana_anio'] ?? null;
$mes_anio = $_GET['mes_anio'] ?? null;
$anio_input = $_GET['anio'] ?? null;

$datos_reporte = [];
$titulo_reporte_final = "Reporte";
$subtitulo_reporte_final = "";
$columnas_reporte = [];
$error_reporte = null;

try {
    // Lógica para determinar el rango de fechas según periodicidad
    $fecha_inicio_sql = null;
    $fecha_fin_sql = null;

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
            setlocale(LC_TIME, 'es_ES.UTF-8');
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

    // Lógica para construir la consulta SQL según el tipo de reporte
    $sql = "";
    $params = [];
    $types = "";

    switch ($tipo_reporte) {
        case 'ingresos_totales':
            $titulo_reporte_final = "Ingresos Totales";
            $columnas_reporte = ['Tipo Ingreso', 'Monto Total (S/)'];
            
            // Ingresos por Reservas
            $sql_reservas_ing = "SELECT 'Reservas' as tipo_ingreso, SUM(monto_total) as monto 
                                 FROM reservas 
                                 WHERE estado = 'Completa' AND fecha_salida BETWEEN ? AND ?";
            $stmt_r = $conexion->prepare($sql_reservas_ing);
            $stmt_r->bind_param("ss", $fecha_inicio_sql, $fecha_fin_sql);
            $stmt_r->execute();
            $res_r = $stmt_r->get_result()->fetch_assoc();
            $stmt_r->close();
            if ($res_r && $res_r['monto'] !== null) $datos_reporte[] = $res_r;

            // Ingresos por Venta de Productos
            $sql_ventas_ing = "SELECT 'Venta de Productos' as tipo_ingreso, SUM(monto_total_venta) as monto 
                               FROM venta_productos 
                               WHERE fecha_consumo BETWEEN ? AND ?";
            $stmt_v = $conexion->prepare($sql_ventas_ing);
            $stmt_v->bind_param("ss", $fecha_inicio_sql, $fecha_fin_sql);
            $stmt_v->execute();
            $res_v = $stmt_v->get_result()->fetch_assoc();
            $stmt_v->close();
            if ($res_v && $res_v['monto'] !== null) $datos_reporte[] = $res_v;
            
            // Calcular total general
            $gran_total = ($res_r['monto'] ?? 0) + ($res_v['monto'] ?? 0);
            $datos_reporte[] = ['tipo_ingreso' => 'GRAN TOTAL', 'monto' => $gran_total];
            break;

        case 'ocupacion_hotel':
            $titulo_reporte_final = "Ocupación del Hotel";
            $columnas_reporte = ['Concepto', 'Valor'];
            
            $sql = "SELECT COUNT(id) as numero_reservas, SUM(estancia) AS total_noches_vendidas
                    FROM reservas
                    WHERE estado IN ('Confirmada', 'Completa') 
                      AND fecha_entrada BETWEEN ? AND ?";
            $params = [$fecha_inicio_sql, $fecha_fin_sql];
            $types = "ss";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res_ocup = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $datos_reporte[] = ['Concepto' => 'Número de Reservas Iniciadas', 'Valor' => $res_ocup['numero_reservas'] ?? 0];
            $datos_reporte[] = ['Concepto' => 'Total Noches Vendidas', 'Valor' => $res_ocup['total_noches_vendidas'] ?? 0];
            break;

        case 'reservas_resumen':
            $titulo_reporte_final = "Resumen de Reservas";
            $columnas_reporte = ['Estado Reserva', 'Cantidad', 'Promedio Estancia (días)', 'Monto Total (S/)'];
            
            $sql = "SELECT estado, COUNT(id) as cantidad, AVG(estancia) as promedio_estancia, SUM(monto_total) as monto_total
                    FROM reservas 
                    WHERE fecha_reserva BETWEEN ? AND ? 
                    GROUP BY estado";
            $params = [$fecha_inicio_sql, $fecha_fin_sql];
            $types = "ss";
            break;

        case 'ventas_productos_resumen':
            $titulo_reporte_final = "Resumen de Venta de Productos";
            $columnas_reporte = ['Producto', 'Categoría', 'Cantidad Vendida', 'Monto Total (S/)'];
            
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
            $subtitulo_reporte_final = "Stock menor o igual a 10 unidades";
            $columnas_reporte = ['ID Producto', 'Nombre', 'Categoría', 'Stock Actual'];
            
            $stock_critico_umbral = 10;
            $sql = "SELECT id, nombre, categoria, stock FROM productos WHERE stock <= ? ORDER BY stock ASC, nombre ASC";
            $params = [$stock_critico_umbral];
            $types = "i";
            $fecha_inicio_sql = null; $fecha_fin_sql = null;
            break;

        default:
            throw new Exception("Tipo de reporte no implementado: " . htmlspecialchars($tipo_reporte));
    }

    // Ejecutar consulta principal si no se manejó ya
    if (!empty($sql) && $tipo_reporte !== 'ingresos_totales' && $tipo_reporte !== 'ocupacion_hotel') {
        $stmt = $conexionn->prepare($sql);
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

$conexion->close();

// Manejar errores mostrando un PDF de error
if ($error_reporte) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->MultiCell(0, 10, mb_convert_encoding("Error al generar el reporte:\n" . $error_reporte, 'ISO-8859-1', 'UTF-8'));
    $pdf->Output('D', 'Error_Reporte.pdf');
    exit();
}

// Crear el PDF con los datos obtenidos
$pdf = new PDF();
$pdf->setReportTitles($titulo_reporte_final, $subtitulo_reporte_final);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// Determinar anchos de columnas según el tipo de reporte
$anchoColumnas = [];
switch($tipo_reporte) {
    case 'ingresos_totales':
        $anchoColumnas = [130, 60];
        break;
    case 'ocupacion_hotel':
        $anchoColumnas = [130, 60];
        break;
    case 'reservas_resumen':
        $anchoColumnas = [50, 40, 50, 50];
        break;
    case 'ventas_productos_resumen':
        $anchoColumnas = [70, 40, 40, 40];
        break;
    case 'stock_productos_critico':
        $anchoColumnas = [30, 80, 40, 40];
        break;
    default:
        $anchoColumnas = [130, 60];
}

// Dibujar la cabecera de la tabla
$pdf->SetFont('','B',10);
$pdf->SetFillColor(230, 230, 230);
$pdf->SetTextColor(0);

foreach($columnas_reporte as $index => $columna) {
    $ancho = $anchoColumnas[$index] ?? 60;
    $pdf->Cell($ancho, 8, mb_convert_encoding($columna, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();

// Dibujar los datos de la tabla
$pdf->SetFont('','',10);
$pdf->SetFillColor(255, 255, 255);

foreach($datos_reporte as $fila) {
    $i = 0;
    foreach($fila as $valor) {
        $ancho = $anchoColumnas[$i] ?? 60;
        $align = is_numeric($valor) ? 'R' : 'L';
        $valorFormateado = is_numeric($valor) ? number_format((float)$valor, 2) : $valor;
        
        // Poner en negrita si es el total
        if(isset($fila['tipo_ingreso']) && strpos(strtoupper($fila['tipo_ingreso']), 'TOTAL') !== false) {
            $pdf->SetFont('','B',10);
        } else {
            $pdf->SetFont('','',10);
        }
        
        $pdf->Cell($ancho, 7, mb_convert_encoding($valorFormateado, 'ISO-8859-1', 'UTF-8'), 'LR', 0, $align, true);
        $i++;
    }
    $pdf->Ln();
}

// Línea de cierre de la tabla
$pdf->Cell(array_sum($anchoColumnas), 0, '', 'T');

// Forzar la descarga del PDF
$pdf->Output('D', 'Reporte_Hotel_' . date('Ymd_His') . '.pdf');
?>