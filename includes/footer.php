<?php // includes/footer.php (DÜZELTİLMİŞ)?>

                </div>
            </div>
        </div>
    </main>
</div>
<!-- Modallar -->
<div class="modal fade" id="shareViaMessageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gönderiyi Paylaş</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="shareModalBody">
                <input type="text" class="form-control mb-3" id="shareUserSearch" placeholder="Kullanıcı ara...">
                <div id="shareUserList">
                    <div class="text-center p-4"><div class="spinner-border"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="generalModal" tabindex="-1" aria-labelledby="generalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generalModalLabel">Yükleniyor...</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="generalModalBody">
                <div class="d-flex justify-content-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Üçüncü Parti Kütüphaneler -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.js"></script>
    <script src="https://unpkg.com/tippy.js@6.3.7/dist/tippy.umd.min.js"></script>
    <script src="https://unpkg.com/isotope-layout@3.0.6/dist/isotope.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/countup.js@2.6.2/dist/countUp.umd.js"></script>
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>

<!-- Global Değişkenler -->
<script>
    const BASE_URL = "<?php echo BASE_URL; ?>";
    const userId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
    // CSRF token'ı utils.js içinde okunacak
</script>
<input type="hidden" id="csrf_token_field" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

<!-- Bizim Uygulama Scriptlerimiz -->
<script src="<?php echo BASE_URL; ?>public/js/utils.js"></script>
<script src="<?php echo BASE_URL; ?>public/js/app.js"></script>

<?php
// Sayfaya özel script'leri yükle
// $page_name değişkeninin ilgili ana PHP dosyasında (index.php, profile.php vb.) tanımlandığını varsayıyoruz.
if (isset($page_name)) {
    switch ($page_name) {
        case 'index':
        case 'explore':
            echo '<script src="'.BASE_URL.'public/js/feed.js"></script>';
            break;

        case 'profile':
            echo '<script src="'.BASE_URL.'public/js/post_manage.js"></script>'; // Modal için
            echo '<script src="'.BASE_URL.'public/js/profile.js"></script>';
            echo '<script src="'.BASE_URL.'public/js/feed.js"></script>'; // Modal için
            break;
        case 'home':
        case 'index': // Hem solaris/ hem de solaris/home için çalışsın
            echo '<script src="'.BASE_URL.'public/js/feed.js"></script>'; // Beğeni, Yorumlar, Kaydetme için
            echo '<script src="'.BASE_URL.'public/js/post_manage.js"></script>'; // "Beğenenler" gibi Modalların çalışması için BU GEREKLİ
            // Gerekirse sonsuz kaydırma için home.js de eklenebilir.
            // echo '<script src="'.BASE_URL.'public/js/home.js"></script>';
            break;
        case 'post':
            echo '<script src="'.BASE_URL.'public/js/feed.js"></script>'; // Beğeni, yorum, kaydetme gibi etkileşimler için
            echo '<script src="'.BASE_URL.'public/js/post_manage.js"></script>'; // Gönderi düzenleme/silme için
            break;

            // 'settings', 'messages' gibi diğer sayfalar için case'ler eklenebilir
            // case 'settings':
            //     echo '<script src="' . BASE_URL . 'public/js/settings.js"></script>';
            //     break;
    }
}
?>

</body>
</html>
