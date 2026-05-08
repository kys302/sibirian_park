document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("form[novalidate]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        form.querySelectorAll(":invalid").forEach((field) => field.classList.add("is-invalid"));
      }
    });

    form.querySelectorAll("input, textarea, select").forEach((field) => {
      field.addEventListener("input", () => field.classList.remove("is-invalid"));
      field.addEventListener("change", () => field.classList.remove("is-invalid"));
    });
  });
});
