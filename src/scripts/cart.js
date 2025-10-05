document.addEventListener("DOMContentLoaded", () => {
  const cartItemsContainer = document.getElementById("cart-items");
  const subtotalEl = document.getElementById("subtotal");
  const discountEl = document.getElementById("discount");
  const totalEl = document.getElementById("total");
  const clearCartBtn = document.getElementById("clear-cart");
  const addToCartBtns = document.querySelectorAll(".add-to-cart");

  function getCart() {
    return JSON.parse(localStorage.getItem("cart")) || [];
  }

  function setCart(cart) {
    localStorage.setItem("cart", JSON.stringify(cart));
    document.dispatchEvent(new Event("renderCart"));
  }

  function showEmptyMessage() {
    cartItemsContainer.innerHTML = `
      <div class="flex flex-col items-center justify-center text-gray-500 py-10">
        <span class="text-5xl mb-2">📦</span>
        <p class="text-lg font-semibold">No hay productos</p>
      </div>`;
    subtotalEl.textContent = "$0.00";
    discountEl.textContent = "$0.00";
    totalEl.textContent = "$0.00";
  }

  function renderCart() {
    const cart = getCart();
    if (!cart.length) return showEmptyMessage();

    cartItemsContainer.innerHTML = "";
    let subtotal = 0;

    cart.forEach((item, index) => {
      const itemTotal = item.price * item.quantity;
      subtotal += itemTotal;

      const itemDiv = document.createElement("div");
      itemDiv.className = "flex flex-col gap-2 border-b pb-2";
      itemDiv.innerHTML = `
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <img src="${item.img}" alt="${item.name}" class="w-12 h-12 object-cover rounded">
            <div>
              <p class="font-semibold">${item.name}</p>
              <p class="text-sm text-gray-500">$${item.price.toFixed(2)}</p>
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
          <div class="mt-2">
            <button class="discount-product text-blue-500 text-sm">💲 Descuento</button>
          </div>
        </div>`;

      itemDiv.querySelector(".increase").addEventListener("click", () => {
        item.quantity++;
        setCart(cart);
        renderCart();
      });
      itemDiv.querySelector(".decrease").addEventListener("click", () => {
        if (item.quantity > 1) item.quantity--;
        else cart.splice(index, 1);
        setCart(cart);
        renderCart();
      });
      itemDiv.querySelector(".remove").addEventListener("click", () => {
        cart.splice(index, 1);
        setCart(cart);
        renderCart();
      });
      itemDiv.querySelector(".discount-product")?.addEventListener("click", () => {
        const event = new CustomEvent("openProductDiscount", { detail: { item, renderCart } });
        document.dispatchEvent(event);
      });

      cartItemsContainer.appendChild(itemDiv);
    });

    subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    discountEl.textContent = "$0.00";
    totalEl.textContent = `$${subtotal.toFixed(2)}`;
  }

  clearCartBtn?.addEventListener("click", () => {
    setCart([]);
    renderCart();
  });

  addToCartBtns.forEach(btn => {
    btn.addEventListener("click", e => {
      const product = e.target.closest(".producto");
      if (!product) return;

      const name = product.querySelector("h3").textContent;
      const price = parseFloat(product.querySelector("p.font-bold").textContent.replace("$", ""));
      const img = product.querySelector("img").src;
      const cart = getCart();
      const existing = cart.find(i => i.name === name && i.size === "M" && i.color === "Negro");

      if (existing) existing.quantity++;
      else cart.push({ name, price, img, quantity: 1, size: "M", color: "Negro" });

      setCart(cart);
      renderCart();
    });
  });

  renderCart();
  document.addEventListener("renderCart", renderCart);
});
