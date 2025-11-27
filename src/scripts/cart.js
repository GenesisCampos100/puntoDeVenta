// cart.js - Manejo de carrito y UI lateral
const STORAGE_KEY = "cart";
const CLIENT_KEY = "selectedClient";

// CARGAR CARRITO DESDE LOCALSTORAGE
let cart = [];
try {
    cart = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
} catch (e) {
    cart = [];
    localStorage.removeItem(STORAGE_KEY);
}

let globalDiscount = parseFloat(localStorage.getItem("globalDiscount")) || 0;
let selectedClient = null;
try {
    selectedClient = JSON.parse(localStorage.getItem(CLIENT_KEY)) || null;
} catch (e) {
    selectedClient = null;
}

document.addEventListener("DOMContentLoaded", () => {

    // ELEMENTOS DEL DOM
    const subtotalEl = document.getElementById("subtotal");
    const discountEl = document.getElementById("discount");
    const totalEl = document.getElementById("total");
    const clearCartBtn = document.getElementById("clear-cart");
    const payBtn = document.getElementById("pay-btn");
    const cartRows = document.getElementById("cart-rows");

    // --------------------------------
    // FUNCIONES DE CLIENTE
    // --------------------------------
    function setSelectedClient(cliente) {
        const nameEl = document.getElementById('client-name');
        const phoneEl = document.getElementById('client-phone');

        if (cliente) {
            nameEl.textContent = cliente.nombre_completo;
            phoneEl.textContent = cliente.celular || '';
            selectedClient = cliente;
            localStorage.setItem(CLIENT_KEY, JSON.stringify(cliente));
        } else {
            nameEl.textContent = 'Público General';
            phoneEl.textContent = '';
            selectedClient = null;
            localStorage.removeItem(CLIENT_KEY);
        }
        updateCart();
    }

    if (selectedClient) setSelectedClient(selectedClient);
    else setSelectedClient(null);

    // Selección desde el modal
    $(document).on('click', '.seleccionarCliente', function () {
        const cliente = {
            id_cliente: $(this).data('id'),
            nombre_completo: $(this).data('nombre'),
            celular: $(this).closest('tr').find('td').eq(2).text()
        };
        setSelectedClient(cliente);
        $('#modalClientes').addClass('hidden');
    });

    $('#remove-client').on('click', () => setSelectedClient(null));

    document.addEventListener('click', (e) => {
        if (e.target?.id === 'eliminarCliente') setSelectedClient(null);
        if (e.target?.id === 'cambiarCliente') {
            const modal = document.getElementById('modalClientes');
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        }
    });

    const clientBtn = document.getElementById('client-btn');
    clientBtn?.addEventListener('click', () => {
        const modal = document.getElementById('modalClientes');
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    });

    const cerrarModalBtn = document.getElementById('cerrar-modal-cliente');
    cerrarModalBtn?.addEventListener('click', () => {
        const modal = document.getElementById('modalClientes');
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    });

    const buscarClienteInput = document.getElementById('buscarCliente');
    buscarClienteInput?.addEventListener('input', () => {
        const q = buscarClienteInput.value.toLowerCase().trim();
        document.querySelectorAll('#tablaClientes tbody tr, #tablaClientes tr')
            .forEach(row => row.style.display = (row.textContent || '').toLowerCase().includes(q) ? '' : 'none');
    });

    window.guardarCliente = function (id, nombre, telefono = '') {
        setSelectedClient({ id_cliente: String(id), nombre_completo: String(nombre), celular: telefono });
    };

    // --------------------------------
    // FUNCIONES DE CARRITO
    // --------------------------------
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
        let subtotal = 0;
        let individualDiscounts = 0;

        cart.forEach(item => {
            subtotal += item.price * item.quantity;
            individualDiscounts += getItemDiscountAmount(item);
        });

        const subtotalAfterIndividual = subtotal - individualDiscounts;
        const globalType = localStorage.getItem("globalDiscountType") || "percent";
        const globalDiscountAmount =
            globalType === "percent" ? subtotalAfterIndividual * (globalDiscount / 100) : globalDiscount;

        const totalDiscount = individualDiscounts + globalDiscountAmount;
        const total = subtotal - totalDiscount;

        localStorage.setItem("lastTotal", total);

        subtotalEl && (subtotalEl.textContent = `$${subtotal.toFixed(2)}`);
        discountEl && (discountEl.textContent = `-$${totalDiscount.toFixed(2)}`);
        totalEl && (totalEl.textContent = `$${total.toFixed(2)}`);
    }

    function renderCart() {
        if (!cartRows) return;
        cartRows.innerHTML = "";

        cart.forEach((item, index) => {
            const row = document.createElement("tr");
            row.className = "border-b cart-row";
            row.dataset.index = index;

            const tdCodigo = document.createElement("td"); tdCodigo.className = "py-2 px-3"; tdCodigo.textContent = item.cod_barras;
            const tdProducto = document.createElement("td"); tdProducto.className = "py-2 px-3"; tdProducto.textContent = item.name;
            const tdVariant = document.createElement("td"); tdVariant.className = "py-2 px-3 text-center"; tdVariant.textContent = `${item.talla} / ${item.color}`;
            const tdPrecio = document.createElement("td"); tdPrecio.className = "py-2 px-3 text-center"; tdPrecio.textContent = `$${item.price.toFixed(2)}`;

            const tdQty = document.createElement("td"); tdQty.className = "py-2 px-3 text-center";
            tdQty.innerHTML = `
                <div class="flex items-center justify-center gap-1">
                    <button class="px-2 py-1 bg-gray-200 rounded text-xs" onclick="decreaseQuantity(${index})">-</button>
                    <input type="number" class="w-12 text-center border rounded quantity-input" min="1" max="${item.stock}" value="${item.quantity}" data-index="${index}" />
                    <button class="px-2 py-1 bg-gray-200 rounded text-xs" onclick="increaseQuantity(${index})">+</button>
                </div>`;

            const tdTotal = document.createElement("td"); tdTotal.className = "py-2 px-3 text-center font-bold"; tdTotal.textContent = `$${(item.price * item.quantity - getItemDiscountAmount(item)).toFixed(2)}`;

            const tdDesc = document.createElement("td"); tdDesc.className = "py-2 px-3 text-center";
            tdDesc.innerHTML = `<button class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs" onclick="editItemDiscount(${index})">%</button>`;

            const tdDel = document.createElement("td"); tdDel.className = "py-2 px-3 text-center";
            tdDel.innerHTML = `<button class="text-red-600 text-lg" onclick="removeItem(${index})">×</button>`;

            row.append(tdCodigo, tdProducto, tdVariant, tdPrecio, tdQty, tdTotal, tdDesc, tdDel);
            cartRows.appendChild(row);
        });

        recalcTotals();
    }

    function updateCart() { renderCart(); }

    window.addToCart = function (prod) {
        let existente = cart.find(item =>
            item.cod_barras === prod.cod_barras &&
            item.talla === (prod.talla ?? '') &&
            item.color === (prod.color ?? '')
        );

        if (existente) {
            if (existente.quantity >= existente.stock) {
                Swal.fire({ icon: 'warning', title: 'Stock insuficiente', text: `Solo hay ${existente.stock} unidades disponibles.` });
                return;
            }
            existente.quantity++;
        } else {
            if ((prod.quantity ?? 1) > prod.stock) {
                Swal.fire({ icon: 'warning', title: 'Stock insuficiente', text: `Solo hay ${prod.stock} unidades disponibles.` });
                return;
            }
            cart.push({ ...prod, quantity: Number(prod.quantity) || 1, discount: null, stock: Number(prod.stock) || 0 });
        }
        saveCart();
    };

    window.increaseQuantity = function (index) {
        if (cart[index].quantity + 1 > cart[index].stock) {
            Swal.fire({ icon: 'warning', title: 'Stock insuficiente', text: `Solo hay ${cart[index].stock} unidades disponibles.` });
            return;
        }
        cart[index].quantity++;
        saveCart();
    };

    window.decreaseQuantity = function (index) { if (cart[index].quantity > 1) { cart[index].quantity--; saveCart(); } };
    window.removeItem = function (index) { cart.splice(index, 1); saveCart(); };
    window.editItemDiscount = function (index) {
        const modal = document.getElementById("product-discount-modal");
        if (!modal) return;

        modal.dataset.index = index;

        const item = cart[index];
        if (!item) return;

        // Calculamos total del producto
        const itemTotal = (item.price * item.quantity) - getItemDiscountAmount(item);
        const input = document.getElementById("product-discount-input");
        input.value = item.discount?.value || 0;
        input.dataset.itemTotal = itemTotal; // Guardamos para validación

        // Reset visual
        const warning = document.getElementById("product-discount-warning");
        if (warning) warning.classList.add("hidden");
        const applyBtn = document.getElementById("product-discount-apply");
        if (applyBtn) applyBtn.disabled = false;

        modal.classList.remove("hidden");
        modal.classList.add("flex");
    };


    document.addEventListener('input', function (e) {
        if (e.target.classList.contains("quantity-input")) {
            const index = parseInt(e.target.dataset.index);
            let nuevaCantidad = parseInt(e.target.value);
            if (isNaN(nuevaCantidad) || nuevaCantidad < 1) { e.target.value = cart[index].quantity; return; }
            if (nuevaCantidad > cart[index].stock) { Swal.fire({ icon: 'warning', title: 'Stock insuficiente', text: `Solo hay ${cart[index].stock} unidades disponibles.` }); nuevaCantidad = cart[index].stock; e.target.value = cart[index].stock; }
            cart[index].quantity = nuevaCantidad;
            saveCart();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.target.classList.contains("quantity-input") && e.key === "Enter") e.target.blur();
    });

    // --------------------------------
    // DESCUENTOS DESDE MODAL
    // --------------------------------
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

    // --------------------------------
    // MODAL DE PAGO
    // --------------------------------
    payBtn?.addEventListener("click", () => {
        const paymentModal = document.getElementById('payment-modal');
        document.getElementById("cart-data-input").value = JSON.stringify(cart);
        document.getElementById("cliente-input").value = selectedClient?.id_cliente || "";
        document.getElementById("descuento-general-input").value = globalDiscount;
        document.getElementById("descuento-general-type").value = localStorage.getItem("globalDiscountType") || "percent";

        let subtotal = 0, individualDiscounts = 0;
        cart.forEach(item => { subtotal += item.price * item.quantity; individualDiscounts += getItemDiscountAmount(item); });
        const subtotalAfterIndividual = subtotal - individualDiscounts;
        const globalType = localStorage.getItem("globalDiscountType") || "percent";
        const globalDiscountAmount = globalType === "percent" ? subtotalAfterIndividual * (globalDiscount / 100) : globalDiscount;
        const total = subtotal - (individualDiscounts + globalDiscountAmount);

        document.getElementById("subtotal-input").value = subtotal.toFixed(2);
        document.getElementById("total-input").value = total.toFixed(2);
        paymentModal?.classList.remove('hidden');
    });

    document.addEventListener('processPayment', async (e) => {
        const { metodo, referencia, monto } = e.detail;
        const formData = new FormData();
        formData.append('cart_data', JSON.stringify(cart));
        formData.append('tipo_pago', metodo);
        formData.append('id_cliente', selectedClient?.id_cliente || '');
        formData.append('descuento_general', document.getElementById("descuento-general-input").value);
        formData.append('tipo_descuento_general', document.getElementById("descuento-general-type").value);
        formData.append('subtotal', document.getElementById("subtotal-input").value);
        formData.append('total', document.getElementById("total-input").value);
        formData.append('monto', monto || document.getElementById("total-input").value);
        formData.append('referencia', referencia || "");

        try {
            const r = await fetch('scripts/procesar_venta.php', { method: 'POST', body: formData });
            const data = await r.json();
            if (data.status === 'success') {
                alert(`✅ Venta registrada. Total: $${data.total}`);
                cart = []; localStorage.removeItem('cart'); localStorage.removeItem('globalDiscount'); updateCart();
                document.getElementById('payment-modal')?.classList.add('hidden');
            } else alert(`❌ ${data.message || 'Error al registrar la venta'}`);
        } catch (err) {
            console.error('Error fetch procesar_venta:', err);
            alert("❌ Error al procesar la venta. Revisa la consola para más detalles.");
        }
    });

    // CERRAR MODALES CON ESCAPE
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.fixed.inset-0').forEach(m => m.classList.add('hidden'));
    });

    // LIMPIAR CARRITO
    clearCartBtn?.addEventListener('click', () => {
        cart = [];
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem('globalDiscount');
        saveCart();
    });

    // INICIALIZAR
    updateCart();
});


