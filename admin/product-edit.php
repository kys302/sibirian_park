<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();
$productId = (int) ($_GET['id'] ?? 0);
$product = fetch_product($pdo, $productId);

if (!$product) {
    set_flash('error', 'Товар не найден.');
    redirect('admin/products.php');
}

$categories = fetch_categories($pdo);
$errors = [];

if (is_post()) {
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
    $stock = (int) ($_POST['stock'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if ($categoryId <= 0) {
        $errors['category_id'] = 'Выберите категорию.';
    }
    if ($name === '') {
        $errors['name'] = 'Укажите название товара.';
    }
    if ($description === '') {
        $errors['description'] = 'Добавьте описание товара.';
    }
    if ($price <= 0) {
        $errors['price'] = 'Укажите корректную цену.';
    }
    if ($stock < 0) {
        $errors['stock'] = 'Остаток не может быть отрицательным.';
    }
    if (!$errors) {
        try {
            $image = save_uploaded_image('image_file', $product['image']);
        } catch (RuntimeException $exception) {
            $errors['image'] = $exception->getMessage();
            $image = $product['image'];
        }
    }

    if (!$errors) {
        $image = $imageUrl !== '' ? $imageUrl : $image;
        if ($image === '') {
            $errors['image'] = 'Укажите ссылку на изображение или загрузите файл.';
        }
    }

    if (!$errors) {
        $statement = $pdo->prepare(
            'UPDATE products
             SET category_id = :category_id,
                 name = :name,
                 description = :description,
                 price = :price,
                 image = :image,
                 stock = :stock,
                 is_featured = :is_featured
             WHERE id = :id'
        );
        $statement->execute([
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'image' => $image,
            'stock' => $stock,
            'is_featured' => $isFeatured,
            'id' => $productId,
        ]);

        set_flash('success', 'Товар успешно обновлён.');
        redirect('admin/products.php');
    }
}

$pageTitle = 'Редактирование товара — Админка Сибирский парк';
$pageDescription = 'Редактирование карточки товара.';
$pageKey = 'admin-products';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Товары</p>
            <h1>Редактирование товара</h1>
            <p>Обновите данные выбранной позиции каталога.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="card form-card admin-form-page" method="post" enctype="multipart/form-data">
                <h2><?= e($product['name']) ?></h2>
                <div class="form-grid two-columns">
                    <div>
                        <label class="label" for="category-id">Категория</label>
                        <select id="category-id" name="category_id" class="select">
                            <?php foreach ($categories as $category): ?>
                                <?php $selectedCategoryId = (int) old('category_id', (string) $product['category_id']); ?>
                                <option value="<?= (int) $category['id'] ?>" <?= $selectedCategoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-error"><?= e($errors['category_id'] ?? '') ?></p>
                    </div>
                    <div>
                        <label class="label" for="product-name">Название</label>
                        <input id="product-name" name="name" class="input" type="text" value="<?= e((string) old('name', $product['name'])) ?>">
                        <p class="field-error"><?= e($errors['name'] ?? '') ?></p>
                    </div>
                    <div>
                        <label class="label" for="product-price">Цена</label>
                        <input id="product-price" name="price" class="input" type="number" min="0" step="0.01" value="<?= e((string) old('price', (string) $product['price'])) ?>">
                        <p class="field-error"><?= e($errors['price'] ?? '') ?></p>
                    </div>
                    <div>
                        <label class="label" for="product-stock">Остаток</label>
                        <input id="product-stock" name="stock" class="input" type="number" min="0" step="1" value="<?= e((string) old('stock', (string) $product['stock'])) ?>">
                        <p class="field-error"><?= e($errors['stock'] ?? '') ?></p>
                    </div>
                </div>

                <div>
                    <label class="label" for="product-image-url">Ссылка на изображение</label>
                    <input id="product-image-url" name="image_url" class="input" type="url" value="<?= e((string) old('image_url', $product['image'])) ?>" placeholder="https://your-project.supabase.co/storage/v1/object/public/products/plant-photo.jpg">
                    <p class="mini-note">Можно оставить действующую публичную ссылку, вставить новую внешнюю ссылку или загрузить локальный файл.</p>
                </div>

                <div>
                    <label class="label" for="product-image-file">Загрузка файла</label>
                    <input id="product-image-file" name="image_file" class="input" type="file" accept="image/*">
                    <p class="field-error"><?= e($errors['image'] ?? '') ?></p>
                </div>

                <div>
                    <label class="label" for="product-description">Описание</label>
                    <textarea id="product-description" name="description" class="textarea" rows="6"><?= e((string) old('description', $product['description'])) ?></textarea>
                    <p class="field-error"><?= e($errors['description'] ?? '') ?></p>
                </div>

                <label class="admin-checkbox">
                    <input type="checkbox" name="is_featured" value="1" <?= (int) old('is_featured', (string) $product['is_featured']) === 1 ? 'checked' : '' ?>>
                    <span>Популярный товар</span>
                </label>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Сохранить изменения</button>
                    <a class="btn btn-secondary" href="<?= e(url('admin/products.php')) ?>">Отмена</a>
                </div>
            </form>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
