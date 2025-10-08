// ======================
// VARIABLES GLOBALES
// ======================
let cart = JSON.parse(localStorage.getItem("cart")) || [];
const cartContainer = document.getElementById("cart-items");
const subtotalEl = document.getElementById("subtotal");
const discountEl = document.getElementById("discount");
const totalEl = document.getElementById("total");

// ======================
// ACTUALIZAR CARRITO
// ======================
function updateCart() {
  cartContainer.innerHTML = "";
  if (!cart.length) {
    cartContainer.innerHTML = `<div class="text-center text-gray-500 py-10"><p class="text-lg font-medium">🛒 Tu carrito está vacío</p><p class="text-sm mt-2">Agrega productos desde el catálogo.</p></div>`;
    subtotalEl.textContent = "$0.00";
    discountEl.textContent = "-$0.00";
    totalEl.textContent = "$0.00";
    return;
  }

  let subtotal = 0, totalDiscount = 0;

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity - (item.discount || 0);
    subtotal += item.price * item.quantity;
    totalDiscount += item.discount || 0;

    const wrapper = document.createElement("div");
    wrapper.innerHTML = `
      <div class="flex items-center justify-between bg-white shadow-md rounded-2xl p-3 mb-3 w-full">
        <div class="flex items-center gap-3 w-full">
          <img src="${item.img}" alt="${item.name}" class="w-20 h-20 rounded-xl object-cover">
          <div class="flex flex-col w-full">
            <div class="flex justify-between items-center">
              <p class="font-semibold truncate text-gray-800">${item.name}</p>
              <div class="flex gap-2">
                <button class="discount-btn text-blue-600 hover:underline text-sm">Descuento</button>
                <button class="remove-btn text-red-600 hover:underline text-sm">Eliminar</button>
              </div>
            </div>

            <!-- Selects solo si hay más de una opción o variantes -->
            ${(item.sizes && item.sizes.length > 1 ? `
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

    // --- BOTONES DE CANTIDAD ---
    card.querySelector(".increase-btn").addEventListener("click", () => { item.quantity++; saveCart(); });
    card.querySelector(".decrease-btn").addEventListener("click", () => { if(item.quantity>1)item.quantity--; saveCart(); });

    // --- ELIMINAR ---
    card.querySelector(".remove-btn").addEventListener("click", () => { cart.splice(index,1); saveCart(); });

    // --- DESCUENTO ---
    card.querySelector(".discount-btn").addEventListener("click", () => {
      const value = parseFloat(prompt("Ingrese descuento:", item.discount||0))||0;
      item.discount = Math.max(0,value); saveCart();
    });

    // --- VARIANTES ---
    const sizeSelect = card.querySelector(".size-select");
    const colorSelect = card.querySelector(".color-select");
    if(sizeSelect && colorSelect && item.variants && item.variants.length){
      const updateColors = ()=>{
        const validColors = item.variants.filter(v=>v.size===sizeSelect.value).map(v=>v.color);
        colorSelect.querySelectorAll("option").forEach(opt=>opt.disabled=!validColors.includes(opt.value));
        if(!validColors.includes(item.color)) item.color = validColors[0]||"";
        colorSelect.value = item.color;
        updateVariant();
      };
      const updateVariant = ()=>{
        const v = item.variants.find(vv=>vv.size===sizeSelect.value && vv.color===colorSelect.value);
        if(v){ item.price=parseFloat(v.price); item.img=v.image||item.img; }
        card.querySelector("p.font-semibold.text-lg").textContent=`$${(item.price*item.quantity-(item.discount||0)).toFixed(2)}`;
        card.querySelector("img").src = item.img;
        recalcTotals();
      };
      sizeSelect.addEventListener("change",()=>{item.size=sizeSelect.value; updateColors();});
      colorSelect.addEventListener("change",()=>{item.color=colorSelect.value; updateVariant();});
      updateColors();
    }

    cartContainer.appendChild(card);
  });

  recalcTotals();
}

// ======================
// GUARDAR Y RECALCULAR
// ======================
function saveCart(){ localStorage.setItem("cart",JSON.stringify(cart)); updateCart(); }
function recalcTotals(){
  let subtotal=0,totalDiscount=0;
  cart.forEach(item=>{ subtotal+=item.price*item.quantity; totalDiscount+=item.discount||0; });
  subtotalEl.textContent=`$${subtotal.toFixed(2)}`;
  discountEl.textContent=`-$${totalDiscount.toFixed(2)}`;
  totalEl.textContent=`$${(subtotal-totalDiscount).toFixed(2)}`;
}

// ======================
// AGREGAR AL CARRITO DESDE GRID
// ======================
document.querySelectorAll(".add-to-cart").forEach(btn=>{
  btn.addEventListener("click",()=>{
    const card=btn.closest(".producto");
    const id=card.dataset.id;
    const name=card.dataset.name;
    const price=parseFloat(card.dataset.price);
    const img=card.dataset.img||"src/uploads/sin-imagen.png";
    const variants=card.dataset.variants?JSON.parse(card.dataset.variants):[];
    const sizeSelect=card.querySelector(".variant-size");
    const colorSelect=card.querySelector(".variant-color");

    const size = sizeSelect ? sizeSelect.value : (card.dataset.size || "Única");
    const color = colorSelect ? colorSelect.value : (card.dataset.color || "Sin color");

    // Crear arrays para selects (aunque tenga solo un valor)
    const sizes = sizeSelect ? Array.from(sizeSelect.options).map(o=>o.value) : [size];
    const colors = colorSelect ? Array.from(colorSelect.options).map(o=>o.value) : [color];

    addToCart({id,name,price,img,size,color,sizes,colors,variants,quantity:1,discount:0});
  });
});

function addToCart(product){
  const existing=cart.find(p=>p.id===product.id && p.size===product.size && p.color===product.color);
  if(existing){ existing.quantity+=product.quantity; existing.price=product.price; existing.img=product.img; }
  else cart.push(product);
  saveCart();
}

updateCart();