import { getProducts } from "./data-service.js";
import { getCart, removeFromCart, saveCart, updateCartQuantity } from "./state.js";
import { createEmptyState, formatPrice, refreshLayout, showToast } from "./ui.js";

export async function initCartPage() {
  const cartRoot = document.getElementById("cart-items");
  if (!cartRoot) {
    return;
  }

  const products = await getProducts();
  const productMap = new Map(products.map((product) => [product.id, product]));
  const countNode = document.getElementById("cart-count");
  const totalNode = document.getElementById("cart-total");
  const checkoutLink = document.getElementById("checkout-link");

  function normalizeCart() {
    const normalizedCart = getCart()
      .map((item) => {
        const product = productMap.get(item.product_id);
        if (!product || product.stock <= 0) {
          return null;
        }

        return {
          product_id: item.product_id,
          quantity: Math.max(1, Math.min(item.quantity, product.stock))
        };
      })
      .filter(Boolean);

    saveCart(normalizedCart);
    return normalizedCart;
  }

  function render() {
    const cart = normalizeCart();

    if (!cart.length) {
      cartRoot.innerHTML = createEmptyState(
        "Корзина пока пуста",
        "Добавьте растения из каталога, чтобы перейти к оформлению заказа.",
        "catalog.html",
        "Перейти в каталог"
      );
      countNode.textContent = "0";
      totalNode.textContent = formatPrice(0);
      checkoutLink.classList.add("hidden");
      return;
    }

    checkoutLink.classList.remove("hidden");

    cartRoot.innerHTML = cart.map((item) => {
      const product = productMap.get(item.product_id);
      if (!product) {
        return "";
      }

      return `
        <article class="cart-item card">
          <div class="cart-item-media">
            <img src="${product.image}" alt="${product.name}" loading="lazy" decoding="async">
          </div>
          <div class="cart-item-body">
            <div class="cart-item-top">
              <div>
                <p class="product-category">${product.name}</p>
                <p class="muted-text">Цена за единицу: ${formatPrice(product.price)}</p>
              </div>
              <strong>${formatPrice(product.price * item.quantity)}</strong>
            </div>
            <div class="cart-item-actions">
              <div class="cart-item-quantity">
                <span class="stock">В наличии: ${product.stock} шт.</span>
                <span class="cart-item-hint">${item.quantity >= product.stock ? "Достигнут доступный остаток" : `Можно увеличить до ${product.stock} шт.`}</span>
                <div class="quantity-control quantity-control--picker quantity-control--cart">
                  <button type="button" data-cart-action="decrease" data-product-id="${product.id}" aria-label="Уменьшить количество">-</button>
                  <span class="quantity-control__value">${item.quantity}</span>
                  <button type="button" data-cart-action="increase" data-product-id="${product.id}" aria-label="Увеличить количество" ${item.quantity >= product.stock ? "disabled" : ""}>+</button>
                </div>
              </div>
              <button class="btn btn-danger btn-small cart-remove-button" type="button" data-remove-item="${product.id}">Удалить</button>
            </div>
          </div>
        </article>
      `;
    }).join("");

    const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalAmount = cart.reduce((sum, item) => {
      const product = productMap.get(item.product_id);
      return sum + ((product?.price || 0) * item.quantity);
    }, 0);

    countNode.textContent = String(totalCount);
    totalNode.textContent = formatPrice(totalAmount);
  }

  cartRoot.addEventListener("click", (event) => {
    const button = event.target.closest("[data-product-id], [data-remove-item]");
    if (!button) {
      return;
    }

    const productId = Number(button.dataset.productId || button.dataset.removeItem);
    if (!productId) {
      return;
    }

    if (button.dataset.removeItem) {
      removeFromCart(productId);
      refreshLayout();
      render();
      showToast("Товар удалён из корзины.");
      return;
    }

    const item = getCart().find((entry) => entry.product_id === productId);
    if (!item) {
      return;
    }

    const product = productMap.get(productId);
    if (!product) {
      removeFromCart(productId);
      refreshLayout();
      render();
      return;
    }

    if (button.dataset.cartAction === "increase") {
      if (item.quantity >= product.stock) {
        showToast(`Доступно только ${product.stock} шт.`);
        return;
      }
      updateCartQuantity(productId, item.quantity + 1);
    }

    if (button.dataset.cartAction === "decrease") {
      updateCartQuantity(productId, Math.max(1, item.quantity - 1));
    }

    refreshLayout();
    render();
  });

  render();
}
