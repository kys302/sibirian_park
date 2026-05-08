<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'profile.php');
}

$pdo = db();
$errors = [];

if (is_post()) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Укажите корректный email.';
    }

    if ($password === '' || mb_strlen($password) < 6) {
        $errors['password'] = 'Введите пароль длиной не менее 6 символов.';
    }

    if (!$errors) {
        $user = fetch_user_by_email($pdo, $email);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['common'] = 'Не удалось выполнить вход. Проверьте email и пароль.';
        } else {
            login_user($user);
            set_flash('success', 'Вы успешно вошли в аккаунт.');
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'profile.php');
        }
    }
}

$pageTitle = 'Вход — Сибирский парк';
$pageDescription = 'Вход в личный кабинет магазина Сибирский парк.';
$pageKey = 'login';
$pageScripts = ['validation.js'];

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="section auth-section">
        <div class="container auth-layout">
            <div class="auth-info">
                <p class="eyebrow">Вход</p>
                <h1>Личный кабинет</h1>
                <p>Войдите в аккаунт, чтобы просматривать историю заказов, повторять покупки и быстрее оформлять новые заявки.</p>
            </div>

            <form id="login-form" class="card form-card auth-form" method="post" novalidate>
                <h2>Войти</h2>
                <?php if (isset($errors['common'])): ?>
                    <div class="field-error field-error--box"><?= e($errors['common']) ?></div>
                <?php endif; ?>
                <div>
                    <label class="label" for="login-email">Email</label>
                    <input id="login-email" name="email" class="input" type="email" value="<?= e((string) old('email')) ?>" required>
                    <p class="field-error"><?= e($errors['email'] ?? '') ?></p>
                </div>
                <div>
                    <label class="label" for="login-password">Пароль</label>
                    <input id="login-password" name="password" class="input" type="password" required>
                    <p class="field-error"><?= e($errors['password'] ?? '') ?></p>
                </div>
                <button class="btn btn-primary btn-full" type="submit">Войти</button>
                <p class="auth-switch">Нет аккаунта? <a class="text-link" href="<?= e(url('register.php')) ?>">Зарегистрироваться</a></p>
            </form>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
