import {
  createCategory,
  createProduct,
  deleteCategory,
  deleteProduct,
  getCategories,
  getOrders,
  getProducts,
  getUsers,
  updateCategory,
  updateOrderStatus,
  updateProduct
} from "./data-service.js";
import { getCurrentUser } from "./state.js";
import { CATEGORY_IMAGE_CHOICES, DEFAULT_CATEGORY_IMAGE, formatDate, formatPrice, showToast } from "./ui.js";

const ORDER_STATUSES = ["Новый", "В обработке", "Подтверждён", "Доставлен", "Отменён"];

export async function initAdminPage() {
  const root = document.getElementById("admin-content");
  if (!root) {
    return;
  }

  const user = getCurrentUser();
  if (!user || user.role !== "admin") {
    root.innerHTML = `
      <div class="access-card card">
        <h2>Доступ ограничен</h2>
        <p>Этот раздел доступен только сотрудникам магазина с правами управления.</p>
        <div class="inline-actions">
          <a class="btn btn-primary" href="login.html">Войти</a>
          <a class="btn btn-secondary" href="index.html">На главную</a>
        </div>
      </div>
    `;
    return;
  }

  const state = { activeTab: "products", products: [], categories: [], orders: [], users: [], modal: null };

  const loadState = async () => {
    const [products, categories, orders, users] = await Promise.all([
      getProducts(),
      getCategories(),
      getOrders(),
      getUsers()
    ]);
    state.products = products;
    state.categories = categories;
    state.orders = orders;
    state.users = users;
  };

  const refresh = async () => {
    await loadState();
    render();
  };

  const closeModal = () => {
    state.modal = null;
    render();
  };

  const openProductModal = (product = null) => {
    state.modal = {
      kind: "product",
      mode: product ? "edit" : "create",
      productId: product?.id || null,
      title: product ? "Редактировать товар" : "Добавить товар",
      values: {
        name: product?.name || "",
        category_id: String(product?.category_id || state.categories[0]?.id || ""),
        price: product?.price ? String(product.price) : "",
        stock: Number.isFinite(product?.stock) ? String(product.stock) : "",
        description: product?.description || "",
        imageUrl: "",
        is_featured: Boolean(product?.is_featured)
      },
      existingImage: product?.image || "",
      uploadedImage: "",
      previewImage: product?.image || resolveCategoryImageById(state.categories[0]?.id, state.categories),
      errors: {}
    };
    render();
  };

  const openCategoryModal = (category = null) => {
    state.modal = {
      kind: "category",
      mode: category ? "edit" : "create",
      categoryId: category?.id || null,
      title: category ? "Редактировать категорию" : "Добавить категорию",
      values: {
        name: category?.name || "",
        description: category?.description || "",
        image: category?.image || DEFAULT_CATEGORY_IMAGE
      },
      errors: {}
    };
    render();
  };

  const openOrderModal = (orderId) => {
    state.modal = { kind: "order", orderId: Number(orderId) };
    render();
  };

  const openDeleteModal = (title, text, confirmAction, entityId) => {
    state.modal = { kind: "confirm", title, text, confirmAction, entityId };
    render();
  };

  function render() {
    const categoryMap = new Map(state.categories.map((category) => [category.id, category.name]));
    const userMap = new Map(state.users.map((account) => [account.id, account.name]));
    document.body.classList.toggle("has-modal", Boolean(state.modal));

    root.innerHTML = `
      <section class="admin-stack">
        <div class="admin-overview">
          <article class="overview-card card"><h3>Товары</h3><p class="overview-value">${state.products.length}</p><p class="mini-note">Позиции в каталоге</p></article>
          <article class="overview-card card"><h3>Категории</h3><p class="overview-value">${state.categories.length}</p><p class="mini-note">Разделы каталога</p></article>
          <article class="overview-card card"><h3>Заказы</h3><p class="overview-value">${state.orders.length}</p><p class="mini-note">Статусы и детали заказов</p></article>
        </div>

        <div class="admin-tabs">
          <button class="admin-tab ${state.activeTab === "products" ? "active" : ""}" type="button" data-tab="products">Товары</button>
          <button class="admin-tab ${state.activeTab === "categories" ? "active" : ""}" type="button" data-tab="categories">Категории</button>
          <button class="admin-tab ${state.activeTab === "orders" ? "active" : ""}" type="button" data-tab="orders">Заказы</button>
        </div>

        <section class="admin-section card ${state.activeTab === "products" ? "" : "hidden"}">
          <div class="admin-header">
            <div><p class="eyebrow">Управление товарами</p><h2>Список товаров</h2></div>
            <button class="btn btn-primary" type="button" data-action="product-create">Добавить товар</button>
          </div>
          ${renderProductsTable(state.products, categoryMap)}
        </section>

        <section class="admin-section card ${state.activeTab === "categories" ? "" : "hidden"}">
          <div class="admin-header">
            <div><p class="eyebrow">Категории</p><h2>Список категорий</h2></div>
            <button class="btn btn-primary" type="button" data-action="category-create">Добавить категорию</button>
          </div>
          ${renderCategoriesTable(state.categories)}
        </section>

        <section class="admin-section card ${state.activeTab === "orders" ? "" : "hidden"}">
          <div class="admin-header">
            <div><p class="eyebrow">Заказы</p><h2>Список заказов</h2></div>
          </div>
          ${renderOrdersTable(state.orders, userMap)}
        </section>
      </section>
      ${renderModal(state, userMap)}
    `;
  }

  root.addEventListener("click", async (event) => {
    const overlay = event.target.closest("[data-modal-overlay]");
    if (overlay && event.target === overlay) {
      closeModal();
      return;
    }
    if (event.target.closest("[data-modal-close]")) {
      closeModal();
      return;
    }

    const tab = event.target.closest("[data-tab]");
    if (tab) {
      state.activeTab = tab.dataset.tab;
      render();
      return;
    }

    const actionButton = event.target.closest("[data-action]");
    if (!actionButton) {
      return;
    }

    const action = actionButton.dataset.action;
    if (action === "product-create") return openProductModal();
    if (action === "category-create") return openCategoryModal();
    if (action === "order-open") return openOrderModal(actionButton.dataset.id);

    if (action === "product-edit") {
      const product = state.products.find((item) => item.id === Number(actionButton.dataset.id));
      if (product) openProductModal(product);
      return;
    }

    if (action === "category-edit") {
      const category = state.categories.find((item) => item.id === Number(actionButton.dataset.id));
      if (category) openCategoryModal(category);
      return;
    }

    if (action === "product-delete") {
      const product = state.products.find((item) => item.id === Number(actionButton.dataset.id));
      if (product) openDeleteModal("Удалить товар", `Товар «${product.name}» будет удалён из каталога.`, "delete-product", product.id);
      return;
    }

    if (action === "category-delete") {
      const category = state.categories.find((item) => item.id === Number(actionButton.dataset.id));
      if (!category) return;
      if (state.products.some((product) => product.category_id === category.id)) {
        showToast("Нельзя удалить категорию, пока в ней есть товары.");
        return;
      }
      openDeleteModal("Удалить категорию", `Категория «${category.name}» будет удалена без возможности восстановления.`, "delete-category", category.id);
      return;
    }

    if (action === "confirm-delete") {
      const modal = state.modal;
      closeModal();
      if (modal?.confirmAction === "delete-product") {
        await deleteProduct(modal.entityId);
        await refresh();
        showToast("Товар удалён.");
      }
      if (modal?.confirmAction === "delete-category") {
        await deleteCategory(modal.entityId);
        await refresh();
        showToast("Категория удалена.");
      }
    }
  });

  root.addEventListener("submit", async (event) => {
    if (event.target.matches("[data-product-form]")) {
      event.preventDefault();
      const values = collectProductFormState(event.target, state.modal);
      const payload = buildProductPayload(values, state.categories);
      const errors = validateProductPayload(payload);
      state.modal.values = values;
      state.modal.errors = errors;
      state.modal.previewImage = payload.image || resolveCategoryImageById(values.category_id, state.categories);
      if (Object.keys(errors).length) {
        render();
        return;
      }
      const { mode, productId } = state.modal;
      closeModal();
      if (mode === "create") {
        await createProduct(payload);
        await refresh();
        showToast("Товар добавлен.");
      } else {
        await updateProduct(productId, payload);
        await refresh();
        showToast("Изменения по товару сохранены.");
      }
      return;
    }

    if (event.target.matches("[data-category-form]")) {
      event.preventDefault();
      const payload = buildCategoryPayload(event.target);
      const errors = validateCategoryPayload(payload);
      state.modal.values = payload;
      state.modal.errors = errors;
      if (Object.keys(errors).length) {
        render();
        return;
      }
      const { mode, categoryId } = state.modal;
      closeModal();
      if (mode === "create") {
        await createCategory(payload);
        await refresh();
        showToast("Категория добавлена.");
      } else {
        await updateCategory(categoryId, payload);
        await refresh();
        showToast("Категория обновлена.");
      }
      return;
    }

    if (event.target.matches("[data-order-form]")) {
      event.preventDefault();
      const formData = new FormData(event.target);
      const status = String(formData.get("status") || "").trim();
      if (!ORDER_STATUSES.includes(status)) {
        showToast("Выберите корректный статус заказа.");
        return;
      }
      const orderId = state.modal?.orderId;
      closeModal();
      await updateOrderStatus(orderId, status);
      await refresh();
      showToast("Статус заказа обновлён.");
    }
  });

  root.addEventListener("input", (event) => {
    if (state.modal?.kind === "product") {
      if (event.target.name === "name" || event.target.name === "description") {
        state.modal.values[event.target.name] = event.target.value;
        render();
      }
      if (event.target.name === "imageUrl") {
        state.modal.values.imageUrl = event.target.value;
        if (event.target.value.trim()) state.modal.uploadedImage = "";
        state.modal.previewImage = event.target.value.trim();
        render();
      }
    }
    if (state.modal?.kind === "category" && (event.target.name === "name" || event.target.name === "description")) {
      state.modal.values[event.target.name] = event.target.value;
      render();
    }
  });

  root.addEventListener("change", (event) => {
    if (state.modal?.kind === "product") {
      if (event.target.name === "category_id") {
        state.modal.values.category_id = event.target.value;
        render();
      }
      if (event.target.name === "is_featured") {
        state.modal.values.is_featured = event.target.checked;
      }
      if (event.target.name === "imageFile") {
        const [file] = event.target.files || [];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
          state.modal.uploadedImage = String(reader.result);
          state.modal.previewImage = String(reader.result);
          render();
        };
        reader.readAsDataURL(file);
      }
    }
    if (state.modal?.kind === "category" && event.target.name === "image") {
      state.modal.values.image = event.target.value || DEFAULT_CATEGORY_IMAGE;
      render();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && state.modal) {
      closeModal();
    }
  });

  await refresh();
}

