<?php

declare(strict_types=1);

function app_root_url(): string
{
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = preg_replace('#/admin/.*$#', '', $scriptName);
    $baseUrl = preg_replace('#/[^/]+\.php$#', '', $baseUrl);

    return $baseUrl ?: '';
}

function url(string $path = ''): string
{
    $base = app_root_url();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base !== '' ? $base : '/';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return ($base !== '' ? $base : '') . '/' . $path;
}

function asset_url(string $path): string
{
    $normalizedPath = ltrim($path, '/');
    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
    $assetUrl = url($normalizedPath);

    return is_file($absolutePath) ? $assetUrl . '?v=' . filemtime($absolutePath) : $assetUrl;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path = ''): never
{
    header('Location: ' . url($path));
    exit;
}

function redirect_raw(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax_request(): bool
{
    $requestedWith = mb_strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = mb_strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return is_array($messages) ? $messages : [];
}

function old(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function format_price(float|int|string $value): string
{
    return number_format((float) $value, 0, ',', ' ') . ' ₽';
}

function format_date(string $value): string
{
    $timestamp = strtotime($value);

    return $timestamp ? date('d.m.Y H:i', $timestamp) : $value;
}

function order_status_label(string $status): string
{
    return [
        'new' => 'Новый',
        'processing' => 'В обработке',
        'completed' => 'Завершён',
        'cancelled' => 'Отменён',
    ][$status] ?? $status;
}

function order_status_class(string $status): string
{
    return [
        'new' => 'admin-status--new',
        'processing' => 'admin-status--processing',
        'completed' => 'admin-status--confirmed',
        'cancelled' => 'admin-status--cancelled',
    ][$status] ?? 'admin-status--default';
}

function normalize_image_path(?string $path): string
{
    if (!$path) {
        return 'assets/images/products/category-placeholder.svg';
    }

    return trim($path);
}

function save_uploaded_image(string $fieldName, ?string $existingPath = null): ?string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return $existingPath;
    }

    $file = $_FILES[$fieldName];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Не удалось загрузить изображение. Попробуйте выбрать файл ещё раз.');
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Временный файл изображения не найден.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!$mimeType || !isset($allowed[$mimeType])) {
        throw new RuntimeException('Разрешены только изображения JPG, PNG, WEBP или GIF.');
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Не удалось создать папку для загруженных изображений.');
    }

    $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mimeType];
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('Не удалось сохранить загруженное изображение.');
    }

    return 'assets/images/uploads/' . $fileName;
}

function ensure_contact_messages_table(PDO $pdo): void
{
    static $isReady = false;

    if ($isReady) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $isReady = true;
}

function store_contact_message(PDO $pdo, array $payload): void
{
    ensure_contact_messages_table($pdo);

    $statement = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, message)
         VALUES (:name, :email, :message)'
    );

    $ok = $statement->execute([
        'name' => trim((string) ($payload['name'] ?? '')),
        'email' => trim((string) ($payload['email'] ?? '')),
        'message' => trim((string) ($payload['message'] ?? '')),
    ]);

    if (!$ok) {
        throw new RuntimeException('Не удалось сохранить сообщение формы в базе данных.');
    }
}

function slugify(string $value): string
{
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    $value = mb_strtolower(trim($value));
    $value = strtr($value, $map);
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value);

    return trim((string) $value, '-');
}

function product_cart_quantity(int $productId): int
{
    $cart = get_cart();

    return (int) ($cart[$productId] ?? 0);
}

function render_cart_state_meta(int $productId, int $stock, string $variant = 'card'): string
{
    $inCart = product_cart_quantity($productId);
    $availableToAdd = max(0, $stock - $inCart);
    $classes = 'product-cart-meta' . ($variant === 'detail' ? ' product-cart-meta--detail' : '');
    $availabilityLabel = $availableToAdd > 0 ? 'Можно добавить ещё' : 'Доступный лимит выбран';
    $availabilitySuffix = $availableToAdd > 0 ? ' шт.' : '';

    return '
        <div class="' . e($classes) . '" data-cart-state>
            <span class="product-cart-meta__line">
                В корзине: <strong data-cart-in-cart>' . $inCart . '</strong> из ' . $stock . ' шт.
            </span>
            <span class="product-cart-meta__line product-cart-meta__line--muted">
                ' . e($availabilityLabel) . ' <strong data-cart-available>' . $availableToAdd . '</strong>' . e($availabilitySuffix) . '
            </span>
        </div>
    ';
}

