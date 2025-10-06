const buttons = document.querySelectorAll(".category-btn");
  const products = document.querySelectorAll(".producto");

  buttons.forEach(btn => {
    btn.addEventListener("click", () => {
      const category = btn.getAttribute("data-category");

      // reset estilo botones
      buttons.forEach(b => b.classList.remove("bg-red-700"));
      buttons.forEach(b => b.classList.add("bg-red-500"));
      
      // marcar activo
      btn.classList.remove("bg-red-500");
      btn.classList.add("bg-red-700");

      products.forEach(product => {
        if (category === "all" || product.getAttribute("data-category") === category) {
          product.classList.remove("hidden");
        } else {
          product.classList.add("hidden");
        }
      });
    });
  });