function renderProductsTable(products, categoryMap) {
  return `<div class="table-card card admin-table-shell"><div class="admin-table-scroll"><table class="admin-table-data admin-table-data--products"><thead><tr><th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Действия</th></tr></thead><tbody>${products.map((product) => `<tr><td class="cell-nowrap">${product.id}</td><td class="cell-text">${escapeHtml(product.name)}</td><td class="cell-text">${escapeHtml(categoryMap.get(product.category_id) || "Без категории")}</td><td class="cell-nowrap">${formatPrice(product.price)}</td><td class="cell-nowrap">${product.stock} шт.</td><td><div class="table-actions table-actions--compact"><button class="btn btn-secondary btn-small" type="button" data-action="product-edit" data-id="${product.id}">Редактировать</button><button class="btn btn-danger btn-small" type="button" data-action="product-delete" data-id="${product.id}">Удалить</button></div></td></tr>`).join("")}</tbody></table></div></div>`;
}

function renderCategoriesTable(categories) {
  return `<div class="table-card card admin-table-shell"><div class="admin-table-scroll"><table class="admin-table-data admin-table-data--categories"><thead><tr><th>ID</th><th>Название</th><th>Описание</th><th>Статус</th><th>Действия</th></tr></thead><tbody>${categories.map((category) => `<tr><td class="cell-nowrap">${category.id}</td><td><span class="category-admin-cell"><span class="category-table-thumb"><img src="${escapeHtml(category.image || DEFAULT_CATEGORY_IMAGE)}" alt="${escapeHtml(category.name)}"></span><span class="category-admin-name">${escapeHtml(category.name)}</span></span></td><td class="cell-text">${escapeHtml(category.description)}</td><td class="cell-nowrap"><span class="admin-status admin-status--confirmed">Активна</span></td><td><div class="table-actions table-actions--compact"><button class="btn btn-secondary btn-small" type="button" data-action="category-edit" data-id="${category.id}">Редактировать</button><button class="btn btn-danger btn-small" type="button" data-action="category-delete" data-id="${category.id}">Удалить</button></div></td></tr>`).join("")}</tbody></table></div></div>`;
}

