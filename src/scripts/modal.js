// modal.js
(() => {
  const discountCard = document.getElementById("discount-card");
  const discountInput = document.getElementById("discount-input");
  const applyDiscountBtn = document.getElementById("apply-discount");
  const closeDiscount = document.getElementById("close-discount");

  let currentItem = null; // null = descuento total, objeto = descuento producto
  let onApplyCallback = null; // callback que se ejecuta al aplicar descuento

  // Abrir modal con opción de descuento
  window.openDiscountModal = (item = null, callback) => {
    currentItem = item;
    onApplyCallback = callback;
    discountInput.value = "";
    discountCard.classList.remove("hidden");
    discountInput.focus();
  };

  // Cerrar modal
  const closeModal = () => {
    discountCard.classList.add("hidden");
    currentItem = null;
    discountInput.value = "";
    onApplyCallback = null;
  };

  closeDiscount?.addEventListener("click", closeModal);

  // Cerrar al hacer clic fuera del modal
  document.addEventListener("click", (e) => {
    if (!discountCard.contains(e.target) && !e.target.matches(".discount-product, #discount-btn")) {
      closeModal();
    }
  });

  // Aplicar descuento
  applyDiscountBtn?.addEventListener("click", () => {
    const value = discountInput.value.trim();
    if (!value) return;

    if (onApplyCallback) {
      onApplyCallback(value, currentItem);
    }

    closeModal();
  });
})();
