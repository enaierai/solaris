<?php
// Sayfanın tüm mantığını çalıştırır.
include_once __DIR__.'/../../includes/logic/security.logic.php';

// Sayfanın başlığını oluşturur.
include_once __DIR__.'/../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <h3 class="mb-4 text-dark"><i class="fas fa-shield-alt me-2 text-primary"></i> Güvenlik Ayarları</h3>
            <hr>

            <div class="card mb-4 bg-light text-dark border-0 rounded-4 shadow-sm">
                <div class="card-body p-4">
                    <p class="card-text text-muted">Bu alanda gelecekte iki faktörlü kimlik doğrulama, aktif oturumları yönetme ve giriş geçmişi gibi özellikler yer alacaktır.</p>
                    <a href="<?php echo BASE_URL; ?>public/pages/settings.php" class="btn btn-outline-primary mt-2 rounded-pill px-4"><i class="fas fa-cog me-2"></i> Hesap Ayarlarına Dön</a>
                </div>
            </div>

            <!-- Tehlikeli Bölge -->
            <div class="card border-danger rounded-4 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="my-0"><i class="fas fa-exclamation-triangle me-2"></i>Tehlikeli Bölge</h5>
                </div>
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title fw-bold">Hesabı Kalıcı Olarak Sil</h6>
                        <p class="card-text text-muted small mb-0">Bu işlem geri alınamaz. Tüm gönderileriniz, yorumlarınız ve verileriniz silinecektir.</p>
                    </div>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">Hesabı Sil</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hesap Silme Onay Modalı -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteAccountModalLabel">Hesabınızı Silmeyi Onaylayın</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Bu işlem geri alınamaz. Devam etmek için lütfen mevcut şifrenizi girin.</p>
        <form id="deleteAccountForm">
            <div class="mb-3">
                <label for="deleteConfirmPassword" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="deleteConfirmPassword" required>
            </div>
            <div id="deleteAccountError" class="text-danger small"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hesabımı Kalıcı Olarak Sil</button>
      </div>
    </div>
  </div>
</div>


<?php
$conn->close();
include_once __DIR__.'/../../includes/footer.php';
?>