// -----------------------------
// BÚSQUEDA RÁPIDA POR ENTER
// -----------------------------
$('#quick-search').on('keypress', function (e) {
    if (e.which !== 13) return; // solo Enter
    const codigo = $(this).val().trim();
    if (!codigo) return;

    $.ajax({
        url: 'pages/nueva_venta.php',
        method: 'GET',
        data: { buscar_producto: codigo },
        dataType: 'json',
        success: function (productos) {
            // Buscar coincidencia exacta por cod_barras o SKU (case-insensitive)
            const exacto = productos.find(p => {
                const cod = String(p.cod_barras || '').trim().toLowerCase();
                const sku = String(p.sku || '').trim().toLowerCase();
                return cod === codigo.toLowerCase() || sku === codigo.toLowerCase();
            });

            if (exacto) {
                addToCart({
                    cod_barras: exacto.cod_barras,
                    name: exacto.nom_producto,
                    price: parseFloat(exacto.precio),
                    talla: exacto.talla ?? '',
                    color: exacto.color ?? '',
                    quantity: 1,
                    imagen: exacto.imagen ?? null,
                    categoria: exacto.categoria ?? '',
                    discount: null,
                    stock: exacto.cantidad ?? 0
                });
                $('#quick-search').val('');
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Producto no encontrado',
                    text: 'El código o SKU ingresado no existe.'
                });
            }
        },
        error: function (err) {
            console.error('Error en búsqueda rápida:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo realizar la búsqueda. Revisa la consola.'
            });
        }
    });
});

