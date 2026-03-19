import { getCategories, getProductById, getRelatedProducts } from "./data-service.js";
import { createEmptyState, createProductCard, formatPrice, getPlantProfile, getQueryParam, getRemainingStock, syncPurchaseControls } from "./ui.js";

export async function initProductPage() {
  const root = document.getElementById("product-content");
  if (!root) {
    return;
  }

  const productId = Number(getQueryParam("id"));
  const product = await getProductById(productId);

  if (!product) {
    root.innerHTML = `
      <div class="product-not-found card">
        <h2>Товар не найден</h2>
        <p>Возможно, позиция была снята с продажи или ссылка устарела.</p>
        <a class="btn btn-secondary" href="catalog.html">Вернуться в каталог</a>
      </div>
    `;
    return;
  }

  const [categories, relatedProducts] = await Promise.all([
    getCategories(),
    getRelatedProducts(product)
  ]);

  const categoryMap = new Map(categories.map((category) => [category.id, category]));
  const category = categoryMap.get(product.category_id) || {
    id: 0,
    name: "Категория"
  };
  const relatedRoot = document.getElementById("related-products");
  const breadcrumbs = document.getElementById("product-breadcrumbs");
  const isAvailable = product.stock > 0;
  const availabilityClass = isAvailable ? "" : " status-badge--muted";
  const profile = getPlantProfile(product);
  const remainingStock = getRemainingStock(product);

  breadcrumbs.innerHTML = `
    <a href="index.html">Главная</a> /
    <a href="catalog.html">Каталог</a> /
    <a href="${category.id ? `catalog.html?category=${category.id}` : "catalog.html"}">${category.name}</a> /
    <span>${product.name}</span>
  `;

  root.innerHTML = `
    <div class="product-layout">
      <div class="product-media">
        <img src="${product.image}" alt="${product.name}">
      </div>
      <article class="product-main card">
        <p class="eyebrow">${category.name}</p>
        <h1>${product.name}</h1>
        <div class="product-meta">
          <span class="status-badge${availabilityClass}"><span class="status-dot"></span>${product.stock > 0 ? "В наличии" : "Нет в наличии"}</span>
          <span class="status-badge">Остаток: ${product.stock} шт.</span>
        </div>
        <p class="price">${formatPrice(product.price)}</p>
        <p class="product-description">${product.description}</p>
        <div class="product-purchase card" data-purchase-panel data-product-id="${product.id}" data-stock="${product.stock}">
          <div class="product-purchase__head">
            <div>
              <span class="stock">В наличии: ${product.stock} шт.</span>
              <p class="product-purchase__hint" data-purchase-hint>${remainingStock > 0 ? `Можно добавить до ${remainingStock} шт.` : "Весь доступный остаток уже находится в корзине"}</p>
            </div>
            <p class="product-purchase__price">${formatPrice(product.price)}</p>
          </div>
          <div class="product-purchase__actions">
            <div class="quantity-control quantity-control--picker quantity-control--large" data-quantity-picker data-selected-quantity="1">
              <button type="button" data-quantity-action="decrease" aria-label="Уменьшить количество" ${remainingStock > 0 ? "" : "disabled"}>-</button>
              <span class="quantity-control__value" data-quantity-value>1</span>
              <button type="button" data-quantity-action="increase" aria-label="Увеличить количество" ${remainingStock > 1 ? "" : "disabled"}>+</button>
            </div>
            <button class="btn btn-primary" type="button" data-add-to-cart="${product.id}" data-add-label="Добавить в корзину" data-disabled-label="Максимум в корзине" ${remainingStock > 0 ? "" : "disabled"}>${remainingStock > 0 ? "Добавить в корзину" : "Максимум в корзине"}</button>
            <a class="btn btn-secondary" href="${category.id ? `catalog.html?category=${category.id}` : "catalog.html"}">Вернуться в каталог</a>
          </div>
        </div>
        <div class="plant-info card">
          <div class="plant-info-head">
            <p class="eyebrow">Информация о растении</p>
            <h3>Условия выращивания</h3>
          </div>
          <div class="plant-info-grid">
            <div class="plant-info-item">
              <span>Тип растения</span>
              <strong>${profile.plantTypeLabel}</strong>
            </div>
            <div class="plant-info-item">
              <span>Уход</span>
              <strong>${profile.care}</strong>
            </div>
            <div class="plant-info-item">
              <span>Почва</span>
              <strong>${profile.soil}</strong>
            </div>
            <div class="plant-info-item">
              <span>Полив</span>
              <strong>${profile.watering}</strong>
            </div>
            <div class="plant-info-item">
              <span>Освещение</span>
              <strong>${profile.light}</strong>
            </div>
          </div>
        </div>
      </article>
    </div>
  `;

  if (relatedProducts.length) {
    relatedRoot.innerHTML = relatedProducts
      .map((item) => createProductCard(item, categoryMap.get(item.category_id)?.name))
      .join("");
  } else {
    relatedRoot.innerHTML = createEmptyState(
      "Похожие товары пока не найдены",
      "Для этой категории пока нет дополнительных предложений.",
      "catalog.html",
      "Перейти в каталог"
    );
  }

  syncPurchaseControls();
}
