<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$id_venta = $_GET['id_venta'] ?? null;
if (!$id_venta) {
    echo json_encode(['error' => 'Falta id_venta']);
    exit;
}

// ✅ Consulta optimizada: busca el nombre del producto ya sea desde variantes o directamente de productos
$sql = "
    SELECT 
        v.id_venta,
        v.pago_total,
        COALESCE(CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', c.apellido_materno), 'Sin cliente') AS cliente,
        
        dv.cantidad,
        dv.precio_unitario,
        dv.cod_barras,
        dv.id_variante,

        -- ✅ Nombre del producto con soporte para variantes
        COALESCE(
            CONCAT(
                p.nom_producto,
                CASE 
                    WHEN var.talla IS NOT NULL OR var.color IS NOT NULL THEN 
                        CONCAT(' (', 
                               COALESCE(var.talla, ''), 
                               CASE 
                                   WHEN var.talla IS NOT NULL AND var.color IS NOT NULL THEN ' - ' 
                                   ELSE '' 
                               END,
                               COALESCE(var.color, ''), 
                               ')')
                    ELSE ''
                END
            ),
            p.nom_producto,
            'Producto desconocido'
        ) AS nombre_producto

    FROM detalle_ventas dv
    INNER JOIN ventas v ON dv.id_venta = v.id_venta
    LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
    LEFT JOIN variantes var ON dv.id_variante = var.id_variante
    LEFT JOIN productos p 
        ON p.cod_barras = COALESCE(dv.cod_barras, var.cod_barras)
    WHERE dv.id_venta = ?
";


$stmt = $pdo->prepare($sql);
$stmt->execute([$id_venta]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$detalles) {
    echo json_encode([]);
    exit;
}

// ✅ Armar respuesta
$pagoTotal = $detalles[0]['pago_total'] ?? 0;
$cliente = $detalles[0]['cliente'] ?? 'Sin cliente';

$productos = [];
foreach ($detalles as $d) {
    $productos[] = [
        'nombre' => $d['nombre_producto'],
        'cantidad' => $d['cantidad'],
        'precio_unitario' => $d['precio_unitario']
    ];
}

echo json_encode([
    'cliente' => $cliente,
    'total' => $pagoTotal,
    'productos' => $productos
], JSON_UNESCAPED_UNICODE);
