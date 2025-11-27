<?php
require_once __DIR__ . '/../config/translation.php';
require_once __DIR__ . '/../config/db.php';

$proveedor_id = $_GET['proveedor'] ?? '';
$orden_get    = $_GET['orden'] ?? 'nom_asc';

$mapOrder = [
    'nom_asc'    => 'p.nom_producto ASC',
    'nom_desc'   => 'p.nom_producto DESC',
    'precio_asc' => 'p.precio ASC',
    'precio_desc'=> 'p.precio DESC'
];

// Normalizar y validar el parámetro de orden para evitar inyección SQL
$orden_get_trim = trim((string)$orden_get);
$orden_sql = $mapOrder['nom_asc'];
if (isset($mapOrder[$orden_get_trim])) {
    $orden_sql = $mapOrder[$orden_get_trim];
} else {
    if (in_array($orden_get_trim, $mapOrder, true)) {
        $orden_sql = $orden_get_trim;
    } else {
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
            p.precio,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_proveedor = :proveedor
        ORDER BY $orden_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute([':proveedor' => $proveedor_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Precargar variantes y agrupar por producto (cod_barras / id_producto)
$variantesPorProducto = [];
$codigos = array_column($productos, 'id_producto');
if (!empty($codigos)) {
    // Preparar la cláusula IN con placeholders
    $placeholders = implode(',', array_fill(0, count($codigos), '?'));
    $variantesSql = "SELECT v.cod_barras AS id_producto, v.id_variante, v.sku, v.talla, v.color, v.cantidad, v.precio, v.costo, v.imagen FROM variantes v WHERE v.cod_barras IN ($placeholders)";
    $variantesStmt = $pdo->prepare($variantesSql);
    $variantesStmt->execute($codigos);
    $variantesRaw = $variantesStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($variantesRaw as $v) {
        $variantesPorProducto[$v['id_producto']][] = $v;
    }
}

// Construir la lista final de filas a mostrar: si un producto tiene variantes, mostrar cada variante como fila "normal" y ocultar el producto padre
$displayProductos = [];
foreach ($productos as $p) {
    $pid = $p['id_producto'];
    // Determinar si tiene variantes (por subconsulta o por precarga)
    $tieneVar = isset($p['tiene_variante']) ? (int)$p['tiene_variante'] : 0;
    if (($tieneVar > 0 || isset($variantesPorProducto[$pid])) && isset($variantesPorProducto[$pid]) && count($variantesPorProducto[$pid]) > 0) {
        foreach ($variantesPorProducto[$pid] as $v) {
            $row = [];
            // Nombre: producto padre + identificación de variante (talla/color o SKU)
            $ident = trim((($v['talla'] ?? '') . ' ' . ($v['color'] ?? '')));
            if ($ident === '') $ident = ($v['sku'] ?? 'Variante');
            $row['nom_producto'] = $p['nom_producto'] . ' — ' . $ident;
            $row['imagen'] = !empty($v['imagen']) ? $v['imagen'] : $p['imagen'];
            $row['marca'] = $p['marca'];
            $row['descripcion'] = $p['descripcion'];
            $row['categoria'] = $p['categoria'];
            $row['cantidad'] = $v['cantidad'] ?? $p['cantidad'];
            $row['precio'] = $v['precio'] ?? $p['precio'];
            $row['id_producto'] = $v['id_variante']; // usar id_variante para identificar la fila
            $displayProductos[] = $row;
        }
    } else {
        // Producto sin variantes -> mostrar normal
        $displayProductos[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - <?= htmlspecialchars($proveedor_nombre) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#b4c24d',
                        secondary: '#2d4353',
                        dark: '#121212',
                        'dark-card': '#1e1e1e',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Animaciones suaves */
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .slide-up { animation: slideUp 0.5s ease-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar personalizada */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-dark dark:text-gray-100 transition-colors duration-300">

    <div class="min-h-screen p-6 md:p-10">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 fade-in">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-primary tracking-wider uppercase">Catálogo Digital</span>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white tracking-tight">
                        <?= htmlspecialchars($proveedor_nombre) ?>
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1 text-lg">
                        <?= htmlspecialchars($prov['empresa'] ?? 'Empresa no registrada') ?>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php?view=proveedores" 
                       class="group flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-dark-card border border-gray-200 dark:border-gray-700 rounded-xl text-gray-600 dark:text-gray-300 font-medium hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-lg transition-all duration-300">
                        <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            <!-- Controls & Filters -->
            <div class="bg-white dark:bg-dark-card rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4 slide-up" style="animation-delay: 0.1s;">
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span class="font-medium text-sm">Filtros activos:</span>
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded-md text-xs font-semibold text-gray-700 dark:text-gray-300">
                        <?= count($productos) ?> Productos
                    </span>
                </div>

                <form method="GET" action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']) ?>" class="flex items-center gap-3">
                    <input type="hidden" name="view" value="catalogo_proveedor">
                    <input type="hidden" name="proveedor" value="<?= htmlspecialchars($proveedor_id) ?>">
                    
                    <div class="relative group">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                        </svg>
                        <select name="orden" onchange="this.form.submit()" 
                                class="pl-10 pr-8 py-2 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-primary/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors appearance-none">
                            <option value="nom_asc"    <?= ($orden_get=='nom_asc')    ? 'selected' : '' ?>>Nombre (A-Z)</option>
                            <option value="nom_desc"   <?= ($orden_get=='nom_desc')   ? 'selected' : '' ?>>Nombre (Z-A)</option>
                            <option value="precio_asc" <?= ($orden_get=='precio_asc') ? 'selected' : '' ?>>Precio (Menor a Mayor)</option>
                            <option value="precio_desc"<?= ($orden_get=='precio_desc')? 'selected' : '' ?>>Precio (Mayor a Menor)</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Products Grid/Table -->
            <div class="bg-white dark:bg-dark-card rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-none overflow-hidden border border-gray-100 dark:border-gray-800 slide-up" style="animation-delay: 0.2s;">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php if (!empty($displayProductos)): ?>
                                <?php foreach ($displayProductos as $p): ?>
                                    <tr class="group hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="relative w-14 h-14 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 group-hover:shadow-md transition-all duration-300">
                                                    <?php if (!empty($p['imagen'])): ?>
                                                        <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                            </svg>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors">
                                                        <?= htmlspecialchars($p['nom_producto']) ?>
                                                    </h3>
                                                    <?php if (!empty($p['marca'])): ?>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                            <?= htmlspecialchars($p['marca']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                                <?= htmlspecialchars($p['categoria'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php 
                                            $stock = (int)$p['cantidad'];
                                            $stockClass = $stock > 20 
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800' 
                                                : ($stock > 5 
                                                    ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800' 
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800');
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $stockClass ?>">
                                                <?= $stock ?> un.
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-base font-bold text-gray-900 dark:text-white font-mono">
                                                $<?= number_format($p['precio'], 2) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No hay productos disponibles</h3>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm max-w-xs mx-auto">
                                                Este proveedor aún no tiene productos registrados en el catálogo.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Animaciones suaves */
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .slide-up { animation: slideUp 0.5s ease-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar personalizada */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>

</html>