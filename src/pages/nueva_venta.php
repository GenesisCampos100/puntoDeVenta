<?php
require_once __DIR__ . "/../config/db.php";

// Traer productos y variantes
$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            p.talla AS producto_talla,
            p.color AS producto_color,
            p.precio_unitario AS producto_precio,
            c.nombre AS categoria,
            v.id AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla AS variante_talla,
            v.color AS variante_color,
            v.imagen AS variante_imagen,
            v.precio_unitario AS variante_precio
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.id_producto = p.id
        ORDER BY p.nombre ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar productos con sus variantes
$productos = [];
foreach ($rows as $row) {
    $id = $row['id_producto'];
    if (!isset($productos[$id])) {
        $productos[$id] = [
            'id' => $id,
            'codigo' => $row['producto_cod_barras'],
            'nombre' => $row['producto_nombre'],
            'imagen' => $row['producto_imagen'],
            'precio' => $row['producto_precio'] ?: 0,
            'categoria' => $row['categoria'],
            'variantes' => [],
            'size_default' => $row['producto_talla'] ?: 'Única',
            'color_default' => $row['producto_color'] ?: 'Sin color',
        ];
    }

    if ($row['variante_talla'] || $row['variante_color']) {
        $productos[$id]['variantes'][] = [
            'size' => $row['variante_talla'] ?: $productos[$id]['size_default'],
            'color' => $row['variante_color'] ?: $productos[$id]['color_default'],
            'price' => $row['variante_precio'] ?: $productos[$id]['precio'],
            'image' => $row['variante_imagen'] ?: $productos[$id]['imagen'],
            'code' => $row['variante_cod_barras'] ?: $productos[$id]['codigo'],
        ];
    }
}

// Traer categorías
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