function render_cart_stepper(int $productId, int $stock, string $variant = 'card'): string
{
    $inCart = product_cart_quantity($productId);
    $availableToAdd = max(0, $stock - $inCart);
    $classes = 'product-cart-control' . ($variant === 'detail' ? ' product-cart-control--detail' : '');
    $classes .= $inCart > 0 ? ' is-visible' : '';

    return '
        <div class="' . e($classes) . '" data-cart-control data-stock="' . $stock . '">
            <button class="cart-stepper-btn" type="button" data-cart-step="-1" aria-label="Уменьшить количество">-</button>
            <span class="cart-stepper-value"><strong data-cart-in-cart>' . $inCart . '</strong> шт.</span>
            <button class="cart-stepper-btn" type="button" data-cart-step="1" aria-label="Увеличить количество">+</button>
            <span class="cart-stepper-note">ещё <strong data-cart-available>' . $availableToAdd . '</strong></span>
        </div>
    ';
}

function render_add_to_cart_form(array $product, string $redirectTo, string $buttonClass = 'btn btn-primary btn-small'): string
{
    $stock = (int) $product['stock'];
    $inCart = product_cart_quantity((int) $product['id']);
    $formClass = 'inline-form add-to-cart-form' . ($inCart > 0 ? ' is-hidden' : '');
    $isLimitReached = $inCart >= $stock;

    if ($stock <= 0) {
        return '<button class="' . e($buttonClass) . '" type="button" disabled>Нет в наличии</button>';
    }

    if (false && $inCart >= $stock) {
        return '<button class="' . e($buttonClass) . '" type="button" disabled>Максимум в корзине</button>';
    }

    return '
        <form method="post" class="' . e($formClass) . '" data-add-to-cart-form data-product-id="' . (int) $product['id'] . '">
            <input type="hidden" name="action" value="add_to_cart">
            <input type="hidden" name="product_id" value="' . (int) $product['id'] . '">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="redirect_to" value="' . e($redirectTo) . '">
            <button class="' . e($buttonClass) . '" type="submit" data-add-to-cart-button>В корзину</button>
        </form>
    ';
}

function render_product_card(array $product, string $redirectTo): string
{
    $productId = (int) $product['id'];
    $stock = (int) $product['stock'];
    $detailUrl = url('product.php?id=' . $productId);
    $stockLabel = (int) $product['stock'] > 0
        ? 'В наличии: ' . (int) $product['stock'] . ' шт.'
        : 'Нет в наличии';

    return '
        <article class="product-card card" data-cart-product data-product-id="' . $productId . '">
            <a class="product-card-image" href="' . e($detailUrl) . '">
                <img src="' . e(url(normalize_image_path($product['image'] ?? null))) . '" alt="' . e($product['name']) . '" loading="lazy">
            </a>
            <div class="product-card-head">
                <div>
                    <p class="product-category">' . e($product['category_name'] ?? 'Категория') . '</p>
                    <h3><a href="' . e($detailUrl) . '">' . e($product['name']) . '</a></h3>
                </div>
                <span class="price">' . e(format_price((float) $product['price'])) . '</span>
            </div>
            <p>' . e($product['description']) . '</p>
            <div class="product-card-footer">
                <span class="stock">' . e($stockLabel) . '</span>
                ' . render_cart_stepper($productId, $stock) . '
                <div class="product-card-actions">
                    <a class="btn btn-secondary btn-small" href="' . e($detailUrl) . '">Подробнее</a>
                    ' . render_add_to_cart_form($product, $redirectTo) . '
                </div>
            </div>
        </article>
    ';
}

function render_catalog_product_card(array $product, string $redirectTo): string
{
    $productId = (int) $product['id'];
    $stock = (int) $product['stock'];
    $detailUrl = url('product.php?id=' . $productId);
    $stockLabel = $stock > 0 ? 'В наличии: ' . $stock . ' шт.' : 'Нет в наличии';

    return '
        <article class="product-card card" data-cart-product data-product-id="' . $productId . '" data-stock="' . $stock . '">
            <a class="product-card-image" href="' . e($detailUrl) . '">
                <img src="' . e(url(normalize_image_path($product['image'] ?? null))) . '" alt="' . e($product['name']) . '" loading="lazy">
            </a>
            <div class="product-card-head">
                <div>
                    <p class="product-category">' . e($product['category_name'] ?? 'Категория') . '</p>
                    <h3><a href="' . e($detailUrl) . '">' . e($product['name']) . '</a></h3>
                </div>
                <span class="price">' . e(format_price((float) $product['price'])) . '</span>
            </div>
            <p>' . e($product['description']) . '</p>
            <div class="product-card-footer">
                <span class="stock">' . e($stockLabel) . '</span>
                ' . render_cart_stepper($productId, $stock) . '
                <div class="product-card-actions">
                    <a class="btn btn-secondary btn-small" href="' . e($detailUrl) . '">Подробнее</a>
                    ' . render_add_to_cart_form($product, $redirectTo) . '
                </div>
            </div>
        </article>
    ';
}