function renderOrdersTable(orders, userMap) {
  return `<div class="table-card card admin-table-shell"><div class="admin-table-scroll"><table class="admin-table-data admin-table-data--orders"><thead><tr><th>№ заказа</th><th>Дата</th><th>Покупатель</th><th>Сумма</th><th>Статус</th><th>Действия</th></tr></thead><tbody>${orders.map((order) => `<tr><td class="cell-nowrap">${order.id}</td><td class="cell-nowrap">${formatDate(order.created_at)}</td><td class="cell-text">${escapeHtml(userMap.get(order.user_id) || order.customer_name || "Покупатель")}</td><td class="cell-nowrap">${formatPrice(order.total_amount)}</td><td class="cell-nowrap">${renderStatusBadge(order.status)}</td><td><div class="table-actions table-actions--compact"><button class="btn btn-secondary btn-small" type="button" data-action="order-open" data-id="${order.id}">Подробнее</button></div></td></tr>`).join("")}</tbody></table></div></div>`;
}

function renderModal(state, userMap) {
  if (!state.modal) return "";
  if (state.modal.kind === "product") return renderProductModal(state.modal, state.categories);
  if (state.modal.kind === "category") return renderCategoryModal(state.modal);
  if (state.modal.kind === "order") {
    const order = state.orders.find((item) => item.id === state.modal.orderId);
    return order ? renderOrderModal(order, state.products, userMap) : "";
  }
  if (state.modal.kind === "confirm") return renderDeleteModal(state.modal);
  return "";
}

