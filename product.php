<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();
handle_cart_actions($pdo);

$productId = (int) ($_GET['id'] ?? 0);
$product = fetch_product($pdo, $productId);

if (!$product) {
    $pageTitle = 'Товар не найден — Сибирский парк';
    $pageDescription = 'Запрошенный товар не найден.';
    $pageKey = 'product';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="page-main">
        <section class="section">
            <div class="container">
                <div class="product-not-found card">
                    <h2>Товар не найден</h2>
                    <p>Возможно, позиция была снята с продажи или ссылка устарела.</p>
                    <a class="btn btn-secondary" href="<?= e(url('catalog.php')) ?>">Вернуться в каталог</a>
                </div>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/includes/footer.php'; exit; ?>
    <?php
}

$relatedProducts = fetch_related_products($pdo, (int) $product['category_id'], (int) $product['id'], 3);
$careProfiles = [
    1 => ['Тип растения' => 'Плодовое дерево', 'Уход' => 'Умеренный', 'Почва' => 'Плодородная и дренированная', 'Полив' => 'Регулярный', 'Освещение' => 'Солнечное место'],
    2 => ['Тип растения' => 'Плодовый кустарник', 'Уход' => 'Неприхотливый', 'Почва' => 'Рыхлая и влагоёмкая', 'Полив' => 'Умеренный', 'Освещение' => 'Солнце или полутень'],
    3 => ['Тип растения' => 'Декоративный кустарник', 'Уход' => 'Регулярный', 'Почва' => 'Питательная садовая почва', 'Полив' => 'Без застоя воды', 'Освещение' => 'Солнце или полутень'],
    4 => ['Тип растения' => 'Хвойное растение', 'Уход' => 'Лёгкий', 'Почва' => 'Лёгкая и дренированная', 'Полив' => 'Умеренный', 'Освещение' => 'Солнце или полутень'],
    5 => ['Тип растения' => 'Многолетнее растение', 'Уход' => 'Умеренный', 'Почва' => 'Плодородная садовая почва', 'Полив' => 'Регулярный в период роста', 'Освещение' => 'Солнце или полутень'],
];
$profile = $careProfiles[(int) $product['category_id']] ?? ['Тип растения' => 'Садовое растение', 'Уход' => 'Умеренный', 'Почва' => 'Садовая почва', 'Полив' => 'Умеренный', 'Освещение' => 'Светлое место'];

$pageTitle = $product['name'] . ' — Сибирский парк';
$pageDescription = $product['description'];
$pageKey = 'product';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="section">
        <div class="container">
            <nav class="breadcrumbs">
                <a href="<?= e(url('index.php')) ?>">Главная</a> /
                <a href="<?= e(url('catalog.php')) ?>">Каталог</a> /
                <a href="<?= e(url('catalog.php' . build_catalog_query(['category' => (string) $product['category_id']]))) ?>"><?= e($product['category_name']) ?></a> /
                <span><?= e($product['name']) ?></span>
            </nav>

            <div class="product-layout">
                <div class="product-media">
                    <img src="<?= e(url(normalize_image_path($product['image'] ?? null))) ?>" alt="<?= e($product['name']) ?>">
                </div>

                <article class="product-main card">
                    <p class="eyebrow"><?= e($product['category_name']) ?></p>
                    <h1><?= e($product['name']) ?></h1>
                    <div class="product-meta">
                        <span class="status-badge <?= (int) $product['stock'] > 0 ? '' : 'status-badge--muted' ?>">
                            <span class="status-dot"></span><?= (int) $product['stock'] > 0 ? 'В наличии' : 'Нет в наличии' ?>
                        </span>
                        <span class="status-badge">Остаток: <?= (int) $product['stock'] ?> шт.</span>
                    </div>
                    <p class="price"><?= e(format_price((float) $product['price'])) ?></p>
                    <p class="product-description"><?= e($product['description']) ?></p>

                    <div class="product-purchase card" data-cart-product data-product-id="<?= (int) $product['id'] ?>">
                        <div class="product-purchase__head">
                            <div>
                                <span class="stock">В наличии: <?= (int) $product['stock'] ?> шт.</span>
                                <p class="product-purchase__hint">Количество товара в корзине не может превышать доступный остаток.</p>
                            </div>
                            <p class="product-purchase__price"><?= e(format_price((float) $product['price'])) ?></p>
                        </div>
                        <?= render_cart_stepper((int) $product['id'], (int) $product['stock'], 'detail') ?>
                        <div class="product-purchase__actions">
                            <?= render_add_to_cart_form($product, $_SERVER['REQUEST_URI'], 'btn btn-primary') ?>
                            <a class="btn btn-secondary" href="<?= e(url('catalog.php' . build_catalog_query(['category' => (string) $product['category_id']]))) ?>">Вернуться в каталог</a>
                        </div>
                    </div>

                    <div class="plant-info card">
                        <div class="plant-info-head">
                            <p class="eyebrow">Информация о растении</p>
                            <h3>Условия выращивания</h3>
                        </div>
                        <div class="plant-info-grid">
                            <?php foreach ($profile as $label => $value): ?>
                                <div class="plant-info-item">
                                    <span><?= e($label) ?></span>
                                    <strong><?= e($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Рекомендуем</p>
                    <h2>Похожие растения</h2>
                </div>
            </div>

            <?php if ($relatedProducts): ?>
                <div class="product-grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <?= render_product_card($relatedProduct, $_SERVER['REQUEST_URI']) ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Похожие товары пока не найдены</h3>
                    <p>Для этой категории пока нет дополнительных предложений.</p>
                    <a class="btn btn-secondary" href="<?= e(url('catalog.php')) ?>">Перейти в каталог</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
