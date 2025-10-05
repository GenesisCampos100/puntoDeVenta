(() => {
  const discountBtn = document.getElementById("discount-btn");
  const discountCard = document.getElementById("discount-card");
  const closeDiscount = document.getElementById("close-discount");
  const discountInput = document.getElementById("discount-input");
  const applyDiscountBtn = document.getElementById("apply-discount");

  const paymentCard = document.getElementById("payment-card");
  const closePayment = document.getElementById("close-payment");
  const paymentInputs = document.querySelectorAll(".payment-input");
  const totalCartEl = document.getElementById("total");
  const confirmPaymentBtn = document.getElementById("confirm-payment");

  let globalCart = JSON.parse(localStorage.getItem("cart")) || [];
  let currentProduct = null;
  let renderCartFn = null;

  discountBtn?.addEventListener("click", () => discountCard.classList.remove("hidden"));
  closeDiscount?.addEventListener("click", () => discountCard.classList.add("hidden"));

  document.addEventListener("openProductDiscount", e => {
    currentProduct = e.detail.item;
    renderCartFn = e.detail.renderCart;
    discountCard.classList.remove("hidden");
    discountInput.value = "";
  });

  applyDiscountBtn?.addEventListener("click", () => {
    const val = discountInput.value.trim();
    if (!val) return alert("Ingrese un descuento válido");

    if (val.endsWith("%")) {
      const perc = parseFloat(val);
      if (isNaN(perc) || perc < 0 || perc > 100) return alert("Porcentaje inválido");
      if (currentProduct) currentProduct.price -= (currentProduct.price * perc) / 100;
      else globalCart.forEach(i => i.price -= (i.price * perc) / 100);
    } else {
      const num = parseFloat(val);
      if (isNaN(num) || num < 0) return alert("Valor inválido");
      if (currentProduct) currentProduct.price -= num;
      else globalCart.forEach(i => i.price -= num);
    }

    renderCartFn?.();
    localStorage.setItem("cart", JSON.stringify(globalCart));
    discountCard.classList.add("hidden");
  });

  const calculateTotal = () => {
    globalCart = JSON.parse(localStorage.getItem("cart")) || [];
    const subtotal = globalCart.reduce((acc, i) => acc + i.price * i.quantity, 0);
    totalCartEl.textContent = `$${subtotal.toFixed(2)}`;
    return subtotal;
  };

  document.addEventListener("openPaymentModal", () => {
    globalCart = JSON.parse(localStorage.getItem("cart")) || [];
    if (!globalCart.length) return alert("El carrito está vacío");
    paymentCard.classList.remove("hidden");
    calculateTotal();
    paymentInputs.forEach(input => input.value = 0);
  });

  closePayment?.addEventListener("click", () => paymentCard.classList.add("hidden"));

  confirmPaymentBtn?.addEventListener("click", () => {
    const payments = {};
    let totalPayment = 0;
    paymentInputs.forEach(input => {
      const val = parseFloat(input.value) || 0;
      payments[input.dataset.method] = val;
      totalPayment += val;
    });
    const totalCart = calculateTotal();
    if (totalPayment < totalCart) return alert("El total pagado es menor al total del carrito");

    document.getElementById("cart-data").value = JSON.stringify(globalCart);
    document.getElementById("payments-data").value = JSON.stringify(payments);
    document.getElementById("submit-checkout")?.click();
  });
})();
