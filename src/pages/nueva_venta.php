<?php
require_once __DIR__ . "/../config/db.php";

// Traer productos y variantes
$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            c.nombre AS categoria,
            v.id AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla,
            v.color,
            v.imagen AS imagen_variante,
            v.precio_unitario
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.id_producto = p.id
        ORDER BY p.nombre ASC";

$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traer categorías para filtros
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Venta</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- FILTROS DE CATEGORÍA -->
<div class="flex flex-wrap justify-start gap-4 mb-8 mt-4 px-6">
  <button data-category="all" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">
    Todos
  </button>
  <?php foreach($categorias as $cat): ?>
    <button data-category="<?= strtolower($cat['nombre']) ?>" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">
      <?= htmlspecialchars($cat['nombre']) ?>
    </button>
  <?php endforeach; ?>
</div>

<?php
require_once __DIR__ . "/../config/db.php";

$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            c.nombre AS categoria,
            v.id AS id_variante,
            v.cod_barras AS variante_cod_barras,
            v.talla,
            v.color,
            v.imagen AS imagen_variante,
            v.precio_unitario
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.id_producto = p.id
        ORDER BY p.nombre ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por producto
$productos = [];
foreach ($rows as $row) {
    $id = $row['id_producto'];
    if (!isset($productos[$id])) {
        $productos[$id] = [
            'codigo' => $row['producto_cod_barras'],
            'nombre' => $row['producto_nombre'],
            'precio' => $row['precio_unitario'],
            'imagen' => $row['producto_imagen'],
            'categoria' => $row['categoria'],
            'variantes' => []
        ];
    }

    if (!empty($row['talla']) || !empty($row['color'])) {
        $productos[$id]['variantes'][] = [
            'size' => $row['talla'],
            'color' => $row['color'],
            'price' => $row['precio_unitario'] ?: $productos[$id]['precio'],
            'image' => $row['imagen_variante'] ?: $productos[$id]['imagen'],
            'code'  => $row['variante_cod_barras'] ?: $productos[$id]['codigo']
        ];
    }
}
?>

<!-- GRID PRODUCTOS -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 justify-items-center" id="productos-grid">
  <?php foreach($productos as $prod): ?>
    <?php 
      $variantes = json_encode($prod['variantes'], JSON_UNESCAPED_UNICODE);
      $sizes = array_unique(array_filter(array_column($prod['variantes'], 'size')));
      $colors = array_unique(array_filter(array_column($prod['variantes'], 'color')));

      $imagen = !empty($prod['imagen']) ? $prod['imagen'] : 'sin-imagen.png';
      $precio = $prod['precio'] ?: 0;
    ?>
    <article class="producto bg-white shadow rounded-lg p-4 text-center w-60" 
             data-name="<?= htmlspecialchars($prod['nombre']) ?>"
             data-code="<?= htmlspecialchars($prod['codigo']) ?>"
             data-img="../uploads/<?= htmlspecialchars($imagen) ?>"
             data-price="<?= htmlspecialchars($precio) ?>"
             data-variants='<?= htmlspecialchars($variantes, ENT_QUOTES, 'UTF-8') ?>'>

      <img src="../src/uploads/<?= htmlspecialchars($imagen) ?>" 
           alt="<?= htmlspecialchars($prod['nombre']) ?>" 
           class="w-full h-40 object-cover rounded product-image">

      <h3 class="mt-2 font-semibold"><?= htmlspecialchars($prod['nombre']) ?></h3>
      <p class="text-gray-500 text-sm"><?= htmlspecialchars($prod['categoria']) ?></p>
      <p class="text-lg font-bold mt-1 price">$<?= number_format($precio, 2) ?></p>

      <?php if ($sizes): ?>
        <select class="variant-size border rounded-lg px-2 py-1 text-sm font-medium text-center mt-2 w-full">
          <?php foreach ($sizes as $size): ?>
            <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>

      <?php if ($colors): ?>
        <select class="variant-color border rounded-lg px-2 py-1 text-sm font-medium text-center mt-2 w-full">
          <?php foreach ($colors as $color): ?>
            <option value="<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($color) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>

      <button class="add-to-cart mt-3 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded w-full">
        Agregar
      </button>
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

<!-- MODAL DESCUENTO -->
<div id="discount-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-80 shadow-lg">
    <h3 class="font-bold mb-2">Aplicar Descuento (%)</h3>
    <input id="discount-input" type="number" placeholder="10" class="border w-full px-3 py-2 mb-3 rounded">
    <div class="flex justify-end gap-2">
      <button id="close-discount" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
      <button id="discount-apply-btn" class="px-4 py-2 bg-lime-500 text-white rounded">Aplicar</button>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="../src/scripts/cart.js"></script>
<script>
  // Enviar carrito al backend antes de procesar venta
  document.getElementById("checkout-form").addEventListener("submit", (e) => {
    const cartData = localStorage.getItem("cart") || "[]";
    document.getElementById("cart-data").value = cartData;
  });

  // Filtrar productos por categoría
  document.querySelectorAll(".category-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const category = btn.dataset.category;
      document.querySelectorAll("#productos-grid .producto").forEach(prod => {
        if(category === "all" || prod.dataset.category === category) {
          prod.classList.remove("hidden");
        } else {
          prod.classList.add("hidden");
        }
      });
    });
  });
</script>

</body>
</html>
