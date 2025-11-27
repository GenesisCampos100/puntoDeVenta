<?php
// src/pages/productos_contenido.php
// Versión corregida y funcional — Inventario (productos + variantes + filtros AJAX)

require_once __DIR__ . '/../config/translation.php';
require_once __DIR__ . "/../config/db.php";
// Versión Premium - Estilo consistente con clientes_contenido.php
// Mantiene TODA la lógica de backend intacta.

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
// Estado de pestañas (evita notice si no viene en la URL)
$status = $_GET['status'] ?? '';

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

<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= __('products_title') ?></title>
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

    * Tema oscuro */
    /* Textos oscuros en modo oscuro */
    body.dark-mode .text-gray-500,
    body.dark-mode .text-gray-600,
    body.dark-mode .text-gray-700,
    body.dark-mode .text-gray-800,
    body.dark-mode .text-gray-900 {
        color: #f5f5f5 !important; /* texto claro */
    }
    /* Sobrescribir fondos blancos */
body.dark-mode .bg-white,
body.dark-mode .bg-gray-50,
body.dark-mode .bg-gray-100 {
    background-color: #1e1e1e !important;
}
body.dark-mode table,
body.dark-mode thead,
body.dark-mode tbody,
body.dark-mode tr,
body.dark-mode td,
body.dark-mode th {
    background-color: #1e1e1e !important;
    color: #f5f5f5 !important;
}
/* Forzar textos blancos */
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode h3,
body.dark-mode h4,
body.dark-mode p,
body.dark-mode span,
body.dark-mode small,
body.dark-mode label,
body.dark-mode .text-gray-400,
body.dark-mode .text-gray-500,
body.dark-mode .text-gray-600,
body.dark-mode .text-gray-700,
body.dark-mode .text-gray-800,
body.dark-mode .text-gray-900,
body.dark-mode .text-slate-700,
body.dark-mode .text-slate-800 {
    color: #ffffff !important;
}

body.dark-mode .divide-gray-200 {
    border-color: #444 !important;
}
body.dark-mode .border-gray-100,
body.dark-mode .border-gray-200,
body.dark-mode .border-gray-300 {
    border-color: #333 !important;
}
body.dark-mode span[style*="rgba("] {
    background-color: rgba(255,255,255,0.1) !important;
}
body.dark-mode button[style*="linear-gradient"] {
    filter: brightness(0.8);
}
body.dark-mode main,
body.dark-mode .content {
    background-color: #121212 !important;
}
body.dark-mode .dropdown-menu {
    background: #1e1e1e !important;
    border-color: #333 !important;
}

body.dark-mode .dropdown-menu p,
body.dark-mode .dropdown-menu select,
body.dark-mode .dropdown-menu option {
    color: #ffffff !important;
    background: #2a2a2a !important;
}
body.dark-mode select {
    background: #2c2c2c !important;
    color: #fff !important;
    border-color: #000 !important;
}
/* Botón Eliminar (modo oscuro) */
body.dark-mode .btn-eliminar {
    background-color: #b30000 !important;  /* Rojo fuerte */
    color: #fff !important;
    border: none !important;
}

body.dark-mode .btn-eliminar:hover {
    background-color: #cc0000 !important; /* Rojo más brillante */
}
/* Botón Agregar (modo oscuro) */
body.dark-mode .btn-agregar {
    background-color: #3ba55d !important; /* Verde moderno */
    color: #fff !important;
    border: none !important;
}

body.dark-mode .btn-agregarProducto:hover {
    background-color: #b4c24d !important;
}

