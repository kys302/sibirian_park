<?php

declare(strict_types=1);

function get_cart(): array
{
    $cart = $_SESSION['cart'] ?? [];

    return is_array($cart) ? $cart : [];
}

function save_cart(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cart_count(): int
{
    return array_sum(get_cart());
}

function add_to_cart_session(int $productId, int $quantity): void
{
    $cart = get_cart();
    $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
    save_cart($cart);
}

function update_cart_item(int $productId, int $quantity): void
{
    $cart = get_cart();

    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }

    save_cart($cart);
}

function remove_cart_item(int $productId): void
{
    $cart = get_cart();
    unset($cart[$productId]);
    save_cart($cart);
}

function clear_cart_session(): void
{
    unset($_SESSION['cart']);
}

function cart_details(PDO $pdo): array
{
    $cart = get_cart();

    if (!$cart) {
        return [];
    }

    $productIds = array_map('intval', array_keys($cart));
    $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
    $statement = $pdo->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id IN ($placeholders)"
    );
    $statement->execute($productIds);

    $products = [];
    foreach ($statement->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    $items = [];
    $normalizedCart = [];

    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        $product = $products[$productId] ?? null;

        if (!$product || (int) $product['stock'] <= 0) {
            continue;
        }

        $quantity = max(1, min($quantity, (int) $product['stock']));
        $normalizedCart[$productId] = $quantity;
        $items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $quantity * (float) $product['price'],
        ];
    }

    save_cart($normalizedCart);

    return $items;
}

function cart_total(array $items): float
{
    return array_reduce(
        $items,
        static fn (float $sum, array $item): float => $sum + (float) $item['subtotal'],
        0.0
    );
}

function sanitize_redirect_target(string $target): string
{
    $target = trim($target);

    if ($target === '' || preg_match('#^https?://#i', $target)) {
        return url('cart.php');
    }

    return $target;
}

function cart_json_payload(?array $product, string $message, string $type = 'success'): array
{
    $payload = [
        'ok' => $type === 'success',
        'type' => $type,
        'message' => $message,
        'cartCount' => cart_count(),
    ];

    if ($product) {
        $productId = (int) $product['id'];
        $stock = (int) $product['stock'];
        $quantity = (int) (get_cart()[$productId] ?? 0);

        $payload['product'] = [
            'id' => $productId,
            'stock' => $stock,
            'quantity' => $quantity,
            'available' => max(0, $stock - $quantity),
            'maxReached' => $quantity >= $stock,
        ];
    }

    return $payload;
}

function finish_cart_action(string $redirectTo, string $type, string $message, ?array $product = null, int $statusCode = 200): never
{
    if (is_ajax_request()) {
        json_response(cart_json_payload($product, $message, $type), $statusCode);
    }

    set_flash($type, $message);
    redirect_raw($redirectTo);
}

function handle_cart_actions(PDO $pdo): void
{
    if (!is_post()) {
        return;
    }

    $action = $_POST['action'] ?? '';
    $cartActions = ['add_to_cart', 'cart_update', 'cart_remove', 'cart_clear'];

    if (!in_array($action, $cartActions, true)) {
        return;
    }

    $redirectTo = sanitize_redirect_target((string) ($_POST['redirect_to'] ?? url('cart.php')));

    if ($action === 'cart_clear') {
        clear_cart_session();
        finish_cart_action($redirectTo, 'success', 'Корзина очищена.');
    }

    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        finish_cart_action($redirectTo, 'error', 'Товар не найден.', null, 422);
    }

    $product = fetch_product($pdo, $productId);
    if (!$product) {
        finish_cart_action($redirectTo, 'error', 'Товар не найден или удалён.', null, 404);
    }

    $stock = (int) $product['stock'];
    $requestedQuantity = (int) ($_POST['quantity'] ?? 1);
    $currentInCart = (int) (get_cart()[$productId] ?? 0);

    if ($action === 'add_to_cart') {
        $requestedQuantity = max(1, $requestedQuantity);

        if ($stock <= 0) {
            finish_cart_action($redirectTo, 'error', 'Товар временно отсутствует в наличии.', $product, 409);
        }

        if (($currentInCart + $requestedQuantity) > $stock) {
            finish_cart_action($redirectTo, 'error', 'Нельзя добавить больше доступного остатка.', $product, 409);
        }

        add_to_cart_session($productId, $requestedQuantity);
        finish_cart_action($redirectTo, 'success', 'Товар добавлен в корзину.', $product);
    }

    if ($action === 'cart_update') {
        $requestedQuantity = max(0, $requestedQuantity);
        $message = 'Корзина обновлена.';
        $type = 'success';

        if ($requestedQuantity > $stock) {
            $requestedQuantity = $stock;
            $message = 'Количество товара скорректировано по доступному остатку.';
            $type = 'error';
        }

        update_cart_item($productId, $requestedQuantity);
        finish_cart_action($redirectTo, $type, $message, $product);
    }

    if ($action === 'cart_remove') {
        remove_cart_item($productId);
        finish_cart_action($redirectTo, 'success', 'Товар удалён из корзины.', $product);
    }
}
