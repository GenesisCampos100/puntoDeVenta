<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Validar fechas
if (!$fecha_inicio) $fecha_inicio = date('Y-m-d');
if (!$fecha_fin) $fecha_fin = date('Y-m-d');

// Ajustar fin para incluir todo el día
$fecha_fin_sql = $fecha_fin . ' 23:59:59';
$fecha_inicio_sql = $fecha_inicio . ' 00:00:00';

try {
    switch ($action) {
        case 'getKpis':
            // 1. Total Ventas
            $stmt = $pdo->prepare("SELECT SUM(total) as total, COUNT(*) as transacciones FROM ventas WHERE fecha BETWEEN ? AND ?");
            $stmt->execute([$fecha_inicio_sql, $fecha_fin_sql]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalVentas = floatval($res['total'] ?? 0);
            $transacciones = intval($res['transacciones'] ?? 0);
            $ticketPromedio = $transacciones > 0 ? $totalVentas / $transacciones : 0;

            // 2. Margen Bruto
            // Margen = (Precio Venta - Costo) * Cantidad
            // Nota: Usamos el precio unitario de detalle_ventas (precio real vendido) y el costo actual del producto
            $sqlMargen = "
                SELECT 
                    SUM(dv.cantidad * (dv.precio_unitario - COALESCE(p.costo, 0))) as margen_bruto,
                    SUM(dv.cantidad * dv.precio_unitario) as venta_total_bruta
                FROM detalle_ventas dv
                JOIN productos p ON dv.cod_barras = p.cod_barras
                JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE v.fecha BETWEEN ? AND ?
            ";
            $stmtMargen = $pdo->prepare($sqlMargen);
            $stmtMargen->execute([$fecha_inicio_sql, $fecha_fin_sql]);
            $resMargen = $stmtMargen->fetch(PDO::FETCH_ASSOC);
            $margenBruto = floatval($resMargen['margen_bruto'] ?? 0);
            $ventaTotalBruta = floatval($resMargen['venta_total_bruta'] ?? 0);
            
            $porcentajeMargen = $ventaTotalBruta > 0 ? ($margenBruto / $ventaTotalBruta) * 100 : 0;

            echo json_encode([
                'success' => true,
                'total_ventas' => $totalVentas,
                'transacciones' => $transacciones,
                'ticket_promedio' => $ticketPromedio,
                'margen_bruto' => $margenBruto,
                'porcentaje_margen' => $porcentajeMargen
            ]);
            break;

        case 'getIngresos':
            // Agrupado por día o mes según el rango
            $diff = strtotime($fecha_fin) - strtotime($fecha_inicio);
            $days = round($diff / (60 * 60 * 24));
            
            if ($days > 31) {
                // Agrupar por mes
                $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') as periodo, SUM(total) as total FROM ventas WHERE fecha BETWEEN ? AND ? GROUP BY periodo ORDER BY periodo ASC";
            } else {
                // Agrupar por día
                $sql = "SELECT DATE(fecha) as periodo, SUM(total) as total FROM ventas WHERE fecha BETWEEN ? AND ? GROUP BY periodo ORDER BY periodo ASC";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha_inicio_sql, $fecha_fin_sql]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'getTopProductos':
            $sql = "
                SELECT p.nom_producto, SUM(dv.cantidad) as cantidad
                FROM detalle_ventas dv
                JOIN productos p ON dv.cod_barras = p.cod_barras
                JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE v.fecha BETWEEN ? AND ?
                GROUP BY p.nom_producto
                ORDER BY cantidad DESC
                LIMIT 10
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha_inicio_sql, $fecha_fin_sql]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'getVentasCategoria':
            $sql = "
                SELECT c.nombre as categoria, SUM(dv.cantidad * dv.precio_unitario) as total
                FROM detalle_ventas dv
                JOIN productos p ON dv.cod_barras = p.cod_barras
                JOIN categorias c ON p.id_categoria = c.id_categoria
                JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE v.fecha BETWEEN ? AND ?
                GROUP BY c.nombre
                ORDER BY total DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha_inicio_sql, $fecha_fin_sql]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'getVentasEmpleado':
            $sql = "
                SELECT CONCAT(e.nombre, ' ', e.apellido_paterno) as empleado, SUM(v.total) as total
                FROM ventas v
                JOIN empleados e ON v.id_empleado = e.id_empleado
                WHERE v.fecha BETWEEN ? AND ?
                GROUP BY empleado
                ORDER BY total DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha_inicio_sql, $fecha_fin_sql]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
