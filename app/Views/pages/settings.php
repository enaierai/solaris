<?php

// Bu dosya artık bir View'dir. Tüm PHP mantığı SettingsController'da olacaktır.
// $user_data, $message, $csrf_token, $is_logged_in, $current_user_id değişkenlerinin
// Controller tarafından buraya aktarıldığını varsayıyoruz.

// Varsayılan değerler (eğer Controller'dan gelmezse hata vermemesi için)
$user_data = $user_data ?? [];
$message = $message ?? '';
$csrf_token = $csrf_token ?? '';
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null;

// Kullanıcı verilerini daha okunabilir değişkenlere atayalım
$user_email = htmlspecialchars($user_data['email'] ?? '');
$user_name = htmlspecialchars($user_data['name'] ?? '');
$user_username = htmlspecialchars($user_data['username'] ?? '');
$user_pronouns = htmlspecialchars($user_data['pronouns'] ?? '');
$user_bio = htmlspecialchars($user_data['bio'] ?? '');
$user_gender = htmlspecialchars($user_data['gender'] ?? '');
$user_business_email = htmlspecialchars($user_data['business_email'] ?? '');
$user_business_phone = htmlspecialchars($user_data['business_phone'] ?? '');
$user_whatsapp_number = htmlspecialchars($user_data['whatsapp_number'] ?? '');
$user_display_contact_info = (bool) ($user_data['display_contact_info'] ?? false);

// Header'ı yükle (Bu kısım Controller'da olmalı, ancak mevcut yapıda burada kalıyor)
// $data = [
//     'meta' => [
//         'meta_title' => 'Ayarlar - Solaris',
//         'meta_description' => 'Hesap ayarlarınızı ve gizlilik tercihlerinizi yönetin.',
//     ],
//     'is_logged_in' => $is_logged_in,
//     'current_user_id' => $current_user_id,
// ];
// $this->view('layouts/header', $data);

