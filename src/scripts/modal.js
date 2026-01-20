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
    const discountWarning = document.getElementById("discount-warning");

    document.getElementById("discount-btn")?.addEventListener("click", () => {
      discountModal?.classList.remove("hidden");
      discountApply.disabled = false;
      discountWarning.classList.add("hidden");
      discountInput.value = "";
    });

    discountClose?.addEventListener("click", () => discountModal?.classList.add("hidden"));

    // Validación en tiempo real
    discountInput?.addEventListener("input", () => {
      const val = parseFloat(discountInput.value) || 0;
      const type = discountType.value;

      let maxValid = type === "percent" ? 100 : parseFloat(localStorage.getItem("lastTotal")) || 999999;

      if (val < 0 || val > maxValid) {
        discountWarning.textContent = type === "percent"
          ? "El descuento no puede ser mayor al 100%"
          : `El descuento no puede superar el total: $${maxValid.toFixed(2)}`;
        discountWarning.classList.remove("hidden");
        discountApply.disabled = true;
      } else {
        discountWarning.classList.add("hidden");
        discountApply.disabled = false;
      }
    });

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
    const productDiscountWarning = document.getElementById("product-discount-warning");

    let currentItemIndex = null;

    window.openProductDiscountModal = function (index, currentDiscount = 0) {
      currentItemIndex = index;

      const item = cart[index];
      if (!item) return;

      // Total del producto
      const itemTotal = (item.price * item.quantity) - getItemDiscountAmount(item);

      productDiscountInput.value = currentDiscount || 0;
      productDiscountInput.dataset.itemTotal = itemTotal; // guardamos para la validación

      productDiscountModal?.classList.remove("hidden");
      productDiscountApply.disabled = false;
      productDiscountWarning.classList.add("hidden");
    };

    productDiscountClose?.addEventListener("click", () => {
      productDiscountModal?.classList.add("hidden");
      currentItemIndex = null;
    });

    // Validación en tiempo real
    productDiscountInput?.addEventListener("input", () => {
      const val = parseFloat(productDiscountInput.value) || 0;
      const type = productDiscountType.value;
      const maxValid = type === "percent"
        ? 100
        : parseFloat(productDiscountInput.dataset.itemTotal || 999999);

      if (val < 0 || val > maxValid) {
        productDiscountWarning.textContent = type === "percent"
          ? "El descuento no puede ser mayor al 100%"
          : `El descuento no puede superar el total del producto: $${maxValid.toFixed(2)}`;
        productDiscountWarning.classList.remove("hidden");
        productDiscountApply.disabled = true;
      } else {
        productDiscountWarning.classList.add("hidden");
        productDiscountApply.disabled = false;
      }
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


    /*
         MODAL CLIENTE*/
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

    /*
         MODAL PRODUCTOS
  = */
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
    <button class="add-product-modal
                   bg-[var(--primary)] text-white px-3 py-1 rounded-lg font-medium
                   transition hover:bg-[var(--primary-600)]"

      data-codigo="${p.cod_barras}"
      data-nombre="${p.nom_producto}"
      data-precio="${p.precio}"
      data-talla="${p.talla ?? ''}"
      data-color="${p.color ?? ''}"
      data-imagen="${p.imagen ?? ''}"
      data-stock="${p.cantidad}"

    >Agregar</button>
  </td>
</tr>
        `).join('');

          $("#tablaProductosModal").html(rows);


        }

      });
    });

    $(document).on('click', '.add-product-modal', function () {

      const prod = {
        cod_barras: $(this).data("codigo"),
        name: $(this).data("nombre"),
        price: parseFloat($(this).data("precio")),
        talla: $(this).data("talla") ?? "",
        color: $(this).data("color") ?? "",
        quantity: 1,                                  // ← unificamos nombre
        imagen: $(this).data("imagen") ?? null,
        categoria: "",                                // opcional
        discount: null,                               // opcional
        stock: parseInt($(this).data("stock")) || 0   // ← ahora sí pasa stock
      };

      addToCart(prod);
    });



    /*
         MODAL PAGO
  = */
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
      const total = parseFloat(localStorage.getItem("lastTotal"));
      return isNaN(total) ? 0 : total;
    }

    function validarPago() {
      const total = getTotal();
      const metodo = document.querySelector("input.payment-method:checked")?.value?.toLowerCase();

      if (!metodo || !confirmPayment) return;

      // Resetear estados
      confirmPayment.disabled = true;
      confirmPayment.classList.add("opacity-50", "cursor-not-allowed");

      if (metodo === "efectivo") {
        const monto = parseFloat(montoEfectivo.value) || 0;

        if (monto < total) {
          alertaEfectivo?.classList.remove("hidden");
          if (cambioEfectivo) cambioEfectivo.textContent = "0.00";
          confirmPayment.disabled = true;
          confirmPayment.classList.add("opacity-50", "cursor-not-allowed");
        } else {
          alertaEfectivo?.classList.add("hidden");
          if (cambioEfectivo) cambioEfectivo.textContent = (monto - total).toFixed(2);
          confirmPayment.disabled = false;
          confirmPayment.classList.remove("opacity-50", "cursor-not-allowed");
        }
      } else if (metodo === "tarjeta") {
        // Para tarjeta, solo validamos que haya referencia si es requerida, 
        // o simplemente habilitamos si no hay validación extra
        const ref = referenciaTarjeta.value.trim();
        // Si la referencia es obligatoria:
        // confirmPayment.disabled = ref === ""; 
        // Si no es obligatoria (o se valida diferente):
        confirmPayment.disabled = false;
        confirmPayment.classList.remove("opacity-50", "cursor-not-allowed");
      } else if (metodo === "mixto") {
        const efec = parseFloat(mixtoEfectivo.value) || 0;
        const tarj = parseFloat(mixtoTarjeta.value) || 0;
        const ref = mixtoReferencia.value.trim();
        const suma = efec + tarj;

        // Calcular faltante
        const faltante = total - suma;

        if (suma < total) {
          alertaMixto?.classList.remove("hidden");
          if (alertaMixto) alertaMixto.textContent = `Faltan: $${faltante.toFixed(2)}`;
          if (cambioMixto) cambioMixto.textContent = "0.00";
          confirmPayment.disabled = true;
          confirmPayment.classList.add("opacity-50", "cursor-not-allowed");
        } else {
          alertaMixto?.classList.add("hidden");
          // Si paga de más en mixto, asumimos que el excedente es cambio del efectivo
          if (cambioMixto) cambioMixto.textContent = (suma - total).toFixed(2);
          confirmPayment.disabled = false;
          confirmPayment.classList.remove("opacity-50", "cursor-not-allowed");
        }
      }
    }

    paymentInputs.forEach(input => input?.addEventListener("input", validarPago));
    // Actualizar visualmente la opción seleccionada + hidden real
    document.querySelectorAll(".payment-method").forEach(radio => {
      radio.addEventListener("change", (e) => {
        const metodo = e.target.value.toLowerCase();

        // Actualizar input hidden que recibe PHP
        document.getElementById("tipo_pago").value = metodo;

        // Actualizar campos visibles del modal
        updatePaymentFields(metodo);

        // Marcar visualmente la opción activa
        document.querySelectorAll(".payment-option").forEach(option => {
          option.classList.remove("border-blue-500", "ring-2", "ring-blue-400", "bg-blue-50");
          option.classList.add("border-gray-200");
        });

        e.target.closest("label").classList.add(
          "border-blue-500", "ring-2", "ring-blue-400", "bg-blue-50"
        );
      });
    });


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
      // 1. Actualizar visibilidad de secciones
      document.getElementById("efectivo-section")?.classList.toggle("hidden", method !== "efectivo");
      document.getElementById("tarjeta-section")?.classList.toggle("hidden", method !== "tarjeta");
      document.getElementById("mixto-section")?.classList.toggle("hidden", method !== "mixto");

      // 2. Resaltar la opción seleccionada visualmente
      document.querySelectorAll(".payment-method").forEach(input => {
        const label = input.closest("label");
        if (!label) return;

        if (input.value === method) {
          // Estilos para seleccionado
          label.classList.add("border-primary", "ring-2", "ring-primary", "bg-blue-50");
          label.classList.remove("border-gray-200", "hover:bg-gray-50");
        } else {
          // Estilos por defecto
          label.classList.remove("border-primary", "ring-2", "ring-primary", "bg-blue-50");
          label.classList.add("border-gray-200", "hover:bg-gray-50");
        }
      });


      // 3. Enfocar el input correspondiente
      if (method === "efectivo") {
        setTimeout(() => montoEfectivo?.focus(), 100);
      } else if (method === "tarjeta") {
        setTimeout(() => referenciaTarjeta?.focus(), 100);
      } else if (method === "mixto") {
        setTimeout(() => mixtoEfectivo?.focus(), 100);
      }

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

    /*
     DETALLE DE VENTA= */
    const ventaModal = document.getElementById('venta-modal');
    const ventaDetalles = document.getElementById('venta-detalles');
    const closeVentaModal = document.getElementById('close-venta-modal');

    document.addEventListener('click', async (e) => {
      if (e.target && e.target.classList.contains('ver-detalle-btn')) {
        const idVenta = e.target.dataset.id;
        if (!idVenta) return alert('❌ No se encontró ID de venta');

        try {
          // CORRECCIÓN: Usar el script correcto que devuelve pagos
          const res = await fetch(`scripts/obtener_detalle_venta.php?id_venta=${idVenta}`);
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
          clienteDiv.textContent = `Cliente: ${data.venta.cliente || 'Público General'}`;
          ventaDetalles.appendChild(clienteDiv);

          // Mostrar empleado
          if (data.venta.empleado) {
            const empleadoDiv = document.createElement('p');
            empleadoDiv.className = 'font-medium mt-1';
            empleadoDiv.textContent = `Empleado: ${data.venta.empleado}`;
            ventaDetalles.appendChild(empleadoDiv);
          }

          // Mostrar total
          if (data.venta.total !== undefined) {
            const totalDiv = document.createElement('p');
            totalDiv.className = 'font-bold mt-2 text-lg';
            totalDiv.textContent = `Total: $${parseFloat(data.venta.total).toFixed(2)}`;
            ventaDetalles.appendChild(totalDiv);
          }

          // MOSTRAR PAGOS
          if (data.pagos && data.pagos.length > 0) {
            const pagosTitle = document.createElement('h3');
            pagosTitle.className = "font-bold mt-4 mb-2 text-gray-700 border-b pb-1";
            pagosTitle.textContent = "Métodos de Pago";
            ventaDetalles.appendChild(pagosTitle);

            data.pagos.forEach(p => {
              const pDiv = document.createElement('div');
              pDiv.className = "flex justify-between text-sm mb-1";

              let refText = p.referencia ? ` (Ref: ${p.referencia})` : '';

              pDiv.innerHTML = `
                <span>${p.metodo}${refText}</span>
                <span class="font-medium">$${parseFloat(p.monto).toFixed(2)}</span>
              `;
              ventaDetalles.appendChild(pDiv);
            });
          }

          // Abrir modal
          ventaModal.classList.remove('hidden');

        } catch (err) {
          console.error(err);
          alert('❌ Error al cargar el detalle de venta');
        }
      }
    });


  }); // fin DOMContentLoaded
})(); // fin IIFE