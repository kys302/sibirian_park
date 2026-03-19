import { STORAGE_KEYS } from "./state.js";
import { readStorage, writeStorage } from "./storage.js";

const DEFAULT_CATEGORY_IMAGE = "assets/images/products/category-placeholder.svg";

const DATA_FILES = {
  categories: "data/categories.json",
  products: "data/products.json",
  users: "data/users.json",
  orders: "data/orders.json"
};

const cache = {};

async function fetchJson(path) {
  const response = await fetch(path);

  if (!response.ok) {
    throw new Error(`Не удалось загрузить ${path}`);
  }

  return response.json();
}

async function loadBaseData(key) {
  if (!cache[key]) {
    cache[key] = fetchJson(DATA_FILES[key]);
  }

  return cache[key];
}

function sortByIdDesc(items) {
  return [...items].sort((a, b) => b.id - a.id);
}

function getRegisteredUsersRaw() {
  return readStorage(STORAGE_KEYS.registeredUsers, []);
}

function getCreatedOrdersRaw() {
  return readStorage(STORAGE_KEYS.createdOrders, []);
}

function sanitizeUser(user) {
  const { password, ...safeUser } = user;
  return safeUser;
}

function slugify(value) {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-zа-яё0-9]+/gi, "-")
    .replace(/^-+|-+$/g, "");
}

function looksBroken(value) {
  if (typeof value !== "string") {
    return false;
  }

  return value.includes("\uFFFD") || value.includes("пїЅ") || value.includes("Р");
}

function shouldRefreshStoredProductImage(image) {
  if (!image) {
    return true;
  }

  return /^assets\/images\/products\/[^/]+\.svg$/i.test(String(image).trim());
}

function normalizeStoredProducts(storedProducts, baseProducts) {
  const baseProductMap = new Map(baseProducts.map((product) => [product.id, product]));

  return storedProducts.map((product) => {
    const baseProduct = baseProductMap.get(product.id);
    if (!baseProduct) {
      return product;
    }

    const hasBrokenText = looksBroken(product.name) || looksBroken(product.description);
    const needsImageRefresh = shouldRefreshStoredProductImage(product.image);

    return {
      ...product,
      name: hasBrokenText ? baseProduct.name : product.name,
      description: hasBrokenText ? baseProduct.description : product.description,
      image: needsImageRefresh ? baseProduct.image : product.image
    };
  });
}

function normalizeStoredCategories(storedCategories, baseCategories) {
  const baseCategoryMap = new Map(baseCategories.map((category) => [category.id, category]));

  return storedCategories.map((category) => {
    const baseCategory = baseCategoryMap.get(category.id);
    const hasBrokenText = looksBroken(category.name) || looksBroken(category.description);

    return {
      ...category,
      name: hasBrokenText && baseCategory ? baseCategory.name : category.name,
      description: hasBrokenText && baseCategory ? baseCategory.description : category.description,
      image: category.image || baseCategory?.image || DEFAULT_CATEGORY_IMAGE
    };
  });
}

function normalizeStoredOrders(storedOrders, baseOrders) {
  const baseOrderMap = new Map(baseOrders.map((order) => [order.id, order]));

  return storedOrders.map((order) => {
    const baseOrder = baseOrderMap.get(order.id);
    if (!baseOrder) {
      return order;
    }

    return {
      ...order,
      status: looksBroken(order.status) ? baseOrder.status : order.status,
      customer_name: looksBroken(order.customer_name) ? baseOrder.customer_name : order.customer_name,
      customer_address: looksBroken(order.customer_address) ? baseOrder.customer_address : order.customer_address,
      customer_comment: looksBroken(order.customer_comment) ? baseOrder.customer_comment : order.customer_comment
    };
  });
}

function normalizeStoredUsers(storedUsers, baseUsers) {
  const baseUserMap = new Map(baseUsers.map((user) => [user.id, user]));

  return storedUsers.map((user) => {
    const baseUser = baseUserMap.get(user.id);
    if (!baseUser) {
      return user;
    }

    return {
      ...user,
      name: looksBroken(user.name) ? baseUser.name : user.name
    };
  });
}

