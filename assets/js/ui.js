import { getCart, getCartCount, getCurrentUser } from "./state.js";

export const DEFAULT_CATEGORY_IMAGE = "assets/images/products/category-placeholder.svg";

export const CATEGORY_IMAGE_CHOICES = [
  {
    id: "placeholder",
    label: "Нейтральная заглушка",
    image: DEFAULT_CATEGORY_IMAGE
  },
  {
    id: "fruit-trees",
    label: "Плодовые деревья",
    image: "assets/images/products/fruit-tree.svg"
  },
  {
    id: "fruit-shrubs",
    label: "Плодовые кустарники",
    image: "assets/images/products/fruit-shrub.svg"
  },
  {
    id: "ornamental-shrubs",
    label: "Декоративные кустарники",
    image: "assets/images/products/ornamental-shrub.svg"
  },
  {
    id: "conifers",
    label: "Хвойные растения",
    image: "assets/images/products/conifer.svg"
  },
  {
    id: "perennials",
    label: "Многолетние цветы",
    image: "assets/images/products/perennial.svg"
  }
];

const plantProfiles = {
  1: {
    type: "Плодовое растение",
    plantTypeLabel: "Плодовое дерево",
    care: "Умеренный уход",
    soil: "Плодородная, дренированная",
    watering: "Регулярный полив в сезон",
    light: "Солнечное место"
  },
  2: {
    type: "Плодовое растение",
    plantTypeLabel: "Плодовый кустарник",
    care: "Неприхотливый уход",
    soil: "Рыхлая, влагоёмкая",
    watering: "Умеренный полив",
    light: "Солнце или лёгкая полутень"
  },
  3: {
    type: "Декоративное растение",
    plantTypeLabel: "Декоративный кустарник",
    care: "Подходит для регулярного ухода",
    soil: "Питательная садовая почва",
    watering: "Регулярный без застоя",
    light: "Полутень или солнце"
  },
  4: {
    type: "Декоративное растение",
    plantTypeLabel: "Хвойное растение",
    care: "Лёгкий уход",
    soil: "Лёгкая, дренированная",
    watering: "Умеренный полив",
    light: "Солнце или полутень"
  },
  5: {
    type: "Декоративное растение",
    plantTypeLabel: "Многолетнее растение",
    care: "Умеренный уход",
    soil: "Плодородная садовая почва",
    watering: "Регулярный в период роста",
    light: "Солнце или полутень"
  }
};

export function formatPrice(value) {
  return `${new Intl.NumberFormat("ru-RU").format(value).replace(/\s/g, "\u00A0")}\u00A0₽`;
}

export function formatDate(value) {
  return new Intl.DateTimeFormat("ru-RU", {
    day: "2-digit",
    month: "long",
    year: "numeric"
  }).format(new Date(value));
}

export function getQueryParam(name) {
  return new URLSearchParams(window.location.search).get(name);
}

function getCartItemsLabel(count) {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) {
    return `${count} товар`;
  }

  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    return `${count} товара`;
  }

  return `${count} товаров`;
}

function shouldShowMobileCartDock() {
  const currentPath = window.location.pathname.split("/").pop() || "index.html";
  const blockedPages = new Set(["cart.html", "checkout.html"]);
  return document.body.dataset.page !== "admin" && !blockedPages.has(currentPath);
}

export function getCartQuantityForProduct(productId) {
  return getCart()
    .filter((item) => item.product_id === productId)
    .reduce((total, item) => total + item.quantity, 0);
}

export function getRemainingStock(product) {
  return Math.max((product?.stock || 0) - getCartQuantityForProduct(product.id), 0);
}

function clampPurchaseQuantity(value, maxQuantity) {
  if (maxQuantity <= 0) {
    return 1;
  }

  return Math.max(1, Math.min(value, maxQuantity));
}

function getPurchaseMeta(product, selectedQuantity = 1) {
  const inCart = getCartQuantityForProduct(product.id);
  const remaining = getRemainingStock(product);
  const normalizedQuantity = clampPurchaseQuantity(selectedQuantity, remaining);

  return {
    inCart,
    remaining,
    selectedQuantity: normalizedQuantity,
    canAdd: remaining > 0
  };
}

function getRemainingStockLabel(remaining) {
  if (remaining <= 0) {
    return "Весь доступный остаток уже в корзине";
  }

  if (remaining === 1) {
    return "Можно добавить ещё 1 шт.";
  }

  return `Можно добавить до ${remaining} шт.`;
}

