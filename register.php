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
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        $errors['name'] = 'Укажите имя длиной не менее 2 символов.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Укажите корректный email.';
    } elseif (fetch_user_by_email($pdo, $email)) {
        $errors['email'] = 'Пользователь с таким email уже зарегистрирован.';
    }

    if ($password === '' || mb_strlen($password) < 6) {
        $errors['password'] = 'Пароль должен содержать минимум 6 символов.';
    }

    if ($confirmPassword !== $password) {
        $errors['confirm_password'] = 'Пароли не совпадают.';
    }

    if (!$errors) {
        $statement = $pdo->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'
        );
        $statement->execute([
            'name' => $name,
            'email' => mb_strtolower($email),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
        ]);

        $userId = (int) $pdo->lastInsertId();
        login_user([
            'id' => $userId,
            'name' => $name,
            'email' => mb_strtolower($email),
            'role' => 'user',
        ]);

        set_flash('success', 'Регистрация прошла успешно. Добро пожаловать!');
        redirect('profile.php');
    }
}

$pageTitle = 'Регистрация — Сибирский парк';
$pageDescription = 'Регистрация в интернет-магазине растений Сибирский парк.';
$pageKey = 'register';
$pageScripts = ['validation.js'];

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="section auth-section">
        <div class="container auth-layout">
            <div class="auth-info">
                <p class="eyebrow">Регистрация</p>
                <h1>Создание аккаунта</h1>
                <p>Зарегистрируйтесь, чтобы быстрее оформлять заказы и видеть историю покупок в личном кабинете.</p>
                <ul class="bullet-list card">
                    <li>Быстрое оформление повторных заказов.</li>
                    <li>Доступ к истории покупок и статусам заказов.</li>
                    <li>Удобный личный кабинет для клиентов магазина.</li>
                </ul>
            </div>

            <form id="register-form" class="card form-card auth-form" method="post" novalidate>
                <h2>Зарегистрироваться</h2>
                <div>
                    <label class="label" for="register-name">Имя</label>
                    <input id="register-name" name="name" class="input" type="text" value="<?= e((string) old('name')) ?>" required>
                    <p class="field-error"><?= e($errors['name'] ?? '') ?></p>
                </div>
                <div>
                    <label class="label" for="register-email">Email</label>
                    <input id="register-email" name="email" class="input" type="email" value="<?= e((string) old('email')) ?>" required>
                    <p class="field-error"><?= e($errors['email'] ?? '') ?></p>
                </div>
                <div>
                    <label class="label" for="register-password">Пароль</label>
                    <input id="register-password" name="password" class="input" type="password" required>
                    <p class="field-error"><?= e($errors['password'] ?? '') ?></p>
                </div>
                <div>
                    <label class="label" for="register-confirm-password">Повтор пароля</label>
                    <input id="register-confirm-password" name="confirm_password" class="input" type="password" required>
                    <p class="field-error"><?= e($errors['confirm_password'] ?? '') ?></p>
                </div>
                <button class="btn btn-primary btn-full" type="submit">Создать аккаунт</button>
                <p class="auth-switch">Уже зарегистрированы? <a class="text-link" href="<?= e(url('login.php')) ?>">Войти</a></p>
            </form>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
