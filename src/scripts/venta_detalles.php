<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$id_venta = $_GET['id_venta'] ?? null;
if (!$id_venta) {
    echo json_encode([]);
    exit;
}

// Consulta corregida con el nombre real de la columna
$sql = "
    SELECT 
        dv.cantidad, 
        dv.precio_unitario,
        CASE 
            WHEN p.nom_producto IS NOT NULL THEN p.nom_producto
            WHEN v.id_variante IS NOT NULL THEN CONCAT(p2.nom_producto, ' (', v.talla, ' - ', v.color, ')')
            ELSE 'Producto desconocido'
        END AS nombre
    FROM detalle_ventas dv
    LEFT JOIN productos p ON dv.cod_barras = p.cod_barras
    LEFT JOIN variantes v ON dv.id_variante = v.id_variante
    LEFT JOIN productos p2 ON v.cod_barras = p2.cod_barras
    WHERE dv.id_venta = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_venta]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($detalles);