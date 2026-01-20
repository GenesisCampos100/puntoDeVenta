<?php
// src/api/product_row.partial.php
// Render: Fila de un producto PRINCIPAL con diseño premium

$pid = htmlspecialchars($producto['cod_barras']);
$nombre = htmlspecialchars($producto['nom_producto']);
$sku = htmlspecialchars($producto['cod_barras']); // Fallback si SKU es null, usar cod_barras
if (!empty($producto['sku'])) $sku = htmlspecialchars($producto['sku']);

$categoria = htmlspecialchars($producto['nombre_categoria'] ?? $producto['categoria'] ?? 'Sin categoría');
$cantidad = (int)($producto['cantidad'] ?? 0);
$cantidad_min = (int)($producto['cantidad_min'] ?? 0);
$is_active = (int)($producto['is_active'] ?? 1);
$precio = $producto['precio'] ?? 0;
$tieneVariantes = ($producto['tiene_variante'] ?? 0) > 0;

// Lógica de color para stock
if ($cantidad > $cantidad_min) {
    $stockClass = 'bg-green-50 text-green-700 ring-1 ring-green-600/20';
} elseif ($cantidad > 0 && $cantidad <= $cantidad_min) {
    $stockClass = 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20';
} else {
    $stockClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
}

$imagen = !empty($producto['imagen']) ? "/puntoDeVenta/src/uploads/".htmlspecialchars($producto['imagen']) : "/puntoDeVenta/src/uploads/sin-imagen.png";
$jsonProducto = htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8');

// Detectar si el usuario es Cajero (solo puede ver detalles)
$esCajero = isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'cajero';
?>

<tr class="group hover:bg-gray-50 transition-colors duration-200 <?= $tieneVariantes ? 'product-parent cursor-pointer' : '' ?> <?php if(!$is_active) echo 'opacity-60 bg-gray-50'; ?>" 
    id="product-row-<?= $pid ?>"
    data-product-id="<?= $pid ?>"
    data-details="<?= $jsonProducto ?>">
    
    <td class="px-6 py-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 shadow-sm flex-shrink-0 bg-white group-hover:border-blue-200 transition-colors">
                <img src="<?= $imagen ?>" class="w-full h-full object-cover" alt="<?= $nombre ?>">
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-gray-900 text-sm mb-0.5 truncate group-hover:text-blue-600 transition-colors"><?= $nombre ?></div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-mono border border-gray-200"><?= $sku ?></span>
                    <?php if ($tieneVariantes): ?>
                        <span class="text-blue-600 font-medium flex items-center gap-1 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Variantes
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($tieneVariantes): ?>
                <button class="toggle-variants flex-shrink-0 w-8 h-8 rounded-full hover:bg-gray-200 transition-all duration-200 flex items-center justify-center text-gray-400 hover:text-gray-600" 
                        data-target-id="variants-<?= $pid ?>">
                    <svg class="arrow-icon h-5 w-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    </td>
    
    <td class="px-4 py-4 text-center">
        <span id="stock-<?= $pid ?>" data-min="<?= $cantidad_min ?>" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $stockClass ?>">
            <?= $cantidad ?>
        </span>
    </td>
    
    <td class="px-4 py-4 text-center hidden sm:table-cell">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
            <?= $categoria ?>
        </span>
    </td>
    
    <td class="px-4 py-4 text-center">
        <span class="font-bold text-gray-900 text-sm tracking-tight">
            <?= $tieneVariantes ? '—' : '$'.number_format($precio, 2) ?>
        </span>
    </td>
    
 <td class="px-6 py-4 text-right">
    <div class="inline-flex gap-2">
        <?php if ($tieneVariantes): ?>
            <!-- Si el producto tiene variantes, solo mostrar descatalogar/activar -->
            <button class="toggle-active px-3 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 hover:shadow-md hover:scale-105 <?= $is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>" 
                    title="<?= $is_active ? 'Descatalogar' : 'Activar' ?>"
                    data-id="<?= $pid ?>" 
                    data-type="producto" 
                    data-active="<?= $is_active ? 'true' : 'false' ?>">
                <?= $is_active ? 'Descatalogar' : 'Activar' ?>
            </button>
        <?php else: ?>
            <!-- Si NO tiene variantes, mostrar Ver, Ajustar y Activar/Descatalogar -->
            <button class="open-modal-btn px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-all duration-200 hover:shadow-md hover:scale-105" 
                    title="Ver Detalles"
                    data-id="<?= $pid ?>"
                    data-details='<?= $jsonProducto ?>'>
                Ver
            </button>

            <button class="btn-ajuste px-3 py-1.5 bg-green-50 text-green-600 rounded-lg text-sm font-semibold hover:bg-green-100 transition-all duration-200 hover:shadow-md hover:scale-105" 
                title="Ajustar Stock"
                data-id="<?= $pid ?>"
                data-type="producto"
                data-nombre="<?= htmlspecialchars($nombre) ?>">
                Ajustar
            </button>

            <button class="toggle-active px-3 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200 hover:shadow-md hover:scale-105 <?= $is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>" 
                    title="<?= $is_active ? 'Descatalogar' : 'Activar' ?>"
                    data-id="<?= $pid ?>" 
                    data-type="producto" 
                    data-active="<?= $is_active ? 'true' : 'false' ?>">
                <?= $is_active ? 'Descatalogar' : 'Activar' ?>
            </button>
        <?php endif; ?>
    </div>
</td>


</tr>