// Función para normalizar nombres de categoría
function normalizeCategory($name) {
    return strtolower(trim(preg_replace('/\s+/', '', $name)));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Venta</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-0">

<!-- FILTROS DE CATEGORÍA -->
<div class="flex flex-wrap justify-start gap-2 mb-8 px-6">
  <button data-category="all" class="category-btn px-6 py-2 rounded-full text-white font-medium hover:bg-red-600 transition" style="background-color:#ec3678; font-size: .9rem">
    Todos
  </button>
  <?php foreach($categorias as $cat): ?>
    <button data-category="<?= normalizeCategory($cat['nombre']) ?>" 
            class="category-btn px-6 py-2 rounded-full text-white font-medium hover:bg-red-600 transition" style="background-color:#ec3678; font-size: .9rem">
      <?= htmlspecialchars($cat['nombre']) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- GRID PRODUCTOS -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 justify-items-center px-6" id="productos-grid">
  <?php foreach($productos as $prod): ?>
    <?php 
      $variantes = json_encode($prod['variantes'], JSON_UNESCAPED_UNICODE);
      $sizes = array_unique(array_map(fn($v)=>$v['size'],$prod['variantes']));
      $colors = array_unique(array_map(fn($v)=>$v['color'],$prod['variantes']));
      if(empty($sizes)) $sizes = [$prod['size_default']];
      if(empty($colors)) $colors = [$prod['color_default']];
      $imagen = !empty($prod['imagen']) ? $prod['imagen'] : 'sin-imagen.png';
      $precio = $prod['precio'] ?: 0;
    ?>
    <article class="producto bg-white shadow rounded-lg p-4 text-center w-60"
             data-id="<?= $prod['id'] ?>"
             data-name="<?= htmlspecialchars($prod['nombre']) ?>"
             data-code="<?= htmlspecialchars($prod['codigo']) ?>"
             data-img="../src/uploads/<?= htmlspecialchars($imagen) ?>"
             data-price="<?= htmlspecialchars($precio) ?>"
             data-category="<?= normalizeCategory($prod['categoria']) ?>"
             data-variants='<?= htmlspecialchars($variantes, ENT_QUOTES, 'UTF-8') ?>'>

      <img src="../src/uploads/<?= htmlspecialchars($imagen) ?>" 
           alt="<?= htmlspecialchars($prod['nombre']) ?>" 
           class="w-full h-40 object-cover rounded product-image">

      <h3 class="mt-2 font-semibold"><?= htmlspecialchars($prod['nombre']) ?></h3>
      <p class="text-gray-500 text-sm"><?= htmlspecialchars($prod['categoria']) ?></p>
      <p class="text-lg font-bold mt-1 price">$<?= number_format($precio, 2) ?></p>

      <!-- Selects de talla y color -->
      <select class="variant-size border rounded-lg px-2 py-1 text-sm font-medium text-center mt-2 w-full">
        <?php foreach ($sizes as $size): ?>
          <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
        <?php endforeach; ?>
      </select>

      <select class="variant-color border rounded-lg px-2 py-1 text-sm font-medium text-center mt-2 w-full">
        <?php foreach ($colors as $color): ?>
          <option value="<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($color) ?></option>
        <?php endforeach; ?>
      </select>

      <button class="add-to-cart mt-3 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded w-full">Agregar</button>
    </article>
  <?php endforeach; ?>
</div>

<!-- CARRITO LATERAL -->
<aside id="cart" class="fixed top-0 right-0 w-80 h-full bg-white shadow-lg flex flex-col p-4 z-50">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-bold">Orden</h2>
    <div class="flex gap-2">
      <button id="discount-btn" class="bg-yellow-300 p-2 text-white rounded-full hover:bg-yellow-400">%</button>
      <button id="clear-cart" class="bg-red-100 p-2 rounded-full hover:bg-red-200">🗑</button>
    </div>
  </div>

  <div id="cart-items" class="flex-1 overflow-y-auto space-y-4"></div>

  <form id="checkout-form" method="POST" action="procesar_venta.php" class="mt-4">
    <input type="hidden" name="cart_data" id="cart-data">
    <div class="border-t pt-4 mt-4">
      <div class="flex justify-between text-sm">
        <span>Subtotal:</span><span id="subtotal">$0.00</span>
      </div>
      <div class="flex justify-between text-sm text-red-500">
        <span>Descuento:</span><span id="discount">$0.00</span>
      </div>
      <div class="flex justify-between font-bold text-lg mt-2">
        <span>Total:</span><span id="total">$0.00</span>
      </div>
      <button type="button" id="pay-btn" class="w-full bg-lime-500 hover:bg-lime-600 text-white font-semibold py-2 rounded mt-4">
        Realizar Pago
      </button>
      <button type="submit" id="submit-checkout" class="hidden"></button>
    </div>
  </form>
</aside>

<!-- MODAL DESCUENTO GLOBAL -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
    <h2 class="text-lg font-bold mb-3">Descuento General</h2>

    <div class="flex gap-2 mb-3">
      <select id="discount-type" class="border rounded-lg p-2 w-1/3 text-center">
        <option value="percent">%</option>
        <option value="amount">$</option>
      </select>
      <input type="number" id="discount-input" class="border rounded-lg p-2 w-2/3" placeholder="Valor">
    </div>

    <div class="flex justify-end gap-2">
      <button id="close-discount" class="bg-gray-200 hover:bg-gray-300 rounded-lg px-3 py-1">Cancelar</button>
      <button id="apply-discount" class="bg-lime-500 hover:bg-lime-600 text-white rounded-lg px-3 py-1">Aplicar</button>
    </div>
  </div>
</div>

<!-- MODAL DESCUENTO INDIVIDUAL -->
<div id="product-discount-modal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
    <h2 class="text-lg font-bold mb-3">Descuento del Producto</h2>

    <div class="flex gap-2 mb-3">
      <select id="product-discount-type" class="border rounded-lg p-2 w-1/3 text-center">
        <option value="percent">%</option>
        <option value="amount">$</option>
      </select>
      <input type="number" id="product-discount-input" class="border rounded-lg p-2 w-2/3" placeholder="Valor">
    </div>

    <div class="flex justify-end gap-2">
      <button id="product-discount-close" class="bg-gray-200 hover:bg-gray-300 rounded-lg px-3 py-1">Cancelar</button>
      <button id="product-discount-apply" class="bg-lime-500 hover:bg-lime-600 text-white rounded-lg px-3 py-1">Aplicar</button>
    </div>
  </div>
</div>





<script>
// Filtrado de productos por categoría
document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('.category-btn');
  const productos = document.querySelectorAll('.producto');

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      const selectedCat = btn.dataset.category.toLowerCase().trim();

      productos.forEach(prod => {
        const prodCat = prod.dataset.category.toLowerCase().trim();
        if (selectedCat === 'all' || prodCat === selectedCat) {
          prod.style.display = 'block';
        } else {
          prod.style.display = 'none';
        }
      });
    });
  });
});

// Actualizar colores según talla seleccionada
document.querySelectorAll('.producto').forEach(prod => {
  const sizeSelect = prod.querySelector('.variant-size');
  const colorSelect = prod.querySelector('.variant-color');
  const variants = JSON.parse(prod.dataset.variants || '[]');

  if (!variants.length) return;

  const colorMap = {};
  variants.forEach(v => {
    if (!colorMap[v.size]) colorMap[v.size] = [];
    if (!colorMap[v.size].includes(v.color)) colorMap[v.size].push(v.color);
  });

  const updateColors = () => {
    const validColors = colorMap[sizeSelect.value] || [];
    colorSelect.innerHTML = '';
    validColors.forEach(color => {
      const opt = document.createElement('option');
      opt.value = color;
      opt.textContent = color;
      colorSelect.appendChild(opt);
    });
  };

  sizeSelect.addEventListener('change', updateColors);
  updateColors();
});
</script>

<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>
</body>
</html>
