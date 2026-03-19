import { readStorage, removeStorage, writeStorage } from "./storage.js";

export const STORAGE_KEYS = {
  cart: "sp_cart",
  currentUser: "sp_current_user",
  registeredUsers: "sp_registered_users",
  createdOrders: "sp_created_orders",
  adminProducts: "sp_admin_products",
  adminCategories: "sp_admin_categories",
  adminOrderStatuses: "sp_admin_order_statuses"
};

export function getCart() {
  return readStorage(STORAGE_KEYS.cart, []);
}

export function saveCart(cart) {
  writeStorage(STORAGE_KEYS.cart, cart);
}

export function getCartCount() {
  return getCart().reduce((total, item) => total + item.quantity, 0);
}

export function addToCart(productId, quantity = 1) {
  const cart = getCart();
  const existing = cart.find((item) => item.product_id === productId);

  if (existing) {
    existing.quantity += quantity;
  } else {
    cart.push({ product_id: productId, quantity });
  }

  saveCart(cart);
  return cart;
}

export function updateCartQuantity(productId, quantity) {
  const cart = getCart()
    .map((item) => item.product_id === productId ? { ...item, quantity } : item)
    .filter((item) => item.quantity > 0);

  saveCart(cart);
  return cart;
}

export function removeFromCart(productId) {
  const cart = getCart().filter((item) => item.product_id !== productId);
  saveCart(cart);
  return cart;
}

export function clearCart() {
  saveCart([]);
}

function looksBrokenText(value) {
  return typeof value === "string" && (value.includes("\uFFFD") || value.includes("пїЅ") || value.includes("Р"));
}

function normalizeCurrentUser(user) {
  if (!user || !looksBrokenText(user.name)) {
    return user;
  }

  const nameByEmail = {
    "user@sibirpark.local": "Анна Петрова",
    "admin@sibirpark.local": "Елена Смирнова"
  };

  const normalizedName = nameByEmail[String(user.email || "").toLowerCase()];
  if (!normalizedName) {
    return user;
  }

  const normalizedUser = {
    ...user,
    name: normalizedName
  };
  writeStorage(STORAGE_KEYS.currentUser, normalizedUser);
  return normalizedUser;
}

export function getCurrentUser() {
  return normalizeCurrentUser(readStorage(STORAGE_KEYS.currentUser, null));
}

export function setCurrentUser(user) {
  writeStorage(STORAGE_KEYS.currentUser, user);
}

export function logoutUser() {
  removeStorage(STORAGE_KEYS.currentUser);
}
