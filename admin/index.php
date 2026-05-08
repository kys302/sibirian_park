<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();
$counts = fetch_dashboard_counts($pdo);
$latestOrders = fetch_latest_orders($pdo, 5);

$pageTitle = 'Панель администратора — Сибирский парк';
$pageDescription = 'Панель администратора интернет-магазина Сибирский парк.';
$pageKey = 'admin-dashboard';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Управление магазином</p>
            <h1>Панель администратора</h1>
            <p>Сводная информация по товарам, заказам и пользователям.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <section class="admin-stack">
                <div class="admin-overview">
                    <article class="overview-card card">
                        <h3>Товары</h3>
                        <p class="overview-value"><?= $counts['products'] ?></p>
                        <p class="mini-note">Позиции в каталоге</p>
                    </article>
                    <article class="overview-card card">
                        <h3>Заказы</h3>
                        <p class="overview-value"><?= $counts['orders'] ?></p>
                        <p class="mini-note">Оформленные заявки</p>
                    </article>
                    <article class="overview-card card">
                        <h3>Пользователи</h3>
                        <p class="overview-value"><?= $counts['users'] ?></p>
                        <p class="mini-note">Зарегистрированные аккаунты</p>
                    </article>
                </div>

                <section class="admin-section card">
                    <div class="admin-header">
                        <div>
                            <p class="eyebrow">Последние заказы</p>
                            <h2>Новые заказы магазина</h2>
                        </div>
                        <a class="btn btn-secondary" href="<?= e(url('admin/orders.php')) ?>">Все заказы</a>
                    </div>

                    <div class="table-card card admin-table-shell">
                        <div class="admin-table-scroll">
                            <table class="admin-table-data admin-table-data--orders">
                                <thead>
                                    <tr>
                                        <th>№ заказа</th>
                                        <th>Дата</th>
                                        <th>Покупатель</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestOrders as $order): ?>
                                        <tr>
                                            <td class="cell-nowrap"><?= (int) $order['id'] ?></td>
                                            <td class="cell-nowrap"><?= e(format_date($order['created_at'])) ?></td>
                                            <td class="cell-text"><?= e($order['user_name'] ?: $order['customer_name']) ?></td>
                                            <td class="cell-nowrap"><?= e(format_price((float) $order['total_amount'])) ?></td>
                                            <td class="cell-nowrap">
                                                <span class="admin-status <?= e(order_status_class($order['status'])) ?>">
                                                    <?= e(order_status_label($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a class="btn btn-secondary btn-small" href="<?= e(url('admin/order-view.php?id=' . (int) $order['id'])) ?>">Подробнее</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            </section>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
