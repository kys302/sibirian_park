import { getCategories, getFeaturedProducts } from "./data-service.js";
import { createCategoryCard, createProductCard } from "./ui.js";

export async function initHomePage() {
  const featuredRoot = document.getElementById("featured-products");
  const categoriesRoot = document.getElementById("home-categories");

  if (!featuredRoot || !categoriesRoot) {
    return;
  }

  const [categories, products] = await Promise.all([
    getCategories(),
    getFeaturedProducts(6)
  ]);

  const categoryMap = new Map(categories.map((category) => [category.id, category.name]));

  featuredRoot.innerHTML = products
    .map((product) => createProductCard(product, categoryMap.get(product.category_id)))
    .join("");

  categoriesRoot.innerHTML = categories
    .map((category) => createCategoryCard(category))
    .join("");
}
