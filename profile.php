<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

require_login();

$pdo = db();
$user = current_user();
$orders = fetch_user_orders($pdo, (int) $user['id']);

$pageTitle = 'Личный кабинет — Сибирский парк';
$pageDescription = 'Личный кабинет покупателя магазина Сибирский парк.';
$pageKey = 'profile';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact">
        <div class="container">
            <p class="eyebrow">Профиль</p>
            <h1>Личный кабинет покупателя</h1>
            <p>Здесь можно просматривать данные аккаунта и историю оформленных заказов.</p>
        </div>
    </section>

    <section class="section">
        <div class="container profile-layout">
            <section class="profile-stack">
                <article class="profile-card card">
                    <p class="eyebrow">Личные данные</p>
                    <h2><?= e($user['name']) ?></h2>
                    <div class="profile-line">
                        <span>Email</span>
                        <strong><?= e($user['email']) ?></strong>
                    </div>
                    <div class="profile-line">
                        <span>Статус</span>
                        <strong>Активный профиль</strong>
                    </div>
                    <div class="profile-line">
                        <span>Тип аккаунта</span>
                        <strong><?= $user['role'] === 'admin' ? 'Администратор' : 'Покупатель' ?></strong>
                    </div>
                </article>
            </section>

            <section class="profile-orders">
                <article class="profile-card profile-orders-card card">
                    <p class="eyebrow">История заказов</p>
                    <h2>Ваши заказы</h2>

                    <?php if ($orders): ?>
                        <?php foreach ($orders as $order): ?>
                            <?php $items = fetch_order_items($pdo, (int) $order['id']); ?>
                            <article class="profile-order card">
                                <div class="profile-order-head">
                                    <div>
                                        <strong>Заказ №<?= (int) $order['id'] ?></strong>
                                        <p class="mini-note"><?= e(format_date($order['created_at'])) ?></p>
                                    </div>
                                    <span class="admin-status <?= e(order_status_class($order['status'])) ?>">
                                        <?= e(order_status_label($order['status'])) ?>
                                    </span>
                                </div>

                                <p class="profile-order-items">
                                    <?php foreach ($items as $index => $item): ?>
                                        <?= $index > 0 ? ', ' : '' ?>
                                        <?= e($item['product_name']) ?> × <?= (int) $item['quantity'] ?>
                                    <?php endforeach; ?>
                                </p>

                                <div class="summary-row total">
                                    <span>Сумма заказа</span>
                                    <strong><?= e(format_price((float) $order['total_amount'])) ?></strong>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>Заказов пока нет</h3>
                            <p>После оформления покупки информация о заказах появится в этом разделе.</p>
                            <a class="btn btn-secondary" href="<?= e(url('catalog.php')) ?>">Выбрать растения</a>
                        </div>
                    <?php endif; ?>
                </article>
            </section>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
