<?php
require_once __DIR__ . '/../config/db.php';

$proveedor_id = $_GET['proveedor'] ?? '';
$orden_get = $_GET['orden'] ?? 'nom_asc';

$mapOrder = [
    'nom_asc' => 'p.nom_producto ASC',
    'nom_desc' => 'p.nom_producto DESC',
    'precio_asc' => 'p.precio ASC',
    'precio_desc' => 'p.precio DESC'
];

// Normalizar y validar el parámetro de orden para evitar inyección SQL
$orden_get_trim = trim((string)$orden_get);
$orden_sql = $mapOrder['nom_asc'];
if (isset($mapOrder[$orden_get_trim])) {
    $orden_sql = $mapOrder[$orden_get_trim];
} else {
    // Si se pasó la forma completa (ej. 'p.nom_producto ASC'), aceptar solo si coincide exactamente con un valor permitido
    if (in_array($orden_get_trim, $mapOrder, true)) {
        $orden_sql = $orden_get_trim;
    } else {
        // Intento de interpretación tolerante (p. ej. 'nombre desc', 'precio_asc', 'precio desc')
        $low = strtolower($orden_get_trim);
        if (strpos($low, 'precio') !== false) {
            $orden_sql = (strpos($low, 'desc') !== false) ? $mapOrder['precio_desc'] : $mapOrder['precio_asc'];
        } elseif (strpos($low, 'nom') !== false || strpos($low, 'producto') !== false || strpos($low, 'nombre') !== false) {
            $orden_sql = (strpos($low, 'desc') !== false) ? $mapOrder['nom_desc'] : $mapOrder['nom_asc'];
        }
    }
}

if (empty($proveedor_id)) {
    echo "<p style='padding:20px;font-family:sans-serif;'>ID de proveedor no proporcionado.</p>";
    exit;
}

// Obtener datos del proveedor
$stmtP = $pdo->prepare("SELECT id_proveedor, empresa, nombre, apellido_paterno, apellido_materno FROM proveedores WHERE id_proveedor = ? LIMIT 1");
$stmtP->execute([$proveedor_id]);
$prov = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$prov) {
    echo "<p style='padding:20px;font-family:sans-serif;'>Proveedor no encontrado.</p>";
    exit;
}

$proveedor_nombre = trim(($prov['nombre'] ?? '') . ' ' . ($prov['apellido_paterno'] ?? '') . ' ' . ($prov['apellido_materno'] ?? ''));
if (empty($proveedor_nombre)) $proveedor_nombre = $prov['empresa'] ?? 'Proveedor';

// Obtener productos del proveedor
$sql = "SELECT 
            p.cod_barras AS id_producto,
            p.nom_producto,
            p.imagen,
            p.marca,
            p.descripcion,
            c.nombre AS categoria,
            p.cantidad,
            p.precio
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_proveedor = :proveedor
        ORDER BY $orden_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute([':proveedor' => $proveedor_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Catálogo: <?= htmlspecialchars($proveedor_nombre) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:Poppins, sans-serif;background:#f7fafc;}</style>
</head>
<body class="p-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Catálogo de <?= htmlspecialchars($proveedor_nombre) ?></h1>
                <p class="text-sm text-gray-600">Empresa: <?= htmlspecialchars($prov['empresa'] ?? '-') ?></p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME'] . '?view=catalogo_proveedor') ?>" class="inline-flex items-center gap-2">
                    <input type="hidden" name="view" value="catalogo_proveedor">
                    <input type="hidden" name="proveedor" value="<?= htmlspecialchars($proveedor_id) ?>">
                    <select name="orden" onchange="this.form.submit()" class="px-3 py-2 rounded border">
                        <option value="nom_asc" <?= ($orden_get=='nom_asc')? 'selected':'' ?>>Nombre A-Z</option>
                        <option value="nom_desc" <?= ($orden_get=='nom_desc')? 'selected':'' ?>>Nombre Z-A</option>
                        <option value="precio_asc" <?= ($orden_get=='precio_asc')? 'selected':'' ?>>Precio: Menor</option>
                        <option value="precio_desc" <?= ($orden_get=='precio_desc')? 'selected':'' ?>>Precio: Mayor</option>
                    </select>
                </form>
                <a href="index.php?view=proveedores" class="px-4 py-2 rounded bg-gray-100 hover:bg-gray-200">Volver a proveedores</a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $p): ?>
                                <tr>
                                    <td class="px-6 py-4 flex items-center gap-4">
                                        <?php if (!empty($p['imagen'])): ?>
                                            <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" class="w-12 h-12 object-cover rounded-md">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-100 rounded-md flex items-center justify-center text-gray-400">--</div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-semibold text-gray-900"><?= htmlspecialchars($p['nom_producto']) ?></div>
                                            <?php if (!empty($p['marca'])): ?><div class="text-xs text-gray-500">Marca: <?= htmlspecialchars($p['marca']) ?></div><?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-gray-700"><?= htmlspecialchars($p['categoria'] ?? '-') ?></td>
                                    <td class="px-4 py-4 text-center text-sm text-gray-700"><?= htmlspecialchars($p['cantidad']) ?></td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">$<?= number_format($p['precio'],2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-600">
                                    No se encontraron productos para este proveedor.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>