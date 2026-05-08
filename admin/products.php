<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();

if (is_post() && ($_POST['action'] ?? '') === 'delete_product') {
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($productId > 0) {
        $statement = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $productId]);
        set_flash('success', 'Товар удалён.');
    }

    redirect('admin/products.php');
}

$products = fetch_products($pdo, ['sort' => 'name_asc']);

$pageTitle = 'Товары — Админка Сибирский парк';
$pageDescription = 'Управление товарами интернет-магазина Сибирский парк.';
$pageKey = 'admin-products';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Товары</p>
            <h1>Управление каталогом</h1>
            <p>Добавление, редактирование и удаление товаров магазина.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <section class="admin-section card">
                <div class="admin-header">
                    <div>
                        <p class="eyebrow">Управление товарами</p>
                        <h2>Список товаров</h2>
                    </div>
                    <a class="btn btn-primary" href="<?= e(url('admin/product-create.php')) ?>">Добавить товар</a>
                </div>

                <div class="table-card card admin-table-shell">
                    <div class="admin-table-scroll">
                        <table class="admin-table-data admin-table-data--products">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Категория</th>
                                    <th>Цена</th>
                                    <th>Остаток</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="cell-nowrap"><?= (int) $product['id'] ?></td>
                                        <td class="cell-text"><?= e($product['name']) ?></td>
                                        <td class="cell-text"><?= e($product['category_name']) ?></td>
                                        <td class="cell-nowrap"><?= e(format_price((float) $product['price'])) ?></td>
                                        <td class="cell-nowrap"><?= (int) $product['stock'] ?> шт.</td>
                                        <td>
                                            <div class="table-actions table-actions--compact">
                                                <a class="btn btn-secondary btn-small" href="<?= e(url('admin/product-edit.php?id=' . (int) $product['id'])) ?>">Редактировать</a>
                                                <form method="post" onsubmit="return confirm('Удалить товар из каталога?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                                    <button class="btn btn-danger btn-small" type="submit">Удалить</button>
                                                </form>
                                            </div>
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
