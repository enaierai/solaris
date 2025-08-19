<?php $page_name = 'home'; ?>

<div class="row">
    <div class="col-lg-7 col-xl-8">

        <?php if (!$is_logged_in) { ?>
            <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered animate__animated animate__bounceIn">
                    <div class="modal-content rounded-3 shadow">
                        <div class="modal-header border-bottom-0 text-center flex-column pb-0">
                            <h5 class="modal-title w-100 h4 fw-bold" id="loginModalLabel">
                                <i class="fas fa-solar-panel text-info me-2"></i> Solaris'e Hoş Geldin!
                            </h5>
                            <p class="text-muted small w-100">Topluluğun bir parçası ol ve ilhamını paylaş.</p>
                        </div>
                        <div class="modal-body py-4 px-4 text-center">
                            <p>İçerikleri beğenmek, yorum yapmak ve kendi gönderilerini paylaşmak için hemen topluluğa katıl.</p>
                        </div>
                        <div class="modal-footer flex-column border-top-0 pt-0">
                            <a href="<?php echo BASE_URL; ?>login" class="btn btn-primary w-75 mb-2">Giriş Yap</a>
                            <a href="<?php echo BASE_URL; ?>register" class="btn btn-outline-secondary w-75">Kayıt Ol</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if (empty($posts)) { ?>
            <div class="card mb-4 bg-light border-0 text-center shadow-sm">
                <div class="card-body p-5">
                    <h5 class="card-title text-dark">Akışınızda Henüz Gönderi Yok</h5>
                    <p class="card-text text-secondary">Yeni insanları ve etiketleri keşfederek akışınızı renklendirin!</p>
                    <a href="<?php echo BASE_URL; ?>explore" class="btn btn-primary">Keşfet'e Git</a>
                </div>
            </div>
        <?php } else { ?>

            <div id="posts-container-main">
            <?php foreach ($posts as $post) {
                include __DIR__.'/../includes/templates/post_card_feed.php';
            } ?>
            </div>
            <div id="posts-container-ajax"></div>

            <div id="loading-trigger" class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>

        <?php } ?>

    </div>
    
    <div class="col-lg-5 col-xl-4">
        <div class="sticky-top pt-3" style="top: 80px;">
            <div class="card mb-4 border-0 rounded-3 shadow-sm">
                <div class="card-body">
                    <h6 class="text-dark fw-bold mb-3">Popüler Etiketler</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                                        $popular_tags = getPopularTags($conn, 10);
if (!empty($popular_tags)) {
    foreach ($popular_tags as $tag) {
        $tag_name_for_url = str_replace('#', '', $tag['name']);
        // DEĞİŞTİRİLDİ: Link yeni yapıya uygun hale getirildi.
        echo '<a href="'.BASE_URL.'search?q='.urlencode('#'.$tag_name_for_url).'" class="btn btn-sm btn-outline-dark rounded-pill">#'.htmlspecialchars($tag_name_for_url).'</a>';
    }
} else {
    echo '<p class="text-muted small">Henüz popüler etiket yok.</p>';
}
?>
                    </div>
                </div>
            </div>
            
            <?php if ($is_logged_in) { ?>
            <div class="card bg-white border-0 rounded-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-dark fw-bold mb-0">Önerilen Kullanıcılar</h6>
                        <a href="<?php echo BASE_URL; ?>explore" class="text-decoration-none small text-info">Tümünü Gör</a>
                    </div>
                    <?php
                    // Bu bölümdeki veritabanı sorgusu ve döngü olduğu gibi kalabilir.
                    // Ancak Faz 2'de bu sorguyu bir Model dosyasına taşımak çok daha temiz olacaktır.
                    $suggested_users_sql = 'SELECT u.id, u.username, u.profile_picture_url, COUNT(p.id) AS post_count FROM users u LEFT JOIN posts p ON u.id = p.user_id WHERE u.id != ? AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) GROUP BY u.id ORDER BY post_count DESC LIMIT 5';
                $suggested_users_stmt = $conn->prepare($suggested_users_sql);
                $suggested_users_stmt->bind_param('ii', $current_user_id, $current_user_id);
                $suggested_users_stmt->execute();
                $suggested_users_result = $suggested_users_stmt->get_result();
                if ($suggested_users_result->num_rows > 0) {
                    while ($user_row = $suggested_users_result->fetch_assoc()) {
                        $suggested_user_avatar = BASE_URL.'uploads/profile_pictures/'.($user_row['profile_picture_url'] ?? 'default_profile.png');
                        if (empty($user_row['profile_picture_url']) || $user_row['profile_picture_url'] == 'default_profile.png') {
                            $suggested_user_avatar = 'https://ui-avatars.com/api/?name='.urlencode($user_row['username']).'&background=random&color=fff&size=40';
                        }
                        ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="<?php echo BASE_URL.'profile?user='.htmlspecialchars($user_row['username']); ?>">
                                        <img src="<?php echo $suggested_user_avatar; ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo htmlspecialchars($user_row['username']); ?>">
                                    </a>
                                    <div>
                                        <a href="<?php echo BASE_URL.'profile?user='.htmlspecialchars($user_row['username']); ?>" class="text-dark fw-bold text-decoration-none d-block"><?php echo htmlspecialchars($user_row['username']); ?></a>
                                        <small class="text-muted"><?php echo $user_row['post_count']; ?> gönderi</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-info follow-button" data-following-id="<?php echo htmlspecialchars($user_row['id']); ?>" data-is-following="false">Takip Et</button>
                            </div>
                    <?php
                    }
                } else {
                    echo '<p class="text-muted small">Önerilecek kullanıcı bulunamadı.</p>';
                }
                $suggested_users_stmt->close();
                ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>