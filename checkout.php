<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = db();
$cartItems = cart_details($pdo);

if (!$cartItems) {
    set_flash('error', 'Оформление заказа недоступно: корзина пуста.');
    redirect('cart.php');
}

$currentUser = current_user();
$errors = [];
$deliveryOptions = ['Курьером', 'Самовывоз', 'Транспортной компанией'];
$paymentOptions = ['Наличными при получении', 'Банковской картой', 'Переводом'];

if (is_post()) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $deliveryMethod = trim((string) ($_POST['delivery_method'] ?? ''));
    $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));

    if ($name === '' || mb_strlen($name) < 3) {
        $errors['name'] = 'Укажите ФИО длиной не менее 3 символов.';
    }

    if ($phone === '' || mb_strlen(preg_replace('/\D+/', '', $phone)) < 10) {
        $errors['phone'] = 'Укажите корректный телефон.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Укажите корректный email.';
    }

    if ($address === '' || mb_strlen($address) < 8) {
        $errors['address'] = 'Укажите адрес доставки.';
    }

    if (!in_array($deliveryMethod, $deliveryOptions, true)) {
        $errors['delivery_method'] = 'Выберите способ доставки.';
    }

    if (!in_array($paymentMethod, $paymentOptions, true)) {
        $errors['payment_method'] = 'Выберите способ оплаты.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $cart = get_cart();
            $productIds = array_map('intval', array_keys($cart));
            $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
            $statement = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) FOR UPDATE");
            $statement->execute($productIds);

            $products = [];
            foreach ($statement->fetchAll() as $product) {
                $products[(int) $product['id']] = $product;
            }

            $totalAmount = 0.0;
            $orderRows = [];

            foreach ($cart as $productId => $quantity) {
                $productId = (int) $productId;
                $quantity = (int) $quantity;
                $product = $products[$productId] ?? null;

                if (!$product) {
                    throw new RuntimeException('Один из товаров больше недоступен.');
                }

                if ((int) $product['stock'] < $quantity) {
                    throw new RuntimeException('Недостаточно товара на складе для оформления заказа.');
                }

                $price = (float) $product['price'];
                $subtotal = $price * $quantity;
                $totalAmount += $subtotal;

                $orderRows[] = [
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            $orderStatement = $pdo->prepare(
                'INSERT INTO orders (
                    user_id,
                    customer_name,
                    customer_phone,
                    customer_email,
                    customer_address,
                    delivery_method,
                    payment_method,
                    total_amount,
                    status
                 ) VALUES (
                    :user_id,
                    :customer_name,
                    :customer_phone,
                    :customer_email,
                    :customer_address,
                    :delivery_method,
                    :payment_method,
                    :total_amount,
                    :status
                 )'
            );
            $orderStatement->execute([
                'user_id' => $currentUser['id'] ?? null,
                'customer_name' => $name,
                'customer_phone' => $phone,
                'customer_email' => mb_strtolower($email),
                'customer_address' => $address,
                'delivery_method' => $deliveryMethod,
                'payment_method' => $paymentMethod,
                'total_amount' => $totalAmount,
                'status' => 'new',
            ]);

            $orderId = (int) $pdo->lastInsertId();

            $itemStatement = $pdo->prepare(
                'INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    subtotal
                 ) VALUES (
                    :order_id,
                    :product_id,
                    :product_name,
                    :price,
                    :quantity,
                    :subtotal
                 )'
            );

            $stockStatement = $pdo->prepare(
                'UPDATE products SET stock = stock - :quantity WHERE id = :product_id'
            );

            foreach ($orderRows as $row) {
                $itemStatement->execute([
                    'order_id' => $orderId,
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'subtotal' => $row['subtotal'],
                ]);

                $stockStatement->execute([
                    'quantity' => $row['quantity'],
                    'product_id' => $row['product_id'],
                ]);
            }

            $pdo->commit();
            clear_cart_session();

            set_flash('success', 'Заказ №' . $orderId . ' успешно оформлен.');
            redirect($currentUser ? 'profile.php' : 'index.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors['common'] = $exception->getMessage();
            $cartItems = cart_details($pdo);
        }
    }
}

$pageTitle = 'Оформление заказа — Сибирский парк';
$pageDescription = 'Оформление заказа интернет-магазина растений Сибирский парк.';
$pageKey = 'checkout';
$pageScripts = ['validation.js', 'checkout.js'];

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact">
        <div class="container">
            <p class="eyebrow">Оформление заказа</p>
            <h1>Данные для доставки и связи</h1>
            <p>Заполните контактную информацию, и мы свяжемся с вами для подтверждения заказа.</p>
        </div>
    </section>

    <section class="section">
        <div class="container checkout-layout">
            <form id="checkout-form" class="card form-card" method="post" novalidate>
                <div class="form-section">
                    <h2>Контактные данные</h2>

                    <?php if (isset($errors['common'])): ?>
                        <div class="field-error field-error--box"><?= e($errors['common']) ?></div>
                    <?php endif; ?>

                    <div class="form-grid two-columns">
                        <div>
                            <label class="label" for="checkout-name">ФИО</label>
                            <input id="checkout-name" name="name" class="input" type="text" value="<?= e((string) old('name', $currentUser['name'] ?? '')) ?>" required>
                            <p class="field-error"><?= e($errors['name'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="label" for="checkout-phone">Телефон</label>
                            <input id="checkout-phone" name="phone" class="input" type="tel" value="<?= e((string) old('phone')) ?>" placeholder="+7 (___) ___-__-__" required>
                            <p class="field-error"><?= e($errors['phone'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="label" for="checkout-email">Email</label>
                            <input id="checkout-email" name="email" class="input" type="email" value="<?= e((string) old('email', $currentUser['email'] ?? '')) ?>" required>
                            <p class="field-error"><?= e($errors['email'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="label" for="checkout-address">Адрес доставки</label>
                            <input id="checkout-address" name="address" class="input" type="text" value="<?= e((string) old('address')) ?>" required>
                            <p class="field-error"><?= e($errors['address'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="label" for="delivery-method">Способ доставки</label>
                            <select id="delivery-method" name="delivery_method" class="select" required>
                                <option value="">Выберите вариант</option>
                                <?php foreach ($deliveryOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= old('delivery_method') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-error"><?= e($errors['delivery_method'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="label" for="payment-method">Способ оплаты</label>
                            <select id="payment-method" name="payment_method" class="select" required>
                                <option value="">Выберите вариант</option>
                                <?php foreach ($paymentOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= old('payment_method') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-error"><?= e($errors['payment_method'] ?? '') ?></p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Подтвердить заказ</button>
                    <a class="btn btn-secondary" href="<?= e(url('cart.php')) ?>">Вернуться в корзину</a>
                </div>
            </form>

            <aside class="summary-card card">
                <h2>Состав заказа</h2>
                <div class="summary-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="summary-item">
                            <strong><?= e($item['product']['name']) ?></strong>
                            <div class="summary-row">
                                <span><?= (int) $item['quantity'] ?> шт. × <?= e(format_price((float) $item['product']['price'])) ?></span>
                                <strong><?= e(format_price((float) $item['subtotal'])) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-row total">
                    <span>Итого</span>
                    <strong><?= e(format_price(cart_total($cartItems))) ?></strong>
                </div>
            </aside>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
