<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$statuses = ['new', 'processing', 'completed', 'cancelled'];

if (is_post() && ($_POST['action'] ?? '') === 'update_status') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    if ($orderId > 0 && in_array($status, $statuses, true)) {
        $statement = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $statement->execute([
            'status' => $status,
            'id' => $orderId,
        ]);
        set_flash('success', 'Статус заказа обновлён.');
    }

    redirect('admin/orders.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : ''));
}

$orders = fetch_orders($pdo, in_array($statusFilter, $statuses, true) ? $statusFilter : null);

$pageTitle = 'Заказы — Админка Сибирский парк';
$pageDescription = 'Управление заказами интернет-магазина.';
$pageKey = 'admin-orders';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Заказы</p>
            <h1>Список заказов</h1>
            <p>Просмотр, фильтрация и изменение статусов оформленных заказов.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <section class="admin-section card">
                <div class="admin-header">
                    <div>
                        <p class="eyebrow">Фильтрация</p>
                        <h2>Заказы покупателей</h2>
                    </div>
                    <form method="get" class="admin-filter-form admin-filter-form--compact">
                        <select name="status" class="select" data-auto-submit>
                            <option value="">Все статусы</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                    <?= e(order_status_label($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
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
                                    <th>Изменить статус</th>
                                    <th>Детали</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
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
                                            <form method="post" class="order-status-inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                                <select name="status" class="select" data-auto-submit>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                                            <?= e(order_status_label($status)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <a class="btn btn-secondary btn-small" href="<?= e(url('admin/order-view.php?id=' . (int) $order['id'])) ?>">Открыть</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
