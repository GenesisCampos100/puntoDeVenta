document.addEventListener("DOMContentLoaded", () => {
  const cartItemsContainer = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");
  const addToCartBtns = document.querySelectorAll(".add-to-cart");
  const payBtn = document.getElementById("pay-btn");

  // Modal
  const discountModal = document.getElementById("discount-modal");
  const discountInput = document.getElementById("discount-input");
  const discountApplyBtn = document.getElementById("discount-apply-btn");
  let currentDiscountItem = null;

  // =======================
  // FUNCIONES DE CARRITO
  // =======================
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
    let totalDiscount = 0;

    cart.forEach((item, index) => {
      // Calcular precio con descuento
      item.discount = item.discount || 0;
      const itemPriceAfterDiscount = item.price * (1 - item.discount / 100);
      const itemTotal = itemPriceAfterDiscount * item.quantity;

      subtotal += itemTotal;
      totalDiscount += (item.price * item.quantity - itemTotal);

      // Crear elemento del carrito
      const itemDiv = document.createElement("div");
      itemDiv.className = "flex gap-4 mb-3 bg-white rounded-xl shadow-sm p-2 w-full items-stretch";

      itemDiv.innerHTML = `
        <!-- Imagen -->
        <img src="${item.img}" alt="${item.name}" class="w-20 h-full rounded-xl object-cover">

        <!-- Panel derecho -->
        <div class="flex-1 grid grid-rows-3 gap-2">

          <!-- Fila 1: Nombre + botones -->
          <div class="flex justify-between items-center w-full">
            <p class="font-semibold truncate">${item.name}</p>
            <div class="flex gap-2">
              <button class="discount-btn bg-yellow-400 text-white text-sm px-2 py-1 rounded hover:bg-yellow-500 transition">%</button>
              <button class="remove bg-rose-500 text-white text-sm px-2 py-1 rounded hover:bg-rose-600 transition">✕</button>
            </div>
          </div>

          <!-- Fila 2: Selects -->
          <div class="flex gap-2 w-full">
            <select class="size-select border rounded-lg text-sm font-medium text-center p-2 flex-1 focus:ring-1 focus:ring-gray-300"></select>
            <select class="color-select border rounded-lg text-sm font-medium text-center p-2 flex-1 focus:ring-1 focus:ring-gray-300"></select>
          </div>

          <!-- Fila 3: Contador + Precio -->
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

      // =======================
      // Rellenar selects con variantes
      // =======================
      const sizeSelect = itemDiv.querySelector(".size-select");
      const colorSelect = itemDiv.querySelector(".color-select");

      if (item.variants) {
        const sizes = [...new Set(item.variants.map(v => v.size))];
        const colors = [...new Set(item.variants.map(v => v.color))];

        sizeSelect.innerHTML = sizes.map(s => `<option value="${s}" ${s===item.size?"selected":""}>${s}</option>`).join('');
        colorSelect.innerHTML = colors.map(c => `<option value="${c}" ${c===item.color?"selected":""}>${c}</option>`).join('');
      } else {
        sizeSelect.innerHTML = `<option>${item.size}</option>`;
        colorSelect.innerHTML = `<option>${item.color}</option>`;
      }

      // =======================
      // EVENTOS
      // =======================
      itemDiv.querySelector(".increase").addEventListener("click", () => {
        item.quantity++;
        setCart(cart);
        renderCart();
      });

      itemDiv.querySelector(".decrease").addEventListener("click", () => {
        if(item.quantity > 1) item.quantity--;
        else cart.splice(index, 1);
        setCart(cart);
        renderCart();
      });

      itemDiv.querySelector(".remove").addEventListener("click", () => {
        cart.splice(index,1);
        setCart(cart);
        renderCart();
      });

      sizeSelect.addEventListener("change", e => {
        const newSize = e.target.value;
        if(item.variants){
          const variant = item.variants.find(v => v.size === newSize && v.color === item.color);
          if(variant){
            item.price = variant.price;
            item.img = variant.image;
          }
        }
        item.size = newSize;
        setCart(cart);
        renderCart();
      });

      colorSelect.addEventListener("change", e => {
        const newColor = e.target.value;
        if(item.variants){
          const variant = item.variants.find(v => v.size === item.size && v.color === newColor);
          if(variant){
            item.price = variant.price;
            item.img = variant.image;
          }
        }
        item.color = newColor;
        setCart(cart);
        renderCart();
      });

      itemDiv.querySelector(".discount-btn").addEventListener("click", () => {
        currentDiscountItem = item;
        discountInput.value = item.discount || 0;
        discountModal.classList.remove("hidden");
      });

      cartItemsContainer.appendChild(itemDiv);
    });

    subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    discountEl.textContent = `$${totalDiscount.toFixed(2)}`;
    totalEl.textContent = `$${(subtotal).toFixed(2)}`;
  }

  // =======================
  // LIMPIAR CARRITO
  // =======================
  clearCartBtn?.addEventListener("click", () => {
    localStorage.removeItem("cart");
    renderCart();
  });

  // =======================
  // AGREGAR AL CARRITO
  // =======================
  addToCartBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      const prod = e.target.closest(".producto");

      const variants = JSON.parse(prod.dataset.variants || "[]");
      const selectedSize = prod.querySelector(".variant-size").value;
      const selectedColor = prod.querySelector(".variant-color").value;

      let selectedVariant = variants.find(v => v.size === selectedSize && v.color === selectedColor);

      const cart = getCart();

      const existing = cart.find(i => i.code === (selectedVariant?.code || prod.dataset.code));
      if(existing) existing.quantity++;
      else cart.push({
        code: selectedVariant?.code || prod.dataset.code,
        name: prod.dataset.name,
        price: selectedVariant?.price || parseFloat(prod.dataset.price),
        img: selectedVariant?.image || prod.dataset.img,
        quantity: 1,
        size: selectedSize,
        color: selectedColor,
        discount: 0,
        variants: variants
      });

      setCart(cart);
      renderCart();
    });
  });

  // Detectar cambio de variante
document.querySelectorAll(".producto").forEach(card => {
  const variants = JSON.parse(card.dataset.variants || "[]");
  const img = card.querySelector(".product-image");
  const priceTag = card.querySelector(".price");
  const sizeSelect = card.querySelector(".variant-size");
  const colorSelect = card.querySelector(".variant-color");

  function updateVariantDisplay() {
    if (!variants.length) return;
    const size = sizeSelect ? sizeSelect.value : null;
    const color = colorSelect ? colorSelect.value : null;

    const variant = variants.find(v => 
      (!size || v.size === size) && 
      (!color || v.color === color)
    );

    if (variant) {
      img.src = "../uploads/" + variant.image;
      priceTag.textContent = "$" + parseFloat(variant.price).toFixed(2);
      card.dataset.price = variant.price;
      card.dataset.img = "../uploads/" + variant.image;
      card.dataset.code = variant.code;
    }
  }

  if (sizeSelect) sizeSelect.addEventListener("change", updateVariantDisplay);
  if (colorSelect) colorSelect.addEventListener("change", updateVariantDisplay);
});


  // =======================
  // MODAL DESCUENTO
  // =======================
  discountApplyBtn?.addEventListener("click", () => {
    if(currentDiscountItem){
      currentDiscountItem.discount = parseFloat(discountInput.value) || 0;
      setCart(getCart());
      renderCart();
      discountModal.classList.add("hidden");
      currentDiscountItem = null;
    }
  });

  discountModal?.addEventListener("click", e => {
    if(e.target.id==="discount-modal" || e.target.classList.contains("close-modal")){
      discountModal.classList.add("hidden");
      currentDiscountItem = null;
    }
  });

  // =======================
  // PAGAR
  // =======================
  payBtn?.addEventListener("click", () => {
    document.dispatchEvent(new CustomEvent("openPaymentModal"));
  });

  renderCart();
});
