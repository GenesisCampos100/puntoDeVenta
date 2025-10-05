document.addEventListener("DOMContentLoaded", () => {
  const cartItemsContainer = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");
  const addToCartBtns = document.querySelectorAll(".add-to-cart");
  const payBtn = document.getElementById("pay-btn");

  function getCart() {
    return JSON.parse(localStorage.getItem("cart")) || [];
  }

  function setCart(cart) {
    localStorage.setItem("cart", JSON.stringify(cart));
  }

  function showEmptyMessage() {
    cartItemsContainer.innerHTML = `
      <div class="flex flex-col items-center justify-center text-gray-500 py-10">
        <span class="text-5xl mb-2">📦</span>
        <p class="text-lg font-semibold">No hay productos</p>
      </div>`;
    subtotalEl.textContent = "$0.00";
    discountEl.textContent = "$0.00";
    totalEl.textContent = "$0.00";
  }

function renderCart() {
  const cart = getCart();
  if (!cart.length) return showEmptyMessage();

  cartItemsContainer.innerHTML = "";
  let subtotal = 0;

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity;
    subtotal += itemTotal;

    const itemDiv = document.createElement("div");
    itemDiv.className =
      "flex items-center justify-between bg-gray-50 rounded-sm p-3 mb-3 transition hover:bg-gray-100";

    itemDiv.innerHTML = `
      <div class="flex items-start gap-4">
  <!-- Imagen del producto -->
  <img src="${item.img}" alt="${item.name}" class="w-16 h-16 rounded-xl object-cover">

  <!-- Información y opciones -->
  <div class="flex flex-col gap-2">
    <!-- Nombre -->
    <p class="font-semibold truncate w-32">${item.name}</p>

    <!-- Selects de tamaño y color -->
    <div class="flex gap-2">
      <select class="size-select border rounded-lg text-sm font-medium text-center p-2 focus:ring-1 focus:ring-gray-300">
        <option value="XS" ${item.size === "XS" ? "selected" : ""}>XS</option>
        <option value="S" ${item.size === "S" ? "selected" : ""}>S</option>
        <option value="M" ${item.size === "M" ? "selected" : ""}>M</option>
        <option value="L" ${item.size === "L" ? "selected" : ""}>L</option>
        <option value="XL" ${item.size === "XL" ? "selected" : ""}>XL</option>
      </select>

      <select class="color-select border rounded-lg text-sm font-medium text-center p-2 focus:ring-1 focus:ring-gray-300">
        <option value="Negro" ${item.color === "Negro" ? "selected" : ""}>Negro</option>
        <option value="Blanco" ${item.color === "Blanco" ? "selected" : ""}>Blanco</option>
        <option value="Azul" ${item.color === "Azul" ? "selected" : ""}>Azul</option>
        <option value="Rojo" ${item.color === "Rojo" ? "selected" : ""}>Rojo</option>
        <option value="Verde" ${item.color === "Verde" ? "selected" : ""}>Verde</option>
      </select>
    </div>

    <!-- Contador de cantidad debajo del select -->
    <div class="flex items-center gap-2">
      <button class="decrease bg-rose-500 text-white w-14 h-14 rounded-full flex items-center justify-center text-lg hover:bg-rose-600">−</button>
      <span class="font-semibold w-6 text-center">${String(item.quantity).padStart(2, "0")}</span>
      <button class="increase bg-lime-500 text-white w-7 h-7 rounded-full flex items-center justify-center text-lg hover:bg-lime-600">+</button>
    </div>
  </div>

  <!-- Total y botones de acción -->
  <div class="flex flex-col items-end gap-2">
    <p class="text-lg font-semibold text-gray-700 item-total transition-all duration-200">$${itemTotal.toFixed(2)}</p>
    <div class="flex gap-2">
      <button class="discount-btn bg-pink-200 p-1.5 rounded-full text-pink-700 hover:bg-pink-300">🏷️</button>
      <button class="remove bg-gray-800 p-1.5 rounded-full text-white hover:bg-gray-700">🗑️</button>
    </div>
  </div>
</div>

    `;

    // Eventos cantidad
    itemDiv.querySelector(".increase").addEventListener("click", () => {
      item.quantity++;
      setCart(cart);
      animateCartTotal();
      renderCart();
    });

    itemDiv.querySelector(".decrease").addEventListener("click", () => {
      if (item.quantity > 1) item.quantity--;
      else cart.splice(index, 1);
      setCart(cart);
      animateCartTotal();
      renderCart();
    });

    // Eventos eliminar
    itemDiv.querySelector(".remove").addEventListener("click", () => {
      cart.splice(index, 1);
      setCart(cart);
      animateCartTotal();
      renderCart();
    });

    // Eventos de talla y color
    itemDiv.querySelector(".size-select").addEventListener("change", (e) => {
      item.size = e.target.value;
      setCart(cart);
    });

    itemDiv.querySelector(".color-select").addEventListener("change", (e) => {
      item.color = e.target.value;
      setCart(cart);
    });

    // Descuento individual
    itemDiv.querySelector(".discount-btn").addEventListener("click", () => {
      const event = new CustomEvent("openProductDiscount", {
        detail: { item, renderCart },
      });
      document.dispatchEvent(event);
    });

    cartItemsContainer.appendChild(itemDiv);
  });

  // Animación del total
  animateCartNumber(subtotalEl, subtotal);
  animateCartNumber(discountEl, 0);
  animateCartNumber(totalEl, subtotal);
}



  // Botón limpiar carrito
  clearCartBtn?.addEventListener("click", () => {
    localStorage.removeItem("cart");
    renderCart();
  });

addToCartBtns.forEach(btn => {
  btn.addEventListener("click", (e) => {
    const product = e.target.closest(".producto");
    const name = product.querySelector("h3").textContent;
    const price = parseFloat(product.querySelector("p.font-bold").textContent.replace("$", ""));
    const img = product.querySelector("img").src;
    const code = product.querySelector("p.text-gray-500")?.textContent.replace("Código:", "").trim() || name;

    const cart = getCart();
    // Buscar por código único
    const existing = cart.find(i => i.code === code);

    if (existing) {
      existing.quantity++;
    } else {
      cart.push({
        code,
        name,
        price,
        img,
        quantity: 1
      });
    }

    setCart(cart);
    renderCart();
  });
});


  // Pago
  payBtn?.addEventListener("click", () => {
    document.dispatchEvent(new CustomEvent("openPaymentModal"));
  });

  renderCart();
});

// Animación suave de números (subtotal, total)
function animateCartNumber(el, newValue) {
  const current = parseFloat(el.textContent.replace(/[^0-9.-]+/g, "")) || 0;
  const diff = newValue - current;
  const duration = 300;
  const steps = 20;
  let step = 0;

  const interval = setInterval(() => {
    step++;
    const value = current + (diff * step) / steps;
    el.textContent = `$${value.toFixed(2)}`;
    if (step >= steps) clearInterval(interval);
  }, duration / steps);

  // Efecto de resaltado visual al actualizar
  el.classList.add("text-rose-500");
  setTimeout(() => el.classList.remove("text-rose-500"), 300);
}

function animateCartTotal() {
  const cartContainer = document.querySelector("#cart-items");
  if (!cartContainer) return;
  cartContainer.classList.add("scale-95");
  setTimeout(() => cartContainer.classList.remove("scale-95"), 150);
}