function renderProductModal(modal, categories) {
  const previewImage = getProductPreviewSource(modal, categories);
  return `<div class="admin-modal" data-modal-overlay><div class="admin-modal__dialog admin-modal__dialog--wide" role="dialog" aria-modal="true"><div class="admin-modal__header"><div><p class="eyebrow">Товар</p><h3>${modal.title}</h3></div><button class="admin-modal__close" type="button" aria-label="Закрыть" data-modal-close>&times;</button></div><form class="admin-form" data-product-form><div class="admin-form__layout"><div class="admin-form__fields"><label class="admin-field"><span class="label">Название</span><input class="input" name="name" type="text" value="${escapeHtml(modal.values.name)}" placeholder="Например, Яблоня Антоновка">${renderFieldError(modal.errors.name)}</label><div class="admin-form__grid"><label class="admin-field"><span class="label">Категория</span><select class="select" name="category_id">${categories.map((category) => `<option value="${category.id}" ${String(category.id) === String(modal.values.category_id) ? "selected" : ""}>${escapeHtml(category.name)}</option>`).join("")}</select>${renderFieldError(modal.errors.category_id)}</label><label class="admin-field"><span class="label">Цена, ₽</span><input class="input" name="price" type="number" min="0" step="1" value="${escapeHtml(modal.values.price)}">${renderFieldError(modal.errors.price)}</label><label class="admin-field"><span class="label">В наличии, шт.</span><input class="input" name="stock" type="number" min="0" step="1" value="${escapeHtml(modal.values.stock)}">${renderFieldError(modal.errors.stock)}</label></div><label class="admin-field"><span class="label">Описание</span><textarea class="textarea" name="description" rows="6">${escapeHtml(modal.values.description)}</textarea>${renderFieldError(modal.errors.description)}</label><div class="admin-image-picker card"><div class="admin-image-picker__head"><h4>Изображение товара</h4><p>Укажите ссылку на фото или загрузите файл с компьютера.</p></div>${modal.existingImage ? `<div class="admin-current-image-note"><span class="mini-note">Текущее изображение сохранится, если вы не выберете новое.</span></div>` : ""}<div class="admin-form__grid"><label class="admin-field"><span class="label">Ссылка на изображение</span><input class="input" name="imageUrl" type="url" value="${escapeHtml(modal.values.imageUrl)}" placeholder="https://example.com/photo.jpg"></label><label class="admin-field"><span class="label">Файл с компьютера</span><input class="input" name="imageFile" type="file" accept="image/*"></label></div>${renderFieldError(modal.errors.image)}</div><label class="admin-checkbox"><input type="checkbox" name="is_featured" ${modal.values.is_featured ? "checked" : ""}><span>Показывать на главной странице</span></label></div><aside class="admin-form__preview card"><h4>Предпросмотр</h4><div class="admin-image-preview"><img src="${previewImage}" alt="Предпросмотр товара"></div><div class="admin-preview-meta"><strong>${escapeHtml(modal.values.name || "Новый товар")}</strong><span>${escapeHtml(modal.values.description || "Описание товара появится после заполнения формы.")}</span></div></aside></div><div class="admin-modal__footer"><button class="btn btn-primary" type="submit">Сохранить</button><button class="btn btn-secondary" type="button" data-modal-close>Отмена</button></div></form></div></div>`;
}