function build_catalog_query(array $overrides = []): string
{
    $params = [
        'search' => trim((string) ($_GET['search'] ?? '')),
        'category' => trim((string) ($_GET['category'] ?? '')),
        'sort' => trim((string) ($_GET['sort'] ?? '')),
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
            continue;
        }

        $params[$key] = (string) $value;
    }

    $params = array_filter($params, static fn (string $value): bool => $value !== '');

    return $params ? ('?' . http_build_query($params)) : '';
}

function fetch_categories(PDO $pdo): array
{
    $statement = $pdo->query('SELECT * FROM categories ORDER BY name ASC');

    return $statement->fetchAll();
}

function fetch_category(PDO $pdo, int $categoryId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $categoryId]);

    return $statement->fetch() ?: null;
}

function fetch_featured_products(PDO $pdo, int $limit = 6): array
{
    $statement = $pdo->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.is_featured = 1
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function fetch_products(PDO $pdo, array $filters = []): array
{
    $sql = '
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE 1 = 1
    ';

    $params = [];

    if (!empty($filters['search'])) {
        $sql .= ' AND p.name LIKE :search ';
        $params['search'] = '%' . trim((string) $filters['search']) . '%';
    }

    if (!empty($filters['category_id'])) {
        $sql .= ' AND p.category_id = :category_id ';
        $params['category_id'] = (int) $filters['category_id'];
    }

    $sort = $filters['sort'] ?? 'name_asc';
    $sql .= match ($sort) {
        'price_asc' => ' ORDER BY p.price ASC, p.name ASC ',
        'price_desc' => ' ORDER BY p.price DESC, p.name ASC ',
        'name_desc' => ' ORDER BY p.name DESC ',
        default => ' ORDER BY p.name ASC ',
    };

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function fetch_product(PDO $pdo, int $productId): ?array
{
    $statement = $pdo->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $productId]);

    return $statement->fetch() ?: null;
}

function fetch_related_products(PDO $pdo, int $categoryId, int $productId, int $limit = 3): array
{
    $statement = $pdo->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.category_id = :category_id AND p.id <> :product_id
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $statement->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function fetch_user_by_email(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => mb_strtolower(trim($email))]);

    return $statement->fetch() ?: null;
}

function fetch_user_orders(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        'SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC, id DESC'
    );
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function fetch_order_items(PDO $pdo, int $orderId): array
{
    $statement = $pdo->prepare(
        'SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC'
    );
    $statement->execute(['order_id' => $orderId]);

    return $statement->fetchAll();
}

function fetch_dashboard_counts(PDO $pdo): array
{
    return [
        'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'categories' => (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    ];
}

function fetch_latest_orders(PDO $pdo, int $limit = 5): array
{
    $statement = $pdo->prepare(
        'SELECT o.*, u.name AS user_name
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC, o.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function fetch_contact_messages(PDO $pdo, int $limit = 0): array
{
    ensure_contact_messages_table($pdo);

    $sql = 'SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC';
    $statement = $pdo->prepare($limit > 0 ? $sql . ' LIMIT :limit' : $sql);

    if ($limit > 0) {
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $statement->execute();

    return $statement->fetchAll();
}

function fetch_orders(PDO $pdo, ?string $status = null): array
{
    $sql = '
        SELECT o.*, u.name AS user_name
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
    ';
    $params = [];

    if ($status) {
        $sql .= ' WHERE o.status = :status ';
        $params['status'] = $status;
    }

    $sql .= ' ORDER BY o.created_at DESC, o.id DESC ';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function fetch_order(PDO $pdo, int $orderId): ?array
{
    $statement = $pdo->prepare(
        'SELECT o.*, u.name AS user_name
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $orderId]);

    return $statement->fetch() ?: null;
}
