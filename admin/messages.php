<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();
$messages = fetch_contact_messages($pdo);

$pageTitle = 'Обращения — Админка Сибирский парк';
$pageDescription = 'Сообщения формы обратной связи интернет-магазина.';
$pageKey = 'admin-messages';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Обратная связь</p>
            <h1>Обращения с сайта</h1>
            <p>Сообщения покупателей, отправленные через форму обратной связи.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <section class="admin-section card">
                <div class="admin-header">
                    <div>
                        <p class="eyebrow">Журнал обращений</p>
                        <h2>Все сообщения</h2>
                    </div>
                </div>

                <div class="table-card card admin-table-shell">
                    <div class="admin-table-scroll">
                        <table class="admin-table-data">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата</th>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Сообщение</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($messages): ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr>
                                            <td class="cell-nowrap"><?= (int) $message['id'] ?></td>
                                            <td class="cell-nowrap"><?= e(format_date($message['created_at'])) ?></td>
                                            <td class="cell-nowrap"><?= e($message['name']) ?></td>
                                            <td class="cell-nowrap"><?= e($message['email']) ?></td>
                                            <td class="cell-text"><?= e($message['message']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="cell-text">Сообщений пока нет.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