?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <h3 class="mb-4 text-dark"><i class="fas fa-cog me-2 text-primary"></i> Ayarlar ve Aktivite</h3>
            <hr>

            <?php echo $message; // Mesajları burada göster?>

            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile-pane" type="button" role="tab" aria-controls="edit-profile-pane" aria-selected="true">
                        <i class="fas fa-user-edit me-2"></i>Profili Düzenle
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-settings-tab" data-bs-toggle="tab" data-bs-target="#account-settings-pane" type="button" role="tab" aria-controls="account-settings-pane" aria-selected="false">
                        <i class="fas fa-cogs me-2"></i>Hesap Ayarları
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="your-activity-tab" data-bs-toggle="tab" data-bs-target="#your-activity-pane" type="button" role="tab" aria-controls="your-activity-pane" aria-selected="false">
                        <i class="fas fa-chart-line me-2"></i>Aktiviteniz
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="privacy-security-tab" data-bs-toggle="tab" data-bs-target="#privacy-security-pane" type="button" role="tab" aria-controls="privacy-security-pane" aria-selected="false">
                        <i class="fas fa-shield-alt me-2"></i>Gizlilik ve Güvenlik
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="settingsTabsContent">
                <!-- Profili Düzenle Sekmesi -->
                <div class="tab-pane fade show active" id="edit-profile-pane" role="tabpanel" aria-labelledby="edit-profile-tab">
                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Temel Profil Bilgileri</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>settings/update_profile_info" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                
                                <div class="mb-3">
                                    <label for="profilePicture" class="form-label">Profil Resmi</label>
                                    <input class="form-control" type="file" id="profilePicture" name="profile_picture" accept="image/*">
                                    <small class="form-text text-muted">Mevcut: <img src="<?php echo getUserAvatar($user_data['username'], $user_data['profile_picture_url']); ?>" class="rounded-circle" width="50" height="50" alt="Profil Resmi"></small>
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Ad</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $user_name; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Kullanıcı Adı</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $user_username; ?>" required>
                                    <small class="form-text text-muted">Kullanıcı adınızı 30 günde bir değiştirebilirsiniz.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="pronouns" class="form-label">Zamirler</label>
                                    <input type="text" class="form-control" id="pronouns" name="pronouns" value="<?php echo $user_pronouns; ?>" placeholder="Örn: O/Onu">
                                </div>

                                <div class="mb-3">
                                    <label for="bio" class="form-label">Biyografi</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo $user_bio; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="form-label">Cinsiyet</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Seçiniz...</option>
                                        <option value="Female" <?php echo ($user_gender === 'Female') ? 'selected' : ''; ?>>Kadın</option>
                                        <option value="Male" <?php echo ($user_gender === 'Male') ? 'selected' : ''; ?>>Erkek</option>
                                        <option value="Prefer not to say" <?php echo ($user_gender === 'Prefer not to say') ? 'selected' : ''; ?>>Belirtmek İstemiyorum</option>
                                    </select>
                                </div>

                                <h6 class="mt-4 mb-3">İletişim Seçenekleri</h6>
                                <div class="mb-3">
                                    <label for="businessEmail" class="form-label">İş E-postası</label>
                                    <input type="email" class="form-control" id="businessEmail" name="business_email" value="<?php echo $user_business_email; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="businessPhone" class="form-label">İş Telefon Numarası</label>
                                    <input type="text" class="form-control" id="businessPhone" name="business_phone" value="<?php echo $user_business_phone; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="whatsappNumber" class="form-label">WhatsApp İş Numarası</label>
                                    <input type="text" class="form-control" id="whatsappNumber" name="whatsapp_number" value="<?php echo $user_whatsapp_number; ?>">
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="displayContactInfo" name="display_contact_info" value="1" <?php echo $user_display_contact_info ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="displayContactInfo">İletişim Bilgilerini Profilde Göster</label>
                                </div>

                                <button type="submit" class="btn btn-primary">Profili Güncelle</button>
                            </form>
                        </div>
                    </div>

                    <!-- Link Ekleme Alanı (Daha sonra JavaScript ile dinamik hale getirilecek) -->
                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Bağlantılarım</h5>
                        </div>
                        <div class="card-body">
                            <div id="links-container">
                                <!-- Mevcut linkler buraya JS ile yüklenecek -->
                                <p class="text-muted">Henüz bir bağlantı eklenmedi.</p>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm mt-3" id="add-link-btn"><i class="fas fa-plus me-1"></i> Yeni Bağlantı Ekle</button>
                        </div>
                    </div>
                </div>

                <!-- Hesap Ayarları Sekmesi -->
                <div class="tab-pane fade" id="account-settings-pane" role="tabpanel" aria-labelledby="account-settings-tab">
                    <!-- E-posta Güncelleme Formu -->
                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">E-posta Adresini Değiştir</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>settings/update_email" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Yeni E-posta Adresi</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user_email; ?>" required>
                                </div>
                                <button type="submit" name="update_email" class="btn btn-primary">E-postayı Güncelle</button>
                            </form>
                        </div>
                    </div>

                    <!-- Şifre Güncelleme Formu -->
                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Şifreni Değiştir</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>settings/update_password" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mevcut Şifre</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Yeni Şifre</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password_confirm" class="form-label">Yeni Şifre (Tekrar)</label>
                                    <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" required>
                                </div>
                                <button type="submit" name="update_password" class="btn btn-primary">Şifreyi Güncelle</button>
                            </form>
                        </div>
                    </div>

                    <!-- Güvenlik Ayarlarına Yönlendirme -->
                    <div class="card mb-4 bg-light text-dark border-0 rounded-4 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title fw-bold">Güvenlik Ayarları</h6>
                                <p class="card-text text-muted small mb-0">İki faktörlü kimlik doğrulama ve hesap silme gibi gelişmiş güvenlik seçeneklerini yönetin.</p>
                            </div>
                            <a href="<?php echo BASE_URL; ?>security" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-shield-alt me-2"></i> Güvenlik Ayarları</a>
                        </div>
                    </div>
                </div>

                <!-- Aktiviteniz Sekmesi -->
                <div class="tab-pane fade" id="your-activity-pane" role="tabpanel" aria-labelledby="your-activity-tab">
                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Aktivite Geçmişi</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-heart me-2 text-danger"></i>Beğeniler</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-comment-dots me-2 text-primary"></i>Yorumlar</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-tags me-2 text-success"></i>Etiketlendiğiniz Gönderiler</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Kaldırılan ve Arşivlenen İçerik</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-trash-alt me-2 text-danger"></i>Yakın Zamanda Silinenler</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-archive me-2 text-muted"></i>Arşivlenenler</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Paylaştığınız İçerik</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-image me-2 text-info"></i>Gönderiler (Fotoğraflar)</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-video me-2 text-warning"></i>Videolar</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Önerilen İçerik Tercihleri</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-thumbs-down me-2 text-muted"></i>İlgilenmiyorum</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Yönet</a>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-thumbs-up me-2 text-success"></i>İlgileniyorum</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Yönet</a>
                                </li>
                            </ul>
                            <small class="form-text text-muted mt-2">Keşfet sayfasındaki içerik önerilerini kişiselleştirin.</small>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Hesap Geçmişi</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <!-- Örnekler, veritabanından çekilecek -->
                                <li class="list-group-item">
                                    <i class="fas fa-globe me-2 text-secondary"></i>Biyografinizdeki web sitesini <b>Solaris Ana Sayfa</b>: "<?php echo BASE_URL; ?>" olarak değiştirdiniz. <small class="text-muted">6 hafta önce</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-trash-alt me-2 text-secondary"></i>Profilinizden bir web sitesini kaldırdınız. <small class="text-muted">5 hafta önce</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-edit me-2 text-secondary"></i>Biyografinizi "<?php echo htmlspecialchars(substr($user_data['bio'] ?? '', 0, 50)); ?>..." olarak değiştirdiniz. <small class="text-muted">9 hafta önce</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-envelope me-2 text-secondary"></i>E-posta adresinizi <b><?php echo $user_email; ?></b> olarak değiştirdiniz. <small class="text-muted">9 hafta önce</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-user-plus me-2 text-secondary"></i>Hesabınızı <b><?php echo date('d M Y', strtotime($user_data['created_at'])); ?></b> tarihinde oluşturdunuz. <small class="text-muted"><?php echo time_ago(strtotime($user_data['created_at'])); ?></small>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Son Aramalar</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="fas fa-search me-2 text-secondary"></i>#doğa <small class="text-muted">2 gün önce</small>
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-search me-2 text-secondary"></i>Eray <small class="text-muted">1 hafta önce</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Gizlilik ve Güvenlik Sekmesi -->
                <div class="tab-pane fade" id="privacy-security-pane" role="tabpanel" aria-labelledby="privacy-security-tab">
                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Bildirim Tercihleri</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="emailNotificationsLikes" checked>
                                <label class="form-check-label" for="emailNotificationsLikes">Beğeniler için E-posta Bildirimi</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="emailNotificationsComments" checked>
                                <label class="form-check-label" for="emailNotificationsComments">Yorumlar için E-posta Bildirimi</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotificationsFollows" checked>
                                <label class="form-check-label" for="emailNotificationsFollows">Takipçiler için E-posta Bildirimi</label>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Gizlilik Ayarları</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="whoCanSeeFollowers" class="form-label">Takipçi ve Takip Edilenler Listesini Kimler Görebilir?</label>
                                <select class="form-select" id="whoCanSeeFollowers">
                                    <option value="everyone">Herkes</option>
                                    <option value="only_me">Yalnızca Ben</option>
                                    <option value="my_followers">Takipçilerim</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="whoCanMessage" class="form-label">Kimler Mesaj Gönderebilir?</label>
                                <select class="form-select" id="whoCanMessage">
                                    <option value="everyone">Herkes</option>
                                    <option value="following">Takip Ettiklerim</option>
                                    <option value="nobody">Hiç Kimse</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="whoCanComment" class="form-label">Kimler Gönderilere Yorum Yapabilir?</label>
                                <select class="form-select" id="whoCanComment">
                                    <option value="everyone">Herkes</option>
                                    <option value="following">Takip Ettiklerim</option>
                                    <option value="nobody">Hiç Kimse</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Engellenen Kullanıcılar</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Engellediğiniz kullanıcılar burada listelenecektir.</p>
                            <a href="#" class="btn btn-outline-secondary">Engellenenleri Yönet</a>
                        </div>
                    </div>

                    <div class="card mb-4 rounded-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="my-0">Veri Dışa Aktarma</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Tüm gönderilerinizi, yorumlarınızı ve hesap verilerinizi dışa aktarın.</p>
                            <button class="btn btn-primary">Verileri Dışa Aktar</button>
                        </div>
                    </div>
                    
                    <!-- Güvenlik Ayarlarına Yönlendirme (Mevcut olanın tekrarı, kaldırılabilir veya buraya taşınabilir) -->
                    <div class="card mb-4 bg-light text-dark border-0 rounded-4 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title fw-bold">Hesap Güvenliği</h6>
                                <p class="card-text text-muted small mb-0">İki faktörlü kimlik doğrulama ve hesap silme gibi gelişmiş güvenlik seçeneklerini yönetin.</p>
                            </div>
                            <a href="<?php echo BASE_URL; ?>security" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-shield-alt me-2"></i> Güvenlik Ayarları</a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php
