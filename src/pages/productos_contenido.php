<?php
// src/pages/productos_contenido.php
// VersiÃ³n Premium - Estilo consistente con clientes_contenido.php
// Mantiene TODA la lÃ³gica de backend intacta.

require_once __DIR__ . "/../config/db.php";

// -----------------------
// LÃ“GICA PHP (INTACTA)
// -----------------------

// 1. Mapeo para prevenir errores de SQL Injection y columna invÃ¡lida
$mapOrder = [
    'nom_asc'    => 'p.nom_producto ASC',
    'nom_desc'   => 'p.nom_producto DESC',
    'precio_asc' => 'p.precio ASC',
    'precio_desc' => 'p.precio DESC',
    'p.nom_producto ASC' => 'p.nom_producto ASC'
];

$orden_get = $_GET['orden'] ?? 'nom_asc';
$orden_sql = $mapOrder[$orden_get] ?? $mapOrder['nom_asc'];
$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'p.nom_producto ASC';
$vista_actual = $_GET['view'] ?? 'productos_contenido';

// Consulta Inicial
$sql = "SELECT 
            p.cod_barras,
            p.cod_barras AS id_producto,
            p.nom_producto,
            p.imagen,
            p.marca,
            p.descripcion,
            c.nombre AS categoria,
            p.cantidad,
            p.color,
            p.sku,
            p.cantidad_min,
            p.costo,
            p.precio,
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

// Variantes (pre-carga)
$variantesStmt = $pdo->query("
    SELECT 
        v.cod_barras AS id_producto,
        v.id_variante,
        v.sku,
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

// CategorÃ­as
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
$totalProductos = count($productos);
$stockBajo = 0;
foreach($productos as $p) {
    if($p['cantidad'] <= $p['cantidad_min']) $stockBajo++;
}
?>

<!-- Dependencias -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Poppins', 'sans-serif'],
          },
        },
      },
    }
</script>

