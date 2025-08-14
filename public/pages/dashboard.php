<?php
// Sayfanın tüm mantığını (veri çekme, session işlemleri vb.) çalıştırır.
include_once __DIR__.'/../../includes/logic/dashboard.logic.php';

// Hazırlanan değişkenleri kullanarak sayfanın başlığını oluşturur.
include_once __DIR__.'/../../includes/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4 fw-bold text-center">Kontrol Panelim</h2>

    <div class="card bg-white text-dark border-light shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center mb-4">
            <img src="<?php echo BASE_URL; ?>uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture_url'] ?: 'default.jpg'); ?>" class="rounded-circle me-3" width="60" height="60" alt="Profil Resmi" style="object-fit: cover;">
            <div>
                <h4 class="mb-0">@<?php echo htmlspecialchars($user['username']); ?></h4>
                <small class="text-muted">Kullanıcı ID: <?php echo $current_user_id; ?></small>
            </div>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4 text-center">
            <div class="col"><div class="p-3 border rounded bg-light"><strong><?php echo $post_count; ?></strong><br>Gönderi</div></div>
            <div class="col"><div class="p-3 border rounded bg-light"><strong><?php echo $like_count; ?></strong><br>Alınan Beğeni</div></div>
            <div class="col"><div class="p-3 border rounded bg-light"><strong><?php echo $comment_count; ?></strong><br>Alınan Yorum</div></div>
            <div class="col"><div class="p-3 border rounded bg-light"><strong><?php echo $follower_count; ?></strong><br>Takipçi</div></div>
            <div class="col"><div class="p-3 border rounded bg-light"><strong><?php echo $following_count; ?></strong><br>Takip Edilen</div></div>
            <div class="col"><div class="p-3 border rounded bg-light"><strong><?php echo $locked_count; ?></strong><br>Kilitli İçerik</div></div>
            <div class="col col-lg-12"><div class="p-3 border rounded bg-light"><strong><?php echo $last_post_date ? time_ago($last_post_date) : 'Hiç gönderi yok'; ?></strong><br>Son Aktivite</div></div>
        </div>
    </div>
</div>

<?php
$conn->close();
include_once __DIR__.'/../../includes/footer.php';
?>