// Footer'ı yükle
$this->view('layouts/footer');
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tablarını etkinleştirme
    var settingsTabs = new bootstrap.Tab(document.getElementById('edit-profile-tab'));
    settingsTabs.show(); // Varsayılan olarak "Profili Düzenle" sekmesini aktif yap

    // URL hash'ine göre sekme değiştirme
    const hash = window.location.hash;
    if (hash) {
        const tabToActivate = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tabToActivate) {
            var bsTab = new bootstrap.Tab(tabToActivate);
            bsTab.show();
        }
    }

    // Sekmeler arası geçişte URL hash'ini güncelleme
    document.querySelectorAll('#settingsTabs .nav-link').forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', function(event) {
            const newHash = event.target.getAttribute('data-bs-target');
            if (history.pushState) {
                history.pushState(null, null, newHash);
            } else {
                window.location.hash = newHash;
            }
        });
    });

    // Link Ekleme İşlevi (Şimdilik sadece UI, backend bağlantısı sonra yapılacak)
    const addLinkBtn = document.getElementById('add-link-btn');
    const linksContainer = document.getElementById('links-container');
    let linkCounter = 0; // Dinamik ID'ler için

    if (addLinkBtn) {
        addLinkBtn.addEventListener('click', function() {
            linkCounter++;
            const linkHtml = `
                <div class="input-group mb-3" id="link-row-${linkCounter}">
                    <input type="url" class="form-control" name="links[${linkCounter}][url]" placeholder="URL" required>
                    <input type="text" class="form-control" name="links[${linkCounter}][title]" placeholder="Başlık (Opsiyonel)">
                    <button class="btn btn-outline-danger remove-link-btn" type="button" data-link-id="${linkCounter}"><i class="fas fa-times"></i></button>
                </div>
            `;
            // Eğer "Henüz bir bağlantı eklenmedi." mesajı varsa kaldır
            const noLinksMessage = linksContainer.querySelector('.text-muted');
            if (noLinksMessage) {
                noLinksMessage.remove();
            }
            linksContainer.insertAdjacentHTML('beforeend', linkHtml);
        });

        // Link kaldırma işlevi (event delegation)
        linksContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-link-btn')) {
                const linkIdToRemove = event.target.dataset.linkId;
                document.getElementById(`link-row-${linkIdToRemove}`).remove();
                // Eğer hiç link kalmazsa mesajı tekrar göster (isteğe bağlı)
                if (linksContainer.children.length === 0) {
                    linksContainer.innerHTML = '<p class="text-muted">Henüz bir bağlantı eklenmedi.</p>';
                }
            }
        });
    }

    // Profil resmi ve kapak resmi yükleme işlevi (profile.js'den kopyalandı, burada özelleştirilebilir)
    function setupImageUpload(triggerBtnId, inputId, ajaxUrl, imageType) {
        const triggerBtn = document.getElementById(triggerBtnId); // Bu butonlar settings.php'de yok, profil sayfasında var.
                                                                // Buradaki kod sadece örnek olarak duruyor, profile.js'deki kullanılmalı.
        const input = document.getElementById(inputId);
        if (!input) return; // Sadece input varsa dinleyici ekle

        // settings.php'de doğrudan input change'i dinle
        input.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append(imageType === 'avatar' ? 'profile_picture' : 'cover_picture', file);
                
                Swal.fire({
                    title: 'Yükleniyor...',
                    text: 'Lütfen bekleyin',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                if (typeof csrfToken !== 'undefined' && csrfToken) {
                    formData.append('csrf_token', csrfToken);
                }

                // AJAX isteği doğrudan ProfileController'a yapılacak
                fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Başarılı!', 'Fotoğrafınız güncellendi.', 'success').then(() => {
                                window.location.reload(); 
                            });
                        } else {
                            Swal.fire('Hata!', data.message || 'Yükleme başarısız.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Hata!', 'Bir sunucu hatası oluştu.', 'error');
                        console.error("Resim yükleme hatası:", err);
                    });
            }
        });
    }

    // Settings sayfasındaki profil resmi inputu için
    setupImageUpload('profilePicture', 'profilePicture', `${BASE_URL}profile/upload_profile_picture`, 'avatar');
    // Kapak resmi inputu settings.php'de yok, bu yüzden sadece profil resmi için çağırıyoruz.

});
</script>
