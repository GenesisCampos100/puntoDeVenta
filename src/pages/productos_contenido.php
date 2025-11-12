<?php
// src/pages/productos_contenido.php
// Versión corregida y funcional — Inventario (productos + variantes + filtros AJAX)


require_once __DIR__ . "/../config/db.php";



// --- Parámetros GET (fallback igual que tenías) ---
$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'p.nom_producto ASC';
$vista_actual = $_GET['view'] ?? 'productos_contenido';

// -----------------------
// CONSULTAS INICIALES (para renderizar la página inicialmente)
// -----------------------
// Estas consultas sirven solo para el render inicial (si deseas pre-cargar).
// La carga dinámica de la tabla se hace vía AJAX a ../api/inventario_api.php?action=filtrar
$sql = "SELECT 
            p.cod_barras AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nom_producto AS producto_nombre,
            p.imagen AS producto_imagen,
            p.marca,
            p.descripcion,
            c.nombre AS categoria,
            p.cantidad,
            p.color,
            p.cantidad_min,
            p.costo,
            p.precio AS precio_unitario,
            p.id_categoria,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.cod_barras = p.cod_barras) AS tiene_variante,
            IFNULL(p.is_active,1) AS is_active
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1";

if (!empty($busqueda)) $sql .= " AND (p.nom_producto LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
if (!empty($categoria)) $sql .= " AND p.id_categoria = :categoria";

$sql .= " ORDER BY $orden";

