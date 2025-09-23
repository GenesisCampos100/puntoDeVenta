// index.js

// === Menú lateral ===
const menuBtn = document.getElementById("menu-btn");
const sidebar = document.getElementById("sidebar");
const closeBtn = document.getElementById("close-btn");

menuBtn.addEventListener("click", () => {
  sidebar.classList.remove("-translate-x-64");
});

closeBtn.addEventListener("click", () => {
  sidebar.classList.add("-translate-x-64");
});

// === Carrito ===
let cart = [];

const addToCartBtns = document.querySelectorAll(".add-to-cart");
const cartItemsContainer = document.getElementById("cart-items");
const subtotalEl = document.getElementById("subtotal");
const discountEl = document.getElementById("discount");
const totalEl = document.getElementById("total");
const clearCartBtn = document.getElementById("clear-cart");

// Función para mostrar mensaje vacío
function showEmptyMessage() {
  cartItemsContainer.innerHTML = `
    <div class="flex flex-col items-center justify-center text-gray-500 py-10">
      <span class="text-5xl mb-2">📦</span>
      <p class="text-lg font-semibold">No hay productos</p>
    </div>
  `;
}

// Renderizar carrito
function renderCart() {
  cartItemsContainer.innerHTML = "";

  if (cart.length === 0) {
    showEmptyMessage();
    subtotalEl.textContent = "$0.00";
    discountEl.textContent = "-$0.00";
    totalEl.textContent = "$0.00";
    return;
  }

  let subtotal = 0;

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity;
    subtotal += itemTotal;

    const itemDiv = document.createElement("div");
    itemDiv.classList.add("flex", "flex-col", "gap-2", "border-b", "pb-2");

    itemDiv.innerHTML = `
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <img src="${item.img}" alt="${item.name}" class="w-12 h-12 object-cover rounded">
          <div>
            <p class="font-semibold">${item.name}</p>
            <p class="text-sm text-gray-500">$${item.price.toFixed(2)}</p>
          </div>
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
      </div>

      <!-- Campos de talla y color -->
      <div class="flex gap-2">
        <select class="talla border rounded p-1">
          <option ${item.size === "S" ? "selected" : ""}>S</option>
          <option ${item.size === "M" ? "selected" : ""}>M</option>
          <option ${item.size === "L" ? "selected" : ""}>L</option>
          <option ${item.size === "XL" ? "selected" : ""}>XL</option>
        </select>
        <select class="color border rounded p-1">
          <option ${item.color === "Rojo" ? "selected" : ""}>Rojo</option>
          <option ${item.color === "Azul" ? "selected" : ""}>Azul</option>
          <option ${item.color === "Negro" ? "selected" : ""}>Negro</option>
          <option ${item.color === "Blanco" ? "selected" : ""}>Blanco</option>
        </select>
      </div>

      <!-- Botón de descuento por producto -->
      <div>
        <button class="discount-product text-blue-500 text-sm">💲 Descuento</button>
      </div>
    `;

    // Eventos de +, -, eliminar
    itemDiv.querySelector(".increase").addEventListener("click", () => {
      item.quantity++;
      renderCart();
    });

    itemDiv.querySelector(".decrease").addEventListener("click", () => {
      if (item.quantity > 1) {
        item.quantity--;
      } else {
        cart.splice(index, 1);
      }
      renderCart();
    });

    itemDiv.querySelector(".remove").addEventListener("click", () => {
      cart.splice(index, 1);
      renderCart();
    });

    // Evento para actualizar talla y color
    itemDiv.querySelector(".talla").addEventListener("change", (e) => {
      item.size = e.target.value;
    });

    itemDiv.querySelector(".color").addEventListener("change", (e) => {
      item.color = e.target.value;
    });

    // Evento para descuento individual
    itemDiv.querySelector(".discount-product").addEventListener("click", () => {
      const porcentaje = prompt("Ingrese porcentaje de descuento para este producto:");
      if (porcentaje) {
        const desc = parseFloat(porcentaje) / 100;
        item.price = item.price - (item.price * desc);
        renderCart();
      }
    });

    cartItemsContainer.appendChild(itemDiv);
  });

  // Calcular totales
  const discount = subtotal > 500 ? subtotal * 0.1 : 0;
  const total = subtotal - discount;

  subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  discountEl.textContent = `-$${discount.toFixed(2)}`;
  totalEl.textContent = `$${total.toFixed(2)}`;
}

// Botón para vaciar carrito
clearCartBtn.addEventListener("click", () => {
  cart = [];
  renderCart();
});

// Botón "Add to cart"
addToCartBtns.forEach((btn) => {
  btn.addEventListener("click", (e) => {
    const product = e.target.closest(".producto");
    const name = product.querySelector("h3").textContent;
    const price = parseFloat(product.querySelector(".font-bold").textContent.replace("$", ""));
    const img = product.querySelector("img").src;

    const existingProduct = cart.find((item) => item.name === name);

    if (existingProduct) {
      existingProduct.quantity++;
    } else {
      cart.push({ name, price, img, quantity: 1 });
    }

    renderCart();
  });

  const discountInput = document.getElementById("discount-input");
  const applyDiscountBtn = document.getElementById("apply-discount");

  applyDiscountBtn.addEventListener("click", () => {
    const value = discountInput.value.trim();

    if (value.endsWith("%")) {
      const porcentaje = parseFloat(value.replace("%", "")) / 100;
      cart.forEach(item => {
        item.price = item.price - (item.price * porcentaje);
      });
    } else if (!isNaN(value) && value > 0) {
      const monto = parseFloat(value);
      const totalAntes = cart.reduce((acc, item) => acc + item.price * item.quantity, 0);
      const factor = (totalAntes - monto) / totalAntes;
      cart.forEach(item => {
        item.price = item.price * factor;
      });
    }

    renderCart();
  });
});

// Al iniciar la página, mostrar mensaje vacío
renderCart();



