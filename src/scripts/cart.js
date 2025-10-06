document.addEventListener("DOMContentLoaded", () => {
  const cartItemsContainer = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");
  const addToCartBtns = document.querySelectorAll(".add-to-cart");
  const payBtn = document.getElementById("pay-btn");

  // Modal descuento
  const discountModal = document.getElementById("discount-modal");
  const discountInput = document.getElementById("discount-input");
  const discountApplyBtn = document.getElementById("discount-apply-btn");
  let currentDiscountItem = null;

  // Obtener carrito
  function getCart() {
    return JSON.parse(localStorage.getItem("cart")) || [];
  }

  // Guardar carrito
  function setCart(cart) {
    localStorage.setItem("cart", JSON.stringify(cart));
  }

  // Mostrar mensaje carrito vacío
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

  // Renderizar carrito
  function renderCart() {
    const cart = getCart();
    if (!cart.length) return showEmptyMessage();

    cartItemsContainer.innerHTML = "";
    let subtotal = 0, totalDiscount = 0;

    cart.forEach((item, index) => {
      const priceAfterDiscount = item.price * (1 - (item.discount || 0)/100);
      const itemTotal = priceAfterDiscount * item.quantity;

      subtotal += itemTotal;
      totalDiscount += (item.price * item.quantity - itemTotal);

      const itemDiv = document.createElement("div");
      itemDiv.className = "flex gap-4 mb-3 bg-white rounded-xl shadow-sm p-2 w-full items-stretch";

      itemDiv.innerHTML = `
        <img src="${item.img}" alt="${item.name}" class="w-20 h-full rounded-xl object-cover">
        <div class="flex-1 grid grid-rows-3 gap-2">
          <!-- Nombre + botones -->
          <div class="flex justify-between items-center w-full">
            <p class="font-semibold truncate">${item.name}</p>
            <div class="flex gap-2">
              <button class="discount-btn bg-yellow-400 text-white text-sm px-2 py-1 rounded hover:bg-yellow-500 transition">%</button>
              <button class="remove bg-rose-500 text-white text-sm px-2 py-1 rounded hover:bg-rose-600 transition">✕</button>
            </div>
          </div>

          <!-- Selects -->
          <div class="flex gap-2 w-full">
            <select class="size-select border rounded-lg text-sm font-medium text-center p-2 flex-1 focus:ring-1 focus:ring-gray-300">
              <option value="XS" ${item.size==="XS"?"selected":""}>XS</option>
              <option value="S" ${item.size==="S"?"selected":""}>S</option>
              <option value="M" ${item.size==="M"?"selected":""}>M</option>
              <option value="L" ${item.size==="L"?"selected":""}>L</option>
              <option value="XL" ${item.size==="XL"?"selected":""}>XL</option>
            </select>
            <select class="color-select border rounded-lg text-sm font-medium text-center p-2 flex-1 focus:ring-1 focus:ring-gray-300">
              <option value="Negro" ${item.color==="Negro"?"selected":""}>Negro</option>
              <option value="Blanco" ${item.color==="Blanco"?"selected":""}>Blanco</option>
              <option value="Azul" ${item.color==="Azul"?"selected":""}>Azul</option>
              <option value="Rojo" ${item.color==="Rojo"?"selected":""}>Rojo</option>
              <option value="Verde" ${item.color==="Verde"?"selected":""}>Verde</option>
            </select>
          </div>

          <!-- Contador + precio -->
          <div class="flex w-full">
            <div class="flex items-center justify-between w-1/2">
              <button class="decrease bg-rose-500 h-8 w-8 rounded-full text-white flex justify-center items-center hover:bg-rose-600 transition">−</button>
              <span class="flex-1 text-center font-semibold select-none">${String(item.quantity).padStart(2,"0")}</span>
              <button class="increase bg-lime-500 h-8 w-8 rounded-full text-white flex justify-center items-center hover:bg-lime-600 transition">+</button>
            </div>
            <div class="w-1/2 flex justify-end items-center">
              <p class="font-semibold text-lg text-gray-700">$${itemTotal.toFixed(2)}</p>
            </div>
          </div>
        </div>
      `;

      // Eventos cantidad
      itemDiv.querySelector(".increase").addEventListener("click", () => {
        item.quantity++;
        setCart(cart);
        renderCart();
      });
      itemDiv.querySelector(".decrease").addEventListener("click", () => {
        if (item.quantity > 1) item.quantity--;
        else cart.splice(index, 1);
        setCart(cart);
        renderCart();
      });

      // Eliminar
      itemDiv.querySelector(".remove").addEventListener("click", () => {
        cart.splice(index, 1);
        setCart(cart);
        renderCart();
      });

      // Selects
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
        currentDiscountItem = item;
        discountInput.value = item.discount || 0;
        discountModal.classList.remove("hidden");
      });

      cartItemsContainer.appendChild(itemDiv);
    });

    subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    discountEl.textContent = `$${totalDiscount.toFixed(2)}`;
    totalEl.textContent = `$${subtotal.toFixed(2)}`;
  }

  // Limpiar carrito
  clearCartBtn?.addEventListener("click", () => {
    localStorage.removeItem("cart");
    renderCart();
  });

  // Agregar al carrito
  addToCartBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      const product = e.target.closest(".producto");
      const cart = getCart();

      const code  = product.dataset.code;
      const name  = product.dataset.name;
      const img   = product.dataset.img;
      const price = parseFloat(product.dataset.price);
      const size  = product.dataset.size || "M";
      const color = product.dataset.color || "Negro";

      const existing = cart.find(item => item.code === code);

      if (existing) existing.quantity++;
      else cart.push({ code, name, img, price, size, color, quantity: 1, discount: 0 });

      setCart(cart);
      renderCart();
    });
  });

  // Aplicar descuento
  discountApplyBtn?.addEventListener("click", () => {
    if (currentDiscountItem) {
      currentDiscountItem.discount = parseFloat(discountInput.value) || 0;
      setCart(getCart());
      renderCart();
      discountModal.classList.add("hidden");
      currentDiscountItem = null;
    }
  });

  discountModal?.addEventListener("click", (e) => {
    if (e.target.id === "discount-modal") {
      discountModal.classList.add("hidden");
      currentDiscountItem = null;
    }
  });

  payBtn?.addEventListener("click", () => {
    document.dispatchEvent(new CustomEvent("openPaymentModal"));
  });

  renderCart();
});
