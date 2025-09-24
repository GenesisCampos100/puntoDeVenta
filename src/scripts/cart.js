// index.js

//Menú lateral 
const menuBtn = document.getElementById("menu-btn");
const sidebar = document.getElementById("sidebar");
const closeBtn = document.getElementById("close-btn");

menuBtn.addEventListener("click", () => {
  sidebar.classList.remove("-translate-x-64");
});

closeBtn.addEventListener("click", () => {
  sidebar.classList.add("-translate-x-64");
});

//Carrito 
let cart = [];

const cartItemsContainer = document.getElementById("cart-items");
const subtotalEl = document.getElementById("subtotal");
const discountEl = document.getElementById("discount");
const totalEl = document.getElementById("total");
const clearCartBtn = document.getElementById("clear-cart");

//Mostrar mensaje vacío
function showEmptyMessage() {
  cartItemsContainer.innerHTML = `
    <div class="flex flex-col items-center justify-center text-gray-500 py-10">
      <span class="text-5xl mb-2">📦</span>
      <p class="text-lg font-semibold">No hay productos</p>
    </div>
  `;
}

//Renderizar carrito
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
      <div class="flex items-start justify-between gap-2 relative">
        <div class="flex items-center gap-2">
          <img src="${item.img}" alt="${item.name}" class="w-12 h-12 object-cover rounded">
          <div>
            <p class="font-semibold">${item.name}</p>
            <p class="text-sm text-gray-500">$${item.price.toFixed(2)}</p>
          </div>
        </div>
        <button class="discount-product absolute top-0 right-0 bg-red-500 hover:bg-red-400 text-white p-2 rounded-full">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
          </svg>
        </button>
      </div>

      <div class="flex items-center justify-between gap-2 mt-2">
        <div class="flex items-center gap-2">
          <button class="decrease bg-red-500 text-white px-2 rounded">-</button>
          <span class="font-semibold">${item.quantity}</span>
          <button class="increase bg-lime-500 text-white px-2 rounded">+</button>
        </div>
        <div class="text-right">
          <p class="font-semibold">$${itemTotal.toFixed(2)}</p>
          <button class="remove text-red-500 text-sm">Eliminar</button>
        </div>
      </div>

      <!-- Campos de talla y color -->
      <div class="flex gap-2 mt-2">
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
    `;

    //eventos
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

    // actualizar talla y color
    itemDiv.querySelector(".talla").addEventListener("change", (e) => {
      item.size = e.target.value;
    });

    itemDiv.querySelector(".color").addEventListener("change", (e) => {
      item.color = e.target.value;
    });

    //descuento individual -> abre modal
    itemDiv.querySelector(".discount-product").addEventListener("click", () => {
      openProductDiscountModal(item);
    });

    cartItemsContainer.appendChild(itemDiv);
  });

  subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  discountEl.textContent = "-$0.00";
  totalEl.textContent = `$${subtotal.toFixed(2)}`;
}

//vaciar carrito
clearCartBtn.addEventListener("click", () => {
  cart = [];
  renderCart();
});

//AGREGAR PRODUCTOS 
const addToCartBtns = document.querySelectorAll(".add-to-cart");

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
      cart.push({ name, price, img, quantity: 1, size: "M", color: "Negro" });
    }

    renderCart();
  });
});

// MODAL DE DESCUENTO GLOBAL 
const discountBtn = document.getElementById("discount-btn");
const discountModal = document.getElementById("discount-modal");
const closeDiscount = document.getElementById("close-discount");
const cancelDiscount = document.getElementById("cancel-discount");
const applyDiscountBtn = document.getElementById("apply-discount");
const discountInput = document.getElementById("discount-input");
const beforeTotalEl = document.getElementById("before-total");
const afterTotalEl = document.getElementById("after-total");
const tabAmount = document.getElementById("tab-amount");
const tabPercent = document.getElementById("tab-percent");
const inputPrefix = document.getElementById("input-prefix");

let discountMode = "amount"; 

// Abrir modal
discountBtn.addEventListener("click", () => {
  discountModal.classList.remove("hidden");
  discountInput.value = "";
  beforeTotalEl.textContent = "$0.00";
  afterTotalEl.textContent = "$0.00";
});

// Cerrar modal
function closeModal() {
  discountModal.classList.add("hidden");
}
closeDiscount.addEventListener("click", closeModal);
cancelDiscount.addEventListener("click", closeModal);

// Tabs
tabAmount.addEventListener("click", () => {
  discountMode = "amount";
  tabAmount.classList.add("bg-lime-500", "text-white");
  tabPercent.classList.remove("bg-lime-500", "text-white");
  tabPercent.classList.add("bg-gray-100");
  inputPrefix.textContent = "$";
});

tabPercent.addEventListener("click", () => {
  discountMode = "percent";
  tabPercent.classList.add("bg-lime-500", "text-white");
  tabAmount.classList.remove("bg-lime-500", "text-white");
  tabAmount.classList.add("bg-gray-100");
  inputPrefix.textContent = "%";
});

// Vista previa
discountInput.addEventListener("input", () => {
  const subtotal = cart.reduce((acc, item) => acc + item.price * item.quantity, 0);
  let newTotal = subtotal;

  const value = parseFloat(discountInput.value);
  if (!isNaN(value) && value > 0) {
    if (discountMode === "percent") {
      newTotal = subtotal - subtotal * (value / 100);
    } else {
      newTotal = Math.max(0, subtotal - value);
    }
  }

  beforeTotalEl.textContent = `$${subtotal.toFixed(2)}`;
  afterTotalEl.textContent = `$${newTotal.toFixed(2)}`;
});

// Aplicar descuento
applyDiscountBtn.addEventListener("click", () => {
  const subtotal = cart.reduce((acc, item) => acc + item.price * item.quantity, 0);
  const value = parseFloat(discountInput.value);

  if (!isNaN(value) && value > 0) {
    if (discountMode === "percent") {
      cart.forEach(item => {
        item.price = item.price - (item.price * (value / 100));
      });
    } else {
      const factor = (subtotal - value) / subtotal;
      cart.forEach(item => {
        item.price = item.price * factor;
      });
    }
  }

  closeModal();
  renderCart();
});

//MODAL DE DESCUENTO POR PRODUCTO 
const productDiscountModal = document.getElementById("product-discount-modal");
const closeProductDiscount = document.getElementById("close-product-discount");
const cancelProductDiscount = document.getElementById("cancel-product-discount");
const applyProductDiscountBtn = document.getElementById("apply-product-discount");
const productDiscountInput = document.getElementById("product-discount-input");
const productBeforeTotalEl = document.getElementById("product-before-total");
const productAfterTotalEl = document.getElementById("product-after-total");
const productTabAmount = document.getElementById("product-tab-amount");
const productTabPercent = document.getElementById("product-tab-percent");
const productInputPrefix = document.getElementById("product-input-prefix");
const productInfo = document.getElementById("product-info");

let productDiscountMode = "amount"; 
let currentProduct = null;

// Abrir modal de producto
function openProductDiscountModal(item) {
  currentProduct = item;
  productDiscountModal.classList.remove("hidden");
  productDiscountInput.value = "";

  productInfo.innerHTML = `
    <img src="${item.img}" alt="${item.name}" class="w-12 h-12 object-cover rounded">
    <div>
      <p class="font-semibold">${item.name}</p>
      <p class="text-sm text-gray-500">$${item.price.toFixed(2)}</p>
    </div>
  `;

  productBeforeTotalEl.textContent = `$${(item.price * item.quantity).toFixed(2)}`;
  productAfterTotalEl.textContent = `$${(item.price * item.quantity).toFixed(2)}`;
}

// Cerrar modal de producto
function closeProductModal() {
  productDiscountModal.classList.add("hidden");
}
closeProductDiscount.addEventListener("click", closeProductModal);
cancelProductDiscount.addEventListener("click", closeProductModal);

// Tabs producto
productTabAmount.addEventListener("click", () => {
  productDiscountMode = "amount";
  productTabAmount.classList.add("bg-lime-500", "text-white");
  productTabPercent.classList.remove("bg-lime-500", "text-white");
  productTabPercent.classList.add("bg-gray-100");
  productInputPrefix.textContent = "$";
});

productTabPercent.addEventListener("click", () => {
  productDiscountMode = "percent";
  productTabPercent.classList.add("bg-lime-500", "text-white");
  productTabAmount.classList.remove("bg-lime-500", "text-white");
  productTabAmount.classList.add("bg-gray-100");
  productInputPrefix.textContent = "%";
});

// Vista previa producto
productDiscountInput.addEventListener("input", () => {
  if (!currentProduct) return;
  let newTotal = currentProduct.price * currentProduct.quantity;

  const value = parseFloat(productDiscountInput.value);
  if (!isNaN(value) && value > 0) {
    if (productDiscountMode === "percent") {
      newTotal = (currentProduct.price * currentProduct.quantity) - (currentProduct.price * currentProduct.quantity * (value / 100));
    } else {
      newTotal = Math.max(0, (currentProduct.price * currentProduct.quantity) - value);
    }
  }

  productBeforeTotalEl.textContent = `$${(currentProduct.price * currentProduct.quantity).toFixed(2)}`;
  productAfterTotalEl.textContent = `$${newTotal.toFixed(2)}`;
});

// Aplicar descuento producto
applyProductDiscountBtn.addEventListener("click", () => {
  if (!currentProduct) return;
  const value = parseFloat(productDiscountInput.value);

  if (!isNaN(value) && value > 0) {
    if (productDiscountMode === "percent") {
      currentProduct.price = currentProduct.price - (currentProduct.price * (value / 100));
    } else {
      const factor = ((currentProduct.price * currentProduct.quantity) - value) / (currentProduct.price * currentProduct.quantity);
      currentProduct.price = currentProduct.price * factor;
    }
  }

  closeProductModal();
  renderCart();
});

// inicial
renderCart();