export async function getCategories() {
  const storedCategories = readStorage(STORAGE_KEYS.adminCategories, null);
  if (!storedCategories) {
    return loadBaseData("categories");
  }

  const baseCategories = await loadBaseData("categories");
  const normalizedCategories = normalizeStoredCategories(storedCategories, baseCategories);

  if (JSON.stringify(normalizedCategories) !== JSON.stringify(storedCategories)) {
    writeStorage(STORAGE_KEYS.adminCategories, normalizedCategories);
  }

  return normalizedCategories;
}

export async function getProducts() {
  const storedProducts = readStorage(STORAGE_KEYS.adminProducts, null);
  if (!storedProducts) {
    return loadBaseData("products");
  }

  const baseProducts = await loadBaseData("products");
  const normalizedProducts = normalizeStoredProducts(storedProducts, baseProducts);

  if (JSON.stringify(normalizedProducts) !== JSON.stringify(storedProducts)) {
    writeStorage(STORAGE_KEYS.adminProducts, normalizedProducts);
  }

  return normalizedProducts;
}

export async function getFeaturedProducts(limit = 6) {
  const products = await getProducts();
  return products.filter((product) => product.is_featured).slice(0, limit);
}

export async function getProductById(productId) {
  const products = await getProducts();
  return products.find((product) => product.id === Number(productId)) || null;
}

export async function getRelatedProducts(product, limit = 3) {
  const products = await getProducts();
  return products
    .filter((item) => item.category_id === product.category_id && item.id !== product.id)
    .slice(0, limit);
}

export async function getUsers() {
  const baseUsers = await loadBaseData("users");
  const localUsersRaw = getRegisteredUsersRaw();
  const normalizedLocalUsers = normalizeStoredUsers(localUsersRaw, baseUsers);

  if (JSON.stringify(normalizedLocalUsers) !== JSON.stringify(localUsersRaw)) {
    writeStorage(STORAGE_KEYS.registeredUsers, normalizedLocalUsers);
  }

  return sortByIdDesc([...baseUsers, ...normalizedLocalUsers].map(sanitizeUser));
}

export async function getOrders() {
  const baseOrders = await loadBaseData("orders");
  const localOrdersRaw = getCreatedOrdersRaw();
  const normalizedLocalOrders = normalizeStoredOrders(localOrdersRaw, baseOrders);
  const statusOverrides = readStorage(STORAGE_KEYS.adminOrderStatuses, {});

  if (JSON.stringify(normalizedLocalOrders) !== JSON.stringify(localOrdersRaw)) {
    writeStorage(STORAGE_KEYS.createdOrders, normalizedLocalOrders);
  }

  return sortByIdDesc(
    [...baseOrders, ...normalizedLocalOrders].map((order) => ({
      ...order,
      status: looksBroken(statusOverrides[order.id]) ? order.status : (statusOverrides[order.id] || order.status)
    }))
  );
}

export async function getOrdersForUser(userId) {
  const orders = await getOrders();
  return orders.filter((order) => order.user_id === Number(userId));
}

export async function authenticateUser(email, password) {
  const normalizedEmail = email.trim().toLowerCase();
  const normalizedPassword = password.trim();

  if (normalizedPassword.length < 6) {
    return null;
  }

  const baseUsers = await loadBaseData("users");
  const baseMatch = baseUsers.find((user) => (
    user.email.toLowerCase() === normalizedEmail &&
    user.password === normalizedPassword
  ));
  if (baseMatch) {
    return sanitizeUser(baseMatch);
  }

  const localUser = getRegisteredUsersRaw().find((user) => user.email.toLowerCase() === normalizedEmail);
  if (localUser && localUser.password === normalizedPassword) {
    return sanitizeUser(localUser);
  }

  return null;
}

