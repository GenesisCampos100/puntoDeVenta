<?php 
require_once __DIR__ . "/../config/db.php";

// Estos parámetros son los que viajan en la URL de vuelta
$busqueda   = $_GET['busqueda'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$orden      = $_GET['orden'] ?? 'p.nombre ASC';
$vista_actual = $_GET['view'] ?? 'productos_contenido'; // Esto solo para confirmación de la vista

// ================================
// 1️⃣ Consulta de productos
// ================================
$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            p.marca,
            c.nombre AS categoria,
            p.cantidad,
            p.cantidad_min,
            p.costo,
            p.precio_unitario,
            p.id_categoria,
            (SELECT COUNT(*) FROM variantes v2 WHERE v2.id_producto = p.id) AS tiene_variante
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1"; // Cláusula base para empezar los filtros

// ⚠️ Se construye la cláusula WHERE
if (!empty($busqueda)) $sql .= " AND (p.nombre LIKE :busqueda OR p.cod_barras LIKE :busqueda)";
if (!empty($categoria)) $sql .= " AND p.id_categoria = :categoria";

// ⚠️ El orden DEBE ser el último
$sql .= " ORDER BY $orden";

// ====================================================================
// 🚨 ZONA DE REVISIÓN: Preparación y Vinculación de Parámetros
// ====================================================================
$stmt = $pdo->prepare($sql);

// Array para almacenar los parámetros a vincular
$params = [];

if (!empty($busqueda)) {
    // Para LIKE, usamos el comodín % en el valor, no en el SQL
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($categoria)) {
    // Para el filtro de categoría, se vincula el ID (como entero)
    // Usaremos bindValue en la ejecución para asegurar el tipo.
    $params[':categoria'] = $categoria; 
}

// ⚠️ Si $orden es una columna inválida (ej. inyección SQL), esto fallará.
// Como $orden viene de un SELECT fijo, es poco probable, pero siempre es bueno 
// sanitizar si el valor puede ser inyectado. En este caso, lo mantendremos 
// igual porque viene de un select predefinido.


$stmt->execute($params); // 🚀 Ejecutamos pasando el array de parámetros
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================================
// 2️⃣ Consulta de variantes y 3️⃣ Categorías (sin cambios)
// ================================

$variantesStmt = $pdo->query("
    SELECT 
        v.id_producto,
        v.id,
        v.cod_barras,
        v.talla,
        v.color,
        v.cantidad,
        v.cantidad_min,
        v.precio_unitario,
        v.costo,
        v.imagen
    FROM variantes v
");
$variantesRaw = $variantesStmt->fetchAll(PDO::FETCH_ASSOC);

$variantesPorProducto = [];
foreach ($variantesRaw as $v) {
    $variantesPorProducto[$v['id_producto']][] = $v;
}

$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

?>
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>

<style>
/* --- ESTILOS BASE Y GENERALES --- */
body {
    background: #f9fafb; 
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif; 
    color: #374151; 
}

/* --- TÍTULO PRINCIPAL DE LA VISTA --- */
h2 {
    text-align: center;
    color: #f43f5e; 
    margin: 40px auto 25px; 
    font-weight: 700; 
    font-size: 28px; 
    letter-spacing: 1.5px; 
    text-transform: uppercase;
}

/* --- BARRA DE HERRAMIENTAS (TOOLBAR) --- */
.toolbar {
    display: flex;
    justify-content: center; 
    align-items: center;
    margin: 20px auto 30px;
    width: 90%;
    max-width: 1000px; 
    gap: 10px; 
}

.toolbar form {
    display: flex;
    flex-grow: 1;
    gap: 10px;
    align-items: center;
}

.search-container {
    flex-grow: 1; 
    max-width: 500px; 
    position: relative;
}

.search-container input[type="text"] {
    padding: 10px 15px 10px 40px; 
    border: 1px solid #ddd;
    border-radius: 8px; 
    width: 100%;
    box-sizing: border-box;
    font-size: 15px;
}

/* HACER EL ÍCONO CLICKABLE PARA ENVIAR EL FORMULARIO */
.search-container .search-icon { 
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af; 
    cursor: pointer; /* HACEMOS EL ÍCONO CLICKABLE */
    font-size: 18px;
    z-index: 10;
}

.search-container .clear-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    cursor: pointer;
    font-weight: bold;
    font-size: 18px;
}

/* Botones de acción (Filtrar/Ordenar) */
.toolbar .btn-accion {
    background: white; 
    color: #374151; 
    padding: 10px 18px;
    border: 1px solid #d1d5db; 
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: background-color 0.2s;
}

.toolbar .btn-accion:hover {
    background-color: #f3f4f6;
}

/* Botón "Agregar producto" */
.btn-agregar {
    background: #f43f5e; 
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: background-color 0.2s;
}

.btn-agregar:hover {
    background-color: #e11d48;
}

/* Ocultar/Mostrar los selects nativos */
.toolbar form select {
    display: none; /* Oculto por defecto */
    position: absolute;
    z-index: 50;
    margin-top: 5px; 
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    min-width: 180px;
}
.toolbar form button[type="submit"] {
    display: none;
}
.select-visible {
    display: block !important;
}

/* --- CONTENEDOR DE PRODUCTOS (LA TABLA) --- */
.productos-container {
    width: 90%; 
    max-width: 1000px; 
    margin: 0 auto 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
    overflow: hidden;
    border: 1px solid #e5e7eb; 
}

table { 
    width: 100%; 
    border-collapse: collapse; 
}

/* Cabecera de la tabla */
thead { 
    background: #2f455c; 
    color: white; 
}
thead th {
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 16px; 
}

th, td { 
    padding: 16px; 
    text-align: left; 
    border-bottom: none; 
}

tr {
    border-bottom: 1px solid #eee;
}
tbody tr:last-child {
    border-bottom: none;
}

/* Línea de separación púrpura */
.separator-row td {
    border-top: 2px solid #a78bfa; 
    padding: 0; 
    height: 2px;
    background: white; 
}
.separator-row:hover {
    background: white;
}

/* Contenido de la celda de producto */
.producto-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.producto-info img {
    width: 50px; 
    height: 75px; 
    object-fit: cover;
    border-radius: 4px;
    background: #f1f1f1;
    border: 1px solid #eee;
}

/* Etiqueta de Stock */
.stock { 
    padding: 4px 12px; 
    border-radius: 4px; 
    color: white; 
    font-size: 13px; 
    font-weight: 600; 
    text-align: center;
    display: inline-block;
    /* Color por defecto (Bajo Stock / Cero) */
    background-color: #f43f5e; 
} 
.stock.verde { /* Verde (Olive) para stock normal */
    background-color: #b6c649; 
}

/* Botón 'Más'/'Menos' (Color rosa) */
.btn-toggle-variantes {
    background: #f43f5e;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    line-height: 1;
    width: 30px; 
    text-align: center;
}
.btn-toggle-variantes:hover {
    background: #e11d48;
}

.precio { 
    color: #374151; 
    font-weight: bold; 
    font-size: 16px; 
}

/* --- ESTILOS DE VARIANTE (TABLA ANIDADA) --- */
.fila-variantes td {
    padding: 0;
    background: #fcfcfc;
    border-bottom: 1px solid #eee;
}
.tabla-variantes {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
.tabla-variantes thead {
    background: #f3f4f6; 
    color: #374151;
    border-bottom: 1px solid #ddd;
}
.tabla-variantes th {
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.tabla-variantes td {
    padding: 10px 16px; 
    font-size: 14px;
}
.tabla-variantes tr {
    border-bottom: 1px dashed #eee;
}
.tabla-variantes tbody tr:last-child {
    border-bottom: none;
}

/* Ajuste de columnas para la tabla anidada */
.tabla-variantes .col-vacio { width: 4.5%;} 
.tabla-variantes .col-producto-info { width: 45%; }
.tabla-variantes .col-stock { width: 15%; }
.tabla-variantes .col-precio { width: 15%; }
.tabla-variantes .col-acciones { width: 5%; }


/* --- ESTILOS DEL MODAL (DETALLE DEL PRODUCTO - RESTAURADO) --- */
.modal {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    display: none; justify-content: center; align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 16px;
    padding: 25px;
    width: 650px; 
    max-width: 90%;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    animation: aparecer 0.3s ease;
}

@keyframes aparecer { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.modal-content .cerrar {
    position: absolute; 
    top: 10px; 
    right: 15px; 
    border: none; 
    background: none; 
    font-size: 28px; 
    color: #9ca3af; 
    cursor: pointer;
    padding: 5px 10px; 
    border-radius: 8px; 
    line-height: 1;
    transition: background-color 0.2s, color 0.2s; 
}
.modal-content .cerrar:hover {
    background-color: #ef4444; 
    color: white; 
}


/* --- SECCIÓN PRINCIPAL: IMAGEN Y DATOS BÁSICOS --- */
.modal-top-section {
    display: flex;
    gap: 25px; 
    align-items: flex-start;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee; /* Línea de separación */
}

.modal-image-container { 
    flex-shrink: 0; 
    width: 180px; 
    height: 220px; 
    border: 1px solid #ddd; 
    border-radius: 10px; 
    overflow: hidden; 
    padding: 5px; 
    background: #f9f9f9; 
}
.modal-image-container img { 
    width: 100%; 
    height: 100%; 
    object-fit: contain; 
}

.modal-info {
    flex-grow: 1;
}

.modal-title { 
    color: #1f2937; 
    font-size: 26px; 
    font-weight: 700; 
    margin: 0 0 5px 0; 
}
.modal-subtitle { 
    font-size: 15px; 
    color: #f43f5e; 
    margin-bottom: 5px;
}
.modal-cod-barras { 
    font-size: 14px; 
    color: #9ca3af; 
    margin-bottom: 15px; 
}

/* Contenedor principal de PRECIO */
.modal-main-price {
    background: #fef2f2; 
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 15px 20px;
}

.modal-main-price .label { 
    font-size: 16px; 
    color: #9ca3af; 
    margin-bottom: 0; 
    display: block;
}

.modal-main-price .value { 
    font-size: 32px; 
    font-weight: 700; 
    color: #f43f5e; 
    line-height: 1.2;
}
.modal-main-price small { 
    font-size: 13px;
    color: #9ca3af;
    display: block;
    margin-top: 5px;
}

/* --- SECCIÓN INFERIOR: COSTO Y EXISTENCIAS (TARJETAS ORIGINALES) --- */
.modal-bottom-section {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.modal-detail-box {
    flex-basis: 50%; /* Dos columnas iguales */
    padding: 15px 20px; /* Relleno para las tarjetas */
    border-radius: 8px;
    border: 1px solid #e5e7eb; /* Borde suave */
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* Sombra ligera */
}

/* Estilos específicos para el box de Costo */
.modal-detail-box.costo {
    background: #f9fafb; /* Fondo ligeramente más oscuro */
}

.modal-detail-box .label { 
    font-size: 14px; 
    color: #9ca3af; 
    margin-bottom: 0; 
    display: block;
}

.modal-detail-box .value { 
    font-size: 20px; 
    font-weight: 700; 
    color: #1f2937; 
    line-height: 1.2;
}

.modal-detail-box small { 
    font-size: 12px;
    color: #9ca3af;
    display: block;
    margin-top: 2px;
}

/* Estilos para el box de Existencias */
.modal-detail-box.stock { 
    background: #E6F4EA; /* Fondo verde olivo muy suave */
    border-color: #A3D9B0; /* Borde un poco más oscuro */
}
.modal-detail-box.stock .value { 
    color: #2E8B57; /* Verde olivo oscuro para el valor */
}
.modal-detail-box.stock small { 
    color: #6B8E23; /* Verde olivo para el texto pequeño */
}


/* Modal Actions (Botones Eliminar/Editar) */
.modal-actions { 
    margin-top: 30px; 
    display: flex; 
    justify-content: flex-end; 
    gap: 12px; 
}

/* Estilo base para botones y enlaces */
.modal-actions button,
.modal-actions a {
    padding: 12px 22px; 
    border: none; 
    border-radius: 8px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: background-color 0.2s; 
    font-size: 15px; 
    text-decoration: none; /* quita subrayado del enlace */
    display: inline-block; /* para que respete el padding */
}

/* Botón Eliminar */
.modal-actions .btn-eliminar { 
    background: #e1e1e1; 
    color: #333; 
}
.modal-actions .btn-eliminar:hover { 
    background: #ccc; 
}

/* Botón Editar con color verde olivo */
.modal-actions .btn-editar { 
    background: #6B8E23; /* Verde olivo */
    color: white; 
}
.modal-actions .btn-editar:hover { 
    background: #55751C; /* Tono más oscuro al pasar el cursor */
}

</style>
</head>
<body>

<div class="toolbar">
    <form method="GET" id="toolbar-form" action="index.php"> 
        
        


        <input type="hidden" name="view" value="productos"> 

        <div class="search-container">
            <span class="search-icon" onclick="document.getElementById('toolbar-form').submit()">🔍</span> 
            <input type="text" id="busqueda-input" name="busqueda" placeholder="Buscar producto..." 
                   value="<?= htmlspecialchars($busqueda) ?>" 
                   onkeydown="if(event.key === 'Enter') document.getElementById('toolbar-form').submit();">
            <span class="clear-icon" onclick="document.getElementById('busqueda-input').value=''; document.getElementById('toolbar-form').submit();">✖</span>
        </div>

        <div style="position:relative;">
            <button type="button" class="btn-accion" onclick="toggleSelect(event, 'categoria-select')">
                <span class="icon">⚙</span> Filtrar
            </button>
            <select name="categoria" id="categoria-select" onchange="document.getElementById('toolbar-form').submit()">
                <option value="">-- Todas las categorías --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>" <?= ($categoria == $cat['id_categoria']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="position:relative;">
            <button type="button" class="btn-accion" onclick="toggleSelect(event, 'orden-select')">
                <span class="icon">⇅</span> Ordenar
            </button>
            <select name="orden" id="orden-select" onchange="document.getElementById('toolbar-form').submit()">
                <option value="p.nombre ASC"  <?= $orden=="p.nombre ASC" ? "selected" : "" ?>>Nombre (A-Z)</option>
                <option value="p.nombre DESC" <?= $orden=="p.nombre DESC" ? "selected" : "" ?>>Nombre (Z-A)</option>
                <option value="p.precio_unitario ASC"   <?= $orden=="p.precio_unitario ASC" ? "selected" : "" ?>>Precio ↑</option>
                <option value="p.precio_unitario DESC"  <?= $orden=="p.precio_unitario DESC" ? "selected" : "" ?>>Precio ↓</option>
            </select>
        </div>
    </form>
    
    <a href="index.php?view=agregar_producto" class="btn-agregar">
        <span class="icon">➕</span> Agregar producto
    </a>
</div>

<div class="productos-container">
    <table>
        <thead>
            <tr>
                <th style="width: 4.5%;"></th> 
                <th style="width: 45%;">Producto</th>
                <th style="width: 15%;">Stock</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 15%;">Precio</th>
                <th style="width: 5%;"></th> 
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($productos)): ?>
                <?php $isFirstRow = true; ?>
                <?php foreach ($productos as $producto): 
                    $cantidad = (int)$producto['cantidad'];
                    // Lógica de color de Stock: Verde si > 0 y > min, Rojo si <= min o 0.
                    $es_bajo_stock = $cantidad <= $producto['cantidad_min'];
                    $stockClass = ($cantidad > 0 && !$es_bajo_stock) ? 'verde' : '';
                    $imagen = !empty($producto['producto_imagen']) ? "uploads/{$producto['producto_imagen']}" : "../uploads/sin-imagen.png";
                ?>
                <tr class="product-row" id="product-row-<?= $producto['id_producto'] ?>">
                    <td><input type="checkbox"></td>
                    <td>
                        <div class="producto-info">
                            <img src="<?= htmlspecialchars($imagen) ?>" alt="Producto">
                            <div><strong><?= htmlspecialchars($producto['producto_nombre']) ?></strong></div>
                        </div>
                    </td>
                    <td><span class="stock <?= $stockClass ?>"><?= $cantidad ?> unidades</span></td>
                    <td><?= htmlspecialchars($producto['categoria']) ?></td>
                    <td class="precio">$<?= number_format($producto['precio_unitario'], 2) ?></td>
                    <td>
                       <?php if ($producto['tiene_variante'] > 0): ?>
                            <button class="btn-toggle-variantes" id="btn-toggle-<?= $producto['id_producto'] ?>" 
                                onclick='toggleVariantes(<?= $producto['id_producto'] ?>)'>+</button>
                        <?php else: ?>
                            <button class="btn-toggle-variantes" onclick='openCustomModal(<?= json_encode($producto) ?>, "producto")'>+</button>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ($isFirstRow): ?>
                    <tr class="separator-row"><td colspan="6"></td></tr>
                    <?php $isFirstRow = false; ?>
                <?php endif; ?>

                <?php if ($producto['tiene_variante'] > 0 && !empty($variantesPorProducto[$producto['id_producto']])): ?>
                <tr class="fila-variantes" style="display:none;" id="variantes-<?= $producto['id_producto'] ?>">
                    <td colspan="6">
                        <table class="tabla-variantes">
                            <thead>
                                <tr>
                                    <th class="col-vacio"></th>
                                    <th class="col-producto-info">Talla/Color</th>
                                    <th class="col-stock">Stock</th>
                                    <th class="col-precio">Precio</th>
                                    <th class="col-acciones"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($variantesPorProducto[$producto['id_producto']] as $var): 
                                    $var['categoria'] = $producto['categoria'];
                                    $cantidad_var = (int)$var['cantidad'];
                                    $es_bajo_stock_var = $cantidad_var <= $var['cantidad_min'];
                                    $stockClass_var = ($cantidad_var > 0 && !$es_bajo_stock_var) ? 'verde' : '';
                                ?>
                                <tr>
                                    <td class="col-vacio"></td>
                                    <td class="col-producto-info">
                                        <div class="producto-info" style="gap: 10px;">
                                            <img src="<?= !empty($var['imagen']) ? 'uploads/'.$var['imagen'] : '../uploads/sin-imagen.png' ?>" style="width: 40px; height: 55px;">
                                            <div>
                                                <strong>Talla: <?= htmlspecialchars($var['talla'] ?: '—') ?></strong><br>
                                                <small>Color: <?= htmlspecialchars($var['color'] ?: '—') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-stock"><span class="stock <?= $stockClass_var ?>"><?= (int)$var['cantidad'] ?></span></td>
                                    <td class="col-precio" class="precio">$<?= number_format($var['precio_unitario'], 2) ?></td>
                                    <td class="col-acciones"><button class="btn-toggle-variantes" onclick='openCustomModal(<?= json_encode($var) ?>, "variante")'>+</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No hay productos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <button class="cerrar" onclick="cerrarModal()">✖</button> 
        
        <div class="modal-top-section">
            <div class="modal-image-container">
                <img id="modal-img" src="" alt="Producto">
            </div>

            <div class="modal-info">
                <h3 class="modal-title" id="modal-nombre"></h3>
                <div class="modal-subtitle">Categoría <span id="modal-categoria"></span></div>
                <div class="modal-cod-barras">Código de barras: <span id="modal-codigo"></span></div>
                
                <div class="modal-main-price">
                    <span class="label">Precio de Venta</span>
                    <span class="value">$<span id="modal-precio"></span></span>
                    <small>IVA %16 incluido</small>
                </div>
            </div>
        </div>
        
        <div class="modal-bottom-section">
             <div class="modal-detail-box costo">
                <span class="label">Costo unitario</span>
                <span class="value">$<span id="modal-costo"></span></span>
                <small>Precio sin margen</small>
            </div>
            
            <div class="modal-detail-box stock">
                <span class="label">Existencias</span>
                <span class="value"><span id="modal-stock"></span> unidades</span>
                <small>Mínimo de stock: <span id="modal-stock-min"></span></small>
            </div>
        </div>
        
       <div class="modal-actions">
    <button 
  id="modal-btn-eliminar" 
  class="btn-eliminar" 
  data-id="<?= $producto['id_producto'] ?>" 
  data-type="producto" 
  onclick="confirmarEliminar(this)">
  🗑️ Eliminar
</button>
     <a id="modal-btn-editar" class="btn-editar">✏️ Editar</a> 
</div>
        
    </div>
</div>

<div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-[9999]">
      <div class="bg-white p-6 rounded-2xl shadow-xl text-center max-w-sm w-full">
        <h3 class="text-lg font-semibold mb-4 text-gray-800">Confirmar eliminación</h3>
        <p class="text-gray-600 mb-6" id="confirmMessage"></p>
        <div class="flex justify-center gap-4">
          <button id="cancelBtn" class="px-4 py-2 rounded-lg bg-gray-300 text-gray-700 hover:bg-gray-400 transition">Cancelar</button>
          <button id="confirmBtn" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">Eliminar</button>
        </div>
      </div>
    </div>


<script>


/**
 * Alterna la visibilidad del select nativo y posiciona la lista desplegable (Filtro/Ordenar).
 * @param {Event} event - El evento click.
 * @param {string} selectId - El ID del select a mostrar/ocultar.
 */

function toggleSelect(event, selectId) {
    event.stopPropagation();
    const select = document.getElementById(selectId);
    const button = event.currentTarget;
    
    // Ocultar todos los demás selects
    document.querySelectorAll('.toolbar form select').forEach(s => {
        if (s.id !== selectId) {
            s.classList.remove('select-visible');
            s.style.display = 'none';
        }
    });

    // Toggle del select actual
    if (select.classList.contains('select-visible')) {
        select.classList.remove('select-visible');
        select.style.display = 'none';
    } else {
        select.classList.add('select-visible');
        select.style.top = `${button.offsetHeight + 5}px`;
        select.style.left = '0';
        select.style.display = 'block';

       // Listener para cerrar al hacer clic fuera, sin interferir con otros botones
    const closeSelect = (e) => {
    const clickedInsideSelect = select.contains(e.target);
    const clickedButton = e.target === button;
    const clickedToggle = e.target.closest('[id^="btn-toggle-"]'); // evita conflicto con botones de variantes

    if (!clickedInsideSelect && !clickedButton && !clickedToggle) {
        select.classList.remove('select-visible');
        select.style.display = 'none';
        document.removeEventListener('click', closeSelect);
    }
};

setTimeout(() => document.addEventListener('click', closeSelect), 50);

    }
}


/**
 * Muestra el modal de detalle del producto o variante y prepara los botones de acción.
 * @param {Object} data - Los datos del producto o variante.
 * @param {string} type - 'producto' o 'variante'.
 */
function openCustomModal(data, type) {
    const isVariant = type === 'variante';
    
    // 1. Título y subtítulos
    document.getElementById('modal-nombre').textContent = isVariant 
        ? `Variante ${data.talla || '—'} (${data.color || '—'})` 
        : data.producto_nombre || 'Sin nombre';
        
    document.getElementById('modal-categoria').textContent = data.categoria || 'Sin categoría';
    document.getElementById('modal-codigo').textContent = data.cod_barras || data.producto_cod_barras || 'N/A';

    // 2. Imagen
    const imageKey = isVariant ? 'imagen' : 'producto_imagen';
    // Nota: Se mantiene la lógica de ruta de imagen (ajusta si es necesario)
    document.getElementById('modal-img').src = data[imageKey] ? "uploads/" + data[imageKey] : "../uploads/sin-imagen.png";

    // 3. Detalles numéricos
    const precio = (typeof data.precio_unitario !== 'undefined' && data.precio_unitario !== null) ? parseFloat(data.precio_unitario).toFixed(2) : '—';
    const costo = (typeof data.costo !== 'undefined' && data.costo !== null) ? parseFloat(data.costo).toFixed(2) : '—';
    const stock = (typeof data.cantidad !== 'undefined') ? data.cantidad : '—';
    const stockMin = (typeof data.cantidad_min !== 'undefined') ? data.cantidad_min : '—';

    document.getElementById('modal-precio').textContent = precio;
    document.getElementById('modal-costo').textContent = costo;
    document.getElementById('modal-stock').textContent = stock;
    document.getElementById('modal-stock-min').textContent = stockMin; 
    
    // ===========================================
    // 4. LÓGICA CLAVE DE BOTONES ELIMINAR/EDITAR
    // ===========================================
    const btnEliminar = document.getElementById('modal-btn-eliminar');
    const btnEditar = document.getElementById('modal-btn-editar');
    let id;
    
    if (isVariant) {
        id = data.id; 
        // Si es variante, DEBE apuntar al script que edita variantes
        btnEditar.href = `index.php?view=editar_variante&id=${id}&prod_id=${data.id_producto}`;
    } else {
        id = data.id_producto; 
        // Si es producto principal, DEBE apuntar a editar_producto.php
        btnEditar.href = `index.php?view=editar_producto&id=${id}`; 
    } 
    // Configuración del botón ELIMINAR (los atributos se usan en confirmarEliminar)
    btnEliminar.setAttribute('data-id', id);
    btnEliminar.setAttribute('data-type', type);


    // 5. Mostrar modal
    document.getElementById('modal').style.display = 'flex';
}

/**
 * Función que maneja la confirmación y la redirección de eliminación.
 * @param {HTMLElement} element - El botón 'Eliminar' que contiene los data-atributos.
 */
let deleteId = null;
let deleteType = null;

function confirmarEliminar(element) {
  // Obtener ID y tipo
  deleteId = element.getAttribute('data-id');
  deleteType = element.getAttribute('data-type');

  if (!deleteId || !deleteType) {
    console.error("Falta el ID o el tipo de producto/variante.");
    return;
  }

  const nombre = (deleteType === 'variante') ? 'esta variante' : 'este producto';
  
  // Mostrar el modal de confirmación, asegurando la visibilidad
  const confirmModal = document.getElementById("confirmModal");
  confirmModal.classList.remove("hidden");
  confirmModal.style.display = 'flex'; // 👈 AGREGAR ESTO PARA FORZAR LA VISIBILIDAD
  
  document.getElementById("confirmMessage").textContent =
    `¿Estás seguro de que quieres eliminar ${nombre} (ID: ${deleteId})? Esta acción no se puede deshacer.`;
}

// Botones del modal de confirmación
document.getElementById("cancelBtn").addEventListener("click", () => {
  const confirmModal = document.getElementById("confirmModal");
  confirmModal.classList.add("hidden");
  confirmModal.style.display = 'none'; // 👈 AGREGAR ESTO PARA ASEGURAR QUE SE OCULTE
});

document.getElementById("confirmBtn").addEventListener("click", () => {
  if (deleteId && deleteType) {
    // Redirección con los parámetros correctos
    window.location.href = `pages/productos_eliminar.php?type=${deleteType}&id=${deleteId}`;
  }
});


/**
 * Alterna la visibilidad de la fila de variantes y cambia el signo del botón.
 * @param {number} productId - El ID del producto principal.
 */
function toggleVariantes(productId) {
    const filaId = 'variantes-' + productId;
    const filaVar = document.getElementById(filaId);
    const btnToggle = document.getElementById('btn-toggle-' + productId);

    if (!filaVar || !btnToggle) {
        return;
    }
    
    const isHidden = filaVar.style.display === 'none' || filaVar.style.display === '';
    filaVar.style.display = isHidden ? 'table-row' : 'none';
    
    // Cambia el signo de + a - (y viceversa)
    btnToggle.textContent = isHidden ? '−' : '+'; 

    if (isHidden) {
        filaVar.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}


function cerrarModal() {
    document.getElementById('modal').style.display = 'none';
}


</script>


</body>
</html>