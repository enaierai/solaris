<?php
// Bu dosya bir View'dir. Tüm PHP mantığı PostController'da olacaktır.
// $post_data, $post_comments, $is_owner, $is_logged_in, $current_user_id değişkenlerinin
// Controller tarafından buraya aktarıldığını varsayıyoruz.

// Varsayılan değerler (eğer Controller'dan gelmezse hata vermemesi için)
$post_data = $post_data ?? [
    'id' => 0,
    'user_id' => 0,
    'username' => 'Bilinmeyen Kullanıcı',
    'profile_picture_url' => '',
    'caption' => '',
    'created_at' => '',
    'media' => [],
    'tags' => [],
    'like_count' => 0,
    'comment_count' => 0,
    'user_liked' => false,
    'user_saved' => false,
];
$post_comments = $post_comments ?? [];
$is_owner = $is_owner ?? false;
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null;

// Post verilerini daha okunabilir değişkenlere atayalım
$post_id = $post_data['id'];
$username = $post_data['username'];
$profile_picture_url = $post_data['profile_picture_url'];
$caption = $post_data['caption'];
$created_at = $post_data['created_at'];
$media = $post_data['media'];
$like_count = $post_data['like_count'];
$comment_count = $post_data['comment_count'];
$user_liked = $post_data['user_liked'];
$user_saved = $post_data['user_saved'];
$post_user_id = $post_data['user_id'];

// Yardımcı fonksiyonları doğrudan çağırıyoruz (functions.php'den)
$user_avatar = getUserAvatar($username, $profile_picture_url);
$time_ago_text = time_ago($created_at);
$linked_caption = linkify(htmlspecialchars($caption));

// Header'ı yükle (Bu kısım Controller'da olmalı, ancak mevcut yapıda burada kalıyor)
// $data = [
//     'meta' => [
//         'meta_title' => 'Gönderi - Solaris', // Dinamik olarak gönderi başlığı eklenebilir
//         'meta_description' => 'Gönderi detayları.',
//     ],
//     'is_logged_in' => $is_logged_in,
//     'current_user_id' => $current_user_id,
// ];
// $this->view('layouts/header', $data);

