<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();
$errors = [];

if (is_post()) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || mb_strlen($name) < 2) {
        $errors['name'] = 'Укажите имя длиной не менее 2 символов.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Укажите корректный email.';
    }

    if ($message === '' || mb_strlen($message) < 10) {
        $errors['message'] = 'Сообщение должно содержать не менее 10 символов.';
    }

    if (!$errors) {
        try {
            store_contact_message($pdo, [
                'name' => $name,
                'email' => mb_strtolower($email),
                'message' => $message,
            ]);
            set_flash('success', 'Сообщение принято. Мы свяжемся с вами по указанным контактам.');
            redirect('contacts.php');
        } catch (RuntimeException $exception) {
            $errors['common'] = $exception->getMessage();
        }
    }
}

$pageTitle = 'Контакты — Сибирский парк';
$pageDescription = 'Контакты и режим работы магазина Сибирский парк.';
$pageKey = 'contacts';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner">
        <div class="container">
            <p class="eyebrow">Контакты</p>
            <h1>Связаться с магазином</h1>
            <p>Контактная информация, режим работы и адрес компании «Сибирский парк».</p>
        </div>
    </section>

    <section class="section">
        <div class="container content-grid">
            <article class="card content-card">
                <h2>Контактные данные</h2>
                <p><strong>Телефон:</strong> <a href="tel:+73830000000">+7 (383) 000-00-00</a></p>
                <p><strong>Email:</strong> <a href="mailto:info@sibirpark.local">info@sibirpark.local</a></p>
                <p><strong>Адрес:</strong> г. Новосибирск, ул. Садовая, 12</p>
                <p><strong>Режим работы:</strong> ежедневно с 09:00 до 19:00</p>
            </article>

            <article class="card content-card">
                <h2>Форма обратной связи</h2>
                <form class="contact-form" method="post" novalidate>
                    <?php if (isset($errors['common'])): ?>
                        <div class="field-error field-error--box"><?= e($errors['common']) ?></div>
                    <?php endif; ?>

                    <label class="label" for="contact-name">Имя</label>
                    <input id="contact-name" name="name" class="input" type="text" value="<?= e((string) old('name')) ?>" placeholder="Ваше имя">
                    <p class="field-error"><?= e($errors['name'] ?? '') ?></p>

                    <label class="label" for="contact-email">Email</label>
                    <input id="contact-email" name="email" class="input" type="email" value="<?= e((string) old('email')) ?>" placeholder="mail@example.com">
                    <p class="field-error"><?= e($errors['email'] ?? '') ?></p>

                    <label class="label" for="contact-message">Сообщение</label>
                    <textarea id="contact-message" name="message" class="textarea" rows="5" placeholder="Ваш вопрос или комментарий"><?= e((string) old('message')) ?></textarea>
                    <p class="field-error"><?= e($errors['message'] ?? '') ?></p>

                    <button class="btn btn-primary" type="submit">Отправить</button>
                    <p class="mini-note">Для оперативной связи рекомендуем использовать телефон или электронную почту.</p>
                </form>
            </article>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
