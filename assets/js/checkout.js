import { createOrder, getProducts } from "./data-service.js";
import { clearCart, getCart, getCurrentUser, saveCart } from "./state.js";
import { createEmptyState, formatPrice, refreshLayout, setFieldErrors } from "./ui.js";

export async function initCheckoutPage() {
  const form = document.getElementById("checkout-form");
  if (!form) {
    return;
  }

  const summaryRoot = document.getElementById("checkout-summary-items");
  const totalRoot = document.getElementById("checkout-total");
  const successRoot = document.getElementById("checkout-success");
  const products = await getProducts();
  const productMap = new Map(products.map((product) => [product.id, product]));
  const currentUser = getCurrentUser();
  const nameField = form.elements.namedItem("name");
  const emailField = form.elements.namedItem("email");

  if (currentUser) {
    if (nameField) {
      nameField.value = currentUser.name;
    }
    if (emailField) {
      emailField.value = currentUser.email;
    }
  }

  function getValidCart() {
    const validCart = getCart()
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

    saveCart(validCart);
    return validCart;
  }

  function renderSummary() {
    const cart = getValidCart();

    if (!cart.length) {
      summaryRoot.innerHTML = createEmptyState(
        "Нет товаров для оформления",
        "Сначала добавьте растения в корзину.",
        "catalog.html",
        "Перейти в каталог"
      );
      totalRoot.textContent = formatPrice(0);
      Array.from(form.elements).forEach((element) => {
        element.disabled = true;
      });
      return;
    }

    summaryRoot.innerHTML = cart.map((item) => {
      const product = productMap.get(item.product_id);
      if (!product) {
        return "";
      }

      return `
        <div class="summary-item">
          <strong>${product.name}</strong>
          <div class="summary-row">
            <span>${item.quantity} шт. × ${formatPrice(product.price)}</span>
            <strong>${formatPrice(product.price * item.quantity)}</strong>
          </div>
        </div>
      `;
    }).join("");

    const total = cart.reduce((sum, item) => {
      const product = productMap.get(item.product_id);
      return sum + ((product?.price || 0) * item.quantity);
    }, 0);

    totalRoot.textContent = formatPrice(total);
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(form);
    const values = Object.fromEntries(formData.entries());
    const errors = validateCheckout(values);
    setFieldErrors(form, errors);

    const cart = getValidCart();

    if (Object.keys(errors).length || !cart.length) {
      return;
    }

    const items = cart.map((item) => {
      const product = productMap.get(item.product_id);
      return {
        product_id: item.product_id,
        quantity: item.quantity,
        price: product?.price || 0
      };
    });

    const totalAmount = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const order = await createOrder({
      userId: currentUser?.id || 0,
      items,
      totalAmount,
      customer: values
    });

    clearCart();
    refreshLayout();
    successRoot.classList.remove("hidden");
    successRoot.innerHTML = `
      <strong>Заказ №${order.id} оформлен.</strong>
      <p>Спасибо за покупку. Мы свяжемся с вами в ближайшее время для подтверждения деталей доставки.</p>
    `;
    form.reset();
    Array.from(form.elements).forEach((element) => {
      if (element.type !== "submit") {
        element.disabled = true;
      }
    });
    summaryRoot.innerHTML = createEmptyState(
      "Корзина очищена",
      "Заказ успешно создан. Вы можете вернуться в каталог и продолжить покупки.",
      "catalog.html",
      "Перейти в каталог"
    );
    totalRoot.textContent = formatPrice(0);
  });

  renderSummary();
}

function validateCheckout(values) {
  const errors = {};

  if (!values.name?.trim()) {
    errors.name = "Укажите имя.";
  }

  if (!values.phone?.trim() || values.phone.trim().length < 10) {
    errors.phone = "Укажите корректный телефон.";
  }

  if (!values.email?.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/i.test(values.email)) {
    errors.email = "Укажите корректный email.";
  }

  if (!values.address?.trim() || values.address.trim().length < 8) {
    errors.address = "Укажите адрес доставки.";
  }

  return errors;
}
