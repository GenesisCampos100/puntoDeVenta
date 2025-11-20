<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// limpiar cualquier salida antes
ob_clean();

$id_venta = $_GET['id_venta'] ?? null;
if (!$id_venta) {
    echo json_encode(['success'=>false,'message'=>'Falta id_venta']);
    exit;
}

// consultar venta
$sql = "
SELECT 
    v.id_venta,
    v.total AS pago_total,
    COALESCE(CONCAT(c.nombre,' ',c.apellido_paterno,' ',c.apellido_materno),'Sin cliente') AS cliente,
    dv.cantidad,
    dv.precio_unitario,
    dv.descuento AS descuento,
    COALESCE(
        CONCAT(
            p.nom_producto,
            CASE 
                WHEN var.talla IS NOT NULL OR var.color IS NOT NULL THEN 
                    CONCAT(' (', COALESCE(var.talla,''), 
                        CASE WHEN var.talla IS NOT NULL AND var.color IS NOT NULL THEN ' - ' ELSE '' END,
                        COALESCE(var.color,''),')')
                ELSE ''
            END
        ),
        p.nom_producto,
        'Producto desconocido'
    ) AS nombre
FROM detalle_ventas dv
INNER JOIN ventas v ON dv.id_venta = v.id_venta
LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
LEFT JOIN variantes var ON dv.id_variante = var.id_variante
LEFT JOIN productos p ON p.cod_barras = COALESCE(dv.cod_barras, var.cod_barras)
WHERE dv.id_venta = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_venta]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$detalles) {
    echo json_encode(['success'=>true,'productos'=>[],'cliente'=>'Sin cliente','total'=>0]);
    exit;
}

$productos = [];
foreach ($detalles as $d) {
    $productos[] = [
        'nombre' => $d['nombre'],
        'cantidad' => $d['cantidad'],
        'precio_unitario' => $d['precio_unitario'],
        'descuento' => $d['descuento'] ?? 0
    ];
}

echo json_encode([
    'success'=>true,
    'productos'=>$productos,
    'cliente'=>$detalles[0]['cliente'],
    'total'=>$detalles[0]['pago_total']
], JSON_UNESCAPED_UNICODE);
