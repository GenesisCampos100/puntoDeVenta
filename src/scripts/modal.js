// modal.js - manejo de modales (descuento, cliente, pago, detalle de venta)
(() => {
  document.addEventListener('DOMContentLoaded', () => {

    /* ===========================
         DESCUENTO GLOBAL
    ============================ */
    const discountModal = document.getElementById("discount-modal");
    const discountType = document.getElementById("discount-type");
    const discountInput = document.getElementById("discount-input");
    const discountApply = document.getElementById("apply-discount");
    const discountClose = document.getElementById("close-discount");

    document.getElementById("discount-btn")?.addEventListener("click", () => {
      discountModal?.classList.remove("hidden");
    });

    discountClose?.addEventListener("click", () => discountModal?.classList.add("hidden"));

    discountApply?.addEventListener("click", () => {
      const value = parseFloat(discountInput.value) || 0;
      const type = discountType.value || 'percent';
      localStorage.setItem("globalDiscount", String(value));
      localStorage.setItem("discountType", type);
      discountModal?.classList.add("hidden");
      document.dispatchEvent(new CustomEvent("applyGlobalDiscount", { detail: { value, type } }));
    });

    /* ===========================
         DESCUENTO INDIVIDUAL
    ============================ */
    const productDiscountModal = document.getElementById("product-discount-modal");
    const productDiscountType = document.getElementById("product-discount-type");
    const productDiscountInput = document.getElementById("product-discount-input");
    const productDiscountApply = document.getElementById("product-discount-apply");
    const productDiscountClose = document.getElementById("product-discount-close");

    let currentItemIndex = null;

    window.openProductDiscountModal = function(index, currentDiscount = 0) {
      currentItemIndex = index;
      productDiscountInput.value = currentDiscount || 0;
      productDiscountModal?.classList.remove("hidden");
    };

    productDiscountClose?.addEventListener("click", () => {
      productDiscountModal?.classList.add("hidden");
      currentItemIndex = null;
    });

    productDiscountApply?.addEventListener("click", () => {
      if (currentItemIndex !== null) {
        const value = parseFloat(productDiscountInput.value) || 0;
        const type = productDiscountType.value || 'percent';
        productDiscountModal?.classList.add("hidden");
        document.dispatchEvent(new CustomEvent("applyProductDiscount", { detail: { index: currentItemIndex, value, type } }));
        currentItemIndex = null;
      }
    });

    /* ===========================
         MODAL CLIENTE
    ============================ */
    const clientBtn = document.getElementById('client-btn');
    const modalClientes = document.getElementById('modalClientes');
    const cerrarModalClienteBtn = document.getElementById('cerrar-modal-cliente');
    const buscarClienteInput = document.getElementById('buscarCliente');
    const tablaClientes = document.getElementById('tablaClientes');

    clientBtn?.addEventListener('click', () => modalClientes?.classList.remove("hidden"));
    cerrarModalClienteBtn?.addEventListener('click', () => modalClientes?.classList.add("hidden"));
    modalClientes?.addEventListener('click', e => { 
      if (e.target === modalClientes) modalClientes.classList.add("hidden"); 
    });

    document.addEventListener('click', e => {
      if (e.target?.classList.contains('seleccionarCliente')) {
        const id = e.target.dataset.id;
        const nombre = e.target.dataset.nombre;
        localStorage.setItem('selectedClient', JSON.stringify({ id: String(id), nombre }));
        const clienteInput = document.getElementById('cliente-input');
        const id_cliente = document.getElementById('id_cliente');
        const clienteNombreEl = document.getElementById('cliente_nombre');
        if (clienteInput) clienteInput.value = id;
        if (id_cliente) id_cliente.value = id;
        if (clienteNombreEl) clienteNombreEl.textContent = nombre;
        modalClientes?.classList.add("hidden");
      }
    });

    buscarClienteInput?.addEventListener('input', e => {
      const q = e.target.value.trim().toLowerCase();
      if (!tablaClientes) return;
      [...tablaClientes.querySelectorAll('tr')].forEach(row => {
        if (row.querySelectorAll('th').length) { 
          row.style.display = ''; 
          return; 
        }
        const text = row.textContent.toLowerCase();
        row.style.display = q === '' || text.includes(q) ? '' : 'none';
      });
    });

    /* ===========================
         MODAL PAGO
    ============================ */
    const payBtn = document.getElementById('pay-btn');
    const paymentModal = document.getElementById('payment-modal');
    const cancelPayment = document.getElementById('cancel-payment');
    const paymentForm = document.getElementById('payment-form');

    payBtn?.addEventListener('click', () => {
      const cart = JSON.parse(localStorage.getItem("cart")) || [];
      const client = JSON.parse(localStorage.getItem("selectedClient")) || null;
      const globalDiscount = parseFloat(localStorage.getItem("globalDiscount")) || 0;
      const discountTypeVal = localStorage.getItem("discountType") || "percent";
      document.getElementById("cart-data-input")?.setAttribute("value", JSON.stringify(cart));
      document.getElementById("cliente-input")?.setAttribute("value", client?.id ?? "");
      document.getElementById("descuento-general-input")?.setAttribute("value", globalDiscount);
      document.getElementById("descuento-general-type")?.setAttribute("value", discountTypeVal);
      paymentModal?.classList.remove("hidden");
    });

    paymentForm?.addEventListener("submit", async e => {
      e.preventDefault();
      const confirmBtn = document.getElementById("confirm-payment");
      if (!confirmBtn) return alert("Botón de confirmar no encontrado");
      confirmBtn.disabled = true;

      const formData = new FormData(paymentForm);
      try {
        const res = await fetch("scripts/procesar_venta.php", { method: "POST", body: formData, credentials: 'same-origin' });
        const text = await res.text();
        let json;
        try { 
          json = JSON.parse(text); 
        } catch { 
          alert("Respuesta inválida del servidor"); 
          confirmBtn.disabled = false; 
          return; 
        }

        if (json.success) {
          localStorage.removeItem("cart");
          paymentModal?.classList.add("hidden");
          document.dispatchEvent(new CustomEvent("ventaExitosa", { detail: json }));
          alert("✅ Venta procesada. ID: " + json.id_venta);
        } else {
          alert("❌ " + (json.message || "Error al procesar la venta"));
        }
      } catch (err) {
        console.error(err);
        alert("❌ Error de red al procesar la venta");
      } finally {
        confirmBtn.disabled = false;
      }
    });

    cancelPayment?.addEventListener('click', () => paymentModal?.classList.add("hidden"));
    paymentModal?.addEventListener('click', e => { 
      if (e.target === paymentModal) paymentModal.classList.add("hidden"); 
    });

    document.querySelectorAll(".payment-method").forEach(radio => {
      radio.addEventListener("change", () => {
        const metodo = radio.value;
        const efectivo = document.getElementById("efectivo-section");
        const tarjeta = document.getElementById("tarjeta-section");
        const mixto = document.getElementById("mixto-section");
        efectivo?.classList.add("hidden");
        tarjeta?.classList.add("hidden");
        mixto?.classList.add("hidden");
        if (metodo === "EFECTIVO") efectivo?.classList.remove("hidden");
        if (metodo === "TARJETA") tarjeta?.classList.remove("hidden");
        if (metodo === "MIXTO") mixto?.classList.remove("hidden");
      });
    });

    /* ===========================
         DETALLE DE VENTA
    ============================ */
/* ===========================
     DETALLE DE VENTA
============================ */
const ventaModal = document.getElementById('venta-modal');
const ventaDetalles = document.getElementById('venta-detalles');
const closeVentaModal = document.getElementById('close-venta-modal');

document.addEventListener('click', async (e) => {
  if (e.target && e.target.classList.contains('ver-detalle-btn')) {
    const idVenta = e.target.dataset.id;
    if (!idVenta) return alert('❌ No se encontró ID de venta');

    try {
      const res = await fetch(`scripts/ventas_detalles.php?id_venta=${idVenta}`);
      const data = await res.json();

      if (!data.success) {
        console.error(data);
        return alert('❌ Error al obtener detalle de venta');
      }

      // Limpiar contenido previo
      ventaDetalles.innerHTML = '';

      // Renderizar productos con descuento
      data.productos.forEach(item => {
        const div = document.createElement('div');
        div.className = 'flex justify-between p-2 border-b';
        div.innerHTML = `
          <div>
            <p class="font-semibold">${item.nombre}</p>
            ${item.descuento > 0 ? `<p class="text-sm text-green-600">Descuento: ${parseFloat(item.descuento).toFixed(2)}</p>` : ''}
          </div>
          <div class="text-right">
            <p>${item.cantidad} x $${parseFloat(item.precio_unitario).toFixed(2)}</p>
          </div>
        `;
        ventaDetalles.appendChild(div);
      });

      // Mostrar cliente
      const clienteDiv = document.createElement('p');
      clienteDiv.className = 'font-medium mt-2';
      clienteDiv.textContent = `Cliente: ${data.cliente || 'Sin cliente'}`;
      ventaDetalles.appendChild(clienteDiv);

      // Mostrar empleado
      if (data.empleado) {
        const empleadoDiv = document.createElement('p');
        empleadoDiv.className = 'font-medium mt-1';
        empleadoDiv.textContent = `Empleado: ${data.empleado}`;
        ventaDetalles.appendChild(empleadoDiv);
      }

      // Mostrar total
      if (data.total !== undefined) {
        const totalDiv = document.createElement('p');
        totalDiv.className = 'font-bold mt-2';
        totalDiv.textContent = `Total: $${parseFloat(data.total).toFixed(2)}`;
        ventaDetalles.appendChild(totalDiv);
      }

      // Abrir modal
      ventaModal.classList.remove('hidden');

    } catch (err) {
      console.error(err);
      alert('❌ Error al cargar el detalle de venta');
    }
  }
});

// Cerrar modal
closeVentaModal?.addEventListener('click', () => ventaModal?.classList.add("hidden"));
ventaModal?.addEventListener('click', e => { 
  if (e.target === ventaModal) ventaModal.classList.add("hidden"); 
});

  }); // fin DOMContentLoaded
})(); // fin IIFE