// -----------------------------
// BÚSQUEDA DE PRODUCTOS POR TEXTO
// -----------------------------
const searchInput = document.getElementById("search-input");
const searchResults = document.getElementById("search-results");
const searchBody = document.getElementById("search-body");

searchInput?.addEventListener("input", function () {
    const texto = this.value.trim();
    if (texto.length < 1) {
        searchResults?.classList.add("hidden");
        searchBody.innerHTML = "";
        return;
    }

    fetch(`pages/nueva_venta.php?buscar_producto=${encodeURIComponent(texto)}`)
        .then(res => res.json())
        .then(data => {
            searchBody.innerHTML = "";
            if (!data || data.length === 0) {
                searchBody.innerHTML = `<tr><td colspan="6" class="text-center py-3 text-gray-500">No hay resultados</td></tr>`;
                searchResults?.classList.remove("hidden");
                return;
            }

            data.forEach(prod => {
                const tr = document.createElement("tr");
                tr.className = "row-hover cursor-pointer";
                tr.innerHTML = `
                    <td class="py-2 px-3">${prod.cod_barras}</td>
                    <td class="py-2 px-3">${prod.nom_producto}</td>
                    <td class="py-2 px-3 text-center">${prod.talla} / ${prod.color}</td>
                    <td class="py-2 px-3 text-center">$${parseFloat(prod.precio).toFixed(2)}</td>
                    <td class="py-2 px-3 text-center">${prod.categoria ?? ''}</td>
                    <td class="py-2 px-3 text-center">${prod.cantidad ?? 0}</td>
                `;

                tr.addEventListener("click", () => addToCart({
                    cod_barras: prod.cod_barras,
                    name: prod.nom_producto,
                    price: parseFloat(prod.precio),
                    talla: prod.talla ?? '',
                    color: prod.color ?? '',
                    quantity: 1,
                    imagen: prod.imagen ?? null,
                    categoria: prod.categoria ?? '',
                    discount: null,
                    stock: prod.stock ?? 0
                }));

                searchBody.appendChild(tr);
            });

            searchResults?.classList.remove("hidden");
        })
        .catch(err => console.error("Error en búsqueda:", err));
});
