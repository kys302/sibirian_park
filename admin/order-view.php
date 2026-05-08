<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();
$orderId = (int) ($_GET['id'] ?? 0);
$order = fetch_order($pdo, $orderId);

if (!$order) {
    set_flash('error', 'Заказ не найден.');
    redirect('admin/orders.php');
}

$items = fetch_order_items($pdo, $orderId);

$pageTitle = 'Заказ №' . $orderId . ' — Админка Сибирский парк';
$pageDescription = 'Подробная информация о заказе.';
$pageKey = 'admin-orders';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Заказ №<?= (int) $order['id'] ?></p>
            <h1>Подробная информация</h1>
            <p>Состав заказа, данные клиента, сумма и текущий статус.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="order-view-grid">
                <article class="card content-card">
                    <h2>Данные клиента</h2>
                    <p><strong>Покупатель:</strong> <?= e($order['user_name'] ?: $order['customer_name']) ?></p>
                    <p><strong>Телефон:</strong> <?= e($order['customer_phone']) ?></p>
                    <p><strong>Email:</strong> <?= e($order['customer_email']) ?></p>
                    <p><strong>Адрес:</strong> <?= e($order['customer_address']) ?></p>
                    <p><strong>Доставка:</strong> <?= e($order['delivery_method']) ?></p>
                    <p><strong>Оплата:</strong> <?= e($order['payment_method']) ?></p>
                    <p><strong>Дата:</strong> <?= e(format_date($order['created_at'])) ?></p>
                    <p>
                        <strong>Статус:</strong>
                        <span class="admin-status <?= e(order_status_class($order['status'])) ?>">
                            <?= e(order_status_label($order['status'])) ?>
                        </span>
                    </p>
                </article>

                <article class="card content-card">
                    <h2>Состав заказа</h2>
                    <?php foreach ($items as $item): ?>
                        <div class="order-item-row">
                            <div>
                                <strong><?= e($item['product_name']) ?></strong>
                                <p><?= (int) $item['quantity'] ?> шт. × <?= e(format_price((float) $item['price'])) ?></p>
                            </div>
                            <strong><?= e(format_price((float) $item['subtotal'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row total">
                        <span>Итоговая сумма</span>
                        <strong><?= e(format_price((float) $order['total_amount'])) ?></strong>
                    </div>
                    <div class="form-actions">
                        <a class="btn btn-secondary" href="<?= e(url('admin/orders.php')) ?>">Назад к заказам</a>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
