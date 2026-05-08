<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();
handle_cart_actions($pdo);

$featuredProducts = fetch_featured_products($pdo, 6);
$categories = fetch_categories($pdo);

$pageTitle = 'Сибирский парк — интернет-магазин растений';
$pageDescription = 'Плодовые и декоративные растения для сада, дачи и уютного участка.';
$pageKey = 'home';

require_once __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero-section hero-section--immersive">
        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Питомник и магазин растений · Новосибирск</p>
                <h1>Сибирский парк</h1>
                <p class="hero-lead">
                    Плодовые и декоративные растения для сада, дачи и уютного участка.
                    В каталоге собраны зимостойкие культуры, подходящие для посадки в сибирском климате.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?= e(url('catalog.php')) ?>">Перейти в каталог</a>
                    <a class="btn btn-secondary" href="#advantages">Преимущества магазина</a>
                </div>
                <ul class="hero-metrics">
                    <li><strong>15+</strong><span>товаров в сезонном каталоге</span></li>
                    <li><strong>5</strong><span>основных категорий растений</span></li>
                    <li><strong>Ежедневно</strong><span>консультации по подбору и уходу</span></li>
                </ul>
            </div>

            <div class="hero-card">
                <div class="hero-card-surface">
                    <p class="hero-card-title">Подбор растений для вашего участка</p>
                    <ul class="hero-picks">
                        <li>
                            <span>Плодовый сад</span>
                            <strong>Яблони, груши, сливы</strong>
                        </li>
                        <li>
                            <span>Живые изгороди</span>
                            <strong>Туи, можжевельники, спиреи</strong>
                        </li>
                        <li>
                            <span>Цветущие композиции</span>
                            <strong>Гортензии, розы, многолетники</strong>
                        </li>
                    </ul>
                    <div class="hero-note">
                        <p>Поможем подобрать растения для плодового сада, декоративных посадок и озеленения участка.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Популярные позиции</p>
                    <h2>Растения, которые чаще всего выбирают</h2>
                </div>
                <a class="text-link" href="<?= e(url('catalog.php')) ?>">Смотреть весь каталог</a>
            </div>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                        <?= render_product_card($product, $_SERVER['REQUEST_URI']) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-soft section-soft--garden">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Категории</p>
                    <h2>Каталог по направлениям</h2>
                </div>
            </div>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a class="category-card card" href="<?= e(url('catalog.php' . build_catalog_query(['category' => (string) $category['id']]))) ?>">
                        <div class="category-visual">
                            <img src="<?= e(url(normalize_image_path($category['image'] ?? null))) ?>" alt="<?= e($category['name']) ?>">
                        </div>
                        <h3><?= e($category['name']) ?></h3>
                        <p><?= e($category['description']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-image section-image--story">
        <div class="container">
            <div class="story-panel">
                <div class="story-copy">
                    <p class="eyebrow">О магазине</p>
                    <h2>Растения для плодового сада, декоративных посадок и уютного зелёного участка</h2>
                    <p>
                        В «Сибирском парке» собран ассортимент для частных садов, дач и ландшафтных композиций:
                        от плодовых деревьев и ягодных кустарников до хвойных культур и многолетников.
                    </p>
                </div>
                <div class="story-points">
                    <div class="story-point">
                        <strong>Подбор по назначению</strong>
                        <span>Для сада, живых изгородей, парадных клумб и сезонных посадок.</span>
                    </div>
                    <div class="story-point">
                        <strong>Ассортимент для климата Сибири</strong>
                        <span>Ставка на культуры, которые уверенно чувствуют себя в регионе.</span>
                    </div>
                    <div class="story-point">
                        <strong>Помощь при выборе</strong>
                        <span>Подскажем сочетания растений и базовые условия выращивания.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="advantages" class="section">
        <div class="container">
            <div class="section-heading narrow">
                <div>
                    <p class="eyebrow">Преимущества</p>
                    <h2>Всё для здорового и красивого сада</h2>
                </div>
            </div>
            <div class="advantages-grid">
                <article class="feature-card">
                    <h3>Качественный посадочный материал</h3>
                    <p>Подбираем растения с хорошей приживаемостью и крепкой корневой системой.</p>
                </article>
                <article class="feature-card">
                    <h3>Широкий выбор культур</h3>
                    <p>В ассортименте плодовые деревья, кустарники, хвойные и многолетние цветы.</p>
                </article>
                <article class="feature-card">
                    <h3>Помощь с выбором</h3>
                    <p>Подскажем подходящие сорта и декоративные решения для сада, дачи и участка.</p>
                </article>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
