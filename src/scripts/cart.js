(() => {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  const addToCartBtns = document.querySelectorAll(".add-to-cart");
  const cartItemsContainer = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");

  // Mostrar mensaje cuando el carrito esté vacío
  function showEmptyMessage() {
    cartItemsContainer.innerHTML = `
      <div class="flex flex-col items-center justify-center text-gray-500 py-10">
        <span class="text-5xl mb-2">🛒</span>
        <p class="text-lg font-semibold">No hay productos en el carrito</p>
      </div>
    `;
  }

  // Renderizar el carrito
  function renderCart() {
    cartItemsContainer.innerHTML = "";

    if (cart.length === 0) {
      showEmptyMessage();
      subtotalEl.textContent = "$0.00";
      discountEl.textContent = "-$0.00";
      totalEl.textContent = "$0.00";
      localStorage.setItem("cart", JSON.stringify(cart));
      return;
    }

    let subtotal = 0;
    let totalDescuento = 0;

    cart.forEach((item, index) => {
      const precioConDescuento = item.price - (item.descuento || 0);
      const itemTotal = precioConDescuento * item.quantity;
      subtotal += item.price * item.quantity;
      totalDescuento += (item.descuento || 0) * item.quantity;

      const itemDiv = document.createElement("div");
      itemDiv.classList.add("flex", "flex-col", "gap-2", "border-b", "pb-2");

      itemDiv.innerHTML = `
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <img src="${item.img}" alt="${item.name}" class="w-12 h-12 object-cover rounded">
            <div>
              <p class="font-semibold">${item.name}</p>
              <p class="text-sm text-gray-500">$${item.price.toFixed(2)} c/u</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button class="decrease bg-gray-200 px-2 rounded">-</button>
            <span class="font-semibold">${item.quantity}</span>
            <button class="increase bg-gray-200 px-2 rounded">+</button>
          </div>
          <div class="text-right">
            <p class="font-semibold">$${itemTotal.toFixed(2)}</p>
            <button class="remove text-red-500 text-sm">Eliminar</button>
          </div>
        </div>

        <div class="flex gap-2">
          <select class="talla border rounded p-1">
            <option ${item.size === "S" ? "selected" : ""}>S</option>
            <option ${item.size === "M" ? "selected" : ""}>M</option>
            <option ${item.size === "L" ? "selected" : ""}>L</option>
            <option ${item.size === "XL" ? "selected" : ""}>XL</option>
          </select>
          <select class="color border rounded p-1">
            <option ${item.color === "Rojo" ? "selected" : ""}>Rojo</option>
            <option ${item.color === "Azul" ? "selected" : ""}>Azul</option>
            <option ${item.color === "Negro" ? "selected" : ""}>Negro</option>
            <option ${item.color === "Blanco" ? "selected" : ""}>Blanco</option>
          </select>
        </div>

        <div>
          <button class="discount-product text-blue-500 text-sm">💲 Descuento</button>
        </div>
      `;

      // Eventos
      itemDiv.querySelector(".increase").addEventListener("click", () => {
        item.quantity++;
        renderCart();
      });

      itemDiv.querySelector(".decrease").addEventListener("click", () => {
        if (item.quantity > 1) item.quantity--;
        else cart.splice(index, 1);
        renderCart();
      });

      itemDiv.querySelector(".remove").addEventListener("click", () => {
        if (confirm("¿Seguro que quieres eliminar este producto?")) {
          cart.splice(index, 1);
          renderCart();
        }
      });

      itemDiv.querySelector(".talla").addEventListener("change", (e) => {
        item.size = e.target.value;
      });

      itemDiv.querySelector(".color").addEventListener("change", (e) => {
        item.color = e.target.value;
      });

      itemDiv.querySelector(".discount-product").addEventListener("click", () => {
        const porcentaje = prompt("Ingrese el porcentaje de descuento (ej: 10 para 10%)");
        if (!porcentaje || isNaN(porcentaje) || porcentaje < 0 || porcentaje > 100) {
          alert("Porcentaje no válido. Debe ser un número entre 0 y 100.");
          return;
        }
        const descuento = (item.price * parseFloat(porcentaje)) / 100;
        item.descuento = descuento;
        renderCart();
      });

      cartItemsContainer.appendChild(itemDiv);
    });

    const total = subtotal - totalDescuento;

    subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    discountEl.textContent = `-$${totalDescuento.toFixed(2)}`;
    totalEl.textContent = `$${total.toFixed(2)}`;

    localStorage.setItem("cart", JSON.stringify(cart));
  }

  // Botón limpiar carrito
  clearCartBtn?.addEventListener("click", () => {
    if (confirm("¿Seguro que deseas vaciar el carrito?")) {
      cart = [];
      renderCart();
    }
  });

  // Botones de agregar al carrito
  addToCartBtns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const product = e.target.closest(".producto");
      const name = product.querySelector("h3").textContent;
      const price = parseFloat(product.querySelector(".font-bold").textContent.replace("$", ""));
      const img = product.querySelector("img").src;
      const cod = product.querySelector(".text-sm").textContent.replace("Código: ", "");

      const existingProduct = cart.find((item) => item.name === name);
      if (existingProduct) existingProduct.quantity++;
      else cart.push({
        name,
        cod_barras: cod,
        price,
        img,
        quantity: 1,
        size: "M",
        color: "Negro",
        descuento: 0,
      });

      renderCart();
    });
  });

  // Inicializar
  renderCart();
})();
