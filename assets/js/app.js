import { getProductById } from "./data-service.js";
import { initAdminPage } from "./admin.js";
import { initLoginPage, initRegisterPage } from "./auth.js";
import { initCartPage } from "./cart.js";
import { initCatalogPage } from "./catalog.js";
import { initCheckoutPage } from "./checkout.js";
import { initHomePage } from "./home.js";
import { initProductPage } from "./product.js";
import { initProfilePage } from "./profile.js";
import { addToCart, getCart, logoutUser, removeFromCart, updateCartQuantity } from "./state.js";
import { pulseMobileCartDock, refreshLayout, showToast } from "./ui.js";

document.addEventListener("DOMContentLoaded", async () => {
  refreshLayout();
  attachGlobalEvents();

  try {
    switch (document.body.dataset.page) {
      case "home":
        await initHomePage();
        break;
      case "catalog":
        await initCatalogPage();
        break;
      case "product":
        await initProductPage();
        break;
      case "cart":
        await initCartPage();
        break;
      case "checkout":
        await initCheckoutPage();
        break;
      case "login":
        initLoginPage();
        break;
      case "register":
        initRegisterPage();
        break;
      case "profile":
        await initProfilePage();
        break;
      case "admin":
        await initAdminPage();
        break;
      default:
        break;
    }
  } catch (error) {
    console.error(error);
    showToast("Не удалось загрузить страницу. Обновите её или попробуйте снова чуть позже.");
  }
});

function attachGlobalEvents() {
  document.body.addEventListener("click", async (event) => {
    const quantityButton = event.target.closest("[data-quantity-action]");
    if (quantityButton) {
      const panel = quantityButton.closest("[data-purchase-panel]");
      if (!panel) {
        return;
      }

      const stock = Number(panel.dataset.stock) || 0;
      const productId = Number(panel.dataset.productId);
      const currentInCart = getCart().find((item) => item.product_id === productId)?.quantity || 0;
      const panelMode = panel.dataset.panelMode || "default";

      if (panelMode === "catalog") {
        if (quantityButton.dataset.quantityAction === "increase") {
          if (currentInCart >= stock) {
            showToast(`Доступно только ${stock} шт.`);
            return;
          }

          updateCartQuantity(productId, currentInCart + 1);
          refreshLayout();
          pulseMobileCartDock();
          return;
        }

        if (quantityButton.dataset.quantityAction === "decrease") {
          if (currentInCart <= 1) {
            removeFromCart(productId);
          } else {
            updateCartQuantity(productId, currentInCart - 1);
          }

          refreshLayout();
          return;
        }
      }

      const remaining = Math.max(stock - currentInCart, 0);
      const picker = panel.querySelector("[data-quantity-picker]");
      const valueNode = panel.querySelector("[data-quantity-value]");
      const currentValue = Number(picker?.dataset.selectedQuantity || valueNode?.textContent || 1);

      if (!picker || !valueNode || remaining <= 0) {
        refreshLayout();
        return;
      }

      let nextValue = currentValue;
      if (quantityButton.dataset.quantityAction === "increase") {
        nextValue = Math.min(currentValue + 1, remaining);
      }

      if (quantityButton.dataset.quantityAction === "decrease") {
        nextValue = Math.max(currentValue - 1, 1);
      }

      picker.dataset.selectedQuantity = String(nextValue);
      valueNode.textContent = String(nextValue);
      refreshLayout();

      if (quantityButton.dataset.quantityAction === "increase" && currentValue >= remaining) {
        showToast(`Доступно для добавления только ${remaining} шт.`);
      }
      return;
    }

    const addButton = event.target.closest("[data-add-to-cart]");
    if (addButton) {
      const productId = Number(addButton.dataset.addToCart);
      const product = await getProductById(productId);
      if (!product) {
        showToast("Товар больше недоступен.");
        return;
      }

      if (product.stock <= 0) {
        showToast("На данный момент товар отсутствует в наличии.");
        return;
      }

      const currentItem = getCart().find((item) => item.product_id === productId);
      const currentQuantity = currentItem?.quantity || 0;
      const purchasePanel = addButton.closest("[data-purchase-panel]");
      const selectedQuantity = Number(
        purchasePanel?.querySelector("[data-quantity-picker]")?.dataset.selectedQuantity
        || purchasePanel?.querySelector("[data-quantity-value]")?.textContent
        || 1
      );
      const remaining = Math.max(product.stock - currentQuantity, 0);

      if (remaining <= 0) {
        refreshLayout();
        showToast("В корзине уже находится весь доступный остаток товара.");
        return;
      }

      if (selectedQuantity > remaining) {
        refreshLayout();
        showToast(`Можно добавить ещё только ${remaining} шт.`);
        return;
      }

      addToCart(productId, selectedQuantity);
      refreshLayout();
      pulseMobileCartDock();
      showToast(`${product.name} добавлен в корзину: ${selectedQuantity} шт.`);
      return;
    }

    const logoutButton = event.target.closest("[data-logout-button]");
    if (logoutButton) {
      logoutUser();
      refreshLayout();
      if (window.location.pathname.endsWith("profile.html") || window.location.pathname.endsWith("admin.html")) {
        window.location.href = "index.html";
      } else {
        showToast("Вы вышли из аккаунта.");
      }
    }
  });
}
