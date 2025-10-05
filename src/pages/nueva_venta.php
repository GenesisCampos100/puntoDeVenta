<?php
require_once __DIR__ . "/../config/db.php";

// Productos y categorías
$sql = "SELECT 
            p.id AS id_producto,
            p.cod_barras AS producto_cod_barras,
            p.nombre AS producto_nombre,
            p.imagen AS producto_imagen,
            c.nombre AS categoria,
            v.id AS id_variante,
            v.talla,
            v.color,
            v.precio_unitario
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN variantes v ON v.id_producto = p.id
        ORDER BY p.nombre ASC";
$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<header class="flex items-center bg-white text-black p-4 fixed top-0 left-0 right-0 z-40 shadow">
  <button id="menu-btn" class="text-2xl mr-4">&#9776;</button>
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
  <div class="flex justify-end p-4"><button id="close-btn" class="text-2xl">&times;</button></div>
  <ul class="p-4 space-y-2">
    <li><a href="nueva_venta.php">Nueva Venta</a></li>
    <li><a href="ventas.php">Ventas</a></li>
  </ul>
</nav>

<!-- MAIN CONTENT -->
<main id="content" class="pt-20 pl-0 transition-all duration-300">
  <section class="p-6 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach($productos as $prod): ?>
      <div class="producto bg-white shadow p-4 text-center">
        <img src="../public/img/productos/<?= $prod['producto_imagen'] ?>" class="w-full h-40 object-cover rounded">
        <h3 class="mt-2 font-semibold"><?= $prod['producto_nombre'] ?></h3>
        <p class="text-lg font-bold mt-2">$<?= number_format($prod['precio_unitario'],2) ?></p>
        <button class="add-to-cart mt-3 bg-gray-800 text-white px-4 py-2 rounded w-full">Agregar</button>
      </div>
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
      <div class="flex justify-between text-sm"><span>Subtotal:</span><span id="subtotal">$0.00</span></div>
      <div class="flex justify-between text-sm text-red-500"><span>Descuento:</span><span id="discount-amount">-$0.00</span></div>
      <div class="flex justify-between font-bold text-lg mt-2"><span>Total:</span><span id="total">$0.00</span></div>
      <button type="button" id="pay-btn" class="w-full bg-lime-500 text-white py-2 mt-4 rounded">Realizar Pago</button>
      <button type="submit" id="submit-checkout" class="hidden"></button>
    </div>
  </form>
</aside>

<!-- SCRIPTS -->
<script>
document.addEventListener("DOMContentLoaded", () => {

  // Sidebar
  const menuBtn = document.getElementById("menu-btn");
  const closeBtn = document.getElementById("close-btn");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  const isSidebarOpen = localStorage.getItem("sidebarOpen") === "true";
  if(isSidebarOpen){ sidebar.classList.remove("-translate-x-64"); content?.classList.add("pl-64"); }

  menuBtn?.addEventListener("click", ()=>{ sidebar.classList.remove("-translate-x-64"); content?.classList.add("pl-64"); localStorage.setItem("sidebarOpen", true); });
  closeBtn?.addEventListener("click", ()=>{ sidebar.classList.add("-translate-x-64"); content?.classList.remove("pl-64"); localStorage.setItem("sidebarOpen", false); });

  // Carrito
  const cartItems = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount-amount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");
  const addBtns = document.querySelectorAll(".add-to-cart");

  const getCart = ()=> JSON.parse(localStorage.getItem("cart")) || [];
  const setCart = (cart)=> localStorage.setItem("cart", JSON.stringify(cart));

  function showEmpty(){ 
    cartItems.innerHTML = `<div class="flex flex-col items-center justify-center text-gray-500 py-10">
      <span class="text-5xl mb-2">📦</span><p class="text-lg font-semibold">No hay productos</p></div>`;
    subtotalEl.textContent="$0.00"; discountEl.textContent="$0.00"; totalEl.textContent="$0.00";
  }

  function renderCart(){
    const cart = getCart();
    if(!cart.length) return showEmpty();
    cartItems.innerHTML=""; let subtotal=0;
    cart.forEach((item, index)=>{
      const itemTotal = item.price*item.quantity; subtotal+=itemTotal;
      const div = document.createElement("div"); div.className="flex flex-col gap-2 border-b pb-2";
      div.innerHTML=`
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <img src="${item.img}" class="w-12 h-12 object-cover rounded">
            <div><p class="font-semibold">${item.name}</p><p class="text-sm text-gray-500">$${item.price.toFixed(2)}</p></div>
          </div>
          <div class="flex items-center gap-2">
            <button class="decrease bg-gray-200 px-2 rounded">-</button>
            <span class="font-semibold">${item.quantity}</span>
            <button class="increase bg-gray-200 px-2 rounded">+</button>
          </div>
          <div class="text-right">
            <p class="font-semibold">$${itemTotal.toFixed(2)}</p>
            <button class="remove text-red-500 text-sm">Eliminar</button>
          </div>
        </div>`;
      div.querySelector(".increase").addEventListener("click", ()=>{ item.quantity++; setCart(cart); renderCart(); });
      div.querySelector(".decrease").addEventListener("click", ()=>{ if(item.quantity>1)item.quantity--; else cart.splice(index,1); setCart(cart); renderCart(); });
      div.querySelector(".remove").addEventListener("click", ()=>{ cart.splice(index,1); setCart(cart); renderCart(); });
      cartItems.appendChild(div);
    });
    subtotalEl.textContent=`$${subtotal.toFixed(2)}`; discountEl.textContent="$0.00"; totalEl.textContent=`$${subtotal.toFixed(2)}`;
  }

  addBtns.forEach(btn=>{
    btn.addEventListener("click",(e)=>{
      const product = e.target.closest(".producto");
      const name = product.querySelector("h3").textContent;
      const price = parseFloat(product.querySelector("p.font-bold").textContent.replace("$",""));
      const img = product.querySelector("img").src;
      const cart = getCart();
      const existing = cart.find(i=>i.name===name && i.size==="M" && i.color==="Negro");
      if(existing) existing.quantity++; else cart.push({name,price,img,quantity:1,size:"M",color:"Negro"});
      setCart(cart); renderCart();
    });
  });

  clearCartBtn?.addEventListener("click", ()=>{ setCart([]); renderCart(); });

  renderCart();

});
</script>

<script src="modal.js"></script>
</body>
</html>
