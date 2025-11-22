// cart.js - manejo del carrito y UI lateral
document.addEventListener('DOMContentLoaded', () => {

const STORAGE_KEY = 'cart';
const CLIENT_KEY = 'selectedClient';

// parsear carrito seguro
let cart = [];
try { cart = JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch(e){ cart = []; localStorage.removeItem(STORAGE_KEY); }

let globalDiscount = parseFloat(localStorage.getItem("globalDiscount")) || 0;

// elementos UI
const cartContainer = document.getElementById("cart-items");
const subtotalEl = document.getElementById("subtotal");
const discountEl = document.getElementById("discount");
const totalEl = document.getElementById("total");
const clearCartBtn = document.getElementById("clear-cart");
const payBtn = document.getElementById("pay-btn");

let selectedClient = null;
try { selectedClient = JSON.parse(localStorage.getItem(CLIENT_KEY)) || null; } catch(e){ selectedClient = null; }

// --------------------
// utilidades
// --------------------
function saveCart() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
    updateCart();
}

function getItemDiscountAmount(item) {
    if (!item.discount) return 0;
    if (typeof item.discount === "object") {
        return item.discount.type === "percent"
            ? item.price * item.quantity * (item.discount.value / 100)
            : Number(item.discount.value) || 0;
    }
    return Number(item.discount) || 0;
}

function recalcTotals() {
    let subtotal = 0, individualDiscounts = 0;
    cart.forEach(item => {
        subtotal += (parseFloat(item.price)||0) * (parseInt(item.quantity)||0);
        individualDiscounts += getItemDiscountAmount(item);
    });
    const subtotalAfterIndividual = subtotal - individualDiscounts;
    const globalType = localStorage.getItem("globalDiscountType") || "percent";
    let globalDiscountAmount = (globalType === "percent") ? subtotalAfterIndividual * (globalDiscount/100) : globalDiscount;
    const totalDiscount = individualDiscounts + globalDiscountAmount;
    const total = subtotal - totalDiscount;

    if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    if (discountEl) discountEl.textContent = `-$${totalDiscount.toFixed(2)}`;
    if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
}

// --------------------
// render carrito
// --------------------
function updateCart() {
    if (!cartContainer) return;
    cartContainer.innerHTML = '';

    const clienteInfo = document.getElementById('cliente_info');
    const clienteNombreEl = document.getElementById('cliente_nombre');
    const clienteIdInput = document.getElementById('cliente_id');
    const hiddenIdClienteForm = document.getElementById('id_cliente');

    if (selectedClient) {
        clienteInfo?.classList.remove('hidden');
        if (clienteNombreEl) clienteNombreEl.textContent = selectedClient.nombre;
        if (clienteIdInput) clienteIdInput.value = selectedClient.id;
        if (hiddenIdClienteForm) hiddenIdClienteForm.value = selectedClient.id;
    } else {
        clienteInfo?.classList.add('hidden');
        if (clienteNombreEl) clienteNombreEl.textContent = 'No hay cliente seleccionado';
        if (clienteIdInput) clienteIdInput.value = '';
        if (hiddenIdClienteForm) hiddenIdClienteForm.value = '';
    }

    if (!cart.length) {
        cartContainer.innerHTML = `<div class="text-center text-gray-500 py-10"><p class="text-lg font-medium">🛒 Tu carrito está vacío</p><p class="text-sm mt-2">Agrega productos desde el catálogo.</p></div>`;
        recalcTotals();
        return;
    }

    cart.forEach((item, index) => {
        const itemDiscount = getItemDiscountAmount(item);
        const itemTotal = (parseFloat(item.price)||0) * (parseInt(item.quantity)||0) - itemDiscount;

        const wrapper = document.createElement('div');
        wrapper.className = 'relative flex items-center justify-between bg-white shadow-md rounded-2xl p-3 mb-3 w-full';
        wrapper.innerHTML = `
            ${itemDiscount > 0 ? `<span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">-$${itemDiscount.toFixed(2)}</span>` : ''}
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
                    <p class="text-sm text-gray-500 mt-1">Talla: ${item.size}, Color: ${item.color}</p>
                    <div class="flex w-full mt-2 items-center">
                        <div class="flex items-center gap-2 justify-start w-1/2">
                            <button class="decrease-btn bg-gray-200 px-2 py-1 rounded-lg">−</button>
                            <input type="number" class="quantity-input font-medium w-16 text-center border rounded px-2 py-1" min="1" value="${item.quantity}">
                            <button class="increase-btn bg-gray-200 px-2 py-1 rounded-lg">+</button>
                        </div>
                        <div class="flex justify-end items-center w-1/2">
                            <p class="font-semibold text-lg text-gray-700">$${itemTotal.toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // events
        wrapper.querySelector('.increase-btn').addEventListener('click', () => { item.quantity++; saveCart(); });
        wrapper.querySelector('.decrease-btn').addEventListener('click', () => { if(item.quantity>1)item.quantity--; saveCart(); });
        const qtyInput = wrapper.querySelector('.quantity-input');
        qtyInput.addEventListener('change', () => { item.quantity = Math.max(1, parseInt(qtyInput.value)||1); saveCart(); });
        wrapper.querySelector('.remove-btn').addEventListener('click', () => { cart.splice(index,1); saveCart(); });
        wrapper.querySelector('.discount-btn').addEventListener('click', () => { window.openProductDiscountModal(index, item.discount || 0); });

        cartContainer.appendChild(wrapper);
    });

    recalcTotals();
}

// --------------------
// agregar desde grid
// --------------------
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', () => {
        const article = btn.closest('.producto');
        const code = article.dataset.code;
        const name = article.dataset.name;
        const price = parseFloat(article.dataset.price);
        const img = article.dataset.img;
        const category = article.dataset.category;
        const sizeSelect = article.querySelector('.variant-size');
        const colorSelect = article.querySelector('.variant-color');
        const size = sizeSelect ? sizeSelect.value : 'Única';
        const color = colorSelect ? colorSelect.value : 'Sin color';
        const variants = article.dataset.variants ? JSON.parse(article.dataset.variants) : [];

        const product = { cod_barras: code, code, name, price, img, category, size, color, variants, quantity:1, discount:0 };

        const existingIndex = cart.findIndex(i=>i.cod_barras===product.cod_barras && i.size===size && i.color===color);
        if(existingIndex>=0) cart[existingIndex].quantity +=1;
        else cart.push(product);
        saveCart();
    });
});

