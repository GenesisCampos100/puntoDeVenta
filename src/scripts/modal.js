(() => {
  // ======================
  // ELEMENTOS DEL DOM
  // ======================
  const discountModal = document.getElementById("discount-modal");
  const discountType = document.getElementById("discount-type");
  const discountInput = document.getElementById("discount-input");
  const discountApply = document.getElementById("apply-discount");
  const discountClose = document.getElementById("close-discount");

  const productDiscountModal = document.getElementById("product-discount-modal");
  const productDiscountType = document.getElementById("product-discount-type");
  const productDiscountInput = document.getElementById("product-discount-input");
  const productDiscountApply = document.getElementById("product-discount-apply");
  const productDiscountClose = document.getElementById("product-discount-close");

  let currentItemIndex = null;

  // ======================
  // MODAL GLOBAL
  // ======================
  document.getElementById("discount-btn")?.addEventListener("click", () => {
    discountModal.classList.remove("hidden");
    discountModal.classList.add("flex");
  });

  discountClose?.addEventListener("click", () => {
    discountModal.classList.add("hidden");
  });

  discountApply?.addEventListener("click", () => {
    const value = parseFloat(discountInput.value) || 0;
    const type = discountType.value; // "percent" o "amount"
    discountModal.classList.add("hidden");
    document.dispatchEvent(new CustomEvent("applyGlobalDiscount", { detail: { value, type } }));
  });

  // ======================
  // MODAL INDIVIDUAL
  // ======================
  window.openProductDiscountModal = function (index, currentDiscount = 0) {
    currentItemIndex = index;
    productDiscountInput.value = currentDiscount;
    productDiscountModal.classList.remove("hidden");
    productDiscountModal.classList.add("flex");
  };

  productDiscountClose?.addEventListener("click", () => {
    productDiscountModal.classList.add("hidden");
    currentItemIndex = null;
  });

  productDiscountApply?.addEventListener("click", () => {
    if (currentItemIndex !== null) {
      const value = parseFloat(productDiscountInput.value) || 0;
      const type = productDiscountType.value; // "percent" o "amount"
      productDiscountModal.classList.add("hidden");
      document.dispatchEvent(new CustomEvent("applyProductDiscount", { detail: { index: currentItemIndex, value, type } }));
      currentItemIndex = null;
    }
  });


// ======================
// MODAL DE PAGO
// ======================

const payBtn = document.getElementById('pay-btn');
const paymentModal = document.getElementById('payment-modal');
const cancelPayment = document.getElementById('cancel-payment');
const paymentForm = document.getElementById('payment-form');

// Mostrar modal al presionar "Pagar"
payBtn?.addEventListener('click', () => {
  const cart = localStorage.getItem('cart');
  
  if (!cart) {
    alert("Tu carrito está vacío.");
    return;
  }

  try {
    const parsedCart = JSON.parse(cart);
    if (!Array.isArray(parsedCart) || parsedCart.length === 0) {
      alert("Tu carrito está vacío.");
      return;
    }

    // Guardamos el carrito en el campo oculto del modal
    document.getElementById('cart-data-input').value = JSON.stringify(parsedCart);
    paymentModal.classList.remove('hidden'); // Mostrar modal
  } catch (e) {
    console.error("Error al procesar el carrito:", e);
    alert("Error al leer los datos del carrito.");
  }
});

// Cancelar pago
cancelPayment?.addEventListener('click', () => {
  paymentModal.classList.add('hidden');
});

// Enviar formulario (confirmar pago)
paymentForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(paymentForm);

  try {
    const res = await fetch(paymentForm.action, { method: 'POST', body: formData });
    const text = await res.text();

    console.log("Respuesta del servidor:", text);

    // Limpiar carrito y cerrar modal
    localStorage.removeItem('cart');
    paymentModal.classList.add('hidden');

    alert("✅ Venta realizada correctamente.");
    window.location.href = 'index.php?view=nueva_venta';
  } catch (err) {
    console.error("Error al procesar el pago:", err);
    alert("❌ Hubo un error al procesar la venta.");
  }
});








    // ======================
    // MODAL DETALLE DE VENTA
    // ======================
    window.verDetalleVenta = function(idVenta) {
    console.log("ID de venta recibido:", idVenta);
    fetch(`scripts/ventas_detalles.php?id_venta=${idVenta}`)
    .then(res => res.json())
    
    .then(data => {
        const contenedor = document.getElementById('venta-detalles');
        contenedor.innerHTML = '';

        if (!data || !data.productos || data.productos.length === 0) {
            contenedor.innerHTML = '<p class="text-center text-gray-500">No hay detalles para esta venta.</p>';
            return;
        }

        // 🧍 Cliente
        const clienteHTML = `
            <div class="mb-3">
                <p class="font-semibold">Cliente: 
                    <span class="font-normal">${data.cliente}</span>
                </p>
            </div>
        `;

        // 🧾 Productos
        const productosHTML = data.productos.map(item => `
            <div class="border-b py-2">
                <strong>${item.nombre}</strong><br>
                Cantidad: ${item.cantidad} | Precio: $${parseFloat(item.precio_unitario).toFixed(2)}
            </div>
        `).join('');

        // 💰 Total
        const totalHTML = `
            <div class="mt-3 font-bold text-right text-lg">
                Total: $${parseFloat(data.total).toFixed(2)}
            </div>
        `;

        contenedor.innerHTML = clienteHTML + productosHTML + totalHTML;

        // Mostrar modal
        document.getElementById('venta-modal').classList.remove('hidden');
    })
    .catch(err => {
        console.error(err);
        alert("❌ Error al obtener los detalles de la venta");
    });
};

// 🧩 Cerrar modal (solo definir si aún no existe)
if (typeof window._ventaModalListenerAttached === 'undefined') {
    const closeVentaModalBtn = document.getElementById('close-venta-modal');
    if (closeVentaModalBtn) {
        closeVentaModalBtn.addEventListener('click', () => {
            document.getElementById('venta-modal').classList.add('hidden');
        });
    }
    window._ventaModalListenerAttached = true;
}

})();