function renderCategoryModal(modal) {
  return `<div class="admin-modal" data-modal-overlay><div class="admin-modal__dialog admin-modal__dialog--wide" role="dialog" aria-modal="true"><div class="admin-modal__header"><div><p class="eyebrow">Категория</p><h3>${modal.title}</h3></div><button class="admin-modal__close" type="button" aria-label="Закрыть" data-modal-close>&times;</button></div><form class="admin-form" data-category-form><div class="admin-form__layout admin-form__layout--category"><div class="admin-form__fields"><label class="admin-field"><span class="label">Название категории</span><input class="input" name="name" type="text" value="${escapeHtml(modal.values.name)}" placeholder="Например, Хвойные растения">${renderFieldError(modal.errors.name)}</label><label class="admin-field"><span class="label">Описание категории</span><textarea class="textarea" name="description" rows="5">${escapeHtml(modal.values.description)}</textarea>${renderFieldError(modal.errors.description)}</label><div class="admin-image-picker card"><div class="admin-image-picker__head"><h4>Изображение категории</h4><p>Выберите подходящую иллюстрацию из готового набора.</p></div><div class="category-image-options">${renderCategoryImageOptions(modal.values.image)}</div></div></div><aside class="admin-form__preview card"><h4>Предпросмотр</h4><div class="admin-image-preview admin-image-preview--category"><img src="${escapeHtml(modal.values.image || DEFAULT_CATEGORY_IMAGE)}" alt="Предпросмотр категории"></div><div class="admin-preview-meta"><strong>${escapeHtml(modal.values.name || "Новая категория")}</strong><span>${escapeHtml(modal.values.description || "Описание категории появится после заполнения формы.")}</span></div></aside></div><div class="admin-modal__footer"><button class="btn btn-primary" type="submit">Сохранить</button><button class="btn btn-secondary" type="button" data-modal-close>Отмена</button></div></form></div></div>`;
}

function renderOrderModal(order, products, userMap) {
  const productMap = new Map(products.map((product) => [product.id, product.name]));
  const customerName = userMap.get(order.user_id) || order.customer_name || "Покупатель";
  return `<div class="admin-modal" data-modal-overlay><div class="admin-modal__dialog admin-modal__dialog--wide" role="dialog" aria-modal="true"><div class="admin-modal__header"><div><p class="eyebrow">Заказ №${order.id}</p><h3>Детали заказа</h3></div><button class="admin-modal__close" type="button" aria-label="Закрыть" data-modal-close>&times;</button></div><div class="order-modal"><div class="order-modal__summary"><div class="order-meta-grid"><div class="order-meta-card"><span>Покупатель</span><strong>${escapeHtml(customerName)}</strong></div><div class="order-meta-card"><span>Дата</span><strong>${formatDate(order.created_at)}</strong></div><div class="order-meta-card"><span>Сумма</span><strong>${formatPrice(order.total_amount)}</strong></div><div class="order-meta-card"><span>Статус</span><div>${renderStatusBadge(order.status)}</div></div></div>${(order.customer_phone || order.customer_email || order.customer_address || order.customer_comment) ? `<div class="order-details-grid">${order.customer_phone ? `<div><span class="mini-note">Телефон</span><p>${escapeHtml(order.customer_phone)}</p></div>` : ""}${order.customer_email ? `<div><span class="mini-note">Email</span><p>${escapeHtml(order.customer_email)}</p></div>` : ""}${order.customer_address ? `<div><span class="mini-note">Адрес</span><p>${escapeHtml(order.customer_address)}</p></div>` : ""}${order.customer_comment ? `<div><span class="mini-note">Комментарий</span><p>${escapeHtml(order.customer_comment)}</p></div>` : ""}</div>` : ""}</div><div class="order-modal__body"><div class="order-items card"><h4>Состав заказа</h4><div class="order-items__list">${order.items.map((item) => `<div class="order-item-row"><div><strong>${escapeHtml(productMap.get(item.product_id) || "Товар")}</strong><p>${item.quantity} шт.</p></div><strong>${formatPrice(item.price * item.quantity)}</strong></div>`).join("")}</div></div><form class="order-status-form card" data-order-form><h4>Статус заказа</h4><label class="admin-field"><span class="label">Текущий статус</span><select class="select" name="status">${ORDER_STATUSES.map((status) => `<option value="${status}" ${status === order.status ? "selected" : ""}>${status}</option>`).join("")}</select></label><div class="admin-modal__footer admin-modal__footer--compact"><button class="btn btn-primary" type="submit">Сохранить статус</button><button class="btn btn-secondary" type="button" data-modal-close>Закрыть</button></div></form></div></div></div></div>`;
}

