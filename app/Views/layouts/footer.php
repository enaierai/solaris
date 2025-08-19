</main> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Bu değişkenler, diğer tüm JS dosyaları tarafından kullanılacak
        // Bu yüzden diğer dosyalardan ÖNCE tanımlanmaları hayati önem taşır.
        const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
        const csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
        const userId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
    </script>

    <script src="<?php echo BASE_URL; ?>public/js/utils.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/feed.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/post_manage.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/profile.js"></script>
    
    <?php
    // Bazen sadece belirli sayfalara özel script'ler gerekebilir.
    // Örnek: Sadece mesajlar sayfasında çalışacak bir script.
    if (isset($page_name) && $page_name === 'messages') {
        // echo '<script src="' . BASE_URL . 'public/js/messages.js"></script>';
    }
        ?>

<script>
    // Bu script, sayfa tamamen yüklendiğinde çalışır
    document.addEventListener("DOMContentLoaded", function() {
        // Eğer sayfada loginModal adında bir element varsa VE
        // kullanıcı giriş yapmamışsa (userId null ise)
        const loginModalElement = document.getElementById('loginModal');
        if (loginModalElement && !userId) {
            // Bootstrap'in Modal sınıfını kullanarak modalı gösterime hazırla
            const loginModal = new bootstrap.Modal(loginModalElement);
            // Modalı göster
            loginModal.show();
        }
    });
</script>

</body>
</html>