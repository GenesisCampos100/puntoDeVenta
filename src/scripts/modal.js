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
      localStorage.setItem("globalDiscountType", type);
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

    window.openProductDiscountModal = function (index, currentDiscount = 0) {
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
        const clientNameEl = document.getElementById('client-name');
        const clientPhoneEl = document.getElementById('client-phone');
        if (clientNameEl) clientNameEl.textContent = nombre;
        if (clientPhoneEl) clientPhoneEl.textContent = ""; // si deseas el teléfono, puedes ajustarlo aquí
        modalClientes?.classList.add("hidden");
      }
    });

    // Eliminar cliente seleccionado
    const removeClientBtn = document.getElementById('remove-client');
    removeClientBtn?.addEventListener('click', () => {
      localStorage.removeItem('selectedClient');
      const clientNameEl = document.getElementById('client-name');
      const clientPhoneEl = document.getElementById('client-phone');
      if (clientNameEl) clientNameEl.textContent = "Público General";
      if (clientPhoneEl) clientPhoneEl.textContent = "";
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
         MODAL PRODUCTOS
    ============================ */
    $('#open-product-modal').on('click', function () {
      $('#modalProductos').removeClass('hidden');
      $('#buscarProductoModal').val('').focus();
      $('#tablaProductosModal').html('');
    });

    $('#cerrar-modal-producto').on('click', function () {
      $('#modalProductos').addClass('hidden');
    });

    // Buscar productos en modal
    $('#buscarProductoModal').on('input', function () {
      const texto = $(this).val().trim();
      if (!texto) {
        $('#tablaProductosModal').html('');
        return;
      }

      $.ajax({
        url: 'pages/nueva_venta.php',
        method: 'GET',
        data: { buscar_producto: texto },
        dataType: 'json',
        success: function (productos) {
          const rows = productos.map(p => `
<tr class="border-b hover:bg-gray-50 transition-colors">
  <td class="p-3">${p.cod_barras}</td>
  <td class="p-3 font-medium">${p.nom_producto}</td>
  <td class="p-3 text-right">${parseFloat(p.precio).toFixed(2)}</td>
  <td class="p-3 text-center">${p.cantidad}</td>
  <td class="p-3">
    <button class="add-product-modal bg-[var(--primary)] text-white px-3 py-1 rounded-lg font-medium transition hover:bg-[var(--primary-600)]"
      data-codigo="${p.cod_barras}"
      data-nombre="${p.nom_producto}"
      data-precio="${p.precio}"
      data-talla="${p.talla ?? ''}"
      data-color="${p.color ?? ''}"
      data-imagen="${p.imagen ?? ''}"
    >Agregar</button>
  </td>
</tr>
`).join('');


          $('#tablaProductosModal').html(rows);
        }

      });
    });

    $(document).on('click', '.add-product-modal', function () {
      const prod = {
        cod_barras: $(this).data('codigo'),
        name: $(this).data('nombre'),
        price: parseFloat($(this).data('precio')),
        cantidad: 1, // cantidad inicial
        talla: $(this).data('talla'),
        color: $(this).data('color'),
        imagen: $(this).data('imagen')
      };
      addToCart(prod); // ahora addToCart recibe un objeto completo
    });


    /* ===========================
         MODAL PAGO
    ============================ */
    const payBtn = document.getElementById('pay-btn');
    const paymentModal = document.getElementById('payment-modal');
    const cancelPayment = document.getElementById('cancel-payment');
    const paymentForm = document.getElementById('payment-form');
    const confirmPayment = document.getElementById("confirm-payment");

    // Inputs y alertas
    const montoEfectivo = document.getElementById("monto-efectivo");
    const referenciaTarjeta = document.getElementById("referencia-tarjeta");
    const mixtoEfectivo = document.getElementById("mixto-efectivo");
    const mixtoTarjeta = document.getElementById("mixto-tarjeta");
    const mixtoReferencia = document.getElementById("mixto-referencia");
    const alertaEfectivo = document.getElementById("alerta-efectivo");
    const cambioEfectivo = document.getElementById("cambio-efectivo");
    const alertaMixto = document.getElementById("alerta-mixto");
    const cambioMixto = document.getElementById("cambio-mixto");

    const paymentInputs = [
      montoEfectivo,
      referenciaTarjeta,
      mixtoEfectivo,
      mixtoTarjeta,
      mixtoReferencia
    ];

    function getTotal() {
      // Suponiendo que guardaste el total en localStorage
      const total = parseFloat(localStorage.getItem("lastTotal")) || 0;
      return total;
    }

    function validarPago() {
      const total = getTotal();
      const metodo = document.querySelector("input.payment-method:checked")?.value?.toLowerCase();
      if (!metodo) return;
      if (!confirmPayment) return;

      confirmPayment.disabled = true;

      if (metodo === "efectivo") {
        const monto = parseFloat(montoEfectivo.value) || 0;
        if (metodo === "efectivo") {
          const monto = parseFloat(montoEfectivo.value) || 0;
          const total = getTotal();

          if (monto < total) {
            alertaEfectivo?.classList.remove("hidden");
            cambioEfectivo.textContent = "0.00";
            confirmPayment.disabled = true;
          } else {
            alertaEfectivo?.classList.add("hidden");
            // calcular cambio correctamente
            cambioEfectivo.textContent = (monto - total).toFixed(2);
            confirmPayment.disabled = false;
          }
        }



      } else if (metodo === "tarjeta") {
        confirmPayment.disabled = referenciaTarjeta.value.trim() === "";
      } else if (metodo === "mixto") {
        const efec = parseFloat(mixtoEfectivo.value) || 0;
        const tarj = parseFloat(mixtoTarjeta.value) || 0;
        const ref = mixtoReferencia.value.trim();
        const suma = efec + tarj;

        if (suma < total || ref === "") {
          alertaMixto?.classList.remove("hidden");
          if (alertaMixto) alertaMixto.textContent = `Faltan: $${(total - suma).toFixed(2)}`;
          confirmPayment.disabled = true;
        } else {
          alertaMixto?.classList.add("hidden");
          confirmPayment.disabled = false;
        }
      }
    }

    paymentInputs.forEach(input => input?.addEventListener("input", validarPago));
    document.querySelectorAll(".payment-method").forEach(radio => radio.addEventListener("change", () => {
      const metodo = (radio.value || '').toLowerCase();
      updatePaymentFields(metodo);
      const tipoPagoHidden = document.getElementById('tipo_pago');
      if (tipoPagoHidden) tipoPagoHidden.value = metodo;
    }));

    payBtn?.addEventListener('click', () => {
      const cart = JSON.parse(localStorage.getItem("cart")) || [];
      const client = JSON.parse(localStorage.getItem("selectedClient")) || null;
      const globalDiscount = parseFloat(localStorage.getItem("globalDiscount")) || 0;
      const discountTypeVal = localStorage.getItem("globalDiscountType") || "percent";

      document.getElementById("cart-data-input")?.setAttribute("value", JSON.stringify(cart));
      document.getElementById("cliente-input")?.setAttribute("value", client?.id ?? "");
      document.getElementById("descuento-general-input")?.setAttribute("value", globalDiscount);
      document.getElementById("descuento-general-type")?.setAttribute("value", discountTypeVal);

      paymentModal?.classList.remove("hidden");

      const selected = document.querySelector(".payment-method:checked");
      const method = selected ? (selected.value || '').toLowerCase() : 'efectivo';
      updatePaymentFields(method);
      inicializarPago();

      const subtotal = parseFloat(localStorage.getItem("lastTotal")) || 0;
      const totalPagarEl = document.getElementById("total-pagar");
      if (totalPagarEl) totalPagarEl.textContent = subtotal.toFixed(2);
    });

    const closePayment = document.getElementById("close-payment");
    closePayment?.addEventListener("click", () => paymentModal?.classList.add("hidden"));
    cancelPayment?.addEventListener("click", () => paymentModal?.classList.add("hidden"));
    paymentModal?.addEventListener('click', e => { if (e.target === paymentModal) paymentModal.classList.add("hidden"); });

    function updatePaymentFields(method) {
      document.getElementById("efectivo-section")?.classList.toggle("hidden", method !== "efectivo");
      document.getElementById("tarjeta-section")?.classList.toggle("hidden", method !== "tarjeta");
      document.getElementById("mixto-section")?.classList.toggle("hidden", method !== "mixto");

      if (montoEfectivo) montoEfectivo.closest('div')?.classList.toggle('hidden', method !== 'efectivo');
      if (referenciaTarjeta) referenciaTarjeta.closest('div')?.classList.toggle('hidden', method !== 'tarjeta');
      if (mixtoEfectivo) mixtoEfectivo.closest('div')?.classList.toggle('hidden', method !== 'mixto');
      if (mixtoTarjeta) mixtoTarjeta.closest('div')?.classList.toggle('hidden', method !== 'mixto');
      if (mixtoReferencia) mixtoReferencia.closest('div')?.classList.toggle('hidden', method !== 'mixto');

      if (alertaEfectivo) alertaEfectivo.classList.toggle('hidden', method !== 'efectivo');
      if (cambioEfectivo) cambioEfectivo.closest('p')?.classList.toggle('hidden', method !== 'efectivo');
      if (alertaMixto) alertaMixto.classList.toggle('hidden', method !== 'mixto');
      if (cambioMixto) cambioMixto.closest('p')?.classList.toggle('hidden', true);

      validarPago();
    }

    paymentForm?.addEventListener("submit", async e => {
      e.preventDefault();
      const confirmBtn = document.getElementById("confirm-payment");
      if (!confirmBtn) return alert("Botón de confirmar no encontrado");
      confirmBtn.disabled = true;

      const formData = new FormData(paymentForm);
      // si existe un hidden tipo_pago (opcional), actualizar su valor
      const tipoPagoHidden = document.getElementById('tipo_pago');
      const selectedMethod = (document.querySelector(".payment-method:checked")?.value || '').toLowerCase();
      if (tipoPagoHidden) tipoPagoHidden.value = selectedMethod;

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
          try {
            const ticketUrl = `scripts/ticket.php?id_venta=${encodeURIComponent(json.id_venta)}`;
            const ticketModal = document.getElementById('ticket-modal');
            const ticketIframe = document.getElementById('ticket-iframe');
            const printBtn = document.getElementById('print-ticket');
            const closeBtn = document.getElementById('close-ticket-modal');
            const cancelBtn = document.getElementById('cancel-ticket');

            if (ticketIframe) {
              ticketIframe.src = ticketUrl;
            }

            ticketModal?.classList.remove('hidden');
            ticketModal?.classList.add('flex');

            if (printBtn) printBtn.disabled = true;

            const cleanup = () => {
              ticketModal?.classList.add('hidden');
              ticketModal?.classList.remove('flex');
              if (ticketIframe) {
                ticketIframe.onload = null;
                ticketIframe.src = '';
              }
              if (printBtn) printBtn.disabled = false;
            };

            if (closeBtn) closeBtn.onclick = cleanup;
            if (cancelBtn) cancelBtn.onclick = cleanup;

            const outsideHandler = (ev) => {
              if (ev.target === ticketModal) cleanup();
            };
            ticketModal?.addEventListener('click', outsideHandler);

            if (ticketIframe) {
              ticketIframe.onload = () => {
                try {
                  const doc = ticketIframe.contentDocument || ticketIframe.contentWindow.document;
                  const contentHeight = Math.max(
                    doc.documentElement.scrollHeight || 0,
                    (doc.body && doc.body.scrollHeight) || 0
                  );
                  ticketIframe.style.height = contentHeight + 'px';
                } catch (e) {
                  console.error('No se pudo ajustar la altura del iframe:', e);
                } finally {
                  if (printBtn) printBtn.disabled = false;
                }
              };
            }

            if (printBtn) {
              printBtn.onclick = () => {
                if (!ticketIframe || !ticketIframe.contentWindow) {
                  return alert('El ticket aún no está listo para imprimir. Intenta de nuevo en unos segundos.');
                }
                try {
                  ticketIframe.contentWindow.focus();
                  ticketIframe.contentWindow.print();
                } catch (e) {
                  console.error('Error al imprimir desde iframe:', e);
                  alert('No fue posible imprimir desde el modal. Abre el ticket en una nueva pestaña manualmente si lo deseas.');
                }
              };
            }

          } catch (err) {
            console.error('No se pudo cargar el ticket:', err);
            alert('Venta procesada, pero no se pudo cargar el ticket.');
          }
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

          ventaDetalles.innerHTML = '';

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

          const clienteDiv = document.createElement('p');
          clienteDiv.className = 'font-medium mt-2';
          clienteDiv.textContent = `Cliente: ${data.cliente || 'Sin cliente'}`;
          ventaDetalles.appendChild(clienteDiv);

          if (data.empleado) {
            const empleadoDiv = document.createElement('p');
            empleadoDiv.className = 'font-medium mt-1';
            empleadoDiv.textContent = `Empleado: ${data.empleado}`;
            ventaDetalles.appendChild(empleadoDiv);
          }

          if (data.total !== undefined) {
            const totalDiv = document.createElement('p');
            totalDiv.className = 'font-bold mt-2';
            totalDiv.textContent = `Total: $${parseFloat(data.total).toFixed(2)}`;
            ventaDetalles.appendChild(totalDiv);
          }

          ventaModal.classList.remove('hidden');

        } catch (err) {
          console.error(err);
          alert('❌ Error al cargar el detalle de venta');
        }
      }
    });

    closeVentaModal?.addEventListener('click', () => ventaModal?.classList.add("hidden"));
    ventaModal?.addEventListener('click', e => {
      if (e.target === ventaModal) ventaModal.classList.add("hidden");
    });







  }); // fin DOMContentLoaded
})(); // fin IIFE
