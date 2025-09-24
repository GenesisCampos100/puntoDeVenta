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