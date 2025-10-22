<<<<<<< Updated upstream
const discountBtn = document.getElementById('discount-btn');
const discountCard = document.getElementById('discount-card');
const closeDiscount = document.getElementById('close-discount');

  discountBtn.addEventListener('click', () => {
    discountCard.classList.toggle('hidden');
});

  closeDiscount.addEventListener('click', () => {
    discountCard.classList.add('hidden');
});

  // Cerrar al hacer clic fuera del card
  document.addEventListener('click', (e) => {
    if (!discountCard.contains(e.target) && !discountBtn.contains(e.target)) {
      discountCard.classList.add('hidden');
    }
});
=======
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
})();
>>>>>>> Stashed changes
