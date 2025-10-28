// ======================
// VARIABLES GLOBALES
// ======================
let cart = JSON.parse(localStorage.getItem("cart")) || [];
let globalDiscount = parseFloat(localStorage.getItem("globalDiscount")) || 0;

const cartContainer = document.getElementById("cart-items");
const subtotalEl = document.getElementById("subtotal");
const discountEl = document.getElementById("discount");
const totalEl = document.getElementById("total");

const clearCartBtn = document.getElementById("clear-cart");
const discountBtn = document.getElementById("discount-btn");
const payBtn = document.getElementById("pay-btn");

let currentItemIndex = null; // Para saber qué producto estamos editando

// ======================
// FUNCIONES AUXILIARES
// ======================
function getItemDiscountAmount(item) {
  if (!item.discount) return 0;
  if (typeof item.discount === "object") {
    if (item.discount.type === "percent") {
      return item.price * item.quantity * (item.discount.value / 100);
    } else {
      return Number(item.discount.value) || 0;
    }
  }
  return Number(item.discount) || 0; // compatibilidad antigua
}

function updateCart() {
  cartContainer.innerHTML = "";

  if (!cart.length) {
    cartContainer.innerHTML = `
      <div class="text-center text-gray-500 py-10">
        <p class="text-lg font-medium">🛒 Tu carrito está vacío</p>
        <p class="text-sm mt-2">Agrega productos desde el catálogo.</p>
      </div>`;
    subtotalEl.textContent = "$0.00";
    discountEl.textContent = "-$0.00";
    totalEl.textContent = "$0.00";
    return;
  }

  cart.forEach((item, index) => {
  const itemDiscount = getItemDiscountAmount(item);
  const itemTotal = item.price * item.quantity - itemDiscount;

  const wrapper = document.createElement("div");
  wrapper.innerHTML = `
    <div class="relative flex items-center justify-between bg-white shadow-md rounded-2xl p-3 mb-3 w-full">
      
      <!-- ETIQUETA DE DESCUENTO -->
      ${itemDiscount > 0 ? `<span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">-$${itemDiscount.toFixed(2)}</span>` : ''}

      <div class="flex items-center gap-3 w-full">
        <img src="../${item.img}" alt="${item.name}" class="w-20 h-20 rounded-xl object-cover">
        <div class="flex flex-col w-full">
          <div class="flex justify-between items-center">
            <p class="font-semibold truncate text-gray-800">${item.name}</p>
            <div class="flex gap-2">
              <button class="discount-btn text-blue-600 hover:underline text-sm">Descuento</button>
              <button class="remove-btn text-red-600 hover:underline text-sm">Eliminar</button>
            </div>
          </div>

          <!-- Selects -->
          ${(item.sizes && item.sizes.length > 0 ? `
          <div class="flex gap-2 mt-1">
            <select class="size-select border rounded-lg text-sm font-medium text-center p-2 w-24 focus:ring-1 focus:ring-blue-400">
              ${item.sizes.map(s => `<option value="${s}" ${s===item.size?'selected':''}>${s}</option>`).join('')}
            </select>
            <select class="color-select border rounded-lg text-sm font-medium text-center p-2 w-24 focus:ring-1 focus:ring-blue-400">
              ${item.colors.map(c => `<option value="${c}" ${c===item.color?'selected':''}>${c}</option>`).join('')}
            </select>
          </div>` : `
          <p class="text-sm text-gray-500 mt-1">Talla: ${item.size}, Color: ${item.color}</p>
          `)}

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
    </div>`;

  const card = wrapper.firstElementChild;

  // --- CANTIDAD ---
  card.querySelector(".increase-btn").addEventListener("click", () => { item.quantity++; saveCart(); });
  card.querySelector(".decrease-btn").addEventListener("click", () => { if(item.quantity>1)item.quantity--; saveCart(); });

  // --- DESCUENTO INDIVIDUAL ---
  card.querySelector(".discount-btn").addEventListener("click", () => {
    window.openProductDiscountModal(index, item.discount || 0);
  });

  // --- ELIMINAR ---
  card.querySelector(".remove-btn").addEventListener("click", () => { cart.splice(index,1); saveCart(); });

  // --- VARIANTES ---
  const sizeSelect = card.querySelector(".size-select");
  const colorSelect = card.querySelector(".color-select");

  if(sizeSelect && colorSelect && item.variants && item.variants.length){
    const colorMap = {};
    item.variants.forEach(v => {
      if (!colorMap[v.size]) colorMap[v.size] = [];
      if (!colorMap[v.size].includes(v.color)) colorMap[v.size].push(v.color);
    });

    const updateColors = () => {
      const validColors = colorMap[sizeSelect.value] || [];
      colorSelect.innerHTML = "";
      validColors.forEach(color => {
        const opt = document.createElement("option");
        opt.value = color;
        opt.textContent = color;
        colorSelect.appendChild(opt);
      });
      if (!validColors.includes(item.color)) item.color = validColors[0] || "Sin color";
      colorSelect.value = item.color;
      updateVariant();
    };

    const updateVariant = () => {
      const v = item.variants.find(vv => vv.size === sizeSelect.value && vv.color === colorSelect.value);
      if (v) {
        item.price = parseFloat(v.price);
        if (v.image) item.img = `uploads/${v.image}`;
      }
      const newDiscount = getItemDiscountAmount(item);
      card.querySelector("p.font-semibold.text-lg").textContent = `$${(item.price * item.quantity - newDiscount).toFixed(2)}`;

      // Actualizar etiqueta de descuento
      let discountLabel = card.querySelector(".absolute");
      if (newDiscount > 0) {
        if (!discountLabel) {
          discountLabel = document.createElement("span");
          discountLabel.className = "absolute top-2 left-2 bg-pink-600 text-white text-xs px-2 py-1 rounded-full";
          card.prepend(discountLabel);
        }
        discountLabel.textContent = `-$${newDiscount.toFixed(2)}`;
      } else if(discountLabel) {
        discountLabel.remove();
      }

      card.querySelector("img").src = item.img;
      recalcTotals();
    };

    sizeSelect.addEventListener("change", () => { item.size = sizeSelect.value; updateColors(); });
    colorSelect.addEventListener("change", () => { item.color = colorSelect.value; updateVariant(); });

    updateColors();
  }

  cartContainer.appendChild(card);
});


  recalcTotals();
}

// ======================
// GUARDAR Y RECALCULAR
// ======================
function saveCart() { 
  localStorage.setItem("cart", JSON.stringify(cart)); 
  updateCart(); 
}

function recalcTotals() {
  let subtotal = 0, individualDiscounts = 0;

  cart.forEach(item => {
    subtotal += item.price * item.quantity;
    individualDiscounts += getItemDiscountAmount(item);
  });

  const subtotalAfterIndividual = subtotal - individualDiscounts;
  let globalDiscountAmount = 0;
  const globalType = localStorage.getItem("globalDiscountType") || "percent";

  if (globalType === "percent") globalDiscountAmount = subtotalAfterIndividual * (globalDiscount / 100);
  else globalDiscountAmount = globalDiscount;

  const totalDiscount = individualDiscounts + globalDiscountAmount;
  const total = subtotal - totalDiscount;

  subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  discountEl.textContent = `-$${totalDiscount.toFixed(2)}`;
  totalEl.textContent = `$${total.toFixed(2)}`;
}

// ======================
// AGREGAR AL CARRITO DESDE GRID
// ======================
document.querySelectorAll(".add-to-cart").forEach(btn => {
  btn.addEventListener("click", () => {
    const card = btn.closest(".producto");
    const id = card.dataset.id;
    const name = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const img = card.dataset.img ? `uploads/${card.dataset.img}` : "uploads/sin-imagen.png";
    const variants = card.dataset.variants ? JSON.parse(card.dataset.variants) : [];

    const sizeSelect = card.querySelector(".variant-size");
    const colorSelect = card.querySelector(".variant-color");

    const size = sizeSelect ? sizeSelect.value : (card.dataset.sizeDefault || "Única");
    const color = colorSelect ? colorSelect.value : (card.dataset.colorDefault || "Sin color");

    const sizes = sizeSelect ? Array.from(sizeSelect.options).map(o => o.value) : [size];
    const colors = colorSelect ? Array.from(colorSelect.options).map(o => o.value) : [color];

    addToCart({id, name, price, img, size, color, sizes, colors, variants, quantity:1, discount:0});
  });
});

function addToCart(product){
  const existing = cart.find(p => p.id===product.id && p.size===product.size && p.color===product.color);
  if(existing){ existing.quantity += product.quantity; existing.price = product.price; existing.img = product.img; }
  else cart.push(product);
  saveCart();
}

// Vaciar carrito
clearCartBtn?.addEventListener("click", () => {
  cart = [];
  globalDiscount = 0;
  saveCart();
});

// Pagar
payBtn?.addEventListener("click", () => {
  document.dispatchEvent(new CustomEvent("openPaymentModal"));
});

// ======================
// DESCUENTOS GLOBALES E INDIVIDUALES
// ======================


document.addEventListener("applyGlobalDiscount", (e) => {
  const { value, type } = e.detail;
  globalDiscount = value;
  localStorage.setItem("globalDiscount", globalDiscount);
  localStorage.setItem("globalDiscountType", type);
  recalcTotals();
});

document.addEventListener("applyProductDiscount", (e) => {
  const { index, value, type } = e.detail;
  if (cart[index]) {
    cart[index].discount = { value, type };
    saveCart();
  }
});

updateCart();