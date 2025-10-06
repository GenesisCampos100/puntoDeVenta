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
            v.cantidad,
            v.cantidad_min,
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

<!-- FILTROS DE CATEGORÍA -->
<div class="flex flex-wrap justify-start gap-4 mb-8">
  <button data-category="all" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">
    Todos
  </button>
  <?php foreach($categorias as $cat): ?>
    <button data-category="<?= strtolower($cat['nombre']) ?>" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">
      <?= $cat['nombre'] ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- GRID PRODUCTOS -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 justify-items-center" id="productos-grid">
  <?php foreach($productos as $prod): ?>
    <?php
$imagen = 'pages/uploads/sin-imagen.png'; // por defecto

if (!empty($prod['imagen_variante'])) {
    $imagen = 'pages/uploads/' . htmlspecialchars($prod['imagen_variante']);
} elseif (!empty($prod['producto_imagen'])) {
    $imagen = 'pages/uploads/' . htmlspecialchars($prod['producto_imagen']);
}
?>
    <article class="producto bg-white shadow rounded-lg p-4 text-center w-60" data-category="<?= strtolower($prod['categoria']) ?>">
      <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($prod['producto_nombre']) ?>" class="w-full h-40 object-cover rounded">
      <h3 class="mt-2 font-semibold"><?= htmlspecialchars($prod['producto_nombre']) ?></h3>
      <p class="text-gray-500 text-sm">Código: <?= htmlspecialchars($prod['producto_cod_barras']) ?></p>
      <p class="text-lg font-bold mt-2">$<?= number_format($prod['precio_unitario'],2) ?></p>
      <button class="add-to-cart mt-3 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded w-full">
        Agregar
      </button>
    </article>
  <?php endforeach; ?>
</div>

<!-- CARRITO -->
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
    <input type="hidden" name="payments_data" id="payments-data">
    <div class="border-t pt-4 mt-4">
      <div class="flex justify-between text-sm">
        <span>Subtotal:</span><span id="subtotal">$0.00</span>
      </div>
      <div class="flex justify-between text-sm text-red-500">
        <span>Descuento:</span><span id="discount">-$0.00</span>
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

<!-- MODALES -->
<div id="discount-card" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-80 shadow-lg">
    <h3 class="font-bold mb-2">Aplicar Descuento</h3>
    <input id="discount-input" type="text" placeholder="10% o 50" class="border w-full px-3 py-2 mb-3 rounded">
    <div class="flex justify-end gap-2">
      <button id="close-discount" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
      <button id="apply-discount" class="px-4 py-2 bg-lime-500 text-white rounded">Aplicar</button>
    </div>
  </div>
</div>

<div id="payment-card" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-96 shadow-lg">
    <h3 class="font-bold mb-2">Pagos</h3>
    <div class="space-y-2">
      <input type="number" data-method="efectivo" placeholder="Efectivo" class="payment-input border w-full px-3 py-2 rounded">
      <input type="number" data-method="tarjeta" placeholder="Tarjeta" class="payment-input border w-full px-3 py-2 rounded">
    </div>
    <p class="text-right font-bold mt-3">Total: <span id="total">$0.00</span></p>
    <div class="flex justify-end gap-2 mt-3">
      <button id="close-payment" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
      <button id="confirm-payment" class="px-4 py-2 bg-lime-500 text-white rounded">Confirmar</button>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="../src/scripts/cart.js"></script>
<script src="../src/scripts/modal.js"></script>

<script>
// Búsqueda global
document.getElementById('globalSearch')?.addEventListener('input', e => {
  const query = e.target.value.toLowerCase();
  document.querySelectorAll('.producto').forEach(prod => {
    const name = prod.querySelector('h3').textContent.toLowerCase();
    prod.style.display = name.includes(query) ? '' : 'none';
  });
});

// Filtros de categoría
document.querySelectorAll('.category-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const cat = btn.dataset.category;
    document.querySelectorAll('.producto').forEach(prod => {
      prod.style.display = (cat === 'all' || prod.dataset.category === cat) ? '' : 'none';
    });
  });
});
</script>
