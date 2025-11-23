// modal.js - Manejo de modales (descuento, cliente, pago, detalle)
(() => {
document.addEventListener('DOMContentLoaded', () => {

    // --- Descuento global ---
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

        document.dispatchEvent(
            new CustomEvent("applyGlobalDiscount", { detail: { value, type } })
        );
    });

    // --- Descuento individual ---
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

            document.dispatchEvent(
                new CustomEvent("applyProductDiscount", {
                    detail: { index: currentItemIndex, value, type }
                })
            );

            currentItemIndex = null;
        }
    });

    // --- Modal cliente ---
    document.getElementById("client-btn").addEventListener("click", () => {
        document.getElementById("modalClientes").classList.remove("hidden");
    });

    document.getElementById("cerrar-modal-cliente").addEventListener("click", () => {
        document.getElementById("modalClientes").classList.add("hidden");
    });

    function mostrarClienteEnUI(id, nombre) {
        const info = document.getElementById("cliente_info");

        document.getElementById("cliente_id").value = id;
        document.getElementById("cliente_nombre").textContent = nombre;

        info.classList.remove("hidden");
    }

    const saved = JSON.parse(localStorage.getItem("selectedClient"));
    if (saved) mostrarClienteEnUI(saved.id, saved.nombre);

    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("seleccionarCliente")) {
            const id = e.target.dataset.id;
            const nombre = e.target.dataset.nombre;

            localStorage.setItem("selectedClient", JSON.stringify({ id, nombre }));
            mostrarClienteEnUI(id, nombre);

            document.getElementById("modalClientes").classList.add("hidden");
        }
    });

    // --- Buscar cliente AJAX ---
    document.getElementById("buscarCliente").addEventListener("keyup", function () {
        const texto = this.value.trim();

        fetch(`nueva_venta.php?buscar_cliente=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById("tablaClientes");
                tbody.innerHTML = "";

                data.forEach(cli => {
                    tbody.innerHTML += `
                        <tr class="border-b">
                            <td class="p-2 border">${cli.id_cliente}</td>
                            <td class="p-2 border">${cli.nombre_completo}</td>
                            <td class="p-2 border">${cli.celular}</td>
                            <td class="p-2 border">
                                <button 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded seleccionarCliente"
                                    data-id="${cli.id_cliente}" 
                                    data-nombre="${cli.nombre_completo}">
                                    Seleccionar
                                </button>
                            </td>
                        </tr>`;
                });
            });
    });

    document.getElementById("cambiarCliente").addEventListener("click", () =>
        document.getElementById("modalClientes").classList.remove("hidden")
    );

    document.getElementById("eliminarCliente").addEventListener("click", () => {
        localStorage.removeItem("selectedClient");
        document.getElementById("cliente_info").classList.add("hidden");
        document.getElementById("cliente_id").value = "";
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            document.getElementById("modalClientes").classList.add("hidden");
        }
    });

    // --- Modal pago ---
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
              // asignar src al iframe (misma-origin) para que cargue ticket.php con sus estilos
              ticketIframe.src = ticketUrl;
            }

            ticketModal?.classList.remove('hidden');
            ticketModal?.classList.add('flex');

            // Desactivar el botón de imprimir hasta que el iframe cargue
            if (printBtn) printBtn.disabled = true;

            const cleanup = () => {
              ticketModal?.classList.add('hidden');
              ticketModal?.classList.remove('flex');
              if (ticketIframe) {
                // limpiar src y onload
                ticketIframe.onload = null;
                ticketIframe.src = '';
              }
              if (printBtn) printBtn.disabled = false;
            };

            if (closeBtn) closeBtn.onclick = cleanup;
            if (cancelBtn) cancelBtn.onclick = cleanup;

            // Cerrar al hacer click fuera del contenido
            const outsideHandler = (ev) => {
              if (ev.target === ticketModal) cleanup();
            };
            ticketModal?.addEventListener('click', outsideHandler);

            // Habilitar impresión y ajustar altura del iframe cuando termine de cargar
            if (ticketIframe) {
              ticketIframe.onload = () => {
                try {
                  const doc = ticketIframe.contentDocument || ticketIframe.contentWindow.document;
                  const contentHeight = Math.max(
                    doc.documentElement.scrollHeight || 0,
                    (doc.body && doc.body.scrollHeight) || 0
                  );
                  // Ajustar la altura del iframe al contenido para que el scrollbar quede en el contenedor exterior
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
    });

    cancelPayment?.addEventListener('click', () => paymentModal?.classList.add("hidden"));

    paymentModal?.addEventListener('click', e => {
        if (e.target === paymentModal) paymentModal.classList.add("hidden");
    });

    document.querySelectorAll(".payment-method").forEach(radio => {
        radio.addEventListener("change", () => {
            const m = radio.value;

            const efectivo = document.getElementById("efectivo-section");
            const tarjeta = document.getElementById("tarjeta-section");
            const mixto = document.getElementById("mixto-section");

            efectivo?.classList.add("hidden");
            tarjeta?.classList.add("hidden");
            mixto?.classList.add("hidden");

            if (m === "EFECTIVO") efectivo?.classList.remove("hidden");
            if (m === "TARJETA") tarjeta?.classList.remove("hidden");
            if (m === "MIXTO") mixto?.classList.remove("hidden");
        });
    });

    // --- Detalle de venta ---
    const ventaModal = document.getElementById('venta-modal');
    const ventaDetalles = document.getElementById('venta-detalles');
    const closeVentaModal = document.getElementById('close-venta-modal');

    document.addEventListener('click', async (e) => {
        if (e.target?.classList.contains('ver-detalle-btn')) {
            const idVenta = e.target.dataset.id;
            if (!idVenta) return alert('❌ No se encontró ID de venta');

            try {
                const res = await fetch(`scripts/ventas_detalles.php?id_venta=${idVenta}`);
                const data = await res.json();

                if (!data.success) return alert('❌ Error al obtener detalle de venta');

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
                        </div>`;
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
