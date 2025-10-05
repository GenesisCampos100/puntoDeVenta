<?php
require_once __DIR__ . "/../config/db.php";

// Consulta de productos
$sql = "SELECT p.cod_barras, p.nombre, p.imagen, p.cantidad, c.nombre AS categoria, p.precio_unitario
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        ORDER BY p.nombre ASC";

$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traer categorías
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
<body class="bg-white">

<!-- HEADER -->
<header class="flex items-center bg-white text-black p-4 fixed top-0 left-0 right-0 z-40 shadow">
  <button id="menu-btn" class="text-2xl focus:outline-none mr-4">&#9776;</button>
  <img src="../public/img/logo.jpeg" alt="logo" class="h-12">
  <div class="flex items-center bg-gray-100 rounded-full overflow-hidden ml-4 w-72">
    <input type="text" placeholder="Buscar..." class="w-full px-4 py-2 text-black focus:outline-none">
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
    <a href="nueva_venta.php" 
       class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-400 text-white font-semibold py-2 px-4 rounded-full">
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
  <section id="productos" class="p-6">
    <div class="flex flex-wrap justify-left gap-4 mb-8">
      <button data-category="all" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">Todos</button>
      <?php foreach($categorias as $cat): ?>
        <button data-category="<?= strtolower($cat['nombre']) ?>" class="category-btn px-6 py-2 rounded-full bg-red-500 text-white font-medium hover:bg-red-600 transition">
          <?= $cat['nombre'] ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 justify-items-center">
      <?php foreach($productos as $prod): ?>
        <article class="producto bg-white shadow rounded-lg p-4 text-center w-60" data-category="<?= strtolower($prod['categoria']) ?>">
          <img src="../public/img/productos/<?= $prod['imagen'] ?>" alt="<?= $prod['nombre'] ?>" class="w-full h-40 object-cover rounded">
          <h3 class="mt-2 font-semibold"><?= $prod['nombre'] ?></h3>
          <p class="text-gray-500 text-sm">Código: <?= $prod['cod_barras'] ?></p>
          <p class="text-lg font-bold mt-2">$<?= number_format($prod['precio_unitario'], 2) ?></p>
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
    <button id="pay-btn" class="w-full bg-lime-500 hover:bg-lime-600 text-white font-semibold py-2 rounded mt-4">
      Realizar Pago
    </button>
  </div>
</aside>

<!-- MODAL DE PAGO -->
<div id="payment-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded shadow-lg w-96 relative">
    <button id="close-payment" class="absolute top-2 right-2 text-xl">&times;</button>
    <h3 class="text-lg font-bold mb-4 text-center">Métodos de Pago</h3>
    <form id="payment-form" class="space-y-4">
      <div>
        <label class="flex justify-between items-center">
          <span>Efectivo</span>
          <input type="number" id="pago-efectivo" class="border p-2 rounded w-32" min="0" step="0.01">
        </label>
      </div>
      <div>
        <label class="flex justify-between items-center">
          <span>Tarjeta</span>
          <input type="number" id="pago-tarjeta" class="border p-2 rounded w-32" min="0" step="0.01">
        </label>
      </div>
      <div>
        <label class="flex justify-between items-center">
          <span>Transferencia</span>
          <input type="number" id="pago-transferencia" class="border p-2 rounded w-32" min="0" step="0.01">
        </label>
      </div>
      <div class="border-t mt-3 pt-3 text-right">
        <button type="button" id="confirm-payment" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Confirmar Pago</button>
      </div>
    </form>
  </div>
</div>

<script src="../src/scripts/menu.js"></script>
<script src="../src/scripts/cart.js"></script>

<script>
// Abrir y cerrar modal de pago
const payBtn = document.getElementById('pay-btn');
const paymentModal = document.getElementById('payment-modal');
const closePayment = document.getElementById('close-payment');
const confirmPayment = document.getElementById('confirm-payment');

payBtn.addEventListener('click', () => {
  paymentModal.classList.remove('hidden');
});

closePayment.addEventListener('click', () => {
  paymentModal.classList.add('hidden');
});

// Enviar datos con validación
confirmPayment.addEventListener('click', async () => {
  const efectivo = parseFloat(document.getElementById('pago-efectivo').value) || 0;
  const tarjeta = parseFloat(document.getElementById('pago-tarjeta').value) || 0;
  const transferencia = parseFloat(document.getElementById('pago-transferencia').value) || 0;

  const totalPago = efectivo + tarjeta + transferencia;
  const totalCarrito = parseFloat(document.getElementById('total').textContent.replace('$', ''));

  if (totalPago.toFixed(2) !== totalCarrito.toFixed(2)) {
    alert(`El total pagado ($${totalPago.toFixed(2)}) debe ser igual al total de la venta ($${totalCarrito.toFixed(2)}).`);
    return;
  }

  const cart = JSON.parse(localStorage.getItem('cart')) || [];

  const formData = new FormData();
  formData.append('cart_data', JSON.stringify(cart));
  formData.append('payments', JSON.stringify({
    efectivo: efectivo,
    tarjeta: tarjeta,
    transferencia: transferencia
  }));

  const res = await fetch('procesar_venta.php', {
    method: 'POST',
    body: formData
  });

  const data = await res.json();

  if (data.status === 'success') {
    alert('Venta registrada correctamente.');
    localStorage.removeItem('cart');
    window.location.href = 'ventas.php';
  } else {
    alert('Error: ' + data.message);
  }
});
</script>
</body>
</html>
