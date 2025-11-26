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
        <div class="flex items-center justify-end gap-2">
            <button class="open-modal-btn p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-all" 
                    title="Ver Detalles"
                    data-details='<?= $jsonVariante ?>'>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            </button>
            
            <button class="btn-ajuste p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-md transition-all" 
                    title="Ajustar Stock"
                    data-id="<?= $skuVar ?>"
                    data-type="variante"
                    data-nombre="<?= htmlspecialchars($var['producto_nombre'] . ' (' . $talla . '/' . $color . ')') ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            </button>
            
            <button class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-all" 
                    title="Eliminar Variante"
                    onclick="confirmarEliminar(this)"
                    data-id="<?= $vid ?>" 
                    data-type="variante">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>
        </div>
    </td>
</tr>
