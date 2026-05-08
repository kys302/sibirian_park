<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_admin();

$pdo = db();
$categories = fetch_categories($pdo);
$editingId = (int) ($_GET['edit'] ?? 0);
$editingCategory = $editingId > 0 ? fetch_category($pdo, $editingId) : null;
$errors = [];

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_category') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $slug = slugify($name);

        if ($name === '') {
            $errors['name'] = 'Укажите название категории.';
        }
        if ($description === '') {
            $errors['description'] = 'Добавьте описание категории.';
        }

        if (!$errors) {
            try {
                $image = save_uploaded_image('image_file', null);
            } catch (RuntimeException $exception) {
                $errors['image'] = $exception->getMessage();
                $image = null;
            }
        }

        if (!$errors) {
            $currentImage = $editingCategory['image'] ?? null;
            $image = $image ?: ($imageUrl !== '' ? $imageUrl : ($currentImage ?: 'assets/images/products/category-placeholder.svg'));

            if ($categoryId > 0) {
                $statement = $pdo->prepare(
                    'UPDATE categories
                     SET slug = :slug, name = :name, description = :description, image = :image
                     WHERE id = :id'
                );
                $statement->execute([
                    'slug' => $slug,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'id' => $categoryId,
                ]);
                set_flash('success', 'Категория обновлена.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO categories (slug, name, description, image)
                     VALUES (:slug, :name, :description, :image)'
                );
                $statement->execute([
                    'slug' => $slug,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                ]);
                set_flash('success', 'Категория добавлена.');
            }

            redirect('admin/categories.php');
        }
    }

    if ($action === 'delete_category') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :category_id');
        $countStatement->execute(['category_id' => $categoryId]);
        $productsCount = (int) $countStatement->fetchColumn();

        if ($productsCount > 0) {
            set_flash('error', 'Нельзя удалить категорию, пока в ней есть товары.');
        } else {
            $deleteStatement = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $deleteStatement->execute(['id' => $categoryId]);
            set_flash('success', 'Категория удалена.');
        }

        redirect('admin/categories.php');
    }
}

$pageTitle = 'Категории — Админка Сибирский парк';
$pageDescription = 'Управление категориями каталога.';
$pageKey = 'admin-categories';
$bodyPage = 'admin';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-main">
    <section class="page-banner compact admin-banner">
        <div class="container">
            <p class="eyebrow">Категории</p>
            <h1>Категории каталога</h1>
            <p>Добавление, редактирование и безопасное удаление категорий растений.</p>
        </div>
    </section>

    <section class="section">
        <div class="container admin-two-columns admin-two-columns--stacked">
            <section class="admin-section card">
                <div class="admin-header">
                    <div>
                        <p class="eyebrow">Список категорий</p>
                        <h2>Текущие категории</h2>
                    </div>
                </div>

                <div class="table-card card admin-table-shell">
                    <div class="admin-table-scroll">
                        <table class="admin-table-data admin-table-data--categories">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td class="cell-nowrap"><?= (int) $category['id'] ?></td>
                                        <td>
                                            <span class="category-admin-cell">
                                                <span class="category-table-thumb">
                                                    <img src="<?= e(url(normalize_image_path($category['image'] ?? null))) ?>" alt="<?= e($category['name']) ?>">
                                                </span>
                                                <span class="category-admin-name"><?= e($category['name']) ?></span>
                                            </span>
                                        </td>
                                        <td class="cell-text"><?= e($category['description']) ?></td>
                                        <td>
                                            <div class="table-actions table-actions--compact">
                                                <a class="btn btn-secondary btn-small" href="<?= e(url('admin/categories.php?edit=' . (int) $category['id'])) ?>">Редактировать</a>
                                                <form method="post" onsubmit="return confirm('Удалить категорию?');">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                                    <button class="btn btn-danger btn-small" type="submit">Удалить</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <aside class="admin-section card">
                <div class="admin-header">
                    <div>
                        <p class="eyebrow"><?= $editingCategory ? 'Редактирование' : 'Создание' ?></p>
                        <h2><?= $editingCategory ? 'Изменить категорию' : 'Добавить категорию' ?></h2>
                    </div>
                </div>

                <form method="post" class="admin-side-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_category">
                    <input type="hidden" name="category_id" value="<?= (int) ($editingCategory['id'] ?? 0) ?>">

                    <label class="label" for="category-name">Название</label>
                    <input id="category-name" name="name" class="input" type="text" value="<?= e((string) old('name', $editingCategory['name'] ?? '')) ?>">
                    <p class="field-error"><?= e($errors['name'] ?? '') ?></p>

                    <label class="label" for="category-description">Описание</label>
                    <textarea id="category-description" name="description" class="textarea" rows="5"><?= e((string) old('description', $editingCategory['description'] ?? '')) ?></textarea>
                    <p class="field-error"><?= e($errors['description'] ?? '') ?></p>

                    <label class="label" for="category-image-url">Ссылка на изображение</label>
                    <input id="category-image-url" name="image_url" class="input" type="url" value="<?= e((string) old('image_url', $editingCategory['image'] ?? '')) ?>" placeholder="https://your-project.supabase.co/storage/v1/object/public/products/plant-photo.jpg">
                    <p class="mini-note">Можно вставить публичную ссылку на изображение или загрузить изображение с компьютера.</p>

                    <label class="label" for="category-image-file">Загрузка файла</label>
                    <input id="category-image-file" name="image_file" class="input" type="file" accept="image/*">
                    <p class="field-error"><?= e($errors['image'] ?? '') ?></p>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><?= $editingCategory ? 'Сохранить изменения' : 'Добавить категорию' ?></button>
                        <?php if ($editingCategory): ?>
                            <a class="btn btn-secondary" href="<?= e(url('admin/categories.php')) ?>">Отмена</a>
                        <?php endif; ?>
                    </div>
                </form>
            </aside>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