/* BOTÓN AGREGAR EN MODO OSCURO */
body.dark-mode .btn-agregarProducto {
  background-color: #b4c24d !important;
}
/* Mantener el fondo verde del botón ACTIVO en modo oscuro */
body.dark-mode .tab-btn.active {
    background-color: #b4c24d !important;   /* Verde original */
    color: #ffffff !important;              /* Texto blanco */
    border-color: #b4c24d !important;
}
body.dark-mode span[data-min] {
  background-color: rgba(34,197,94,0.15) !important; /* verde oscuro suave */
  color: #86efac !important; /* verde claro */
  border-color: rgba(34,197,94,0.5) !important;
}
body.dark-mode .bg-red-50 { background-color: rgba(239, 68, 68, 0.15) !important; }
body.dark-mode .text-red-700 { color: #fca5a5 !important; }
body.dark-mode .ring-red-600\/20 { border-color: rgba(239, 68, 68, 0.5) !important; }

body.dark-mode .bg-yellow-50 { background-color: rgba(234, 179, 8, 0.15) !important; }
body.dark-mode .text-yellow-700 { color: #fcd34d !important; }
body.dark-mode .ring-yellow-600\/20 { border-color: rgba(234, 179, 8, 0.5) !important; }

/* Fondo global del modal */
body.dark-mode #modalDetalle {
  background-color: rgba(0,0,0,0.75) !important;
}

/* Contenedor principal */
body.dark-mode #modalDetalle > div.relative {
  background: #1e293b !important; /* slate-800 */
  color: #e2e8f0 !important; /* slate-200 */
}

/* Header */
body.dark-mode #modalDetalle .header-dark {
  background: linear-gradient(135deg, #111827 0%, #0f172a 100%) !important;
  border-color: #1f2937 !important;
}

/* Imagen */
body.dark-mode #modalDetalle .img-dark {
  background: #0f172a !important;
}

/* Texto sobre imagen */
body.dark-mode #modalDetalle .img-dark-text {
  color: #f1f5f9 !important;
}

/* Secciones del cuerpo */
body.dark-mode #modalDetalle .section-dark {
  border-color: #334155 !important; 
}


/* Mini-cards gris sólido */
body.dark-mode #modalDetalle .card-dark {
  background: #2f2f2f !important;   /* gris oscuro sólido */
  border-color: #444 !important;     /* borde gris */
}

/* Texto de las mini-cards */
body.dark-mode #modalDetalle .card-dark p,
body.dark-mode #modalDetalle .card-dark span {
  color: #f1f1f1 !important;         /* texto claro */
}


/* Botón eliminar */
body.dark-mode #modalDetalle .btn-eliminar-dark {
  background: rgba(255,255,255,0.05) !important;
  border-color: rgba(239,68,68,0.4) !important;
  color: #fca5a5 !important;
}

body.dark-mode #modalDetalle .btn-eliminar-dark:hover {
  background: rgba(239,68,68,0.1) !important;
}

