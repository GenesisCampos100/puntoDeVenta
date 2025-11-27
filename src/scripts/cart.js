document.addEventListener("DOMContentLoaded", () => {

    // CONSTANTES / STORAGE KEYS
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


    // ELEMENTOS DEL DOM
    const subtotalEl = document.getElementById("subtotal");
    const discountEl = document.getElementById("discount");
    const totalEl = document.getElementById("total");
    const clearCartBtn = document.getElementById("clear-cart");
    const payBtn = document.getElementById("pay-btn");
    const searchResults = document.getElementById("search-results");
    const searchBody = document.getElementById("search-body");
    const cartRows = document.getElementById("cart-rows");

    // Función para mostrar el cliente seleccionado
    function setSelectedClient(cliente) {
        const nameEl = document.getElementById('client-name');
        const phoneEl = document.getElementById('client-phone');

        if (cliente) {
            nameEl.textContent = cliente.nombre_completo;
            phoneEl.textContent = cliente.celular || '';
            localStorage.setItem('selectedClient', JSON.stringify(cliente));
        } else {
            nameEl.textContent = 'Público General';
            phoneEl.textContent = '';
            localStorage.removeItem('selectedClient');
        }
    }

    // Cargar cliente al iniciar
    const savedClient = localStorage.getItem('selectedClient');
    if (savedClient) {
        setSelectedClient(JSON.parse(savedClient));
    } else {
        setSelectedClient(null);
    }

    // ==============================
    // Selección desde el modal
    // ==============================
    $(document).on('click', '.seleccionarCliente', function () {
        const cliente = {
            id_cliente: $(this).data('id'),
            nombre_completo: $(this).data('nombre'),
            celular: $(this).closest('tr').find('td').eq(2).text()
        };

        setSelectedClient(cliente);

        // Cerrar modal
        $('#modalClientes').addClass('hidden');
    });

    // ==============================
    // Eliminar cliente seleccionado
    // ==============================
    $('#remove-client').on('click', function () {
        setSelectedClient(null);
    });


    // ---------- PREVIEW DEL PRODUCTO (opciones)
    function actualizarPreview(producto) {
        if (!producto || !producto.imagen) {
            document.getElementById("preview-producto").classList.add("hidden");
            return;
        }

        const ruta = "../uploads/" + producto.imagen;
        document.getElementById("preview-img").src = ruta;
        document.getElementById("preview-producto").classList.remove("hidden");
    }

    // BÚSQUEDA RÁPIDA POR ENTER (código o SKU exacto)
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
                        cod_barras: exacto.cod_barras,       // código
                        name: exacto.nom_producto,           // nombre exacto del producto
                        price: parseFloat(exacto.precio),    // precio
                        talla: exacto.talla ?? '',           // talla
                        color: exacto.color ?? '',           // color
                        cantidad: 1,                          // cantidad inicial
                        imagen: exacto.imagen ?? null,       // imagen
                        categoria: exacto.categoria ?? '',    // categoría
                        discount: null                        // descuento inicial
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






    let selectedClient = null;
    try {
        selectedClient = JSON.parse(localStorage.getItem(CLIENT_KEY)) || null;
    } catch (e) {
        selectedClient = null;
    }

    // FUNCIONES UTILITARIAS
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

    // CALCULAR TOTALES
    function recalcTotals() {
        let subtotal = 0;
        let individualDiscounts = 0;

        cart.forEach(item => {
            subtotal += parseFloat(item.price) * item.quantity;
            individualDiscounts += getItemDiscountAmount(item);
        });

        const subtotalAfterIndividual = subtotal - individualDiscounts;
        const globalType = localStorage.getItem("globalDiscountType") || "percent";
        const globalDiscountAmount =
            globalType === "percent"
                ? subtotalAfterIndividual * (globalDiscount / 100)
                : globalDiscount;

        const totalDiscount = individualDiscounts + globalDiscountAmount;
        const total = subtotal - totalDiscount;

        if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        if (discountEl) discountEl.textContent = `-$${totalDiscount.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
    }

    // RENDER DEL CARRITO (TABLA POS)
    function renderCart() {
        if (!cartRows) return;
        cartRows.innerHTML = "";

        cart.forEach((item, index) => {
            const row = document.createElement("tr");
            row.className = "border-b cart-row";   // ← agregado
            row.dataset.index = index;             // ← agregado

            const tdCodigo = document.createElement("td");
            tdCodigo.className = "py-2 px-3";
            tdCodigo.textContent = item.cod_barras;

            const tdProducto = document.createElement("td");
            tdProducto.className = "py-2 px-3";
            tdProducto.textContent = item.name;

            const tdVariant = document.createElement("td");
            tdVariant.className = "py-2 px-3 text-center";
            tdVariant.textContent = `${item.talla ?? ''} / ${item.color ?? ''}`;
            row.appendChild(tdVariant);


            const tdPrecio = document.createElement("td");
            tdPrecio.className = "py-2 px-3 text-center";
            tdPrecio.textContent = "$" + parseFloat(item.price).toFixed(2);

            const tdQty = document.createElement("td");
            tdQty.className = "py-2 px-3 text-center";
            tdQty.innerHTML = `
            <div class="flex items-center justify-center gap-1">
                <button class="px-2 py-1 bg-gray-200 rounded text-xs" onclick="decreaseQuantity(${index})">-</button>
                <span class="px-2 font-semibold">${item.quantity}</span>
                <button class="px-2 py-1 bg-gray-200 rounded text-xs" onclick="increaseQuantity(${index})">+</button>
            </div>
        `;

            const itemTotal = item.price * item.quantity - getItemDiscountAmount(item);
            const tdTotal = document.createElement("td");
            tdTotal.className = "py-2 px-3 text-center font-bold";
            tdTotal.textContent = "$" + itemTotal.toFixed(2);

            const tdDesc = document.createElement("td");
            tdDesc.className = "py-2 px-3 text-center";
            tdDesc.innerHTML = `<button class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs" onclick="editItemDiscount(${index})">%</button>`;

            const tdDel = document.createElement("td");
            tdDel.className = "py-2 px-3 text-center";
            tdDel.innerHTML = `<button class="text-red-600 text-lg" onclick="removeItem(${index})">×</button>`;

            row.append(tdCodigo, tdProducto, tdVariant, tdPrecio, tdQty, tdTotal, tdDesc, tdDel);
            cartRows.appendChild(row);
        });

        recalcTotals();
    }


    // UPDATE GENERAL
    function updateTotals() { recalcTotals(); }
    function updateCart() { renderCart(); }

    // FUNCIONES PARA BOTONES (+ / - / X)
    window.increaseQuantity = function (index) { cart[index].quantity++; saveCart(); };
    window.decreaseQuantity = function (index) { if (cart[index].quantity > 1) cart[index].quantity--; saveCart(); };
    window.removeItem = function (index) { cart.splice(index, 1); saveCart(); };
    window.editItemDiscount = function (index) {
        const modal = document.getElementById("discount-modal");
        if (!modal) return;
        modal.dataset.index = index;
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    };

    // CLIENTE: CAMBIAR/ELIMINAR Y MODAL CLIENTE
    document.addEventListener('click', (e) => {
        if (e.target && e.target.id === 'eliminarCliente') {
            localStorage.removeItem(CLIENT_KEY);
            selectedClient = null;
            updateCart();
        }
        if (e.target && e.target.id === 'cambiarCliente') {
            const modal = document.getElementById('modalClientes');
            if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        }
    });

    const clientBtn = document.getElementById('client-btn');
    if (clientBtn) clientBtn.addEventListener('click', () => {
        const modal = document.getElementById('modalClientes');
        if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    });

    const cerrarModalBtn = document.getElementById('cerrar-modal-cliente');
    if (cerrarModalBtn) cerrarModalBtn.addEventListener('click', () => {
        const modal = document.getElementById('modalClientes');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    });

    const buscarClienteInput = document.getElementById('buscarCliente');
    if (buscarClienteInput) buscarClienteInput.addEventListener('input', () => {
        const q = buscarClienteInput.value.toLowerCase().trim();
        document.querySelectorAll('#tablaClientes tbody tr, #tablaClientes tr').forEach(row => {
            row.style.display = (row.textContent || '').toLowerCase().includes(q) ? '' : 'none';
        });
    });

    document.addEventListener('click', (e) => {
        const sel = e.target.closest && e.target.closest('.seleccionarCliente');
        if (sel) {
            const id = sel.dataset.id ?? sel.getAttribute('data-id');
            const nombre = sel.dataset.nombre ?? sel.getAttribute('data-nombre');
            const telefono = sel.dataset.telefono ?? sel.getAttribute('data-telefono') ?? '';
            if (id && nombre) window.guardarCliente(id, nombre, telefono);
            const modal = document.getElementById('modalClientes');
            if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        }
    });

    window.guardarCliente = function (id, nombre, telefono = '') {
        selectedClient = { id: String(id), nombre: String(nombre), telefono };
        localStorage.setItem(CLIENT_KEY, JSON.stringify(selectedClient));
        updateCart();
    };

    // RECIBIR DESCUENTOS DESDE modal.js
    document.addEventListener('applyProductDiscount', e => {
        const { index, value, type } = e.detail;
        if (cart[index]) { cart[index].discount = { value, type }; saveCart(); }
    });
    document.addEventListener('applyGlobalDiscount', e => {
        globalDiscount = e.detail.value || 0;
        localStorage.setItem('globalDiscount', globalDiscount);
        localStorage.setItem('globalDiscountType', e.detail.type || 'percent');
        recalcTotals();
    });

    window.addToCart = function (prod) {
        let existente = cart.find(item =>
            item.cod_barras == prod.cod_barras &&
            item.talla == (prod.talla ?? '') &&
            item.color == (prod.color ?? '')
        );

        if (existente) {
            existente.quantity++;
        } else {
            cart.push({
                cod_barras: prod.cod_barras,
                name: prod.name,
                price: parseFloat(prod.price),
                quantity: prod.quantity ?? 1,
                talla: prod.talla ?? '',
                color: prod.color ?? '',
                categoria: prod.categoria ?? '',
                imagen: prod.imagen ?? null,
                discount: null
            });
        }

        saveCart();
        actualizarPreview(prod);
    };



    // AL HACER CLICK EN UNA FILA DEL CARRITO → MOSTRAR PREVIEW
    $(document).on("click", ".cart-row", function () {
        const index = $(this).data("index");
        const producto = cart[index];
        actualizarPreview(producto);
    });

    // CARGAR TODO AL INICIAR
    renderCart();

    clearCartBtn?.addEventListener('click', () => {
        cart = [];
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem('globalDiscount');
        saveCart();
    });

    // ABRIR MODAL DE PAGO
    payBtn?.addEventListener("click", () => {
        const paymentModal = document.getElementById('payment-modal');
        document.getElementById("cart-data-input").value = JSON.stringify(cart);
        document.getElementById("cliente-input").value = selectedClient?.id || "";
        document.getElementById("descuento-general-input").value = globalDiscount;
        document.getElementById("descuento-general-type").value = localStorage.getItem("globalDiscountType") || "percent";

        let subtotal = 0, individualDiscounts = 0;
        cart.forEach(item => { subtotal += item.price * item.quantity; individualDiscounts += getItemDiscountAmount(item); });
        const subtotalAfterIndividual = subtotal - individualDiscounts;
        const globalType = localStorage.getItem("globalDiscountType") || "percent";
        let globalDiscountAmount = globalType === "percent" ? subtotalAfterIndividual * (globalDiscount / 100) : globalDiscount;
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
        formData.append('id_cliente', selectedClient?.id || '');
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

    // INICIALIZAR
    updateCart();

    const searchInput = document.getElementById("search-input");

    // BÚSQUEDA DE PRODUCTOS
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
                    <td class="py-2 px-3 text-center">${prod.stock}</td>
                `;

                    // Agregar al carrito la variante específica
                    tr.addEventListener("click", () => addToCart({
                        cod_barras: prod.cod_barras,
                        name: prod.nom_producto,           // ← cambiar
                        price: parseFloat(prod.precio),    // ← cambiar
                        talla: prod.talla ?? '',
                        color: prod.color ?? '',
                        quantity: 1,                        // ← siempre quantity
                        imagen: prod.imagen ?? null,
                        categoria: prod.categoria ?? '',
                        discount: null
                    }));

                    searchBody.appendChild(tr);
                });

                searchResults?.classList.remove("hidden");
            })
            .catch(err => console.error("Error en búsqueda:", err));
    });


});