export function renderHeader() {
  const headerRoot = document.querySelector("[data-site-header]");
  if (!headerRoot) {
    return;
  }

  const user = getCurrentUser();
  const cartCount = getCartCount();
  const currentPath = window.location.pathname.split("/").pop() || "index.html";
  const isAdminPage = document.body.dataset.page === "admin";
  const navItems = [
    { href: "index.html", label: "Главная", hidden: isAdminPage },
    { href: "catalog.html", label: "Каталог", hidden: isAdminPage },
    { href: "profile.html", label: "Кабинет", hidden: !user || isAdminPage },
    { href: "admin.html", label: "Управление", hidden: user?.role !== "admin" }
  ].filter((item) => !item.hidden);

  headerRoot.innerHTML = `
    <header class="site-header ${isAdminPage ? "site-header--admin" : ""}">
      <div class="container site-header-inner">
        <a class="brand" href="${isAdminPage ? "admin.html" : "index.html"}" aria-label="Сибирский парк">
          <span class="brand-copy">
            <span class="brand-title">Сибирский парк</span>
            <span class="brand-subtitle">${isAdminPage ? "служебный раздел" : "магазин и питомник растений"}</span>
          </span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false" data-menu-toggle>
          <span></span>
          <span></span>
          <span></span>
        </button>

        <nav class="site-nav" data-site-nav>
          ${navItems.map((item) => `
            <a href="${item.href}" class="${item.href === currentPath ? "is-active" : ""}">${item.label}</a>
          `).join("")}
          <div class="site-nav-mobile-meta">
            ${!isAdminPage ? `
              <a class="site-nav-service-link" href="cart.html">
                <span>Корзина</span>
                <strong>${getCartItemsLabel(cartCount)}</strong>
              </a>
            ` : ""}
            ${user ? `
              <div class="site-nav-mobile-user">
                <strong>${user.name}</strong>
                <span>${isAdminPage ? "Панель управления" : "Личный кабинет"}</span>
              </div>
              <button class="btn btn-secondary btn-small" type="button" data-logout-button>Выйти</button>
            ` : `
              <a class="btn btn-secondary btn-small" href="login.html">Войти</a>
            `}
          </div>
        </nav>

        <div class="header-actions">
          ${!isAdminPage ? `<a class="cart-pill" href="cart.html"><span>Корзина</span><span class="cart-pill__count" data-cart-count>${cartCount}</span></a>` : ""}
          ${user ? `
            <div class="header-user">
              <strong>${user.name}</strong>
              <span>${isAdminPage ? "Панель управления" : "Личный кабинет"}</span>
            </div>
            <button class="btn btn-secondary btn-small" type="button" data-logout-button>Выйти</button>
          ` : `
            <a class="btn btn-secondary btn-small" href="login.html">Войти</a>
          `}
        </div>
      </div>
    </header>
  `;

  const toggleButton = headerRoot.querySelector("[data-menu-toggle]");
  const nav = headerRoot.querySelector("[data-site-nav]");
  toggleButton?.addEventListener("click", () => {
    const isOpen = nav?.classList.toggle("is-open");
    toggleButton.classList.toggle("is-open", Boolean(isOpen));
    toggleButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  nav?.querySelectorAll("a, button").forEach((link) => {
    link.addEventListener("click", () => {
      nav.classList.remove("is-open");
      toggleButton?.classList.remove("is-open");
      toggleButton?.setAttribute("aria-expanded", "false");
    });
  });
}

export function renderMobileCartDock() {
  document.querySelector("[data-mobile-cart-dock-root]")?.remove();
  document.body.classList.remove("has-mobile-cart-dock");

  const cartCount = getCartCount();
  if (!shouldShowMobileCartDock() || cartCount <= 0) {
    return;
  }

  const dock = document.createElement("div");
  dock.className = "mobile-cart-dock";
  dock.dataset.mobileCartDockRoot = "";
  dock.innerHTML = `
    <a class="mobile-cart-dock__link" href="cart.html" data-mobile-cart-dock>
      <span class="mobile-cart-dock__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
          <path d="M3 5h2.4l1.4 8.1a1 1 0 0 0 1 .9h8.7a1 1 0 0 0 1-.8l1.6-5.9H7.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
          <circle cx="10" cy="18" r="1.5" fill="currentColor"></circle>
          <circle cx="17" cy="18" r="1.5" fill="currentColor"></circle>
        </svg>
      </span>
      <span class="mobile-cart-dock__copy">
        <strong>В корзине ${getCartItemsLabel(cartCount)}</strong>
        <span>Быстрый переход к заказу</span>
      </span>
      <span class="mobile-cart-dock__badge" data-mobile-cart-count>${cartCount}</span>
    </a>
  `;

  document.body.append(dock);
  document.body.classList.add("has-mobile-cart-dock");
}

export function syncPurchaseControls() {
  document.querySelectorAll("[data-purchase-panel]").forEach((panel) => {
    const productId = Number(panel.dataset.productId);
    const stock = Number(panel.dataset.stock) || 0;
    const panelMode = panel.dataset.panelMode || "default";
    const inCart = getCartQuantityForProduct(productId);
    const selectedQuantity = Number(
      panel.querySelector("[data-quantity-value]")?.textContent
      || panel.querySelector("[data-quantity-picker]")?.dataset.selectedQuantity
      || 1
    );
    const product = { id: productId, stock };
    const { remaining, selectedQuantity: normalizedQuantity, canAdd } = getPurchaseMeta(product, selectedQuantity);

    const picker = panel.querySelector("[data-quantity-picker]");
    const valueNode = panel.querySelector("[data-quantity-value]");
    const decreaseButton = panel.querySelector('[data-quantity-action="decrease"]');
    const increaseButton = panel.querySelector('[data-quantity-action="increase"]');
    const addButton = panel.querySelector("[data-add-to-cart]");
    const hintNode = panel.querySelector("[data-purchase-hint]");
    const cartState = panel.querySelector("[data-catalog-cart-state]");

    if (panelMode === "catalog") {
      if (picker) {
        picker.dataset.selectedQuantity = String(Math.max(inCart, 1));
      }

      if (valueNode) {
        valueNode.textContent = String(Math.max(inCart, 1));
      }

      if (cartState) {
        cartState.classList.toggle("hidden", inCart <= 0);
      }

      if (addButton) {
        addButton.classList.toggle("hidden", inCart > 0);
        addButton.disabled = !canAdd;
        addButton.textContent = canAdd
          ? (addButton.dataset.addLabel || "В корзину")
          : (addButton.dataset.disabledLabel || "Максимум в корзине");
      }

      if (decreaseButton) {
        decreaseButton.disabled = inCart <= 0;
      }

      if (increaseButton) {
        increaseButton.disabled = inCart <= 0 || inCart >= stock;
      }

      return;
    }

    if (picker) {
      picker.dataset.selectedQuantity = String(normalizedQuantity);
    }

    if (valueNode) {
      valueNode.textContent = String(normalizedQuantity);
    }

    if (decreaseButton) {
      decreaseButton.disabled = !canAdd || normalizedQuantity <= 1;
    }

    if (increaseButton) {
      increaseButton.disabled = !canAdd || normalizedQuantity >= remaining;
    }

    if (addButton) {
      const defaultLabel = addButton.dataset.addLabel || "В корзину";
      const disabledLabel = addButton.dataset.disabledLabel || "Максимум в корзине";
      addButton.disabled = !canAdd;
      addButton.textContent = canAdd ? defaultLabel : disabledLabel;
    }

    if (hintNode) {
      hintNode.textContent = getRemainingStockLabel(remaining);
    }
  });
}

export function renderFooter() {
  const footerRoot = document.querySelector("[data-site-footer]");
  if (!footerRoot) {
    return;
  }

  footerRoot.innerHTML = `
    <footer class="site-footer">
      <div class="container site-footer-inner">
        <div class="footer-column footer-caption">
          <h3>Сибирский парк</h3>
          <p>Плодовые и декоративные растения для сада, дачи и ландшафтных посадок в Новосибирске.</p>
          <p class="footer-copy">© 2026 Сибирский парк. Все права защищены.</p>
        </div>

        <div class="footer-column">
          <h4>Контакты</h4>
          <p><a href="tel:+73830000000">383 000 00 00</a></p>
          <p><a href="mailto:info@sibirsky-park.ru">info@sibirsky-park.ru</a></p>
          <p>Новосибирск</p>
        </div>

        <div class="footer-column">
          <h4>Режим работы</h4>
          <p>Ежедневно с 09:00 до 19:00</p>
          <p>Приём заказов на сайте — круглосуточно</p>
        </div>

        <div class="footer-column">
          <h4>Навигация</h4>
          <div class="footer-links">
            <a href="index.html">Главная</a>
            <a href="catalog.html">Каталог</a>
            <a href="cart.html">Корзина</a>
            <a href="profile.html">Личный кабинет</a>
          </div>
        </div>
      </div>
    </footer>
  `;
}

export function refreshLayout() {
  renderHeader();
  renderMobileCartDock();
  renderFooter();
  syncPurchaseControls();
}

export function getPlantProfile(product) {
  return plantProfiles[product.category_id] || {
    type: "Садовое растение",
    plantTypeLabel: "Садовое растение",
    care: "Умеренный уход",
    soil: "Садовая почва",
    watering: "Умеренный полив",
    light: "Светлое место"
  };
}

export function getCategoryImage(category) {
  return category?.image || DEFAULT_CATEGORY_IMAGE;
}

export function createProductCard(product, categoryName = "") {
  const isAvailable = product.stock > 0;
  const profile = getPlantProfile(product);
  const inCart = getCartQuantityForProduct(product.id);

  return `
    <article class="product-card card">
      <a class="product-card-image" href="product.html?id=${product.id}">
        <img src="${product.image}" alt="${product.name}" loading="lazy" decoding="async">
      </a>
      <div class="product-card-head">
        <div>
          <p class="product-category">${categoryName}</p>
          <h3><a href="product.html?id=${product.id}">${product.name}</a></h3>
        </div>
        <span class="price">${formatPrice(product.price)}</span>
      </div>
      <p>${product.description}</p>
      <div class="product-card-tags">
        <span class="product-card-chip">${profile.plantTypeLabel}</span>
        <span class="product-card-chip product-card-chip--soft">${profile.light}</span>
      </div>
      <div class="product-card-footer">
        <span class="stock">${isAvailable ? `В наличии: ${product.stock} шт.` : "Нет в наличии"}</span>
        ${isAvailable
          ? `<div class="catalog-purchase" data-purchase-panel data-panel-mode="catalog" data-product-id="${product.id}" data-stock="${product.stock}">
              <button class="btn btn-primary btn-small ${inCart > 0 ? "hidden" : ""}" type="button" data-add-to-cart="${product.id}" data-add-label="В корзину" data-disabled-label="Максимум в корзине">В корзину</button>
              <div class="catalog-cart-state ${inCart > 0 ? "" : "hidden"}" data-catalog-cart-state>
                <div class="quantity-control quantity-control--picker quantity-control--compact" data-quantity-picker data-selected-quantity="${Math.max(inCart, 1)}">
                  <button type="button" data-quantity-action="decrease" aria-label="Уменьшить количество">-</button>
                  <span class="quantity-control__value" data-quantity-value>${Math.max(inCart, 1)}</span>
                  <button type="button" data-quantity-action="increase" aria-label="Увеличить количество" ${inCart >= product.stock ? "disabled" : ""}>+</button>
                </div>
              </div>
            </div>`
          : `<button class="btn btn-primary btn-small" type="button" disabled>Нет в наличии</button>`}
      </div>
    </article>
  `;
}

export function createCategoryCard(category) {
  const image = getCategoryImage(category);

  return `
    <a class="category-card category-card--${category.slug} card" href="catalog.html?category=${category.id}">
      <div class="category-visual">
        <img src="${image}" alt="${category.name}">
      </div>
      <h3>${category.name}</h3>
      <p>${category.description}</p>
    </a>
  `;
}

export function createEmptyState(title, text, actionHref, actionLabel) {
  return `
    <div class="empty-state">
      <h3>${title}</h3>
      <p>${text}</p>
      ${actionHref && actionLabel ? `<a class="btn btn-secondary" href="${actionHref}">${actionLabel}</a>` : ""}
    </div>
  `;
}

export function setFieldErrors(form, errors) {
  form.querySelectorAll("[data-error-for]").forEach((node) => {
    node.textContent = "";
  });

  Object.entries(errors).forEach(([field, message]) => {
    const target = form.querySelector(`[data-error-for="${field}"]`);
    if (target) {
      target.textContent = message;
    }
  });
}

export function showToast(message) {
  let stack = document.querySelector(".toast-stack");
  if (!stack) {
    stack = document.createElement("div");
    stack.className = "toast-stack";
    document.body.append(stack);
  }

  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = message;
  stack.append(toast);

  window.setTimeout(() => {
    toast.remove();
    if (!stack?.children.length) {
      stack.remove();
    }
  }, 2800);
}

export function pulseMobileCartDock() {
  const dock = document.querySelector("[data-mobile-cart-dock]");
  if (!dock) {
    return;
  }

  dock.classList.remove("is-pulsing");
  window.requestAnimationFrame(() => {
    dock.classList.add("is-pulsing");
  });

  window.setTimeout(() => {
    dock.classList.remove("is-pulsing");
  }, 550);
}
