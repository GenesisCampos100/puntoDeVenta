<?php
// src/api/variant_row.partial.php
// Render: Fila de una VARIANTE (dentro de la tabla anidada)

$vid = htmlspecialchars($var['id_variante']);
$skuVar = htmlspecialchars($var['sku']);
$talla = htmlspecialchars($var['talla'] ?? '-');
$color = htmlspecialchars($var['color'] ?? '-');
$cantidadVar = (int)($var['cantidad'] ?? 0);
$cantidadMinVar = (int)($var['cantidad_min'] ?? 0);
$precioVar = $var['precio'] ?? 0;
// Construir ruta absoluta para imágenes de variantes
$imgNameVar = htmlspecialchars($var['imagen'] ?? '');
if (!empty($imgNameVar)) {
    $imagenVar = '/PrismaMK2C/src/uploads/' . $imgNameVar;
    $imgPathVar = __DIR__ . '/../../src/uploads/' . $var['imagen'];
    if (!file_exists($imgPathVar)) {
        $imagenVar = '/PrismaMK2C/public/img/sin-imagen.png';
    }
} else {
    $imagenVar = '/PrismaMK2C/public/img/sin-imagen.png';
}

// Lógica de color para stock
if ($cantidadVar > $cantidadMinVar) {
    $stockClassVar = 'text-green-600 bg-green-50 ring-green-600/20';
} elseif ($cantidadVar > 0 && $cantidadVar <= $cantidadMinVar) {
    $stockClassVar = 'text-orange-600 bg-orange-50 ring-orange-600/20';
} else {
    $stockClassVar = 'text-red-600 bg-red-50 ring-red-600/20';
}

$jsonVariante = htmlspecialchars(json_encode($var), ENT_QUOTES, 'UTF-8');
?>

<tr class="hover:bg-white transition-colors">
    <td class="pl-12 py-3 pr-4">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md overflow-hidden border border-gray-200 bg-white">
                <img src="<?= $imagenVar ?>" class="w-full h-full object-cover" alt="Variante">
            </div>
            <div>
                <div class="text-xs font-bold text-gray-700">
                    <?= $talla ?> / <?= $color ?>
                </div>
                <div class="text-[10px] text-gray-400 font-mono">
                    <?= $skuVar ?>
                </div>
            </div>
        </div>
    </td>
    
    <td class="px-4 py-3 text-center">
        <span id="stock-<?= $skuVar ?>" data-min="<?= $cantidadMinVar ?>" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset <?= $stockClassVar ?>">
            <?= $cantidadVar ?>
        </span>
    </td>
    
    <td class="px-4 py-3 text-center hidden sm:table-cell">
        <span class="text-xs text-gray-400">—</span>
    </td>
    
    <td class="px-4 py-3 text-center">
        <span class="text-sm font-medium text-gray-700">
            $<?= number_format($precioVar, 2) ?>
        </span>
    </td>
    
    <td class="px-6 py-3 text-right">
        <div class="inline-flex gap-2">
        </div>
    </td>
</tr>