body.dark-mode #modalDetalle .btn-editar-dark {
  background: #1e293b !important;  /* sólido */
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
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 color-black"><?= __('inventory_management') ?></h1>
            <p class="text-gray-600 text-base"><?= __('manage_catalog_stock') ?></p>
        </div>
        
        <div class="flex items-center gap-3 w-full lg:w-3/5">
            <div class="relative w-full">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="busqueda" type="text" placeholder="<?= __('search_product_placeholder') ?>" 
                        value="<?= htmlspecialchars($busqueda) ?>"
                        class="pl-10 pr-10 py-2.5 w-full rounded-full border border-gray-200 focus:ring-2 focus:ring-success/50 focus:border-success/80 transition duration-150"/>
                <button id="clear-search" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-alert hidden">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="tabs" class="ml-2 inline-flex bg-background-subtle rounded-full p-1 shadow-inner flex-shrink-0">
                <button data-status="activo" class="tab-btn px-4 py-2 rounded-full text-sm font-semibold transition duration-200 
                    <?= ($status === 'activo' || empty($status)) ? 'bg-white text-primary shadow' : 'text-gray-600 hover:text-primary' ?>">
                    <?= __('active') ?>
                </button>
                <button data-status="descatalogado" class="tab-btn px-4 py-2 rounded-full text-sm font-semibold transition duration-200
                    <?= ($status === 'descatalogado') ? 'bg-white text-primary shadow' : 'text-gray-600 hover:text-primary' ?>">
                    <?= __('discontinued') ?>
                </button>
            </div>
        </div>

        <div class="flex gap-3 items-center w-full lg:w-auto flex-shrink-0">
            <select id="categoria" class="rounded-full border border-gray-200 px-4 py-2.5 bg-white text-sm focus:ring-success/50 focus:border-success/80 transition duration-150">
                <option value=""><?= __('all_categories') ?></option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id_categoria']) ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(tr_category($cat['nombre'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="orden" class="rounded-full border border-gray-200 px-4 py-2.5 bg-white text-sm focus:ring-success/50 focus:border-success/80 transition duration-150">
                <option value="nom_asc" <?= ($orden == 'nom_asc') ? 'selected' : '' ?>><?= __('name_az') ?></option>
                <option value="nom_desc" <?= ($orden == 'nom_desc') ? 'selected' : '' ?>><?= __('name_za') ?></option>
                <option value="precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>><?= __('price_asc') ?></option>
                <option value="precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>><?= __('price_desc') ?></option>
            </select>

            <button id="btnAgregarProducto" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-white font-semibold transition duration-200 shadow-md hover:opacity-90" style="background-color: #B6C649;">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('add') ?>
            </button>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-xl overflow-x-auto productos-container">
    <div class="relative"> 
        <table id="productos-table" class="w-full border-collapse min-w-max">
            <thead class="bg-black text-white sticky top-0 z-10">
                <tr class="divide-x divide-black/30">
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider w-auto min-w-[280px]"><?= __('product') ?></th>
                    
                    <th class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider w-24 min-w-[96px]"><?= __('stock') ?></th>
                    
                    <th class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider w-32 min-w-[128px] hidden sm:table-cell"><?= __('category') ?></th>
                    
                    <th class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider w-28 min-w-[112px]"><?= __('price') ?></th>
                    
                    <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider w-44 min-w-[176px]"><?= __('actions') ?></th>
                </tr>
            </thead> 

            <tbody id="tabla-productos" class="divide-y divide-gray-200">
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto):
                        // Variables y preparación de datos
                        $pid = htmlspecialchars($producto['id_producto']);
                        $nombre = htmlspecialchars($producto['nom_producto']);
                        $sku = htmlspecialchars($producto['cod_barras']);
                        // ** Clave para la lógica: $tieneVariantes **
                        $tieneVariantes = $producto['tiene_variante'] > 0 && !empty($variantesPorProducto[$producto['id_producto']]);
                        $cantidad = (int)($producto['cantidad'] ?? 0);
                        $cantidad_min = (int)($producto['cantidad_min'] ?? 0);
                        $is_active = (int)($producto['is_active'] ?? 1);
                        
                        // Lógica de Color Condicional para Stock
                        if ($cantidad > $cantidad_min) {
                            $stockClass = 'bg-success/20 text-success'; // Suficiente (Verde Oliva)
                        } elseif ($cantidad > 0 && $cantidad <= $cantidad_min) {
                            $stockClass = 'bg-orange-100 text-orange-700 font-bold border border-orange-300'; // Mínimo (Naranja)
                        } else {
                            $stockClass = 'bg-alert/20 text-alert font-bold border border-alert/50'; // Agotado/Bajo (Rosa/Rojo Suave)
                        }
                        
                        $imagen = !empty($producto['imagen']) ? "uploads/".htmlspecialchars($producto['imagen']) : "../uploads/sin-imagen.png";
                        $jsonProducto = htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8');
                    ?>
                    
                    <tr class="producto-row hover:bg-gray-50/80 transition duration-200 <?= $tieneVariantes ? 'product-parent cursor-pointer' : '' ?> <?php if(!$is_active) echo 'product-inactive opacity-60'; ?>" 
                        id="product-row-<?= $pid ?>"
                        data-product-id="<?= $pid ?>"
                        data-details="<?= $jsonProducto ?>">

                        <td class="px-6 py-3 align-middle w-auto">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-300 shadow-sm">
                                    <img src="<?= $imagen ?>" class="w-full h-full object-cover" alt="<?= $nombre ?>">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 text-sm line-clamp-2"><?= $nombre ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5"><?= __('sku') ?>: <code class="bg-gray-100 px-1.5 py-0.5 rounded"><?= $sku ?></code></div>
                                </div>
                                
                                <?php if ($tieneVariantes): ?>
                                    <button class="toggle-variants flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-primary/10 text-primary transition duration-150 ml-2" 
                                            data-target-id="variants-<?= $pid ?>" 
                                            title="<?= __('view_variants') ?>">
                                        <i data-lucide="chevron-down" class="arrow-icon h-5 w-5"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="px-4 py-3 align-middle text-center w-24">
                            <span id="stock-<?= $pid ?>" data-min="<?= $cantidad_min ?>" class="inline-block px-3 py-1 rounded-full text-xs font-bold <?= $stockClass ?>">
                                <?= $cantidad ?> <?= __('units') ?>
                            </span>
                        </td>

                        <td class="px-4 py-3 align-middle text-center text-sm text-gray-600 w-32 hidden sm:table-cell">
                            <span class="inline-block px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                                <?= htmlspecialchars(tr_category($producto['categoria'])) ?>
                            </span>
                        </td>

                        <td class="px-4 py-3 align-middle text-center font-bold text-gray-900 text-sm w-28">
                            $<?= number_format($producto['precio'], 2) ?>
                        </td>
                        
                        <td class="px-6 py-3 align-middle text-center w-44">
                            <div class="flex items-center justify-center gap-2">
                                
                                <button class="open-modal-btn inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition duration-150 shadow-sm" data-details='<?= $jsonProducto ?>' title="<?= __('view_details') ?>">
                                    <i data-lucide="eye" class="h-5 w-5"></i>
                                </button>
                                
                                <?php if (!$tieneVariantes): ?>
                                    <button class="btn-ajuste inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition duration-150 shadow-sm" 
                                        onclick="openMovimientoModal('<?= $pid ?>','producto','<?= addslashes($nombre) ?>', false)"
                                        title="<?= __('adjust_stock') ?>">
                                        <i data-lucide="settings" class="h-5 w-5"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn-toggle inline-flex items-center justify-center w-9 h-9 rounded-lg <?= $is_active ? 'bg-alert/10 text-alert hover:bg-alert/20' : 'bg-success/10 text-success hover:bg-success/20' ?> transition duration-150 shadow-sm" 
                                    data-id="<?= $pid ?>" 
                                    data-type="producto"
                                    data-estado="<?= $is_active ? 1 : 0 ?>"
                                    data-nombre="<?= htmlspecialchars($nombre) ?>"
                                    title="<?= $is_active ? __('deactivate') : __('activate') ?>">
                                    <?php if ($is_active): ?>
                                        <i data-lucide="power" class="h-5 w-5"></i>
                                    <?php else: ?>
                                        <i data-lucide="check-circle" class="h-5 w-5"></i>
                                    <?php endif; ?>
                                </button>
                                
                                <?php if ($tieneVariantes): ?>
                                    <div class="w-9 h-9"></div> 
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <?php if ($tieneVariantes): ?>
                        <tr id="variants-<?= $pid ?>" class="variant-group hidden transition-all duration-300">
                            <td colspan="5" class="p-0">
                                <div class="bg-gray-50 border-t-2 border-gray-200">
                                    <table class="w-full min-w-max">
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($variantesPorProducto[$producto['id_producto']] as $var):
                                                $vsku = htmlspecialchars($var['cod_barras']);
                                                $vcant = (int)($var['cantidad'] ?? 0);
                                                $vcant_min = (int)($var['cantidad_min'] ?? 0);
                                                
                                                // Lógica de Color Condicional para Stock de Variante
                                                if ($vcant > $vcant_min) {
                                                    $vstockClass = 'bg-success/20 text-success'; 
                                                } elseif ($vcant > 0 && $vcant <= $vcant_min) {
                                                    $vstockClass = 'bg-orange-100 text-orange-700 font-bold border border-orange-300';
                                                } else {
                                                    $vstockClass = 'bg-alert/20 text-alert font-bold border border-alert/50';
                                                }

                                                $jsonVar = htmlspecialchars(json_encode($var + ['producto_nombre' => $producto['nom_producto'], 'categoria' => $producto['categoria'], 'id_producto' => $producto['id_producto']]), ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <tr class="hover:bg-gray-100 transition duration-150">
                                                    
                                                    <td class="px-6 py-3 align-middle text-left w-auto">
                                                        <div class="text-sm font-medium text-gray-900 ml-16">
                                                            <?= __('size') ?>: <span class="text-primary font-bold"><?= htmlspecialchars($var['talla'] ?: '—') ?></span> 
                                                            | <?= __('color') ?>: <span class="text-primary font-bold"><?= htmlspecialchars($var['color'] ?: '—') ?></span>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-0.5 ml-16"><?= __('sku') ?>: <code class="bg-white px-1.5 py-0.5 rounded"><?= $vsku ?></code></div>
                                                    </td>

                                                    <td class="px-4 py-3 align-middle text-center w-24">
                                                        <span id="stock-<?= $vsku ?>" data-min="<?= $vcant_min ?>" class="inline-block px-3 py-1 rounded-full text-xs font-bold <?= $vstockClass ?>">
                                                            <?= $vcant ?> <?= __('units') ?>
                                                        </span>
                                                    </td>

                                                    <td class="px-4 py-3 align-middle text-center w-32 hidden sm:table-cell"></td>

                                                    <td class="px-4 py-3 align-middle text-center font-bold text-gray-900 text-sm w-28">
                                                        $<?= number_format($var['precio'] ?? 0, 2) ?>
                                                    </td>

                                                    <td class="px-6 py-3 align-middle text-center w-44">
                                                        <div class="flex items-center justify-center gap-2">
                                                            
                                                            <button class="btn-ajuste inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition duration-150 shadow-sm" 
                                                                onclick="openMovimientoModal('<?= htmlspecialchars($vsku) ?>','variante','<?= addslashes($producto['nom_producto'] . ' - ' . ($var['talla'] ?? '')) ?>', false)"
                                                                title="<?= __('adjust_stock') ?>">
                                                                <i data-lucide="settings" class="h-5 w-5"></i>
                                                            </button>
                                                            
                                                            <button class="open-modal-btn inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition duration-150 shadow-sm" 
                                                                    data-details='<?= $jsonVar ?>'
                                                                    title="<?= __('view_details') ?>">
                                                                <i data-lucide="eye" class="h-5 w-5"></i>
                                                            </button>
                                                            
                                                            <div class="w-9 h-9"></div> 
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="package-search" class="h-16 w-16 text-gray-300"></i>
                                <p class="text-gray-500 font-medium"><?= __('no_products_found') ?></p>
                                <button onclick="window.location.reload();" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition duration-150"><?= __('refresh_search') ?></button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
                <div class="text-gray-600 text-sm mb-1 sm:mb-2"><?= __('category') ?> <span id="modal-categoria"></span></div>
                <div class="text-gray-500 text-xs sm:text-sm mb-3 sm:mb-5"><?= __('barcode') ?>: <span id="modal-codigo"></span></div>

                <div class="bg-[#f0f4db] p-3 sm:p-4 rounded-lg border-l-4 border-[#b4c24d] text-left">
                    <span class="text-sm text-gray-600 block"><?= __('sale_price') ?></span>
                    <span class="text-3xl sm:text-4xl font-bold text-[#2d4353]">$<span id="modal-precio"></span></span>
                    <small class="text-xs text-gray-500 block"><?= __('vat_included') ?></small>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:gap-5 mb-6">
             <div class="flex-1 p-4 rounded-lg bg-[#f7f7f7] border border-[#eeeeee]">
                 <span class="text-sm text-gray-600 block"><?= __('unit_cost') ?></span>
                 <span class="text-xl sm:text-2xl font-bold text-[#2d4353]">$<span id="modal-costo"></span></span>
                 <small class="text-xs text-gray-500 block"><?= __('price_without_margin') ?></small>
             </div>

             <div class="flex-1 p-4 rounded-lg bg-[#f7f7f7] border border-[#eeeeee]">
                 <span class="text-sm text-gray-600 block"><?= __('stock_exists') ?></span>
                 <span class="text-xl sm:text-2xl font-bold text-[#2d4353]"><span id="modal-stock"></span> <?= __('units') ?></span>
                 <small class="text-xs text-gray-500 block"><?= __('minimum_stock') ?>: <span id="modal-stock-min"></span></small>
             </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <button id="modal-btn-eliminar" class="px-5 py-3 rounded-lg font-semibold text-white bg-[#e15871]" data-id="" data-type="" onclick="confirmarEliminar(this)">
                🗑️ <?= __('delete') ?>
            </button>
            <a id="modal-btn-editar" class="px-5 py-3 rounded-lg font-semibold bg-[#b4c24d] text-[#2d4353]">✏️ <?= __('edit') ?></a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 animate-slideUp">
            <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1"><?= __('total_products') ?></p>
                        <p class="text-3xl font-bold text-gray-900"><?= $totalProductos ?></p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-5 shadow-lg hover-lift delay-100 animate-slideUp border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1"><?= __('low_stock') ?></p>
                        <p class="text-3xl font-bold" style="color: #e15871;"><?= $stockBajo ?></p>
                    </div>

<!-- Modal Confirmación Eliminación -->
<div id="confirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 transform transition-all scale-100 animate-scaleIn text-center">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5 shadow-lg" style="background: linear-gradient(135deg, #e15871 0%, #d14560 100%);">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-3">¿Estás seguro?</h3>
        <p id="confirmMessage" class="text-gray-600 mb-8 leading-relaxed">Esta acción eliminará el producto permanentemente y no se puede deshacer.</p>
        <div class="flex gap-3">
            <button id="cancelBtn" class="flex-1 px-5 py-3.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all">Cancelar</button>
            <button id="confirmBtn" class="flex-1 px-5 py-3.5 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all" style="background: linear-gradient(135deg, #e15871 0%, #d14560 100%);">Eliminar</button>
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
                            <?= htmlspecialchars(tr_category($cat['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="orden" class="px-4 py-3.5 rounded-xl border-2 border-gray-200 bg-white text-sm font-semibold focus:border-primary focus:outline-none cursor-pointer hover:bg-gray-50 transition-all hidden md:block">
                    <option value="nom_asc" <?= ($orden == 'nom_asc') ? 'selected' : '' ?>>Nombre (A-Z)</option>
            <option value="nom_desc" <?= ($orden == 'nom_desc') ? 'selected' : '' ?>><?= __('name_za') ?></option>
            <option value="precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>><?= __('price_asc') ?></option>
            <option value="precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>><?= __('price_desc') ?></option>
        </select>                <button id="btnAgregarProducto" onclick="window.location.href='index.php?view=agregar_producto'" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 hover:opacity-90" style="background-color: #B6C649;">
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
                <thead style="background-color: #000000;">
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

    <script>
/* ==========================
   CONFIGURACIÓN
   ========================== */
const API_URL = 'api/inventario_api.php';
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
        // *** IMPORTANTE: Especificar que esperas JSON ***
        dataType: "json", 
        
        beforeSend: function() {
            $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-gray-500"><?= __('loading_products') ?></td></tr>`);
        },
        success: function(res) {
            // Verifica si el resultado es nulo o si la respuesta es HTML (no JSON)
            if (typeof res !== 'object' || res === null) {
                // Si la respuesta no es un objeto JSON, muestra el contenido crudo
                const rawContent = (typeof res === 'string' && res.length > 0) ? res.substring(0, 100) + '...' : 'Respuesta no JSON o vacía';
                $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-red-500">Error de formato: ${rawContent}</td></tr>`);
                console.error("Respuesta no es JSON:", res);
                return;
            }

            if (res.success) {
                $tablaCuerpo.html(res.html);
                // Reactivar lucide
                try { if (window.lucide) lucide.createIcons(); } catch(e){}
            } else {
                // Muestra el mensaje de error del servidor PHP
                $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-red-500">Error del API: ${res.message || 'Error desconocido'}</td></tr>`);
                console.error("Error lógico del API:", res.message, res);
            }
        },
        error: function(xhr, status, err) {
            console.error("AJAX error:", status, err, xhr.responseText);
            // Muestra un mensaje detallado del error de comunicación
            const httpStatus = xhr.status || 'N/A';
            const errorMsg = `Error HTTP ${httpStatus}. Revisa la consola para el detalle del servidor.`;
            $tablaCuerpo.html(`<tr><td colspan="5" class="text-center py-8 text-red-500">${errorMsg}</td></tr>`);
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

// 1. Ver detalles (Botón: .btn-detalle)
// Llama al API para obtener detalles completos y historial, luego abre el modal.
$(document).on("click", ".btn-detalle", function(e){
    e.preventDefault();
    const $btn = $(this);
    const id = $btn.data('id');
    const tipo = $btn.data('tipo'); // 'producto' o 'variante'
    const nombre = $btn.data('nombre');
    
    // Llama a la nueva función que hace el AJAX y luego abre el modal.
    fetchDetalle(id, tipo, nombre);
});

// Apertura directa del modal con data-details (botón: .open-modal-btn)
$(document).on('click', '.open-modal-btn', function(e){
    e.preventDefault();
    const raw = $(this).attr('data-details');
    try {
        const obj = JSON.parse(raw);
        openCustomModalFromJSON(obj);
    } catch(err) {
        console.error('JSON inválido en data-details:', err, raw);
        Swal.fire('<?= __('error') ?>', '<?= __('cannot_read_product_info') ?>', 'error');
    }
});

// 2. Ajuste de stock (Botón: .btn-ajuste)
$(document).on("click", ".btn-ajuste", function(){
    const $btn = $(this);
    const id = $btn.data("id");
    // Si no hay data-id, este botón usa onclick inline; evitar doble ejecución
    if (typeof id === 'undefined') { return; }
    const nombre = $btn.data("nombre");
    const tipo = $btn.data("tipo"); // 'producto' o 'variante'
    const isVar = tipo === 'variante';

    // openMovimientoModal(cod_entidad, type, nombre, hasVariantes)
    openMovimientoModal(id, tipo, nombre, isVar);
});

// 3. Toggle activo/inactivo (Botón: .btn-toggle)
// CRÍTICO: Se cambia el selector de .toggle-active a .btn-toggle 
// y el atributo de data-active a data-estado.
$(document).on("click", ".btn-toggle", function(e){
    e.preventDefault();
    const $btn = $(this);
    const id = $btn.data("id");
    // Usamos 'data-estado' que generaste en el PHP (0 o 1)
    const currentStatus = parseInt($btn.data("estado"), 10);
    const newStatus = currentStatus === 1 ? 0 : 1;
    const nombre = $btn.data("nombre");

    const accion = newStatus === 1 ? 'activar' : 'descatalogar';

    Swal.fire({
        title: `¿Confirmar ${accion} "${nombre}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<?= __('yes_continue') ?>',
        cancelButtonText: '<?= __('cancel') ?>'
    }).then(result => {
        if (!result.isConfirmed) return;
        $.post(API_URL, { action: "toggle_activo", id: id, status: newStatus }, function(res){
            if (res.success) {
                Swal.fire('Hecho', res.message || 'Estado cambiado', 'success');
                cargarProductos(); // Recargar para actualizar la tabla
            } else {
                Swal.fire('<?= __('error') ?>', res.message || '<?= __('could_not_change_status') ?>', 'error');
            }
        }, "json").fail(() => {
            Swal.fire('<?= __('error') ?>', '<?= __('could_not_connect_server') ?>', 'error');
        });
    });
});


/* ==========================
   Modal: abrir con data-details (robusto)
   ========================== */
function openCustomModalFromJSON(obj) {
    if (!obj) return;
    const isVariant = !!(obj.id_variante || obj.sku && (obj.talla !== undefined || obj.color !== undefined));

    // Nombre del producto
    const nombre = obj.nom_producto || obj.producto_nombre || 'Sin nombre';
    $("#modal-nombre").text(nombre);
    
    // Traducir categoría con helper (asume que tr_category está disponible en servidor)
    const categoria = obj.categoria || 'Sin categoría';
    $("#modal-categoria").text(categoria);
    
    $("#modal-codigo").text(obj.cod_barras || obj.sku || 'N/A');

    // Imagen
    const imageKey = obj.imagen || obj.producto_imagen || null;
    $("#modal-img").attr('src', imageKey ? ("uploads/" + imageKey) : "../uploads/sin-imagen.png");

    // Precios y stock
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


/**
 * Llama al API para obtener detalles completos (incluyendo historial).
 * Una vez obtenidos, usa openCustomModalFromJSON(obj) para mostrarlo.
 */
function fetchDetalle(id, tipo, nombre) {
    // Puedes mostrar un spinner o mensaje de carga aquí
    
    $.ajax({
        url: API_URL,
        method: 'GET',
        // Asegúrate de que tu inventario_api.php tiene el 'case fetch_historial'
        data: { action: 'fetch_historial', id: id, type: tipo }, 
        dataType: 'json',
        success: function(res) {
            if (res.success && res.data) {
                // Combina los datos de respuesta con un fallback para el nombre
                const fullObj = { 
                    ...res.data, 
                    historial: res.historial,
                    // Asegura que la función modal tenga el nombre si el API falla en devolverlo
                    nom_producto: res.data.nom_producto || nombre,
                    producto_nombre: res.data.producto_nombre || nombre,
                };
                
                // Abre el modal utilizando tu función existente
                openCustomModalFromJSON(fullObj);

                // NOTA: Si tu modal requiere que el historial se renderice por separado, 
                // aquí deberás llamar a esa función (ej: renderizarHistorial(res.historial))
                
            } else {
                Swal.fire('Error', res.message || `No se encontraron detalles para ${nombre}.`, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Falla de comunicación al obtener los detalles.', 'error');
        }
    });
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

    $("#confirmMessage").text('<?= __('are_you_sure_delete_undone') ?>');
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
        title: `<?= __('adjust_stock_for') ?>: ${nombre}`,
        input: 'number',
        inputLabel: '<?= __('quantity_pos_neg') ?>',
        inputPlaceholder: '<?= __('quantity_placeholder') ?>',
        showCancelButton: true,
        preConfirm: (value) => {
            if (value === '' || value === null || isNaN(value)) {
                Swal.showValidationMessage('<?= __('enter_valid_quantity') ?>');
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
                    Swal.fire('<?= __('done') ?>', data.message || '<?= __('adjustment_registered') ?>', 'success');
                    if (typeof data.nuevo_stock !== 'undefined') {
                        const target = document.getElementById('stock-' + cod_entidad);
                        if (target) {
                            target.textContent = data.nuevo_stock + ' <?= __('units') ?>';
                            const min = parseInt(target.dataset.min || -1,10);
                            if (min >= 0 && data.nuevo_stock <= min) {
                                target.classList.remove('bg-green-50','text-green-800');
                                target.classList.add('bg-red-50','text-red-800');
                            } else {
                                target.classList.remove('bg-red-50','text-red-800');
                                target.classList.add('bg-green-50','text-green-800');
                            }
                        }
                    }
                    cargarProductos();
                } else {
                    Swal.fire('<?= __('error') ?>', data.message || '<?= __('could_not_register_adjustment') ?>', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('<?= __('error') ?>', '<?= __('connection_failed') ?>', 'error');
            });
    });
}

// Mostrar/Ocultar las variantes en la tabla
$(document).on('click', '.toggle-variants', function(){
    const targetId = $(this).data('target-id');
    const $row = $('#' + targetId);
    if ($row.length) {
        $row.toggleClass('hidden');
        const $icon = $(this).find('.arrow-icon');
        if ($icon.length) { $icon.toggleClass('rotate-180'); }
    }
});

</script>
</body>
</html>
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
    // Configuración Global para JS
    const BASE_URL = "/PrismaMK2C/src/api/inventario_api.php";
    console.log("API URL Configurada:", BASE_URL);
</script>
<script src="js/productos.js?v=<?= time() ?>"></script>
<script src="js/producto_delete.js?v=<?= time() ?>"></script>