?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <!-- Sol taraf (media carousel) -->
        <div class="col-md-7">
            <div id="postCarousel" class="carousel slide bg-dark rounded shadow-sm" data-bs-ride="carousel">
                <div class="carousel-inner rounded">
                    <?php if (empty($media)) { ?>
                        <div class="carousel-item active d-flex align-items-center justify-content-center" style="height: 85vh;">
                            <p class="text-white-50">Görsel bulunamadı.</p>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($media as $index => $media_item) { ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" style="height: 85vh;">
                                <?php if (($media_item['media_type'] ?? '') === 'video') { ?>
                                    <video controls class="d-block w-100 h-100" style="object-fit: contain;">
                                        <source src="<?php echo BASE_URL.'serve.php?path=posts/'.htmlspecialchars($media_item['image_url'] ?? ''); ?>" type="video/mp4">
                                    </video>
                                <?php } else { ?>
                                    <img src="<?php echo BASE_URL.'serve.php?path=posts/'.htmlspecialchars($media_item['image_url'] ?? ''); ?>" class="d-block w-100 h-100" style="object-fit: contain;" alt="Gönderi Resmi">
                                <?php } ?>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <?php if (count($media) > 1) { ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                <?php } ?>
            </div>
        </div>

        <!-- Sağ taraf (detaylar ve yorumlar) -->
        <div class="col-md-5 d-flex flex-column" style="max-height: 85vh;">
            <div class="card h-100 border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo $user_avatar; ?>" class="rounded-circle me-3" width="40" height="40">
                        <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>" class="fw-bold text-dark text-decoration-none">
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                    </div>
                    <?php if ($is_owner) { ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-dark" type="button" id="postOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="postOptionsDropdown">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-archive me-2"></i>Arşivle</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-comment-slash me-2"></i>Yorumları Kapat</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Düzenle</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash-alt me-2"></i>Sil</a></li>
                            </ul>
                        </div>
                    <?php } else { ?>
                        <!-- Diğer kullanıcılar için raporlama seçeneği -->
                        <button class="btn btn-sm btn-link text-muted report-button" data-type="post" data-id="<?php echo $post_id; ?>" title="Raporla"><i class="fas fa-flag"></i></button>
                    <?php } ?>
                </div>

                <div class="overflow-auto p-3" style="flex: 1;">
                    <!-- Gönderi Açıklaması ve Etiketler -->
                    <?php if (!empty($caption)) { ?>
                        <p class="mb-2"><?php echo $linked_caption; ?></p>
                    <?php } ?>
                    <?php if (!empty($post_data['tags']) && is_array($post_data['tags'])) { ?>
                        <div class="mb-3">
                            <?php foreach ($post_data['tags'] as $tag) { ?>
                                <?php if (is_array($tag) && isset($tag['name'])) { // TypeError düzeltmesi?>
                                    <span class="badge bg-primary me-1 mb-1">#<?php echo htmlspecialchars($tag['name']); ?></span>
                                <?php } elseif (is_string($tag)) { // Eğer etiketler sadece string olarak geliyorsa?>
                                    <span class="badge bg-primary me-1 mb-1">#<?php echo htmlspecialchars($tag); ?></span>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <p class="text-muted small mb-3">
                        <i class="far fa-clock me-1"></i><?php echo $time_ago_text; ?>
                    </p>

                    <!-- Yorumlar -->
                    <div class="comments-list-container">
                        <?php if (empty($post_comments)) { ?>
                            <p class="text-center text-muted">Henüz yorum yok.</p>
                        <?php } else { ?>
                            <?php
                            $comment_count_total = count($post_comments);
                            for ($i = 0; $i < $comment_count_total; ++$i) {
                                $comment = $post_comments[$i];
                                $this->view('components/comment_item_template', [
                                    'comment' => $comment,
                                    'is_logged_in' => $is_logged_in,
                                    'current_user_id' => $current_user_id,
                                    'post_owner_id' => $post_user_id,
                                ]);
                            }
                            ?>
                        <?php } ?>
                    </div>
                </div>

                <!-- Etkileşim Alanı (post_card_feed.php'den entegre edildi) -->
                <div class="border-top p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-3 post-actions" data-post-id="<?php echo $post_id; ?>">
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-link text-dark p-0 like-button" data-post-id="<?php echo $post_id; ?>" data-liked="<?php echo $user_liked ? 'true' : 'false'; ?>">
                                    <i class="heart-icon <?php echo $user_liked ? 'fas text-danger' : 'far'; ?> fa-heart fa-lg"></i>
                                </button>
                                <a href="#" class="text-dark text-decoration-none view-likers" data-post-id="<?php echo $post_id; ?>">
                                    <span class="like-count fw-bold small"><?php echo $like_count; ?> beğeni</span>
                                </a>
                            </div>
                            <button class="btn btn-sm btn-link text-dark p-0 comment-toggle-button" type="button" data-post-id="<?php echo $post_id; ?>">
                                <i class="far fa-comment-dots fa-lg me-1"></i>
                                <span class="comment-count small"><?php echo $comment_count; ?></span>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-link text-dark p-0 save-post-button" data-post-id="<?php echo $post_id; ?>" data-saved="<?php echo $user_saved ? 'true' : 'false'; ?>">
                            <i class="fa-bookmark fa-lg <?php echo $user_saved ? 'fas' : 'far'; ?>"></i>
                        </button>
                    </div>

                    <!-- Yorum Giriş Alanı -->
                    <?php if ($is_logged_in) { ?>
                    <form class="add-comment-form mt-2 pt-2 border-top" data-post-id="<?php echo $post_id; ?>">
                        <div class="input-group">
                            <input name="comment_text" class="form-control comment-input border-0 bg-light form-control-sm" placeholder="Yorum ekle...">
                            <button class="btn btn-light comment-submit-button" type="submit"><i class="fas fa-paper-plane text-primary"></i></button>
                        </div>
                    </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı yükle
// $this->view('layouts/footer');
?>
<script src="<?php echo BASE_URL; ?>public/js/post.js"></script>
