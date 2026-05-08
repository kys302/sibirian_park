CREATE DATABASE IF NOT EXISTS `sibirpark_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `sibirpark_db`;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(120) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(190) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `image` VARCHAR(255) DEFAULT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_products_category` (`category_id`),
    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `customer_name` VARCHAR(190) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `customer_email` VARCHAR(190) NOT NULL,
    `customer_address` TEXT NOT NULL,
    `delivery_method` VARCHAR(100) NOT NULL,
    `payment_method` VARCHAR(100) NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('new', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_orders_user` (`user_id`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED DEFAULT NULL,
    `product_name` VARCHAR(190) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Анна Петрова', 'user@sibirpark.local', '$2y$10$2oGQTYwzXJpgGbl13lj8ueqURdLDByZ/J43JybRfCiLvCitJtfZwG', 'user', '2026-03-01 10:00:00'),
(2, 'Елена Смирнова', 'admin@sibirpark.local', '$2y$10$NPzUM02496zGg8V255BBPOfNPTzpDsxE9IJwcLbV/Au3t2hGfHmOm', 'admin', '2026-03-01 10:15:00');

INSERT INTO `categories` (`id`, `slug`, `name`, `description`, `image`) VALUES
(1, 'fruit-trees', 'Плодовые деревья', 'Саженцы яблонь, груш, слив и других плодовых культур для сада.', 'assets/images/products/fruit-tree.svg'),
(2, 'fruit-shrubs', 'Плодовые кустарники', 'Смородина, малина, жимолость и другие ягодные кустарники.', 'assets/images/products/fruit-shrub.svg'),
(3, 'ornamental-shrubs', 'Декоративные кустарники', 'Цветущие и декоративно-лиственные кустарники для оформления участка.', 'assets/images/products/ornamental-shrub.svg'),
(4, 'conifers', 'Хвойные растения', 'Туи, можжевельники и другие хвойные культуры для круглогодичного озеленения.', 'assets/images/products/conifer.svg'),
(5, 'perennials', 'Многолетние цветы', 'Многолетники для клумб, миксбордеров и садовых композиций.', 'assets/images/products/perennial.svg');

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`, `is_featured`, `created_at`) VALUES
(101, 1, 'Яблоня Антоновка', 'Классический зимостойкий сорт с ароматными плодами. Подходит для посадки в садах Новосибирской области.', 950.00, 'assets/images/products/catalog/apple-antonovka.jpg', 12, 1, '2026-03-01 12:00:00'),
(102, 1, 'Груша Лада', 'Раннелетний сорт груши с сочными сладкими плодами и хорошей устойчивостью к погодным условиям.', 1190.00, 'assets/images/products/catalog/pear-lada.jpg', 7, 1, '2026-03-02 09:15:00'),
(103, 1, 'Слива Скороплодная', 'Компактное дерево с ранним вступлением в плодоношение, удобное для дачных участков.', 1100.00, 'assets/images/products/catalog/plum-skoroplodnaya.jpg', 9, 0, '2026-03-02 10:10:00'),
(201, 2, 'Смородина чёрная Селеченская', 'Урожайный куст с крупной ароматной ягодой для плодового ряда на участке.', 520.00, 'assets/images/products/catalog/blackcurrant-selechenskaya.jpg', 18, 1, '2026-03-03 11:00:00'),
(202, 2, 'Малина ремонтантная Геракл', 'Популярный ремонтантный сорт с крупной ягодой и растянутым периодом плодоношения.', 480.00, 'assets/images/products/catalog/raspberry-gerakl.jpg', 24, 0, '2026-03-03 11:30:00'),
(203, 2, 'Жимолость Бакчарский великан', 'Зимостойкий кустарник с ранним плодоношением и крупными ягодами десертного вкуса.', 640.00, 'assets/images/products/catalog/honeysuckle-bakcharskiy-velikan.jpg', 11, 1, '2026-03-03 12:10:00'),
(301, 3, 'Гортензия метельчатая Limelight', 'Эффектный декоративный кустарник с крупными соцветиями и длительным цветением.', 1450.00, 'assets/images/products/catalog/hydrangea-limelight.jpg', 8, 1, '2026-03-04 10:05:00'),
(302, 3, 'Спирея японская Goldflame', 'Неприхотливый кустарник с яркой декоративной листвой, удобен для бордюров.', 690.00, 'assets/images/products/catalog/spirea-goldflame.jpg', 14, 0, '2026-03-04 10:35:00'),
(303, 3, 'Пузыреплодник Diablo', 'Декоративный кустарник с тёмной листвой для живых изгородей и акцентных посадок.', 780.00, 'assets/images/products/catalog/physocarpus-diablo.jpg', 10, 0, '2026-03-04 11:20:00'),
(401, 4, 'Туя западная Смарагд', 'Популярный сорт туи с плотной конической кроной, отлично смотрится в рядовых посадках.', 1850.00, 'assets/images/products/catalog/thuja-smaragd.jpg', 16, 1, '2026-03-05 09:45:00'),
(402, 4, 'Можжевельник казацкий', 'Почвопокровный хвойный кустарник для рокариев, склонов и спокойного озеленения участка.', 990.00, 'assets/images/products/catalog/juniper-sabina.jpg', 13, 0, '2026-03-05 10:20:00'),
(403, 4, 'Ель канадская Коника', 'Компактная хвойная форма с плотной мягкой хвоей для парадных зон и контейнерного озеленения.', 2100.00, 'assets/images/products/catalog/picea-conica.jpg', 5, 1, '2026-03-05 10:55:00'),
(501, 5, 'Роза Avalanche', 'Элегантная крупноцветковая роза со светлыми бутонами, подходит для клумб и срезки.', 890.00, 'assets/images/products/catalog/rose-avalanche.jpg', 20, 1, '2026-03-06 08:40:00'),
(502, 5, 'Хоста Patriot', 'Декоративно-лиственное многолетнее растение для тенистых уголков сада.', 560.00, 'assets/images/products/catalog/hosta-patriot.jpg', 17, 0, '2026-03-06 09:10:00'),
(503, 5, 'Лилейник Stella de Oro', 'Компактный многолетник с длительным цветением и яркими жёлтыми цветками.', 610.00, 'assets/images/products/catalog/daylily-stella-de-oro.jpg', 15, 0, '2026-03-06 09:45:00');

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `delivery_method`, `payment_method`, `total_amount`, `status`, `created_at`) VALUES
(5001, 1, 'Анна Петрова', '+7 (383) 000-00-00', 'user@sibirpark.local', 'г. Новосибирск, ул. Садовая, 12', 'Курьером', 'Наличными при получении', 4650.00, 'processing', '2026-03-05 12:10:00'),
(5002, 1, 'Анна Петрова', '+7 (383) 000-00-00', 'user@sibirpark.local', 'г. Новосибирск, ул. Берёзовая, 8', 'Самовывоз', 'Банковской картой', 3130.00, 'completed', '2026-02-19 16:40:00'),
(5003, 2, 'Елена Смирнова', '+7 (383) 000-00-00', 'admin@sibirpark.local', 'г. Новосибирск, ул. Тенистая, 4', 'Самовывоз', 'Наличными при получении', 1280.00, 'new', '2026-03-11 09:20:00');

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 5001, 101, 'Яблоня Антоновка', 950.00, 1, 950.00),
(2, 5001, 401, 'Туя западная Смарагд', 1850.00, 2, 3700.00),
(3, 5002, 301, 'Гортензия метельчатая Limelight', 1450.00, 1, 1450.00),
(4, 5002, 502, 'Хоста Patriot', 560.00, 3, 1680.00),
(5, 5003, 203, 'Жимолость Бакчарский великан', 640.00, 2, 1280.00);
