<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();
handle_cart_actions($pdo);

$cartItems = cart_details($pdo);
$total = cart_total($cartItems);

$pageTitle = 'Корзина — Сибирский парк';
$pageDescription = 'Корзина интернет-магазина растений Сибирский парк.';
$pageKey = 'cart';
$pageScripts = ['cart.js'];

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact">
        <div class="container">
            <p class="eyebrow">Корзина</p>
            <h1>Ваши выбранные растения</h1>
            <p>Проверьте состав заказа, скорректируйте количество и переходите к оформлению.</p>
        </div>
    </section>

    <section class="section">
        <div class="container cart-layout">
            <div class="cart-items">
                <?php if ($cartItems): ?>
                    <?php foreach ($cartItems as $item): ?>
                        <?php $product = $item['product']; ?>
                        <article class="cart-item card">
                            <div class="cart-item-media">
                                <img src="<?= e(url(normalize_image_path($product['image'] ?? null))) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                            </div>
                            <div class="cart-item-body">
                                <div class="cart-item-top">
                                    <div>
                                        <p class="product-category"><?= e($product['category_name']) ?></p>
                                        <h3><?= e($product['name']) ?></h3>
                                        <p class="muted-text">Цена за единицу: <?= e(format_price((float) $product['price'])) ?></p>
                                    </div>
                                    <strong><?= e(format_price((float) $item['subtotal'])) ?></strong>
                                </div>

                                <div class="cart-item-actions">
                                    <form method="post" class="cart-quantity-form" data-cart-form>
                                        <input type="hidden" name="action" value="cart_update">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                        <label class="label" for="qty-<?= (int) $product['id'] ?>">Количество</label>
                                        <div class="quantity-inline">
                                            <input id="qty-<?= (int) $product['id'] ?>" class="input quantity-input" type="number" name="quantity" min="1" max="<?= (int) $product['stock'] ?>" value="<?= (int) $item['quantity'] ?>">
                                            <button class="btn btn-secondary btn-small" type="submit">Обновить</button>
                                        </div>
                                        <span class="stock">Доступно: <?= (int) $product['stock'] ?> шт.</span>
                                    </form>

                                    <form method="post">
                                        <input type="hidden" name="action" value="cart_remove">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                        <button class="btn btn-danger btn-small cart-remove-button" type="submit">Удалить</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Корзина пока пуста</h3>
                        <p>Добавьте растения из каталога, чтобы перейти к оформлению заказа.</p>
                        <a class="btn btn-secondary" href="<?= e(url('catalog.php')) ?>">Перейти в каталог</a>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="summary-card card" id="cart-summary">
                <h2>Итого</h2>
                <div class="summary-row">
                    <span>Товаров</span>
                    <strong><?= cart_count() ?></strong>
                </div>
                <div class="summary-row total">
                    <span>Сумма</span>
                    <strong><?= e(format_price($total)) ?></strong>
                </div>
                <div class="summary-actions">
                    <?php if ($cartItems): ?>
                        <a class="btn btn-primary btn-full" href="<?= e(url('checkout.php')) ?>">Перейти к оформлению</a>
                        <form method="post">
                            <input type="hidden" name="action" value="cart_clear">
                            <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                            <button class="btn btn-secondary btn-full" type="submit">Очистить корзину</button>
                        </form>
                    <?php endif; ?>
                    <a class="btn btn-secondary btn-full" href="<?= e(url('catalog.php')) ?>">Продолжить покупки</a>
                </div>
            </aside>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
