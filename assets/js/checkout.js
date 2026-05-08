document.addEventListener("DOMContentLoaded", () => {
  const phoneInput = document.getElementById("checkout-phone");
  if (!phoneInput) {
    return;
  }

  phoneInput.addEventListener("input", () => {
    const numbers = phoneInput.value.replace(/\D+/g, "").slice(0, 11);
    let normalized = numbers;

    if (normalized.startsWith("8")) {
      normalized = `7${normalized.slice(1)}`;
    }

    if (!normalized.startsWith("7")) {
      normalized = `7${normalized}`;
    }

    const parts = [
      normalized.slice(1, 4),
      normalized.slice(4, 7),
      normalized.slice(7, 9),
      normalized.slice(9, 11),
    ];

    let formatted = "+7";
    if (parts[0]) formatted += ` (${parts[0]}`;
    if (parts[0]?.length === 3) formatted += ")";
    if (parts[1]) formatted += ` ${parts[1]}`;
    if (parts[2]) formatted += `-${parts[2]}`;
    if (parts[3]) formatted += `-${parts[3]}`;

    phoneInput.value = formatted;
  });
});
