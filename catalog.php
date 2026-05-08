<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();
handle_cart_actions($pdo);

$search = trim((string) ($_GET['search'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);
$sort = trim((string) ($_GET['sort'] ?? 'name_asc'));

$categories = fetch_categories($pdo);
$products = fetch_products($pdo, [
    'search' => $search,
    'category_id' => $categoryId,
    'sort' => $sort,
]);

$pageTitle = 'Каталог растений — Сибирский парк';
$pageDescription = 'Каталог плодовых и декоративных растений магазина Сибирский парк.';
$pageKey = 'catalog';
$pageScripts = ['catalog.js'];

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner">
        <div class="container">
            <p class="eyebrow">Каталог</p>
            <h1>Плодовые и декоративные растения</h1>
            <p>Выбирайте саженцы деревьев, кустарников, хвойных растений и многолетников для частного сада, дачи и озеленения участка.</p>
        </div>
    </section>

    <section class="section">
        <div class="container catalog-layout">
            <aside class="filter-panel card">
                <form method="get" data-catalog-form>
                    <div class="filter-block">
                        <label class="label" for="catalog-search">Поиск по названию</label>
                        <input id="catalog-search" class="input" type="search" name="search" value="<?= e($search) ?>" placeholder="Например, Туя Смарагд">
                    </div>

                    <div class="filter-block">
                        <label class="label" for="catalog-category">Категория</label>
                        <select id="catalog-category" class="select" name="category">
                            <option value="0">Все категории</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-block">
                        <label class="label" for="catalog-sort">Сортировка</label>
                        <select id="catalog-sort" class="select" name="sort">
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>По названию (А-Я)</option>
                            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>По названию (Я-А)</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Сначала дешевле</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Сначала дороже</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button class="btn btn-primary btn-full" type="submit">Применить</button>
                        <a class="btn btn-secondary btn-full" href="<?= e(url('catalog.php')) ?>">Сбросить</a>
                    </div>
                </form>
            </aside>

            <div class="catalog-content">
                <div class="catalog-toolbar card">
                    <div>
                        <h2>Ассортимент магазина</h2>
                        <p class="muted-text">Найдено товаров: <?= count($products) ?></p>
                    </div>
                </div>

                <?php if ($products): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <?= render_product_card($product, $_SERVER['REQUEST_URI']) ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Ничего не найдено</h3>
                        <p>Попробуйте изменить поисковый запрос или выбрать другую категорию.</p>
                        <a class="btn btn-secondary" href="<?= e(url('catalog.php')) ?>">Сбросить фильтры</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