// cliente: cambiar/eliminar
document.addEventListener('click', (e) => {
  if (e.target && e.target.id === 'eliminarCliente') {
    localStorage.removeItem(CLIENT_KEY);
    selectedClient = null;
    updateCart();
  }
  if (e.target && e.target.id === 'cambiarCliente') {
    const modal = document.getElementById('modalClientes');
    if (modal) { modal.classList.remove('hidden'); }
  }
});

// exponer guardarCliente globalmente
window.guardarCliente = function(id, nombre, telefono = '') {
  selectedClient = { id: String(id), nombre: String(nombre), telefono: telefono };
  localStorage.setItem(CLIENT_KEY, JSON.stringify(selectedClient));
  updateCart();
};

// recibir descuentos desde modal.js
document.addEventListener('applyProductDiscount', e => {
  const { index, value, type } = e.detail;
  if (cart[index]) {
    cart[index].discount = { value, type };
    saveCart();
  }
});
document.addEventListener('applyGlobalDiscount', e => {
  globalDiscount = e.detail.value || 0;
  localStorage.setItem('globalDiscount', globalDiscount);
  localStorage.setItem('globalDiscountType', e.detail.type || 'percent');
  recalcTotals();
});


// --------------------
// limpiar carrito
// --------------------
clearCartBtn?.addEventListener('click', () => { cart=[]; localStorage.removeItem(STORAGE_KEY); localStorage.removeItem('globalDiscount'); saveCart(); });

// --------------------
// abrir modal de pago
// --------------------
payBtn?.addEventListener("click", () => {
    const paymentModal = document.getElementById('payment-modal');
    // setear inputs del modal
    document.getElementById("cart-data-input").value = JSON.stringify(cart);
    document.getElementById("cliente-input").value = selectedClient?.id||"";
    document.getElementById("descuento-general-input").value = globalDiscount;
    document.getElementById("descuento-general-type").value = localStorage.getItem("globalDiscountType") || "percent";
    // calcular subtotal y total
    let subtotal = 0, individualDiscounts=0;
    cart.forEach(item=>{subtotal+=item.price*item.quantity; individualDiscounts+=getItemDiscountAmount(item);});
    const subtotalAfterIndividual = subtotal - individualDiscounts;
    const globalType = localStorage.getItem("globalDiscountType")||"percent";
    let globalDiscountAmount = globalType==="percent"? subtotalAfterIndividual*(globalDiscount/100):globalDiscount;
    const total = subtotal - (individualDiscounts+globalDiscountAmount);
    document.getElementById("subtotal-input").value = subtotal.toFixed(2);
    document.getElementById("total-input").value = total.toFixed(2);

    paymentModal?.classList.remove('hidden');
});

// --------------------
// recibir venta desde modal.js
// --------------------
document.addEventListener('processPayment', async (e)=>{
    const { metodo, referencia, monto } = e.detail;
    const formData = new FormData();
    formData.append('cart_data', JSON.stringify(cart));
    formData.append('tipo_pago', metodo);
    formData.append('id_cliente', selectedClient?.id||'');
    formData.append('descuento_general', document.getElementById("descuento-general-input").value);
    formData.append('tipo_descuento_general', document.getElementById("descuento-general-type").value);
    formData.append('subtotal', document.getElementById("subtotal-input").value);
    formData.append('total', document.getElementById("total-input").value);
    formData.append('monto', monto||document.getElementById("total-input").value);
    formData.append('referencia', referencia||"");

    try {
        const r = await fetch('scripts/procesar_venta.php', { method:'POST', body: formData });
        const data = await r.json();
        if(data.status==='success'){
            alert(`✅ Venta registrada. Total: $${data.total}`);
            cart=[]; localStorage.removeItem('cart'); localStorage.removeItem('globalDiscount'); updateCart();
            document.getElementById('payment-modal')?.classList.add('hidden');
        } else {
            alert(`❌ ${data.message||'Error al registrar la venta'}`);
            console.error('Error procesar_venta:', data);
        }
    } catch(err){
        console.error('Error fetch procesar_venta:', err);
        alert("❌ Error al procesar la venta. Revisa la consola para más detalles.");
    }
});

updateCart();
});