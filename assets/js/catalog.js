document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("[data-catalog-form]");
  if (!form) {
    return;
  }

  const autoSubmitFields = form.querySelectorAll("select");
  autoSubmitFields.forEach((field) => {
    field.addEventListener("change", () => form.submit());
  });
});