<style>
    :root {
        --primary: #b4c24d;
        --primary-dark: #9fb03d;
        --secondary: #2d4353;
        --accent: #e15871;
        --gray-bg: #eeeeee;
    }
    
    body { 
        font-family: 'Poppins', sans-serif; 
        background: linear-gradient(135deg, #f9fafb 0%, #eeeeee 100%);
        min-height: 100vh;
    }
    
    /* Animaciones Premium */
    @keyframes fadeIn { 
        from { opacity: 0; } 
        to { opacity: 1; } 
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideUp { 
        from { opacity: 0; transform: translateY(15px); } 
        to { opacity: 1; transform: translateY(0); } 
    }
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }

    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-slideDown { animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .animate-slideUp { animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .animate-scaleIn { animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .delay-100 { animation-delay: 0.1s; animation-fill-mode: both; }
    .delay-200 { animation-delay: 0.2s; animation-fill-mode: both; }

    /* Hover Effects Premium */
    .hover-lift {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .hover-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    }

    /* Search Input Focus */
    .search-input {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .search-input:focus { 
        box-shadow: 0 0 0 4px rgba(180, 194, 77, 0.1);
        border-color: var(--primary);
    }
    .search-input.has-value {
        border-color: var(--primary);
        background: rgba(180, 194, 77, 0.02);
    }
    
    /* Table Row Hover */
    tbody tr {
        transition: all 0.2s ease;
    }
    tbody tr:hover {
        background: rgba(180, 194, 77, 0.04);
    }
    
    /* Tabs Active State */
    .tab-btn {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .tab-btn.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(180, 194, 77, 0.3);
    }
    
    /* Modal Backdrop */
    .modal-backdrop {
        backdrop-filter: blur(8px);
        background: rgba(0, 0, 0, 0.4);
    }
</style>

    <style>
        /* Pequeños ajustes visuales compartidos */
        body { font-family: 'Poppins', sans-serif; background-color: #f3f6f9; color: #0f172a; }
        .product-inactive { background-color: #f8fafc !important; color: #6b7280 !important; }
        .product-inactive strong { color: #6b7280 !important; }
        .fade-in { animation: fadeIn .18s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px);} to { opacity: 1; transform: translateY(0); } }
        /* Utilities small-screen */
        @media (max-width: 640px) {
            .btn-text-mobile-hidden { display: none; }
        }
    </style>

<div class="max-w-7xl mx-auto p-4 md:p-6 pb-32">

    <!-- Header Section -->
    <div class="mb-8 animate-slideDown">
        <div class="mb-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestión de Inventario</h1>
            <p class="text-gray-600 text-base">Administra tu catalogo y existencias de forma eficiente</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Total Productos -->
            <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Total Productos</p>
                        <p class="text-3xl font-bold text-gray-900" id="totalProductos"><?= $totalProductos ?></p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Stock Bajo -->
            <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift animate-slideUp delay-100 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Stock Bajo</p>
                        <p class="text-3xl font-bold" style="color: #e15871;"><?= $stockBajo ?></p>
                    </div>
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #e15871 0%, #d14560 100%);">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 animate-slideUp delay-100 border border-gray-100">
        <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center">
            
            <!-- Search & Tabs Container -->
            <div class="flex flex-col md:flex-row gap-4 flex-1 items-stretch md:items-center">
                <!-- Search Bar -->
                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="busqueda" type="text" placeholder="Buscar por nombre, cÃ³digo o SKU..." 
                           value="<?= htmlspecialchars($busqueda) ?>"
                           class="search-input w-full pl-12 pr-12 py-3.5 rounded-xl border-2 border-gray-200 focus:border-primary focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400 font-medium">
                    <button id="clear-search" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-accent transition-colors hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex bg-gray-100 p-1.5 rounded-xl">
                    <button data-status="activo" class="tab-btn active flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-bold transition-all">
                        Activos
                    </button>
                    <button data-status="descatalogado" class="tab-btn flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-bold text-gray-500 hover:text-gray-900 transition-all">
                        Descatalogados
                    </button>
                </div>
            </div>

            <!-- Filters & Actions -->
            <div class="flex flex-wrap md:flex-nowrap gap-3 justify-end">
                <select id="categoria" class="px-4 py-3.5 rounded-xl border-2 border-gray-200 bg-white text-sm font-semibold focus:border-primary focus:outline-none cursor-pointer hover:bg-gray-50 transition-all">
                    <option value="">Todas las categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['id_categoria']) ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="orden" class="px-4 py-3.5 rounded-xl border-2 border-gray-200 bg-white text-sm font-semibold focus:border-primary focus:outline-none cursor-pointer hover:bg-gray-50 transition-all hidden md:block">
                    <option value="nom_asc" <?= ($orden == 'nom_asc') ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    <option value="nom_desc" <?= ($orden == 'nom_desc') ? 'selected' : '' ?>>Nombre (Z-A)</option>
                    <option value="precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>>Precio: Menor</option>
                    <option value="precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>>Precio: Mayor</option>
                </select>

                <button id="btnAgregarProducto" onclick="window.location.href='index.php?view=agregar_producto'" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="hidden sm:inline">Nuevo Producto</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden animate-slideUp delay-200 border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Stock</th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider hidden sm:table-cell">CategorÃ­a</th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-productos" class="bg-white divide-y divide-gray-200">
                    <?php 
                        // Renderizado Inicial PHP
                        if (!empty($productos)) {
                            foreach ($productos as $producto) {
                                // Ajuste de claves para compatibilidad con partial
                                $producto['nombre_categoria'] = $producto['categoria'];
                                
                                include __DIR__ . '/../api/product_row.partial.php';
                                
                                if (($producto['tiene_variante'] > 0) && isset($variantesPorProducto[$producto['id_producto']])) {
                                    echo '<tr id="variants-' . $producto['id_producto'] . '" class="hidden transition-all duration-300 ease-in-out">';
                                    echo '<td colspan="5" class="p-0 border-t-0">';
                                    echo '<div class="px-6 py-4 bg-gray-50/80 border-y border-gray-100 shadow-inner">';
                                    echo '<table class="w-full">';
                                    echo '<tbody class="divide-y divide-gray-200/50">';
                                    foreach ($variantesPorProducto[$producto['id_producto']] as $var) {
                                        $var['producto_nombre'] = $producto['nom_producto'];
                                        $var['categoria'] = $producto['categoria'];
                                        $var['id_producto'] = $producto['id_producto'];
                                        include __DIR__ . '/../api/variant_row.partial.php';
                                    }
                                    echo '</tbody></table></div></td></tr>';
                                }
                            }
                        } else {
                            echo '<tr><td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center animate-fadeIn">
                                        <svg class="w-24 h-24 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron productos</h3>
                                        <p class="text-gray-500">Intenta ajustar los filtros o tu bÃºsqueda</p>
                                    </div>
                                  </td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Detalles (Premium) -->
<div id="modalDetalle" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="absolute inset-0 modal-backdrop" onclick="cerrarModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden animate-scaleIn">
        
        <!-- Header con gradiente -->
        <div class="px-6 py-4 border-b flex items-center justify-between" style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6" style="color: #b4c24d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Detalle del Producto
            </h3>
            <button onclick="cerrarModal()" class="text-white/70 hover:text-white text-2xl leading-none transition-colors">&times;</button>
        </div>
        
        <!-- Modal Header Image -->
        <div class="relative h-64 bg-gradient-to-br from-gray-100 to-gray-200 group overflow-hidden">
            <img id="modal-img" src="" alt="Producto" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
            
            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                <span id="modal-categoria" class="inline-block px-3 py-1.5 bg-white/20 backdrop-blur-md rounded-lg text-xs font-bold uppercase tracking-wider mb-3 border border-white/20"></span>
                <h3 id="modal-nombre" class="text-2xl font-bold leading-tight"></h3>
            </div>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Price Section -->
            <div class="flex justify-between items-end border-b-2 border-gray-100 pb-6">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Precio de Venta</p>
                    <p class="text-4xl font-bold tracking-tight" style="color: #b4c24d;">$<span id="modal-precio"></span></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Costo</p>
                    <p class="text-xl font-semibold text-gray-600">$<span id="modal-costo"></span></p>
                </div>
            </div>
            
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 p-5 rounded-xl border border-blue-200/50 hover:border-blue-300 transition-all">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5" style="color: #2d4353;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <p class="text-xs text-gray-600 uppercase font-bold">CODIGO / SKU</p>
                    </div>
                    <p id="modal-codigo" class="font-mono text-gray-900 font-bold text-sm truncate"></p>
                </div>
                <div class="bg-gradient-to-br from-pink-50 to-pink-100/50 p-5 rounded-xl border border-pink-200/50 hover:border-pink-300 transition-all">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5" style="color: #e15871;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-xs text-gray-600 uppercase font-bold">Existencias</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span id="modal-stock" class="text-2xl font-bold text-gray-900"></span>
                        <span class="text-xs text-gray-500 font-medium">Min: <span id="modal-stock-min"></span></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <a id="modal-btn-editar"
                href="#"
                class="flex-1 py-4 rounded-xl font-bold text-center transition-all shadow-lg hover:shadow-xl hover:scale-105 flex items-center justify-center gap-2 text-white"
                style="background: linear-gradient(135deg, #2d4353 0%, #1e2d38 100%);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
            </div>

<!-- Hidden input con ID -->
<input type="hidden" id="id_producto_detalle" value="">
        </div>
    </div>
</div>
<script>
    // ConfiguraciÃ³n Global para JS
    const BASE_URL = "/puntoDeVenta/src/api/inventario_api.php";
    console.log("API URL Configurada:", BASE_URL);
</script>
<script src="js/productos.js?v=<?= time() ?>"></script>
<script src="js/producto_delete.js?v=<?= time() ?>"></script>
