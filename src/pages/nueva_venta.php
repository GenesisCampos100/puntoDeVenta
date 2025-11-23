<?php
require_once __DIR__ . "/../config/db.php";

// Búsqueda de cliente (AJAX)
if (isset($_GET['buscar_cliente'])) {
    $texto = $_GET['buscar_cliente'];
    $sql = $db->prepare(
        "SELECT id_cliente, 
               CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre_completo,
               celular
        FROM clientes
        WHERE nombre LIKE ? 
           OR apellido_paterno LIKE ?
           OR apellido_materno LIKE ?
        LIMIT 20"
    );
    $like = "%$texto%";
    $sql->execute([$like, $like, $like]);
    echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Traer productos con variantes
$sql = "SELECT 
            p.cod_barras AS producto_cod_barras,
            p.nom_producto AS producto_nombre,
            p.descripcion,
            p.imagen AS producto_imagen,
            p.talla AS producto_talla,
            p.color AS producto_color,
            p.precio AS producto_precio,
            p.cantidad AS producto_cantidad,
            c.nombre AS categoria,
            v.id_variante AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla AS variante_talla,
            v.color AS variante_color,
            v.imagen AS variante_imagen,
            v.precio AS variante_precio,
            v.cantidad AS variante_cantidad
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.cod_barras = p.cod_barras
        ORDER BY p.nom_producto ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// agrupar productos con variantes (más robusto)
$productos = [];
foreach ($rows as $row) {
    $codigo = $row['producto_cod_barras'] ?? ('P' . uniqid());
    if (!isset($productos[$codigo])) {
        $productos[$codigo] = [
            'producto_cod_barras' => $codigo,
            'nombre' => $row['producto_nombre'] ?? 'Sin nombre',
            'descripcion' => $row['descripcion'] ?? '',
            'imagen' => $row['producto_imagen'] ?? '',
            'precio' => is_numeric($row['producto_precio']) ? (float)$row['producto_precio'] : 0,
            'categoria' => $row['categoria'] ?? 'Sin categoría',
            'variantes' => [],
            'talla_default' => $row['producto_talla'] ?: 'Única',
            'color_default' => $row['producto_color'] ?: 'Sin color',
            'stock' => is_numeric($row['producto_cantidad']) ? (int)$row['producto_cantidad'] : 0,
        ];
    }
    if (!empty($row['id_variante'])) {
        $productos[$codigo]['variantes'][] = [
            'id' => (int)$row['id_variante'],
            'cod_barras' => $row['variante_cod_barras'],
            'talla' => $row['variante_talla'],
            'color' => $row['variante_color'],
            'precio' => is_numeric($row['variante_precio']) ? (float)$row['variante_precio'] : null,
            'imagen' => $row['variante_imagen'],
            'cantidad' => is_numeric($row['variante_cantidad']) ? (int)$row['variante_cantidad'] : 0,
        ];
    }
}

// categorías
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

function normalizeCategory($name) {
    // evitar regex para compatibilidad en replacement: eliminar espacios y control chars
    $chars = array(" ", chr(9), chr(10), chr(13));
    return strtolower(trim(str_replace($chars, '', $name)));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nueva Venta — Catálogo Optimizado</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
:root{ --accent-pink: #ff4d6d; --main-blue: #0A2342; --soft-bg: #f7fafc; }
html,body{height:100%}
body{font-family:'Poppins',sans-serif;margin:0;background:linear-gradient(180deg,#f8fafc,#f3f4f6);color:#374151}

/* Cart & product styles complementando Tailwind */
.cart-panel{position:fixed;top:0;right:0;width:20rem;height:100vh;background:#fff;border-left:1px solid #e6e6e6;padding:1rem;display:flex;flex-direction:column;z-index:60;overflow:hidden}
@media(min-width:1024px){body{padding-right:20rem}}
.cart-body{overflow-y:auto;padding-right:.5rem;margin-bottom:.5rem}
.cart-items{display:flex;flex-direction:column;gap:.75rem}
.cart-item{display:flex;gap:.75rem;align-items:center;background:#fff;padding:.5rem;border-radius:.75rem;border:1px solid #f1f5f9}
.cart-item img{width:56px;height:56px;object-fit:cover;border-radius:.5rem}
.cart-footer{margin-top:auto;border-top:1px solid #eef2f6;padding-top:.75rem}
.cart-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;color:#4b5563}
.cart-total{font-weight:700;font-size:1.05rem;color:#111827}
.btn{display:inline-flex;align-items:center;justify-content:center;border-radius:.75rem;padding:.6rem .9rem;font-weight:600;cursor:pointer}
.btn-primary{background:linear-gradient(90deg,var(--accent-pink),#d43a60);color:#fff}
.btn-success{background:#84cc16;color:#fff}
.btn-ghost{background:#f3f4f6;color:#374151}

.producto-card{background:linear-gradient(180deg,#ffffff,#fafafa);border-radius:1rem;padding:1rem;text-align:center;box-shadow:0 6px 18px rgba(10,35,66,0.04);transition:transform .15s;display:flex;flex-direction:column;align-items:stretch}
.producto-card:hover{transform:translateY(-4px)}
.product-avatar{width:7rem;height:7rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;background:#fff;margin:0 auto;margin-top:-2rem;box-shadow:inset 0 -6px 18px rgba(10,35,66,0.03)}
.product-avatar img{width:6rem;height:6rem;object-fit:cover;border-radius:9999px}
.product-name{color:var(--main-blue);font-weight:600;margin-top:.5rem}
.product-desc{font-size:.8rem;color:#9ca3af;margin-top:.25rem;min-height:25px}
.price{font-weight:700;font-size:1.125rem;margin-top:.5rem}
.variant-select{width:100%;background:#f3f4f6;border-radius:.75rem;padding:.45rem .6rem;border:1px solid #e6e8eb;margin-top:.5rem}
.cart-body::-webkit-scrollbar{width:8px}
.cart-body::-webkit-scrollbar-thumb{background:rgba(10,35,66,0.08);border-radius:8px}

.cat-btn{
  padding: .45rem .9rem;
  border-radius:.75rem;
  background:#e5e7eb;
  transition:.2s;
}
.cat-btn[data-active="true"]{
  background:#2563eb;
  color:#fff;
}
.cat-btn:hover{
  background:#cbd5e1;
}

</style>

</head>
<body>

<!-- HEADER CON BÚSQUEDA -->
<div class="px-6 pt-6 pb-4 animate-fade">
    <div class="search-container">
        <svg class="search-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" id="search-products" class="search-input" placeholder="Buscar productos por nombre, categoría o código...">
    </div>
</div>

<header class="page-header px-6 py-4 flex items-center justify-between bg-white shadow-sm rounded-xl mb-4">
  <h1 class="text-2xl font-bold text-[#0A2342] tracking-wide">Catálogo</h1>

  <div class="flex items-center gap-3 w-[380px]">
    <input 
      id="searchBar" 
      type="text" 
      placeholder="Buscar productos..." 
      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
             focus:ring-2 focus:ring-blue-400 focus:outline-none text-gray-700"
    />
  </div>
</header>



<!-- CATEGORÍAS -->
<div class="px-6 pb-4">
  <div class="categories-desktop p-2">
    <button data-category="all" data-active="true" class="cat-btn mr-2">Todos</button>
    <?php foreach($categorias as $cat): ?>
      <button data-category="<?= normalizeCategory($cat['nombre']) ?>" class="cat-btn"><?= htmlspecialchars($cat['nombre']) ?></button>
    <?php endforeach; ?>
  </div>
</div>

<!-- GRID PRODUCTOS -->
<main class="px-6 pb-6">
  <div id="productos-grid" class="productos-grid grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
    <?php foreach($productos as $prod): 
        $imagen = !empty($prod['imagen']) ? $prod['imagen'] : 'sin-imagen.png';
        $precio = $prod['precio'] ?: 0;
        // generar tallas y colores únicos a partir de variantes
        $sizes = [];
        $colors = [];
        foreach ($prod['variantes'] as $v) {
            if (!empty($v['talla'])) $sizes[] = $v['talla'];
            if (!empty($v['color'])) $colors[] = $v['color'];
        }
        $sizes = array_values(array_unique(array_filter($sizes)));
        $colors = array_values(array_unique(array_filter($colors)));

        // si no hay variantes, usamos defaults
        if (empty($sizes)) $sizes = [$prod['talla_default']];
        if (empty($colors)) $colors = [$prod['color_default']];

        // preparar JSON seguro para dataset
        $variants_json = htmlspecialchars(json_encode($prod['variantes'], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    ?>

    <article class="producto producto-card" 
        data-code="<?= htmlspecialchars($prod['producto_cod_barras']) ?>"
        data-name="<?= htmlspecialchars($prod['nombre']) ?>"
        data-img="<?= htmlspecialchars('../src/uploads/' . $imagen) ?>"
        data-price="<?= htmlspecialchars(number_format($precio, 2, '.', '')) ?>"
        data-category="<?= htmlspecialchars($prod['categoria']) ?>"
        data-stock="<?= (int)$prod['stock'] ?>"
        data-variants='<?= $variants_json ?>'>

      <div class="product-avatar">
        <img src="<?= htmlspecialchars('../src/uploads/' . $imagen) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>">
      </div>

      <h3 class="product-name"><?= htmlspecialchars($prod['nombre']) ?></h3>
      <p class="product-desc"><?= htmlspecialchars($prod['descripcion'] ?: $prod['categoria']) ?></p>
      <p class="price">$<?= number_format($precio,2) ?></p>

      <p class="text-sm mt-1 text-green-600 font-semibold stock-text">
          <?= count($prod['variantes']) > 0 ? 'Stock: Según variante' : 'Stock: ' . (int)$prod['stock'] ?>
      </p>

      <!-- SELECTS: solo se muestran cuando hay más de 1 opción -->
      <?php if (count($sizes) > 0): ?>
        <select class="variant-select variant-size" aria-label="Seleccionar talla">
          <?php foreach ($sizes as $size): ?>
            <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>

      <?php if (count($colors) > 0): ?>
        <select class="variant-select variant-color" aria-label="Seleccionar color">
          <?php foreach ($colors as $color): ?>
            <option value="<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($color) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>

      <button class="btn btn-primary w-full mt-3 add-to-cart">Agregar</button>
    </article>

    <?php endforeach; ?>
  </div>
</main>

<!-- CARRITO LATERAL -->
<aside id="cart" class="cart-panel" aria-label="Carrito de venta">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-bold text-[#0A2342]">Orden</h2>
    <div class="flex gap-2">
      <button id="client-btn" class="btn btn-ghost" aria-label="Seleccionar cliente">👤</button>
      <button id="discount-btn" class="btn btn-ghost" aria-label="Descuento">%</button>
      <button id="clear-cart" class="btn btn-ghost" aria-label="Limpiar carrito">🗑</button>
    </div>
  </div>

  <div id="cliente_info" class="hidden mb-4 bg-blue-50 p-3 rounded-lg">
    <p class="text-sm font-semibold">Cliente seleccionado:</p>
    <p id="cliente_nombre" class="text-gray-700">No hay cliente seleccionado</p>
    <div class="flex gap-2 mt-2">
      <button id="cambiarCliente" class="btn btn-primary text-sm">Cambiar</button>
      <button id="eliminarCliente" class="btn btn-ghost text-sm">Eliminar</button>
    </div>
    <input type="hidden" id="cliente_id" value="">
  </div>

  <div id="cart-items" class="cart-body cart-items" role="list"></div>

  <div class="cart-footer">
    <div class="cart-row"><span>Subtotal:</span><span id="subtotal">$0.00</span></div>
    <div class="cart-row text-red-500"><span>Descuento:</span><span id="discount">$0.00</span></div>
    <div class="cart-row cart-total"><span>Total:</span><span id="total">$0.00</span></div>

    <button type="button" id="pay-btn" class="btn btn-success w-full mt-3">Realizar Pago</button>
  </div>
</aside>

<!-- MODAL CLIENTE (Tailwind) -->
<div id="modalClientes" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-6 m-4 animate-slide">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-2xl font-bold" style="color: var(--secondary);">Seleccionar Cliente</h2>
            <button id="cerrar-modal-cliente" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>
        <input type="text" id="buscarCliente" class="w-full border-2 px-4 py-3 rounded-xl mb-4 focus:border-primary focus:outline-none" placeholder="Buscar cliente por nombre...">
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0" style="background: var(--bg-gray);">
                    <tr>
                        <th class="p-3 border-b-2 font-semibold">ID</th>
                        <th class="p-3 border-b-2 font-semibold">Cliente</th>
                        <th class="p-3 border-b-2 font-semibold">Teléfono</th>
                        <th class="p-3 border-b-2 font-semibold">Acción</th>
                    </tr>
                </thead>
                <tbody id="tablaClientes">
                    <?php
                        $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
                        $stmt = $pdo->query($sql);
                        while ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $full = htmlspecialchars($cli['nombre'].' '.$cli['apellido_paterno'].' '.$cli['apellido_materno']);
                            echo "<tr class='border-b hover:bg-gray-50 transition-colors'>
                                <td class='p-3'>{$cli['id_cliente']}</td>
                                <td class='p-3 font-medium'>{$full}</td>
                                <td class='p-3'>".htmlspecialchars($cli['celular'])."</td>
                                <td class='p-3'>
                                    <button class='seleccionarCliente px-4 py-2 text-white rounded-lg font-medium transition-all hover:shadow-md' style='background: var(--primary);' data-id='{$cli['id_cliente']}' data-nombre='{$full}'>Seleccionar</button>
                                </td>
                            </tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <input type="text" id="buscarCliente" class="w-full border px-3 py-2 rounded mb-3" placeholder="Buscar cliente...">
    <div class="overflow-y-auto max-h-96">
      <table class="w-full text-left border">
        <thead class="bg-gray-100">
          <tr><th class="p-2 border">ID</th><th class="p-2 border">Cliente</th><th class="p-2 border">Teléfono</th><th class="p-2 border">Acción</th></tr>
        </thead>
        <tbody id="tablaClientes">
          <?php
            $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
            $stmt = $pdo->query($sql);
            while ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $full = htmlspecialchars($cli['nombre'].' '.$cli['apellido_paterno'].' '.$cli['apellido_materno']);
                echo "<tr class='border-b'>
                  <td class='p-2 border'>".htmlspecialchars($cli['id_cliente'])."</td>
                  <td class='p-2 border'>{$full}</td>
                  <td class='p-2 border'>".htmlspecialchars($cli['celular'])."</td>
                  <td class='p-2 border'>
                    <button class='bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded seleccionarCliente' data-id='".htmlspecialchars($cli['id_cliente'])."' data-nombre='".$full."'>Seleccionar</button>
                  </td>
                </tr>";
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL PAGO (Tailwind) -->
<div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-lg p-6 w-96">
    <h2 class="text-xl font-semibold mb-4 text-gray-800 text-center">Método de Pago</h2>

    <form id="payment-form">

      <!-- Datos que necesita procesar_venta.php -->
      <input type="hidden" name="cart_data" id="cart-data-input">
      <input type="hidden" id="cliente-input" name="id_cliente" value="">
      <input type="hidden" name="descuento_general" id="descuento-general-input">
      <input type="hidden" name="tipo_descuento_general" id="descuento-general-type">
      <input type="hidden" name="subtotal" id="subtotal-input">
      <input type="hidden" name="total" id="total-input">

      <!-- MÉTODO DE PAGO -->
      <div class="space-y-3 mb-6">
        <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50">
          <input type="radio" name="metodo" value="EFECTIVO" checked class="payment-method">
          <span>Efectivo 💵</span>
        </label>

        <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50">
          <input type="radio" name="metodo" value="TARJETA" class="payment-method">
          <span>Tarjeta 💳</span>
        </label>

        <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50">
          <input type="radio" name="metodo" value="MIXTO" class="payment-method">
          <span>Pago Mixto 💵💳</span>
        </label>
      </div>

      <div id="efectivo-section" class="mb-4">
        <label class="block text-sm mb-1">Monto recibido (efectivo):</label>
        <input type="number" step="0.01" id="monto-efectivo" name="monto_efectivo" class="w-full border rounded-lg p-2" placeholder="0.00">
      </div>

      <div id="tarjeta-section" class="mb-4 hidden">
        <label class="block text-sm mb-1">Monto pagado con tarjeta:</label>
        <input type="number" step="0.01" id="monto-tarjeta" name="monto_tarjeta" class="w-full border rounded-lg p-2" placeholder="0.00">

        <label class="block text-sm mt-2 mb-1">Referencia / Folio:</label>
        <input type="text" id="referencia-tarjeta" name="referencia_tarjeta" class="w-full border rounded-lg p-2" placeholder="Ingrese referencia">
      </div>

      <div id="mixto-section" class="mb-4 hidden">
        <label class="block text-sm mb-1">Efectivo:</label>
        <input type="number" step="0.01" id="mixto-efectivo" name="mixto_efectivo" class="w-full border rounded-lg p-2 mb-2" placeholder="0.00">

        <label class="block text-sm mb-1">Tarjeta:</label>
        <input type="number" step="0.01" id="mixto-tarjeta" name="mixto_tarjeta" class="w-full border rounded-lg p-2 mb-2" placeholder="0.00">

        <label class="block text-sm mb-1">Referencia tarjeta:</label>
        <input type="text" id="mixto-referencia" name="mixto_referencia" class="w-full border rounded-lg p-2" placeholder="Folio, referencia, etc.">
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button type="button" id="cancel-payment" class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
        <button type="submit" id="confirm-payment" class="px-4 py-2 bg-lime-600 text-white rounded-lg">Confirmar</button>
      </div>

    </form>

  </div>
</div>

<!-- MODALES DE DESCUENTO (sin cambios mayores) -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
    <h2 class="text-lg font-bold mb-3">Descuento General</h2>
    <div class="flex gap-2 mb-3">
      <select id="discount-type" class="border rounded-lg p-2 w-1/3 text-center"><option value="percent">%</option><option value="amount">$</option></select>
      <input type="number" id="discount-input" class="border rounded-lg p-2 w-2/3" placeholder="Valor">
    </div>
</div>

<!-- MODAL DESCUENTO POR PRODUCTO -->
<div id="product-discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-96 animate-slide">
        <h2 class="text-xl font-bold mb-5" style="color: var(--secondary);">Descuento del Producto</h2>
        <div class="flex gap-3 mb-5">
            <select id="product-discount-type" class="border-2 rounded-xl p-3 w-1/3 text-center font-semibold focus:border-primary focus:outline-none">
                <option value="percent">%</option>
                <option value="amount">$</option>
            </select>
            <input type="number" id="product-discount-input" class="border-2 rounded-xl p-3 w-2/3 focus:border-primary focus:outline-none" placeholder="Valor">
        </div>
        <div class="flex justify-end gap-3">
            <button id="product-discount-close" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
            <button id="product-discount-apply" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);">Aplicar</button>
        </div>
    </div>
</div>

<!-- MODAL TICKET -->
<div id="ticket-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-start justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-auto max-w-[95%] md:max-w-md animate-slide overflow-hidden mt-12">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" style="color: var(--secondary);">Ticket de Venta</h2>
            <button id="close-ticket-modal" class="text-gray-400 hover:text-gray-600 text-3xl font-bold">&times;</button>
        </div>

        <div class="max-h-[60vh] mb-4 flex items-start justify-center">
            <div class="overflow-auto pr-2" style="max-height:60vh; width: fit-content;">
                <div class="border p-1 bg-gray-50" style="width:85mm;max-width:100%;">
                    <iframe id="ticket-iframe" src="" frameborder="0" style="width:100%;height:60vh;background:white;display:block;margin:0"></iframe>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button id="cancel-ticket" class="px-5 py-2.5 bg-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
            <button id="print-ticket" class="px-5 py-2.5 text-white rounded-xl font-semibold transition-all hover:shadow-lg" style="background: var(--primary);">Imprimir</button>
        </div>
    </div>
</div>

<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>

<script>
// mejoras JS: categorías (toggle active), control responsive y actualizar stock según variante

// categorías click
document.querySelectorAll('.cat-btn').forEach(btn => {
  btn.addEventListener('click', () => {

    // reset estado
    document.querySelectorAll('.cat-btn').forEach(b => b.dataset.active = "false");
    btn.dataset.active = "true";

    // filtrar
    const cat = btn.dataset.category || 'all';
    document.querySelectorAll('#productos-grid article').forEach(card => {
      const prodCat = (card.dataset.category || '').replaceAll(' ', '').toLowerCase();
      card.style.display = (cat === 'all' || prodCat === cat.toLowerCase()) ? '' : 'none';
    });

    // borrar búsqueda al seleccionar categoría
    document.getElementById("searchBar").value = "";
  });
});


// búsqueda por nombre o código
document.getElementById("searchBar").addEventListener("input", function(){
  const q = this.value.toLowerCase().trim();
  document.querySelectorAll("#productos-grid article").forEach(card => {
    const name = (card.dataset.name || "").toLowerCase();
    const code = (card.dataset.code || "").toLowerCase();
    card.style.display = (name.includes(q) || code.includes(q)) ? "" : "none";
  });
});


// ACTUALIZAR STOCK SEGÚN VARIANTE (compatible con dataset variants)
document.querySelectorAll('.producto').forEach(card => {
    const variants = (() => {
        try { return JSON.parse(card.dataset.variants || '[]'); } catch(e) { return []; }
    })();
    const sizeSelect = card.querySelector('.variant-size');
    const colorSelect = card.querySelector('.variant-color');
    const stockText = card.querySelector('.stock-text');

    function findVariant(talla, color) {
        return variants.find(v => {
            const vt = (v.talla || '').toString();
            const vc = (v.color || '').toString();
            return vt === (talla || '') && vc === (color || '');
        });
    }

    function updateStock() {
        if (!variants.length) return;
        const talla = sizeSelect ? sizeSelect.value : '';
        const color = colorSelect ? colorSelect.value : '';
        const variante = findVariant(talla, color);
        if (stockText) stockText.textContent = variante ? `Stock: ${variante.cantidad}` : 'Stock: 0';
    }
    
    if (variants.length) {
        if (sizeSelect) sizeSelect.addEventListener('change', updateStock);
        if (colorSelect) colorSelect.addEventListener('change', updateStock);
        updateStock();
    }
});

// mejora: cerrar modales con Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.fixed.inset-0').forEach(m => m.classList.add('hidden'));
  }
});

</script>

</body>
</html>