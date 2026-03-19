import { getCategories, getProducts } from "./data-service.js";
import { createEmptyState, createProductCard, getQueryParam } from "./ui.js";

const INITIAL_LIMIT = 6;

export async function initCatalogPage() {
  const grid = document.getElementById("catalog-grid");
  if (!grid) {
    return;
  }

  const [categories, products] = await Promise.all([getCategories(), getProducts()]);
  const categoryMap = new Map(categories.map((category) => [category.id, category.name]));
  const categoriesRoot = document.getElementById("catalog-categories");
  const searchInput = document.getElementById("catalog-search");
  const summary = document.getElementById("catalog-summary");
  const loadMoreButton = document.getElementById("catalog-load-more");
  const emptyRoot = document.getElementById("catalog-empty");
  const resetButton = document.getElementById("catalog-reset");

  const state = {
    search: "",
    categoryId: Number(getQueryParam("category")) || 0,
    limit: INITIAL_LIMIT
  };

  categoriesRoot.innerHTML = `
    <label class="filter-option">
      <input type="radio" name="category" value="0" ${state.categoryId === 0 ? "checked" : ""}>
      <span>Все категории</span>
    </label>
    ${categories.map((category) => `
      <label class="filter-option">
        <input type="radio" name="category" value="${category.id}" ${state.categoryId === category.id ? "checked" : ""}>
        <span>${category.name}</span>
      </label>
    `).join("")}
  `;

  function getFilteredProducts() {
    const normalizedSearch = state.search.trim().toLowerCase();

    return products.filter((product) => {
      const matchesCategory = !state.categoryId || product.category_id === state.categoryId;
      const matchesSearch = !normalizedSearch || product.name.toLowerCase().includes(normalizedSearch);
      return matchesCategory && matchesSearch;
    });
  }

  function render() {
    const filtered = getFilteredProducts();
    const visible = filtered.slice(0, state.limit);

    summary.textContent = `Найдено ${filtered.length} ${pluralizeProducts(filtered.length)}.`;
    grid.innerHTML = visible
      .map((product) => createProductCard(product, categoryMap.get(product.category_id)))
      .join("");

    if (!filtered.length) {
      emptyRoot.innerHTML = createEmptyState(
        "Ничего не найдено",
        "Попробуйте изменить поисковый запрос или выбрать другую категорию.",
        "catalog.html",
        "Сбросить фильтры"
      );
      emptyRoot.classList.remove("hidden");
    } else {
      emptyRoot.classList.add("hidden");
    }

    loadMoreButton.classList.toggle("hidden", filtered.length <= state.limit);
  }

  searchInput.addEventListener("input", (event) => {
    state.search = event.target.value;
    state.limit = INITIAL_LIMIT;
    render();
  });

  categoriesRoot.addEventListener("change", (event) => {
    if (event.target.name === "category") {
      state.categoryId = Number(event.target.value);
      state.limit = INITIAL_LIMIT;
      render();
    }
  });

  resetButton.addEventListener("click", () => {
    state.search = "";
    state.categoryId = 0;
    state.limit = INITIAL_LIMIT;
    searchInput.value = "";
    const firstRadio = categoriesRoot.querySelector('input[value="0"]');
    if (firstRadio) {
      firstRadio.checked = true;
    }
    render();
  });

  loadMoreButton.addEventListener("click", () => {
    state.limit += INITIAL_LIMIT;
    render();
  });

  render();
}

function pluralizeProducts(count) {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) {
    return "товар";
  }

  if (mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14)) {
    return "товара";
  }

  return "товаров";
}