export async function registerUser({ name, email, password }) {
  const normalizedEmail = email.trim().toLowerCase();
  const users = await getUsers();
  const exists = users.some((user) => user.email.toLowerCase() === normalizedEmail);

  if (exists) {
    throw new Error("Пользователь с таким email уже зарегистрирован.");
  }

  const registeredUsers = getRegisteredUsersRaw();
  const nextId = Math.max(0, ...users.map((user) => user.id)) + 1;
  const newUser = {
    id: nextId,
    name: name.trim(),
    email: normalizedEmail,
    role: "user",
    password
  };

  registeredUsers.push(newUser);
  writeStorage(STORAGE_KEYS.registeredUsers, registeredUsers);

  const { password: _password, ...safeUser } = newUser;
  return safeUser;
}

export async function createOrder({ userId, items, totalAmount, customer }) {
  const currentOrders = await getOrders();
  const localOrders = getCreatedOrdersRaw();
  const nextId = Math.max(5000, ...currentOrders.map((order) => order.id)) + 1;

  const order = {
    id: nextId,
    user_id: Number(userId) || 0,
    items,
    total_amount: totalAmount,
    status: "Новый",
    created_at: new Date().toISOString(),
    customer_name: customer.name,
    customer_email: customer.email,
    customer_phone: customer.phone,
    customer_address: customer.address,
    customer_comment: customer.comment || ""
  };

  localOrders.push(order);
  writeStorage(STORAGE_KEYS.createdOrders, localOrders);

  return order;
}

export async function createProduct(payload) {
  const products = await getProducts();
  const nextId = Math.max(0, ...products.map((product) => product.id)) + 1;
  const newProduct = {
    id: nextId,
    category_id: Number(payload.category_id),
    name: payload.name.trim(),
    description: payload.description.trim(),
    price: Number(payload.price),
    image: payload.image,
    stock: Number(payload.stock),
    is_featured: Boolean(payload.is_featured)
  };

  writeStorage(STORAGE_KEYS.adminProducts, [...products, newProduct]);
  return newProduct;
}

export async function updateProduct(productId, payload) {
  const products = await getProducts();
  const updatedProducts = products.map((product) => (
    product.id === Number(productId)
      ? {
          ...product,
          category_id: Number(payload.category_id),
          name: payload.name.trim(),
          description: payload.description.trim(),
          price: Number(payload.price),
          image: payload.image,
          stock: Number(payload.stock),
          is_featured: Boolean(payload.is_featured)
        }
      : product
  ));

  writeStorage(STORAGE_KEYS.adminProducts, updatedProducts);
}

export async function deleteProduct(productId) {
  const products = await getProducts();
  const updatedProducts = products.filter((product) => product.id !== Number(productId));
  writeStorage(STORAGE_KEYS.adminProducts, updatedProducts);
}

export async function createCategory(payload) {
  const categories = await getCategories();
  const nextId = Math.max(0, ...categories.map((category) => category.id)) + 1;
  const newCategory = {
    id: nextId,
    slug: payload.slug?.trim() || slugify(payload.name),
    name: payload.name.trim(),
    description: payload.description.trim(),
    image: payload.image || DEFAULT_CATEGORY_IMAGE
  };

  writeStorage(STORAGE_KEYS.adminCategories, [...categories, newCategory]);
  return newCategory;
}

export async function updateCategory(categoryId, payload) {
  const categories = await getCategories();
  const updatedCategories = categories.map((category) => (
    category.id === Number(categoryId)
      ? {
          ...category,
          slug: payload.slug?.trim() || slugify(payload.name),
          name: payload.name.trim(),
          description: payload.description.trim(),
          image: payload.image || DEFAULT_CATEGORY_IMAGE
        }
      : category
  ));

  writeStorage(STORAGE_KEYS.adminCategories, updatedCategories);
}

export async function deleteCategory(categoryId) {
  const categories = await getCategories();
  const updatedCategories = categories.filter((category) => category.id !== Number(categoryId));
  writeStorage(STORAGE_KEYS.adminCategories, updatedCategories);
}

export async function updateOrderStatus(orderId, status) {
  const statuses = readStorage(STORAGE_KEYS.adminOrderStatuses, {});
  statuses[orderId] = status;
  writeStorage(STORAGE_KEYS.adminOrderStatuses, statuses);
}
