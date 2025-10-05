<?php
require_once __DIR__ . "/../config/db.php";

// Productos y variantes
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

// Categorías
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva Venta</title>
<link href="../styles/output.css" rel="stylesheet">
</head>
<script>
document.addEventListener("DOMContentLoaded", () => {
  console.log("✅ Página cargada correctamente");
});
</script>
<body class="bg-white">

<!-- HEADER -->
<header class="flex items-center bg-white text-black p-4 fixed top-0 left-0 right-0 z-40 shadow">
  <button id="menu-btn" class="text-2xl focus:outline-none mr-4">&#9776;</button>
  <img src="../public/img/logo.jpeg" alt="logo" class="h-12">
  <div class="flex items-center bg-gray-100 rounded-full overflow-hidden ml-4 w-72">
    <input type="text" id="globalSearch" placeholder="Buscar..." class="w-full px-4 py-2 text-black focus:outline-none">
  </div>
  <button class="ml-2 bg-botonVerde hover:bg-botonVerde-hover text-white px-6 py-2 rounded-full">
    Filtros
  </button>
</header>

<!-- SIDEBAR -->
<nav id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white -translate-x-64 transition-transform duration-300 z-50">
  <div class="flex items-center justify-center p-4 border-b border-gray-700">
    <img src="../public/img/logo-menu.png" alt="Logo" class="h-12">
  </div>
  <div class="flex justify-end p-4">
    <button id="close-btn" class="text-2xl">&times;</button>
  </div>
  <div class="p-4">
    <a href="nueva_venta.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-400 text-white font-semibold py-2 px-4 rounded-full">
      <span class="text-xl">+</span> Nueva Compra
    </a>
  </div>
  <ul class="mt-4 space-y-2 pl-4">
    <li><a href="ventas.php" class="flex items-center gap-2 hover:bg-red-500 p-2 rounded">Ventas</a></li>
    <li><a href="productos.php" class="flex items-center gap-2 hover:bg-red-500 p-2 rounded">Productos</a></li>
    <li><a href="caja.php" class="flex items-center gap-2 hover:bg-red-500 p-2 rounded">Caja</a></li>
    <li><a href="reportes.php" class="flex items-center gap-2 hover:bg-red-500 p-2 rounded">Reportes</a></li>
  </ul>
</nav>

<!-- MAIN CONTENT -->
<main id="content" class="pt-20 pl-0 pr-80 transition-all duration-300">
  <section id="productos" class="p-6 max-w-[1400px] mx-auto transition-all duration-300">
    <!-- Filtros -->
    <div class="flex flex-wrap justify-start gap-4 mb-8">
      <button data-category="all" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">Todos</button>
      <?php foreach($categorias as $cat): ?>
        <button data-category="<?= strtolower($cat['nombre']) ?>" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">
          <?= $cat['nombre'] ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- GRID PRODUCTOS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 justify-items-center" id="productos-grid">
      <?php foreach($productos as $prod): ?>
        <article class="producto bg-white shadow rounded-lg p-4 text-center w-60" data-category="<?= strtolower($prod['categoria']) ?>">
          <img src="../public/img/productos/<?= $prod['producto_imagen'] ?>" alt="<?= $prod['producto_nombre'] ?>" class="w-full h-40 object-cover rounded">
          <h3 class="mt-2 font-semibold"><?= $prod['producto_nombre'] ?></h3>
          <p class="text-gray-500 text-sm">Código: <?= $prod['producto_cod_barras'] ?></p>
          <p class="text-lg font-bold mt-2">$<?= number_format($prod['precio_unitario'],2) ?></p>
          <button class="add-to-cart mt-3 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded w-full">
            Agregar
          </button>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>

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

<!-- Scripts -->
<script src="../scripts/menu.js"></script>
<script src="../scripts/cart.js"></script>
<script src="../scripts/modal.js"></script>


<script>
document.getElementById('globalSearch')?.addEventListener('input', e => {
  const query = e.target.value.toLowerCase();
  document.querySelectorAll('.producto').forEach(prod => {
    const name = prod.querySelector('h3').textContent.toLowerCase();
    prod.style.display = name.includes(query) ? '' : 'none';
  });
});
</script>

</body>
</html>