function renderDeleteModal(modal) {
  return `<div class="admin-modal" data-modal-overlay><div class="admin-modal__dialog admin-modal__dialog--small" role="dialog" aria-modal="true"><div class="admin-modal__header"><div><p class="eyebrow">Подтверждение</p><h3>${modal.title}</h3></div><button class="admin-modal__close" type="button" aria-label="Закрыть" data-modal-close>&times;</button></div><div class="admin-modal__body"><p class="admin-modal__text">${escapeHtml(modal.text)}</p></div><div class="admin-modal__footer"><button class="btn btn-danger" type="button" data-action="confirm-delete">Удалить</button><button class="btn btn-secondary" type="button" data-modal-close>Отмена</button></div></div></div>`;
}

function collectProductFormState(form, modal) {
  const formData = new FormData(form);
  return {
    name: String(formData.get("name") || "").trim(),
    category_id: String(formData.get("category_id") || "").trim(),
    price: String(formData.get("price") || "").trim(),
    stock: String(formData.get("stock") || "").trim(),
    description: String(formData.get("description") || "").trim(),
    imageUrl: String(formData.get("imageUrl") || "").trim(),
    is_featured: formData.get("is_featured") === "on",
    uploadedImage: modal.uploadedImage || "",
    existingImage: modal.existingImage || ""
  };
}

function buildProductPayload(values, categories) {
  return {
    name: values.name,
    category_id: Number(values.category_id),
    price: Number(values.price),
    stock: Number(values.stock),
    description: values.description,
    image: values.uploadedImage || values.imageUrl || values.existingImage || resolveCategoryImageById(values.category_id, categories),
    is_featured: Boolean(values.is_featured)
  };
}

function buildCategoryPayload(form) {
  const formData = new FormData(form);
  return {
    name: String(formData.get("name") || "").trim(),
    description: String(formData.get("description") || "").trim(),
    image: String(formData.get("image") || "").trim() || DEFAULT_CATEGORY_IMAGE
  };
}

function validateProductPayload(payload) {
  const errors = {};
  if (!payload.name.trim()) errors.name = "Укажите название товара.";
  if (!payload.category_id) errors.category_id = "Выберите категорию.";
  if (!Number.isFinite(payload.price) || payload.price <= 0) errors.price = "Укажите корректную цену.";
  if (!Number.isFinite(payload.stock) || payload.stock < 0) errors.stock = "Укажите корректный остаток.";
  if (!payload.description.trim()) errors.description = "Добавьте описание товара.";
  if (!payload.image) errors.image = "Выберите изображение или укажите ссылку на фото.";
  return errors;
}

function validateCategoryPayload(payload) {
  const errors = {};
  if (!payload.name.trim()) errors.name = "Укажите название категории.";
  if (!payload.description.trim()) errors.description = "Добавьте описание категории.";
  return errors;
}

function renderFieldError(message) {
  return `<p class="field-error">${message || ""}</p>`;
}

function renderStatusBadge(status) {
  return `<span class="admin-status admin-status--${getStatusKey(status)}">${escapeHtml(status)}</span>`;
}

function getStatusKey(status) {
  return {
    "Новый": "new",
    "В обработке": "processing",
    "Подтверждён": "confirmed",
    "Доставлен": "done",
    "Отменён": "cancelled"
  }[status] || "default";
}

function resolveCategoryImageById(categoryId, categories) {
  const category = categories.find((item) => String(item.id) === String(categoryId));
  return category?.image || DEFAULT_CATEGORY_IMAGE;
}

function renderCategoryImageOptions(selectedImage) {
  return CATEGORY_IMAGE_CHOICES.map((choice) => `<label class="category-image-choice ${choice.image === selectedImage ? "is-selected" : ""}"><input type="radio" name="image" value="${choice.image}" ${choice.image === selectedImage ? "checked" : ""}><span class="category-image-choice__media"><img src="${choice.image}" alt="${choice.label}"></span><span class="category-image-choice__label">${choice.label}</span></label>`).join("");
}

function getProductPreviewSource(modal, categories) {
  return modal.uploadedImage || modal.values.imageUrl || modal.previewImage || modal.existingImage || resolveCategoryImageById(modal.values.category_id, categories);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#39;");
}
