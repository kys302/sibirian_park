<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Сибирский парк';
$pageDescription = $pageDescription ?? 'Интернет-магазин плодовых и декоративных растений.';
$pageKey = $pageKey ?? '';
$bodyPage = $bodyPage ?? $pageKey;
$pageScripts = $pageScripts ?? [];
$isAdminArea = str_starts_with((string) $pageKey, 'admin');
$user = current_user();
$flashMessages = get_flash_messages();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

function nav_is_active(array $targets, string $currentScript): string
{
    return in_array($currentScript, $targets, true) ? 'is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/pages.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/responsive.css')) ?>">
</head>
<body data-page="<?= e($bodyPage) ?>">
    <div class="site-shell">
        <header class="site-header <?= $isAdminArea ? 'site-header--admin' : '' ?>">
            <div class="container site-header-inner">
                <a class="brand" href="<?= e($isAdminArea ? url('admin/index.php') : url('index.php')) ?>" aria-label="Сибирский парк">
                    <span class="brand-copy">
                        <span class="brand-title">Сибирский парк</span>
                        <span class="brand-subtitle"><?= $isAdminArea ? 'служебный раздел' : 'магазин и питомник растений' ?></span>
                    </span>
                </a>

                <button class="menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false" data-menu-toggle>
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <nav class="site-nav" data-site-nav>
                    <?php if ($isAdminArea): ?>
                        <a class="<?= nav_is_active(['index.php'], $currentScript) ?>" href="<?= e(url('admin/index.php')) ?>">Панель</a>
                        <a class="<?= nav_is_active(['products.php', 'product-create.php', 'product-edit.php'], $currentScript) ?>" href="<?= e(url('admin/products.php')) ?>">Товары</a>
                        <a class="<?= nav_is_active(['orders.php', 'order-view.php'], $currentScript) ?>" href="<?= e(url('admin/orders.php')) ?>">Заказы</a>
                        <a class="<?= nav_is_active(['messages.php'], $currentScript) ?>" href="<?= e(url('admin/messages.php')) ?>">Обращения</a>
                    <?php else: ?>
                        <a class="<?= nav_is_active(['index.php'], $currentScript) ?>" href="<?= e(url('index.php')) ?>">Главная</a>
                        <a class="<?= nav_is_active(['catalog.php', 'product.php'], $currentScript) ?>" href="<?= e(url('catalog.php')) ?>">Каталог</a>
                        <a class="<?= nav_is_active(['about.php'], $currentScript) ?>" href="<?= e(url('about.php')) ?>">О магазине</a>
                        <a class="<?= nav_is_active(['contacts.php'], $currentScript) ?>" href="<?= e(url('contacts.php')) ?>">Контакты</a>
                        <?php if ($user): ?>
                            <a class="<?= nav_is_active(['profile.php'], $currentScript) ?>" href="<?= e(url('profile.php')) ?>">Кабинет</a>
                        <?php endif; ?>
                        <?php if ($user && $user['role'] === 'admin'): ?>
                            <a href="<?= e(url('admin/index.php')) ?>">Админка</a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="site-nav-mobile-meta">
                        <?php if (!$isAdminArea): ?>
                            <a class="site-nav-service-link" href="<?= e(url('cart.php')) ?>">
                                <span>Корзина</span>
                                <strong><span data-cart-count><?= cart_count() ?></span> шт.</strong>
                            </a>
                        <?php endif; ?>

                        <?php if ($user): ?>
                            <div class="site-nav-mobile-user">
                                <strong><?= e($user['name']) ?></strong>
                                <span><?= $isAdminArea ? 'Панель управления' : 'Личный кабинет' ?></span>
                            </div>
                            <a class="btn btn-secondary btn-small" href="<?= e(url('logout.php')) ?>">Выйти</a>
                        <?php else: ?>
                            <a class="btn btn-secondary btn-small" href="<?= e(url('login.php')) ?>">Войти</a>
                        <?php endif; ?>
                    </div>
                </nav>

                <div class="header-actions">
                    <?php if (!$isAdminArea): ?>
                        <a class="cart-pill" href="<?= e(url('cart.php')) ?>">
                            <span>Корзина</span>
                            <span class="cart-pill__count" data-cart-count><?= cart_count() ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <div class="header-user">
                            <strong><?= e($user['name']) ?></strong>
                            <span><?= $user['role'] === 'admin' ? 'Администратор' : 'Покупатель' ?></span>
                        </div>
                        <a class="btn btn-secondary btn-small" href="<?= e(url('logout.php')) ?>">Выйти</a>
                    <?php else: ?>
                        <a class="btn btn-secondary btn-small" href="<?= e(url('login.php')) ?>">Войти</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <?php if ($flashMessages): ?>
            <div class="flash-stack" aria-live="polite" aria-atomic="true">
                <?php foreach ($flashMessages as $message): ?>
                    <div class="flash flash-<?= e($message['type']) ?>">
                        <?= e($message['message']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
