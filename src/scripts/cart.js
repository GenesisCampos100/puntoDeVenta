document.addEventListener("DOMContentLoaded", () => {
  // =======================
  // VARIABLES GLOBALES
  // =======================
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  let cartDiscount = parseFloat(localStorage.getItem("cartDiscount")) || 0;

  const cartContainer = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");
  const payBtn = document.getElementById("pay-btn");

  // Modal descuento general
  const discountModal = document.getElementById("discount-modal");
  const discountInput = document.getElementById("discount-input");
  const discountApplyBtn = document.getElementById("discount-apply-btn");
  const discountCloseBtn = document.getElementById("close-discount");

  // =======================
  // FUNCIONES DE CARRITO
  // =======================
  function saveCart() {
    localStorage.setItem("cart", JSON.stringify(cart));
    localStorage.setItem("cartDiscount", cartDiscount);
    renderCart();
  }

  function renderCart() {
    cartContainer.innerHTML = "";

    if (cart.length === 0) {
      cartContainer.innerHTML = `
        <div class="text-center text-gray-500 py-10">
          <p class="text-lg font-medium">🛒 Tu carrito está vacío</p>
          <p class="text-sm mt-2">Agrega productos desde el catálogo.</p>
        </div>
      `;
      subtotalEl.textContent = "$0.00";
      discountEl.textContent = "-$0.00";
      totalEl.textContent = "$0.00";
      return;
    }

    let subtotal = 0;
    let totalIndividualDiscount = 0;

    cart.forEach((item, index) => {
      const itemTotal = item.price * item.quantity - (item.discount || 0);
      subtotal += item.price * item.quantity;
      totalIndividualDiscount += item.discount || 0;

      const wrapper = document.createElement("div");
      wrapper.innerHTML = `
        <div class="flex items-center justify-between bg-white shadow-md rounded-2xl p-3 mb-3 w-full">
          <div class="flex items-center gap-3 w-full">
            <img src="${item.img.startsWith('src/uploads/') ? item.img : 'src/uploads/' + item.img}"
                 alt="${item.name}" 
                 class="w-20 h-20 rounded-xl object-cover">
            <div class="flex flex-col w-full">
              <div class="flex justify-between items-center">
                <p class="font-semibold truncate text-gray-800">${item.name}</p>
                <div class="flex gap-2">
                  <button class="discount-btn text-blue-600 hover:underline text-sm">Descuento</button>
                  <button class="remove-btn text-red-600 hover:underline text-sm">Eliminar</button>
                </div>
              </div>
              <div class="flex gap-2 mt-1">
                <select class="size-select border rounded-lg text-sm font-medium text-center p-2 w-24 focus:ring-1 focus:ring-blue-400">
                  ${(item.sizes || []).map(size => `<option value="${size}" ${size === item.size ? "selected" : ""}>${size}</option>`).join("")}
                </select>
                <select class="color-select border rounded-lg text-sm font-medium text-center p-2 w-24 focus:ring-1 focus:ring-blue-400">
                  ${(item.colors || []).map(color => `<option value="${color}" ${color === item.color ? "selected" : ""}>${color}</option>`).join("")}
                </select>
              </div>
              <div class="flex w-full mt-2">
                <div class="flex items-center gap-2 justify-start w-1/2">
                  <button class="decrease-btn bg-gray-200 px-2 py-1 rounded-lg hover:bg-gray-300">−</button>
                  <span class="font-medium">${item.quantity}</span>
                  <button class="increase-btn bg-gray-200 px-2 py-1 rounded-lg hover:bg-gray-300">+</button>
                </div>
                <div class="flex justify-end items-center w-1/2">
                  <p class="font-semibold text-lg text-gray-700">$${itemTotal.toFixed(2)}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
      const card = wrapper.firstElementChild;

      // --- BOTONES ---
      card.querySelector(".increase-btn").addEventListener("click", () => { item.quantity++; saveCart(); });
      card.querySelector(".decrease-btn").addEventListener("click", () => { 
        if(item.quantity > 1) item.quantity--; else cart.splice(index,1);
        saveCart();
      });
      card.querySelector(".remove-btn").addEventListener("click", () => { cart.splice(index,1); saveCart(); });

      // --- DESCUENTO INDIVIDUAL ---
      card.querySelector(".discount-btn").addEventListener("click", () => {
        const value = parseFloat(prompt("Ingrese descuento para este producto:", item.discount || 0)) || 0;
        item.discount = Math.max(0, value);
        saveCart();
      });

      // --- CAMBIO DE VARIANTE ---
      card.querySelector(".size-select").addEventListener("change", (e) => { item.size = e.target.value; saveCart(); });
      card.querySelector(".color-select").addEventListener("change", (e) => { item.color = e.target.value; saveCart(); });

      cartContainer.appendChild(card);
    });

    // =======================
    // TOTAL CON DESCUENTO GENERAL
    // =======================
    const subtotalAfterIndividual = subtotal - totalIndividualDiscount;
    const generalDiscountAmount = subtotalAfterIndividual * (cartDiscount / 100);
    const total = subtotalAfterIndividual - generalDiscountAmount;

    subtotalEl.textContent = `$${subtotalAfterIndividual.toFixed(2)}`;
    discountEl.textContent = `-$${generalDiscountAmount.toFixed(2)}`;
    totalEl.textContent = `$${total.toFixed(2)}`;
  }

  // =======================
  // LIMPIAR CARRITO
  // =======================
  clearCartBtn?.addEventListener("click", () => {
    cart = [];
    cartDiscount = 0;
    saveCart();
  });

  // =======================
  // AGREGAR PRODUCTO
  // =======================
  function addToCart(product) {
    const existing = cart.find(p => p.id === product.id && p.size === product.size && p.color === product.color);
    if(existing) existing.quantity += product.quantity;
    else cart.push(product);
    saveCart();
  }

  // =======================
  // BOTONES AGREGAR DESDE GRID
  // =======================
  document.querySelectorAll(".add-to-cart").forEach(btn => {
    btn.addEventListener("click", () => {
      const card = btn.closest(".producto");
      if(!card) return;
      const id = card.dataset.id;
      const name = card.dataset.name;
      const price = parseFloat(card.dataset.price) || 0;
      const img = card.dataset.img || "sin-imagen.png";
      const variants = JSON.parse(card.dataset.variants || "[]");
      const sizeSelect = card.querySelector(".variant-size");
      const colorSelect = card.querySelector(".variant-color");
      const size = sizeSelect ? sizeSelect.value : "Única";
      const color = colorSelect ? colorSelect.value : "Sin color";
      const selectedVariant = variants.find(v => (!v.size || v.size===size) && (!v.color || v.color===color));

      addToCart({
        id: selectedVariant?.id || id,
        name,
        price: selectedVariant?.price || price,
        img: selectedVariant?.image ? "src/uploads/" + selectedVariant.image : "src/uploads/" + img,
        size,
        color,
        sizes: [...new Set(variants.map(v=>v.size).filter(Boolean))],
        colors: [...new Set(variants.map(v=>v.color).filter(Boolean))],
        quantity: 1,
        discount: 0
      });
    });
  });

  // =======================
  // DESCUENTO GENERAL
  // =======================
  document.getElementById("discount-btn")?.addEventListener("click", () => {
    discountInput.value = cartDiscount || 0;
    discountModal.classList.remove("hidden");
  });

  discountApplyBtn?.addEventListener("click", () => {
    cartDiscount = parseFloat(discountInput.value) || 0;
    discountModal.classList.add("hidden");
    saveCart();
  });

  discountCloseBtn?.addEventListener("click", () => {
    discountModal.classList.add("hidden");
  });

  discountModal?.addEventListener("click", e => {
    if(e.target.id==="discount-modal") discountModal.classList.add("hidden");
  });

  // =======================
  // PAGAR
  // =======================
  payBtn?.addEventListener("click", () => {
    document.dispatchEvent(new CustomEvent("openPaymentModal"));
  });

  // =======================
  // INICIALIZAR
  // =======================
  renderCart();
});
