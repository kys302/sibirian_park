<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_current_user(): void
{
    unset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Для доступа к этой странице необходимо войти в аккаунт.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Сначала выполните вход в систему.');
        redirect('login.php');
    }

    if (!is_admin()) {
        set_flash('error', 'Доступ к разделу администратора ограничен.');
        redirect('index.php');
    }
}
