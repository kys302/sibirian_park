import { getOrdersForUser, getProducts } from "./data-service.js";
import { getCurrentUser } from "./state.js";
import { createEmptyState, formatDate, formatPrice } from "./ui.js";

export async function initProfilePage() {
  const root = document.getElementById("profile-content");
  if (!root) {
    return;
  }

  const user = getCurrentUser();

  if (!user) {
    root.innerHTML = `
      <div class="access-card card">
        <h2>Для просмотра профиля необходимо войти</h2>
        <p>Авторизуйтесь, чтобы увидеть свои данные, историю заказов и информацию по оформленным покупкам.</p>
        <div class="inline-actions">
          <a class="btn btn-primary" href="login.html">Войти</a>
          <a class="btn btn-secondary" href="register.html">Регистрация</a>
        </div>
      </div>
    `;
    return;
  }

  const [orders, products] = await Promise.all([
    getOrdersForUser(user.id),
    getProducts()
  ]);
  const productMap = new Map(products.map((product) => [product.id, product]));
  const accountLabel = user.role === "admin" ? "Администратор магазина" : "Покупатель";

  root.innerHTML = `
    <section class="profile-stack">
      <article class="profile-card card">
        <p class="eyebrow">Личные данные</p>
        <h2>${user.name}</h2>
        <div class="profile-line">
          <span>Email</span>
          <strong>${user.email}</strong>
        </div>
        <div class="profile-line">
          <span>Статус</span>
          <strong>Активный профиль</strong>
        </div>
        <div class="profile-line">
          <span>Тип аккаунта</span>
          <strong>${accountLabel}</strong>
        </div>
      </article>
    </section>

    <section class="profile-orders">
      <article class="profile-card profile-orders-card card">
        <p class="eyebrow">История заказов</p>
        <h2>Ваши заказы</h2>
        <div id="profile-orders-list"></div>
      </article>
    </section>
  `;

  const ordersRoot = document.getElementById("profile-orders-list");

  if (!orders.length) {
    ordersRoot.innerHTML = createEmptyState(
      "Заказов пока нет",
      "После оформления покупки информация о заказах появится в этом разделе.",
      "catalog.html",
      "Выбрать растения"
    );
  } else {
    ordersRoot.innerHTML = orders.map((order) => {
      const itemsText = order.items
        .map((item) => `${productMap.get(item.product_id)?.name || "Товар"} × ${item.quantity}`)
        .join(", ");

      return `
        <article class="profile-order card">
          <div class="profile-order-head">
            <div>
              <strong>Заказ №${order.id}</strong>
              <p class="mini-note">${formatDate(order.created_at)}</p>
            </div>
            <span class="status-badge">${order.status}</span>
          </div>
          <p class="profile-order-items">${itemsText}</p>
          <div class="summary-row total">
            <span>Сумма заказа</span>
            <strong>${formatPrice(order.total_amount)}</strong>
          </div>
        </article>
      `;
    }).join("");
  }
}