$stmt = $pdo->prepare($sql);
$params = [];
if (!empty($busqueda)) $params[':busqueda'] = "%$busqueda%";
if (!empty($categoria)) $params[':categoria'] = $categoria;
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// variantes (pre-carga)
$variantesStmt = $pdo->query("
    SELECT 
        v.cod_barras AS id_producto,
        v.id_variante AS id,
        v.sku AS cod_barras,
        v.talla,
        v.color,
        v.cantidad,
        v.cantidad_min,
        v.precio,                      
        v.costo,
        v.imagen
    FROM variantes v
");
$variantesRaw = $variantesStmt->fetchAll(PDO::FETCH_ASSOC);
$variantesPorProducto = [];
foreach ($variantesRaw as $v) {
    $variantesPorProducto[$v['id_producto']][] = $v;
}

// categorías (para filtro)
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Productos — Inventario</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/lucide@0.257.0/dist/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Definición de la Paleta de Colores en Tailwind JIT */
        :root {
            --color-primary: #2d4353; /* Azul Oscuro */
            --color-success: #b4c24d; /* Verde Oliva */
            --color-alert: #e15871; /* Rosa/Rojo Suave */
            --color-background-subtle: #eeeeee; /* Gris Claro */
        }
        
        .bg-primary { background-color: var(--color-primary); }
        .text-primary { color: var(--color-primary); }
        .hover\:bg-primary-dark:hover { background-color: #243747; } /* Tono más oscuro de primary */
        .bg-success { background-color: var(--color-success); }
        .text-success { color: var(--color-success); }
        .text-alert { color: var(--color-alert); }
        .border-alert { border-color: var(--color-alert); }
        .bg-background-subtle { background-color: var(--color-background-subtle); }


        body { font-family: 'Poppins', sans-serif; }
        .product-inactive { opacity: 0.7; background-color: #fcfcfc; } /* Gris más sutil */

        /* Estilos de transición para la tabla */
        .variant-group.hidden {
            display: none !important;
        }

        /* Alineación de las columnas de la variante (Clave para la limpieza visual) */
        .variant-item {
            display: grid;
            /* La rejilla se ajusta a las columnas de la cabecera:
               Producto (col-span-5) | Stock (w-28) | Categoria (w-40) | Precio (w-28) | Columna Botón (w-20) | Acciones (w-36)
               Esto es una simulación de tabla dentro de un div, ajustada por grid.
               Se usa flex en el item y se ajustan los anchos relativos de la variante.
            */
        }
        
        /* Asegurar que el padding interno de la celda de la variante coincida con el padding de las celdas principales */
        .variant-item > div:first-child {
             /* 40% (Producto) + 28px (pl-4) */
             width: calc(40% - 3rem); /* Ajuste de ancho para la primera columna de la variante */
             padding-left: 2rem !important; /* pl-8 para indentación del contenido de la variante */
        }

        /* Medias Queries (Se mantiene tu base de Mobile First) */
        @media screen and (min-width: 1024px) {
            /* Definición de las columnas de la tabla para alinear la variante */
            #productos-table {
                table-layout: fixed;
            }
            #productos-table th:nth-child(1), #productos-table td:nth-child(1) { width: 40%; }
            #productos-table th:nth-child(2), #productos-table td:nth-child(2) { width: 10%; } /* Stock: 10% */
            #productos-table th:nth-child(3), #productos-table td:nth-child(3) { width: 15%; } /* Categoría: 15% */
            #productos-table th:nth-child(4), #productos-table td:nth-child(4) { width: 10%; } /* Precio: 10% */
            #productos-table th:nth-child(5), #productos-table td:nth-child(5) { width: 5%; } /* Toggle: 5% */
            #productos-table th:nth-child(6), #productos-table td:nth-child(6) { width: 20%; } /* Acciones: 20% */

            /* Ajuste de ancho para la estructura interna de la variante para que coincida con la tabla principal */
            .variant-row .variant-item > div {
                /* Padding uniforme */
                padding-top: 0.75rem; /* py-3 */
                padding-bottom: 0.75rem; /* py-3 */
            }

            .variant-row .variant-item > div:nth-child(1) { width: 40%; padding-left: 3rem; } /* Producto + Indentación */
            .variant-row .variant-item > div:nth-child(2) { width: 10%; } /* Stock */
            .variant-row .variant-item > div:nth-child(3) { width: 15%; } /* Categoría (vacío) */
            .variant-row .variant-item > div:nth-child(4) { width: 10%; } /* Precio */
            .variant-row .variant-item > div:nth-child(5) { width: 5%; } /* Toggle (vacío) */
            .variant-row .variant-item > div:nth-child(6) { width: 20%; text-align: right; } /* Acciones */
        }
        
    </style>
</head>

<body class="bg-background-subtle text-[#0f172a]">
    
    <div class="max-w-7xl mx-auto p-4 lg:pt-8">
    
    <div class="bg-white shadow-lg rounded-xl p-4 flex flex-col lg:flex-row gap-4 lg:items-center justify-between border-b border-gray-100 mb-6">
        
        <div class="flex items-center gap-3 w-full lg:w-3/5">
            <div class="relative w-full">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="busqueda" type="text" placeholder="Buscar producto por nombre o SKU..." 
                        value="<?= htmlspecialchars($busqueda) ?>"
                        class="pl-10 pr-10 py-2.5 w-full rounded-full border border-gray-200 focus:ring-2 focus:ring-success/50 focus:border-success/80 transition duration-150"/>
                <button id="clear-search" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-alert hidden">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="tabs" class="ml-2 inline-flex bg-background-subtle rounded-full p-1 shadow-inner flex-shrink-0">
                <button data-status="activo" class="tab-btn px-4 py-2 rounded-full text-sm font-semibold transition duration-200 
                    <?= ($status === 'activo' || empty($status)) ? 'bg-white text-primary shadow' : 'text-gray-600 hover:text-primary' ?>">
                    Activos
                </button>
                <button data-status="descatalogado" class="tab-btn px-4 py-2 rounded-full text-sm font-semibold transition duration-200
                    <?= ($status === 'descatalogado') ? 'bg-white text-primary shadow' : 'text-gray-600 hover:text-primary' ?>">
                    Descatalogados
                </button>
            </div>
        </div>

        <div class="flex gap-3 items-center w-full lg:w-auto flex-shrink-0">
            <select id="categoria" class="rounded-full border border-gray-200 px-4 py-2.5 bg-white text-sm focus:ring-success/50 focus:border-success/80 transition duration-150">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id_categoria']) ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="orden" class="rounded-full border border-gray-200 px-4 py-2.5 bg-white text-sm focus:ring-success/50 focus:border-success/80 transition duration-150">
                <option value="nom_asc" <?= ($orden == 'nom_asc') ? 'selected' : '' ?>>Nombre (A → Z)</option>
                <option value="nom_desc" <?= ($orden == 'nom_desc') ? 'selected' : '' ?>>Nombre (Z → A)</option>
                <option value="precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>>Precio ↑</option>
                <option value="precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>>Precio ↓</option>
            </select>

            <button id="btnAgregarProducto" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-white font-semibold transition duration-200 hover:bg-primary-dark shadow-md">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Agregar
            </button>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden productos-container">
        <div class="overflow-x-auto">
            <table id="productos-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-primary text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider rounded-tl-2xl">
                            Producto / SKU
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider">Stock</th>
                        <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider hidden sm:table-cell">Categoría</th>
                        <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider">Precio</th>
                        <th class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider"></th>
                        <th class="px-4 py-4 text-right text-xs font-bold uppercase tracking-wider rounded-tr-2xl">Acciones</th>
                    </tr>
                </thead>

                <tbody id="tabla-productos" class="bg-white divide-y divide-gray-100">
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $producto):
                            $pid = htmlspecialchars($producto['id_producto']);
                            $nombre = htmlspecialchars($producto['producto_nombre']);
                            $sku = htmlspecialchars($producto['producto_cod_barras']);
                            $tieneVariantes = $producto['tiene_variante'] > 0 && !empty($variantesPorProducto[$producto['id_producto']]);
                            $cantidad = (int)($producto['cantidad'] ?? 0);
                            $cantidad_min = (int)($producto['cantidad_min'] ?? 0);
                            $is_active = (int)($producto['is_active'] ?? 1);
                            
                            // Colores de la paleta: #e15871 (Alert/Rosa) vs #b4c24d (Success/Verde)
                            $stockClass = ($cantidad <= $cantidad_min && $cantidad_min > 0) 
                                ? 'bg-[#e15871]/20 text-alert font-bold border border-alert/50' 
                                : 'bg-[#b4c24d] text-white font-bold';
                            $imagen = !empty($producto['producto_imagen']) ? "uploads/".htmlspecialchars($producto['producto_imagen']) : "../uploads/sin-imagen.png";
                            $jsonProducto = htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8');
                        ?>
                        
                        <tr class="producto-row transition duration-300 ease-in-out hover:bg-gray-50 <?= $tieneVariantes ? 'product-parent cursor-pointer' : '' ?> <?php if(!$is_active) echo 'product-inactive'; ?>" 
                            id="product-row-<?= $pid ?>"
                            data-product-id="<?= $pid ?>"
                            data-details="<?= $jsonProducto ?>">

                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-4">
                                    <img src="<?= $imagen ?>" class="w-14 h-16 object-cover rounded-lg shadow-sm border border-gray-200" alt="img">
                                    <div>
                                        <div class="font-semibold text-base text-gray-800 line-clamp-2"><?= $nombre ?></div>
                                        <div class="text-xs text-gray-500 mt-0.5">SKU: **<?= $sku ?>**</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <span id="stock-<?= $pid ?>" data-min="<?= $cantidad_min ?>" class="inline-block px-3 py-1 rounded-full text-xs <?= $stockClass ?>">
                                    <?= $cantidad ?> unid.
                                </span>
                            </td>

                            <td class="px-4 py-4 align-top text-sm text-gray-600 hidden sm:table-cell"><?= htmlspecialchars($producto['categoria']) ?></td>

                            <td class="px-4 py-4 align-top font-bold text-gray-800">$<?= number_format($producto['precio_unitario'], 2) ?></td>
                            
                            <td class="px-4 py-4 align-top text-center">
                                <?php if ($tieneVariantes): ?>
                                    <button class="toggle-variants p-1 rounded-full text-primary hover:bg-gray-200 transition duration-150" data-target-id="variants-<?= $pid ?>" title="Ver variantes">
                                        <svg class="h-5 w-5 transform transition duration-300 arrow-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-4 align-top text-right">
                                <div class="inline-flex items-center gap-1 justify-end">
                                    <button title="Ajustar stock" class="btn-ajuste p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-primary transition"
                                        onclick="openMovimientoModal('<?= $pid ?>','producto','<?= addslashes($nombre) ?>', <?= $tieneVariantes ? 'true' : 'false' ?>)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.747 2.25-1.747 2.676 0l.504 2.052a1 1 0 00.9.607l2.179.336c1.808.277 1.808 2.678 0 2.954l-2.179.336a1 1 0 00-.9.607l-.504 2.052c-.426 1.747-2.25 1.747-2.676 0l-.504-2.052a1 1 0 00-.9-.607l-2.179-.336c-1.808-.277-1.808-2.678 0-2.954l2.179-.336a1 1 0 00.9-.607l.504-2.052z"/></svg>
                                    </button>

                                    <button title="Ver detalle" class="p-2 rounded-full text-primary hover:bg-primary-dark hover:text-white open-modal-btn transition" 
                                        data-details='<?= $jsonProducto ?>'>
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    <button title="<?= $is_active ? 'Descatalogar' : 'Activar' ?>" 
                                        class="toggle-active p-2 rounded-full <?= $is_active ? 'text-alert hover:bg-red-50' : 'text-success hover:bg-green-50' ?> transition"
                                        data-id="<?= $pid ?>" data-type="producto" data-active="<?= $is_active ? 'true' : 'false' ?>">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $is_active ? 'M6 18L18 6M6 6l12 12' : 'M5 13l4 4L19 7' ?>" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <?php if ($tieneVariantes): ?>
                            <tr id="variants-<?= $pid ?>" class="variant-group hidden transition-all duration-300 ease-in-out">
                                <td colspan="6" class="p-0 border-t border-gray-200/50">
                                    <div class="px-0 py-0 overflow-hidden">
                                        <div class="bg-gray-50 border-b border-gray-100 divide-y divide-gray-100">
                                            <?php foreach ($variantesPorProducto[$producto['id_producto']] as $var):
                                                $vsku = htmlspecialchars($var['cod_barras']);
                                                $vcant = (int)($var['cantidad'] ?? 0);
                                                $vcant_min = (int)($var['cantidad_min'] ?? 0);
                                                // Colores de la paleta: #e15871 (Alert/Rosa) vs #b4c24d (Success/Verde)
                                                $vstockClass = ($vcant <= $vcant_min && $vcant_min > 0) 
                                                    ? 'bg-[#e15871]/20 text-alert font-semibold' 
                                                    : 'bg-[#b4c24d]/20 text-success font-semibold';
                                                $jsonVar = htmlspecialchars(json_encode($var + ['producto_nombre' => $producto['producto_nombre'], 'categoria' => $producto['categoria'], 'id_producto' => $producto['id_producto']]), ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <div class="variant-item flex items-center text-sm">
                                                    
                                                    <div class="text-gray-700 px-4"> 
                                                        <span class="font-medium">Talla: **<?= htmlspecialchars($var['talla'] ?: '—') ?>** | Color: **<?= htmlspecialchars($var['color'] ?: '—') ?>**</span>
                                                        <div class="text-xs text-gray-500 mt-0.5">SKU: <?= $vsku ?></div>
                                                    </div>

                                                    <div class="px-4">
                                                        <span id="stock-<?= $vsku ?>" data-min="<?= $vcant_min ?>" class="px-2 py-0.5 rounded-md text-xs <?= $vstockClass ?>">
                                                            <?= $vcant ?> unid.
                                                        </span>
                                                    </div>

                                                    <div class="hidden sm:block px-4"></div>

                                                    <div class="px-4 font-bold text-gray-700">
                                                        $<?= number_format($var['precio'] ?? 0, 2) ?>
                                                    </div>
                                                    
                                                    <div class="px-4"></div>

                                                    <div class="px-4 text-right">
                                                        <div class="inline-flex gap-1 justify-end items-center">
                                                            <button title="Ajustar stock variante" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-primary transition"
                                                                onclick="openMovimientoModal('<?= htmlspecialchars($vsku) ?>','variante','<?= addslashes($producto['producto_nombre'] . ' - ' . ($var['talla'] ?? '')) ?>', false)">
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.747 2.25-1.747 2.676 0l.504 2.052a1 1 0 00.9.607l2.179.336c1.808.277 1.808 2.678 0 2.954l-2.179.336a1 1 0 00-.9.607l-.504 2.052c-.426 1.747-2.25 1.747-2.676 0l-.504-2.052a1 1 0 00-.9-.607l-2.179-.336c-1.808-.277-1.808-2.678 0-2.954l2.179-.336a1 1 0 00.9-.607l.504-2.052z"/></svg>
                                                            </button>
                                                            <button title="Ver detalle variante" class="p-2 rounded-full text-primary hover:bg-primary-dark hover:text-white open-modal-btn transition"
                                                                data-details='<?= $jsonVar ?>'>
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">No se encontraron productos que coincidan con los filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- DETALLE MODAL -->
<div id="modal" class="hidden fixed inset-0 flex items-center justify-center z-[1000] bg-[#2d4353]/80 p-4">
    <div class="bg-white p-6 sm:p-8 rounded-xl w-full max-w-2xl relative shadow-2xl text-[#2d4353]">
        <button class="absolute top-2 right-3 sm:top-4 sm:right-5 text-[#2d4353] text-3xl font-bold cursor-pointer" onclick="cerrarModal()">✖</button>

        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 border-b border-[#eeeeee] pb-4 sm:pb-6 mb-4 sm:mb-6">
            <div class="w-32 h-44 sm:w-36 sm:h-52 bg-[#f7f7f7] rounded-lg overflow-hidden flex-shrink-0 border-2 border-[#eeeeee] mx-auto sm:mx-0">
                <img id="modal-img" src="" alt="Producto" class="w-full h-full object-cover">
            </div>

            <div class="flex-grow text-center sm:text-left">
                <h3 class="text-xl sm:text-2xl font-bold mb-1 sm:mb-2 text-[#2d4353]" id="modal-nombre"></h3>
                <div class="text-gray-600 text-sm mb-1 sm:mb-2">Categoría <span id="modal-categoria"></span></div>
                <div class="text-gray-500 text-xs sm:text-sm mb-3 sm:mb-5">Código de barras: <span id="modal-codigo"></span></div>

                <div class="bg-[#f0f4db] p-3 sm:p-4 rounded-lg border-l-4 border-[#b4c24d] text-left">
                    <span class="text-sm text-gray-600 block">Precio de Venta</span>
                    <span class="text-3xl sm:text-4xl font-bold text-[#2d4353]">$<span id="modal-precio"></span></span>
                    <small class="text-xs text-gray-500 block">IVA %16 incluido</small>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:gap-5 mb-6">
             <div class="flex-1 p-4 rounded-lg bg-[#f7f7f7] border border-[#eeeeee]">
                 <span class="text-sm text-gray-600 block">Costo unitario</span>
                 <span class="text-xl sm:text-2xl font-bold text-[#2d4353]">$<span id="modal-costo"></span></span>
                 <small class="text-xs text-gray-500 block">Precio sin margen</small>
             </div>

             <div class="flex-1 p-4 rounded-lg bg-[#f7f7f7] border border-[#eeeeee]">
                 <span class="text-sm text-gray-600 block">Existencias</span>
                 <span class="text-xl sm:text-2xl font-bold text-[#2d4353]"><span id="modal-stock"></span> unidades</span>
                 <small class="text-xs text-gray-500 block">Mínimo de stock: <span id="modal-stock-min"></span></small>
             </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <button id="modal-btn-eliminar" class="px-5 py-3 rounded-lg font-semibold text-white bg-[#e15871]" data-id="" data-type="" onclick="confirmarEliminar(this)">
                🗑️ Eliminar
            </button>
            <a id="modal-btn-editar" class="px-5 py-3 rounded-lg font-semibold bg-[#b4c24d] text-[#2d4353]">✏️ Editar</a>
        </div>

    </div>
</div>

<!-- Confirm modal eliminar -->
<div id="confirmModal" class="hidden fixed inset-0 flex items-center justify-center z-[9999] bg-[#2d4353]/80 p-4">
  <div class="bg-white p-6 rounded-2xl shadow-xl text-center max-w-sm w-full text-[#2d4353]">
    <h3 class="text-lg font-semibold mb-4 text-[#2d4353]">Confirmar eliminación</h3>
    <p class="text-gray-600 mb-6" id="confirmMessage">¿Seguro que deseas eliminar?</p>
    <div class="flex justify-center gap-4">
      <button id="cancelBtn" class="px-4 py-2 rounded-lg bg-[#eeeeee] text-[#2d4353] hover:bg-[#dddddd] w-1/2">Cancelar</button>
      <button id="confirmBtn" class="px-4 py-2 rounded-lg bg-[#e15871] text-white w-1/2">Eliminar</button>
    </div>
  </div>
</div>

<script>
/* ==========================
   CONFIGURACIÓN
   ========================== */
const API_URL = '/puntoDeVenta/src/api/inventario_api.php';
const $tablaCuerpo = $("#tabla-productos");
const $barraBusqueda = $("#busqueda");
const $selectCategoria = $("#categoria");
const $selectOrden = $("#orden");
const $tabsButtons = $("#tabs .tab-btn");
const $clearSearchBtn = $("#clear-search");

// Helper debounce
function debounce(fn, wait=300){
    let t;
    return function(...args){
        clearTimeout(t);
        t = setTimeout(()=> fn.apply(this, args), wait);
    };
}

/* ==========================
   FUNCIÓN CENTRAL: Cargar productos vía AJAX
   ========================== */
function cargarProductos() {
    const activeTab = $("#tabs .tab-activa").data("status") || "activo";
    const params = {
        action: "filtrar",
        busqueda: $barraBusqueda.val() || '',
        categoria: $selectCategoria.val() || '',
        orden: $selectOrden.val() || 'nom_asc',
        tab: activeTab
    };

    $.ajax({
        url: API_URL,
        method: "GET",
        data: params,
        beforeSend: function() {
            $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-gray-500">Cargando productos...</td></tr>`);
        },
        success: function(res) {
            if (!res) {
                $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-red-500">Respuesta vacía del servidor.</td></tr>`);
                return;
            }
            if (res.success) {
                $tablaCuerpo.html(res.html);
                // Reactivar lucide si lo necesitas (silencioso si falla)
                try { if (window.lucide) lucide.createIcons(); } catch(e){}
            } else {
                $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-red-500">${res.message || 'Error'}</td></tr>`);
            }
        },
        error: function(xhr, status, err) {
            console.error("AJAX error:", status, err);
            $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-red-500">Error al cargar los productos.</td></tr>`);
        }
    });
}

/* ==========================
   Eventos: búsqueda, filtros y tabs
   ========================== */
$barraBusqueda.on("input", debounce(function(){
    const val = $(this).val().trim();
    if (val.length) $clearSearchBtn.show(); else $clearSearchBtn.hide();
    cargarProductos();
}, 350));

$clearSearchBtn.on("click", function(){
    $barraBusqueda.val('');
    $(this).hide();
    cargarProductos();
});

$selectCategoria.on("change", cargarProductos);
$selectOrden.on("change", cargarProductos);

// Tabs
$tabsButtons.on("click", function(){
    $tabsButtons.removeClass("tab-activa bg-blue-500 text-white");
    $(this).addClass("tab-activa bg-blue-500 text-white");
    cargarProductos();
});

/* ==========================
   Botón agregar producto
   ========================== */
$("#btnAgregarProducto").on("click", function(){
    // Si quieres abrir página de agregar:
    window.location.href = "index.php?view=agregar_producto";
});

/* ==========================
   Delegación de eventos para filas renderizadas por AJAX
   ========================== */

// Abrir detalle: .open-modal-btn (usa data-details JSON)
$(document).on("click", ".open-modal-btn", function(){
    const details = $(this).attr("data-details");
    if (!details) return;
    let obj = null;
    try {
        obj = JSON.parse(details);
    } catch(e) {
        console.error("Error parseando data-details:", e);
        Swal.fire('Error','No se pudo leer la información del producto','error');
        return;
    }
    openCustomModalFromJSON(obj);
});

// Ajuste de stock (abre prompt simple o modal)
$(document).on("click", ".btn-ajuste", function(){
    const id = $(this).data("id");
    const name = $(this).closest("tr").find(".font-semibold").first().text().trim() || id;
    const isVar = $(this).data("isvariante") || false;
    openMovimientoModal(id, isVar ? 'variante' : 'producto', name, isVar);
});

// Toggle activo/inactivo (botón con class toggle-active)
$(document).on("click", ".toggle-active", function(){
    const $btn = $(this);
    const id = $btn.data("id");
    const currentActive = String($btn.data("active")) === 'true';
    const newStatus = currentActive ? 0 : 1;

    Swal.fire({
        title: `¿Confirmar ${newStatus === 1 ? 'activar' : 'descatalogar'}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (!result.isConfirmed) return;
        $.post(API_URL, { action: "toggle_activo", id: id, status: newStatus }, function(res){
            if (res.success) {
                Swal.fire('Hecho', res.message || 'Estado cambiado', 'success');
                cargarProductos();
            } else {
                Swal.fire('Error', res.message || 'No se pudo cambiar', 'error');
            }
        }, "json").fail(() => {
            Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
        });
    });
});

/* ==========================
   Modal: abrir con data-details (robusto)
   ========================== */
function openCustomModalFromJSON(obj) {
    if (!obj) return;
    const isVariant = !!(obj.id_variante || obj.sku && (obj.talla !== undefined || obj.color !== undefined));

    const productName = obj.producto_nombre || obj.nom_producto || 'Sin nombre';
    $("#modal-nombre").text(productName);
    $("#modal-categoria").text(obj.categoria || obj.nombre_categoria || 'Sin categoría');
    $("#modal-codigo").text(obj.cod_barras || obj.producto_cod_barras || obj.sku || 'N/A');

    const imageKey = obj.imagen || obj.producto_imagen || null;
    $("#modal-img").attr('src', imageKey ? ("uploads/" + imageKey) : "../uploads/sin-imagen.png");

    const precio = (typeof obj.precio !== 'undefined' && obj.precio !== null) ? parseFloat(obj.precio).toFixed(2)
                 : (typeof obj.precio_unitario !== 'undefined' ? parseFloat(obj.precio_unitario).toFixed(2) : '—');
    const costo = (typeof obj.costo !== 'undefined' && obj.costo !== null) ? parseFloat(obj.costo).toFixed(2) : '—';
    const stock = (typeof obj.cantidad !== 'undefined') ? obj.cantidad : '—';
    const stockMin = (typeof obj.cantidad_min !== 'undefined') ? obj.cantidad_min : '—';

    $("#modal-precio").text(precio);
    $("#modal-costo").text(costo);
    $("#modal-stock").text(stock);
    $("#modal-stock-min").text(stockMin);

    const $btnEliminar = $("#modal-btn-eliminar");
    const $btnEditar = $("#modal-btn-editar");

    if (isVariant) {
        const idVar = obj.id_variante ?? obj.sku;
        $btnEditar.attr('href', `index.php?view=editar_variante&id=${encodeURIComponent(idVar)}&prod_cod_barras=${encodeURIComponent(obj.id_producto ?? obj.cod_barras)}`);
        $btnEliminar.attr('data-id', idVar).attr('data-type','variante');
    } else {
        const idProd = obj.id_producto ?? obj.producto_cod_barras ?? obj.cod_barras;
        $btnEditar.attr('href', `index.php?view=editar_producto&id=${encodeURIComponent(idProd)}`);
        $btnEliminar.attr('data-id', idProd).attr('data-type','producto');
    }

    $("#modal").fadeIn(120).removeClass('hidden').css('display','flex');
}

function cerrarModal(){
    $("#modal").fadeOut(120, function(){ $(this).addClass('hidden'); });
}

/* ==========================
   Confirmación eliminación
   ========================== */
let deleteId = null;
let deleteType = null;

function confirmarEliminar(element) {
    deleteId = $(element).attr('data-id');
    deleteType = $(element).attr('data-type');

    if (!deleteId || !deleteType) return console.error("Falta id o tipo.");

    $("#confirmMessage").text('¿Estás seguro de que quieres eliminar? Esta acción no se puede deshacer.');
    $("#confirmModal").removeClass('hidden').fadeIn(120).css('display','flex');
}

$("#cancelBtn").on("click", function(){
    $("#confirmModal").fadeOut(120, function(){ $(this).addClass('hidden'); });
});

$("#confirmBtn").on("click", function(){
    if (!deleteId || !deleteType) return;
    // Redirige a tu script de eliminación (si tienes uno)
    window.location.href = `pages/productos_eliminar.php?type=${encodeURIComponent(deleteType)}&id=${encodeURIComponent(deleteId)}`;
});

/* ==========================
   Ajuste de stock (prompt simple con fetch)
   ========================== */
function openMovimientoModal(cod_entidad, type, nombre, hasVariantes){
    Swal.fire({
        title: `Ajuste: ${nombre}`,
        input: 'number',
        inputLabel: 'Cantidad (positivo para entrada, negativo para salida)',
        inputPlaceholder: 'Ej. 10 o -5',
        showCancelButton: true,
        preConfirm: (value) => {
            if (value === '' || value === null || isNaN(value)) {
                Swal.showValidationMessage('Ingresa una cantidad válida');
            } else return parseInt(value,10);
        }
    }).then(res => {
        if (!res.isConfirmed) return;
        const cantidad = parseInt(res.value,10);
        const fd = new FormData();
        fd.append('cod_entidad', cod_entidad);
        fd.append('cantidad', cantidad);
        fd.append('ajusteEsVariante', (type === 'variante') ? 'true' : 'false');

        fetch(API_URL + '?action=ajustar_stock', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Hecho', data.message || 'Ajuste registrado', 'success');
                    if (typeof data.nuevo_stock !== 'undefined') {
                        const target = document.getElementById('stock-' + cod_entidad);
                        if (target) {
                            target.textContent = data.nuevo_stock + ' unid.';
                            const min = parseInt(target.dataset.min || -1,10);
                            if (min >= 0 && data.nuevo_stock <= min) {
                                target.classList.remove('bg-green-50','text-green-800');
                                target.classList.add('bg-red-100','text-red-600');
                            } else {
                                target.classList.remove('bg-red-100','text-red-600');
                                target.classList.add('bg-green-50','text-green-800');
                            }
                        }
                    }
                } else {
                    Swal.fire('Error', data.message || 'No se pudo registrar', 'error');
                }
            }).catch(err => {
                console.error(err);
                Swal.fire('Error', 'Falla de conexión', 'error');
            });
    });
}

/* ==========================
   Inicialización al cargar la página
   ========================== */
$(document).ready(function(){
    // mostrar icono clear si hay texto inicial
    if ($barraBusqueda.val().trim().length > 0) $clearSearchBtn.show();
    // carga inicial por AJAX (garantiza que los filtros funcionen siempre)
    cargarProductos();
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleButtons = document.querySelectorAll('.toggle-variants');

        toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita que se dispare el evento del row padre si lo hubiese.
                
                const targetId = button.getAttribute('data-target-id');
                const targetRow = document.getElementById(targetId);
                const arrowIcon = button.querySelector('.arrow-icon');

                if (targetRow.classList.contains('hidden')) {
                    // Abrir
                    targetRow.classList.remove('hidden');
                    arrowIcon.classList.add('rotate-180');
                    
                    // Animación de opacidad (opcional, si las filas internas lo soportan)
                    setTimeout(() => {
                        targetRow.style.opacity = '1';
                    }, 50);

                } else {
                    // Cerrar
                    targetRow.style.opacity = '0'; // Animación de fade-out
                    
                    setTimeout(() => {
                        targetRow.classList.add('hidden');
                        arrowIcon.classList.remove('rotate-180');
                    }, 300); // Coincide con la duración de la transición
                }
            });
        });
        
        // Agregar funcionalidad para que hacer clic en la fila principal (si tiene variantes) también haga el toggle
        document.querySelectorAll('.product-parent').forEach(row => {
            row.addEventListener('click', (e) => {
                const productId = row.getAttribute('data-product-id');
                const toggleButton = document.querySelector(`.toggle-variants[data-target-id="variants-${productId}"]`);
                if (toggleButton) {
                    toggleButton.click();
                }
            });
        });
    });
</script>

</body>
</html>     
