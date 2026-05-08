document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".quantity-input").forEach((input) => {
    input.addEventListener("input", () => normalizeQuantityInput(input));
    input.addEventListener("change", () => normalizeQuantityInput(input));
  });

  document.querySelectorAll("[data-cart-form]").forEach((form) => {
    const input = form.querySelector(".quantity-input");
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    let timer = null;
    const submitQuantity = async () => {
      normalizeQuantityInput(input);

      if (input.dataset.lastSubmitted === input.value) {
        return;
      }

      input.dataset.lastSubmitted = input.value;

      try {
        const payload = await submitCartForm(form);
        showToast(payload.message || "Корзина обновлена.", payload.type || "success");
        window.setTimeout(() => window.location.reload(), 350);
      } catch (error) {
        console.error("Cart quantity update failed", error);
        form.submit();
      }
    };

    const scheduleSubmit = () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(submitQuantity, 250);
    };

    input.dataset.lastSubmitted = input.value;
    input.addEventListener("input", scheduleSubmit);
    input.addEventListener("change", scheduleSubmit);

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      window.clearTimeout(timer);
      submitQuantity();
    });
  });
});

function normalizeQuantityInput(input) {
  const min = Number(input.min || 1);
  const max = Number(input.max || 9999);
  const value = Number(input.value || min);

  if (Number.isNaN(value)) {
    input.value = String(min);
    return;
  }

  if (value < min) {
    input.value = String(min);
  }

  if (value > max) {
    input.value = String(max);
  }
}
