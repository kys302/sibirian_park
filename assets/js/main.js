document.addEventListener("DOMContentLoaded", () => {
  const toggleButton = document.querySelector("[data-menu-toggle]");
  const nav = document.querySelector("[data-site-nav]");

  toggleButton?.addEventListener("click", () => {
    const isOpen = nav?.classList.toggle("is-open");
    toggleButton.classList.toggle("is-open", Boolean(isOpen));
    toggleButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  document.querySelectorAll(".flash").forEach((flash) => {
    window.setTimeout(() => {
      flash.classList.add("is-hidden");
      window.setTimeout(() => flash.remove(), 250);
    }, 3500);
  });

  document.querySelectorAll("[data-auto-submit]").forEach((field) => {
    field.addEventListener("change", () => {
      const form = field.closest("form");
      form?.submit();
    });
  });

  initAddToCartForms();
  initCartSteppers();
});

function initAddToCartForms() {
  document.querySelectorAll("[data-add-to-cart-form]").forEach((form) => {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      const button = form.querySelector("[data-add-to-cart-button]");
      if (button instanceof HTMLButtonElement && button.disabled) {
        return;
      }

      const originalText = button?.textContent || "В корзину";
      if (button instanceof HTMLButtonElement) {
        button.disabled = true;
        button.textContent = "Добавляем...";
      }

      const formData = new FormData(form);

      try {
        const payload = await submitCartData(getFormActionUrl(form), formData);
        updateCartUi(payload);
        showToast(payload.message || "Корзина обновлена.", payload.type || "success");
      } catch (error) {
        console.error("Cart request failed", error);
        submitFormDataNormally(getFormActionUrl(form), formData);
      } finally {
        if (button instanceof HTMLButtonElement) {
          if (button.dataset.cartLocked === "true") {
            button.disabled = true;
          } else {
            button.disabled = false;
            button.textContent = originalText;
          }
        }
      }
    });
  });
}

async function submitCartForm(form) {
  return submitCartData(getFormActionUrl(form), new FormData(form));
}

function getFormActionUrl(form) {
  return form.getAttribute("action") || window.location.href;
}

async function submitCartData(url, formData) {
  const response = await fetch(url || window.location.href, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  const contentType = (response.headers.get("content-type") || "").toLowerCase();
  if (!contentType.includes("application/json")) {
    throw new Error("Cart action returned a non-JSON response");
  }

  return response.json();
}

function submitFormDataNormally(url, formData) {
  const fallbackForm = document.createElement("form");
  fallbackForm.method = "post";
  fallbackForm.action = url || window.location.href;
  fallbackForm.hidden = true;

  formData.forEach((value, key) => {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = key;
    input.value = String(value);
    fallbackForm.appendChild(input);
  });

  document.body.appendChild(fallbackForm);
  fallbackForm.submit();
}

function initCartSteppers() {
  document.querySelectorAll("[data-cart-product]").forEach((card) => {
    card.addEventListener("click", async (event) => {
      const stepButton = event.target.closest("[data-cart-step]");
      if (!(stepButton instanceof HTMLButtonElement)) {
        return;
      }

      const form = card.querySelector("[data-add-to-cart-form]");
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const productId = form.querySelector('input[name="product_id"]')?.value;
      const quantityNode = card.querySelector("[data-cart-in-cart]");
      const currentQuantity = Number(quantityNode?.textContent || 0);
      const nextQuantity = Math.max(0, currentQuantity + Number(stepButton.dataset.cartStep || 0));

      const formData = new FormData(form);
      formData.set("action", "cart_update");
      formData.set("product_id", productId || "");
      formData.set("quantity", String(nextQuantity));

      stepButton.disabled = true;
      try {
        const payload = await submitCartData(getFormActionUrl(form), formData);
        updateCartUi(payload);
      } catch (error) {
        console.error("Cart step failed", error);
        submitFormDataNormally(getFormActionUrl(form), formData);
      } finally {
        stepButton.disabled = false;
      }
    });
  });
}

function updateCartUi(payload) {
  document.querySelectorAll("[data-cart-count]").forEach((node) => {
    node.textContent = String(payload.cartCount ?? 0);
  });

  const product = payload.product;
  if (!product?.id) {
    return;
  }

  document.querySelectorAll(`[data-cart-product][data-product-id="${product.id}"]`).forEach((card) => {
    const quantityNode = card.querySelector("[data-cart-in-cart]");
    const availableNode = card.querySelector("[data-cart-available]");
    const button = card.querySelector("[data-add-to-cart-button]");
    const form = card.querySelector("[data-add-to-cart-form]");
    const control = card.querySelector("[data-cart-control]");
    const quantity = Number(product.quantity ?? 0);

    if (quantityNode) {
      quantityNode.textContent = String(quantity);
    }

    if (availableNode) {
      availableNode.textContent = String(product.available ?? 0);
    }

    if (button instanceof HTMLButtonElement) {
      button.disabled = Boolean(product.maxReached);
      button.textContent = product.maxReached ? "Лимит выбран" : "В корзину";
      button.dataset.cartLocked = product.maxReached ? "true" : "false";
    }

    if (form instanceof HTMLFormElement) {
      form.classList.toggle("is-hidden", quantity > 0);
    }

    if (control) {
      control.classList.toggle("is-visible", quantity > 0);
    }
  });
}

function showToast(message, type = "success") {
  let stack = document.querySelector("[data-toast-stack]");
  if (!stack) {
    stack = document.createElement("div");
    stack.className = "toast-stack";
    stack.setAttribute("data-toast-stack", "");
    stack.setAttribute("aria-live", "polite");
    document.body.appendChild(stack);
  }

  const toast = document.createElement("div");
  toast.className = `toast toast--${type === "error" ? "error" : "success"}`;
  toast.textContent = message;
  stack.appendChild(toast);

  requestAnimationFrame(() => toast.classList.add("is-visible"));

  window.setTimeout(() => {
    toast.classList.remove("is-visible");
    window.setTimeout(() => toast.remove(), 220);
  }, 2600);
}
