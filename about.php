<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'О магазине — Сибирский парк';
$pageDescription = 'Краткая информация о магазине растений Сибирский парк.';
$pageKey = 'about';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner">
        <div class="container">
            <p class="eyebrow">О компании</p>
            <h1>Сибирский парк</h1>
            <p>Интернет-магазин плодовых и декоративных растений для сада, дачи и озеленения участка.</p>
        </div>
    </section>

    <section class="section">
        <div class="container content-grid">
            <article class="card content-card">
                <h2>Чем занимается магазин</h2>
                <p>«Сибирский парк» специализируется на продаже саженцев плодовых деревьев, ягодных кустарников, хвойных растений и многолетних цветов.</p>
                <p>Ассортимент ориентирован на покупателей из Новосибирска и других регионов Сибири, где важны зимостойкость, адаптация к климату и понятные рекомендации по уходу.</p>
            </article>

            <article class="card content-card">
                <h2>Подход к работе</h2>
                <p>Магазин делает акцент на удобном подборе растений, наглядном каталоге и понятной информации по уходу, чтобы покупателю было проще выбрать подходящие культуры для своего участка.</p>
                <p>На сайте можно быстро перейти от выбора товара к оформлению заказа, а также отслеживать историю покупок в личном кабинете.</p>
            </article>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
