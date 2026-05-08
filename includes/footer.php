<?php

declare(strict_types=1);
?>
        <footer class="site-footer">
            <div class="container site-footer-inner">
                <div class="footer-column footer-caption">
                    <h3>Сибирский парк</h3>
                    <p>Плодовые и декоративные растения для сада, дачи и ландшафтных композиций в Новосибирске.</p>
                    <p class="footer-copy">© 2026 Сибирский парк. Все права защищены.</p>
                </div>

                <div class="footer-column">
                    <h4>Контакты</h4>
                    <p><a href="tel:+73830000000">+7 (383) 000-00-00</a></p>
                    <p><a href="mailto:info@sibirpark.local">info@sibirpark.local</a></p>
                    <p>г. Новосибирск, ул. Садовая, 12</p>
                </div>

                <div class="footer-column">
                    <h4>Режим работы</h4>
                    <p>Ежедневно с 09:00 до 19:00</p>
                    <p>Заказы на сайте принимаются круглосуточно</p>
                </div>

                <div class="footer-column">
                    <h4>Навигация</h4>
                    <div class="footer-links">
                        <a href="<?= e(url('index.php')) ?>">Главная</a>
                        <a href="<?= e(url('catalog.php')) ?>">Каталог</a>
                        <a href="<?= e(url('about.php')) ?>">О магазине</a>
                        <a href="<?= e(url('contacts.php')) ?>">Контакты</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="<?= e(asset_url('assets/js/main.js')) ?>"></script>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= e(asset_url('assets/js/' . $script)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
