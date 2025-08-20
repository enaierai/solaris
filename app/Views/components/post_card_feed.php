<?php
// post_card_feed.php (NİHAİ VERSİYON - OTOMATİK KAYMA ENGELLENDİ)
// Bu bileşen, Controller'dan gelen $post, $is_logged_in, $current_user_id değişkenlerini bekler.
// İçerisinde başka bir View çağırmaz, tüm HTML'i kendi üretir.

// Gerekli değişkenlerin varlığını ve içeriğini en başta kontrol edelim.
if (!isset($post) || !is_array($post)) {
    return; // Post verisi yoksa hiçbir şey gösterme.
}

// Güvenli varsayılan değerler atayalım (eğer Controller'dan gelmiyorsa)
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null; // current_user_id'nin Controller'dan geldiğini varsayıyoruz

// Post verilerini daha okunabilir değişkenlere atayalım
$post_id = $post['id'] ?? 0;
$username = $post['username'] ?? 'Bilinmeyen Kullanıcı';
$profile_picture_url = $post['profile_picture_url'] ?? null;
$caption = $post['caption'] ?? '';
$created_at = $post['created_at'] ?? '';
$media = $post['media'] ?? [];
$like_count = $post['like_count'] ?? 0;
$comment_count = $post['comment_count'] ?? 0;
$user_liked = $post['user_liked'] ?? false;
$user_saved = $post['user_saved'] ?? false;
$post_user_id = $post['user_id'] ?? 0; // Gönderi sahibinin ID'si

// Yardımcı fonksiyonları doğrudan çağırıyoruz (functions.php'den)
// Bu fonksiyonların global olarak erişilebilir olması gerekir.
$user_avatar = getUserAvatar($username, $profile_picture_url);
$time_ago_text = time_ago($created_at);
$linked_caption = linkify(htmlspecialchars($caption));

// Gönderi sahibi miyiz?
$is_owner = ($is_logged_in && $current_user_id == $post_user_id);
?>

<div class="card mb-4 border-0 shadow-sm rounded-4 animate__animated animate__fadeInUp" data-post-id="<?php echo $post_id; ?>">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom-0">
        <div class="d-flex align-items-center">
            <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>" class="d-flex align-items-center text-dark text-decoration-none">
                <img src="<?php echo $user_avatar; ?>" class="rounded-circle me-3" width="40" height="40" alt="<?php echo htmlspecialchars($username); ?>" style="object-fit: cover;">
                <div>
                    <span class="fw-bold"><?php echo htmlspecialchars($username); ?></span>
                    <small class="text-muted d-block"><?php echo $time_ago_text; ?></small>
                </div>
            </a>
        </div>
        
        <!-- Post Seçenekleri Dropdown Menüsü (post_options_dropdown.php içeriği buraya gömüldü) -->
        <div class="dropdown">
            <button class="btn btn-sm btn-icon border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-h text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="postOptionsDropdown-<?php echo $post_id; ?>">
                <?php if ($is_logged_in) { ?>
                    <?php if ($is_owner) { ?>
                        <li><a class="dropdown-item edit-caption-btn" href="#" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-edit fa-fw me-2"></i> Açıklamayı Düzenle</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger delete-post-btn" href="#" data-post-id="<?php echo $post_id; ?>" data-owner="<?php echo htmlspecialchars($username); ?>"><i class="fas fa-trash-alt fa-fw me-2"></i> Gönderiyi Sil</a></li>
                    <?php } else { ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>"><i class="fas fa-user fa-fw me-2"></i> Profile Git</a></li>
                    <?php } ?>
                    
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item share-via-message-button" href="#" data-bs-toggle="modal" data-bs-target="#shareViaMessageModal" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-paper-plane fa-fw me-2"></i> Mesaj Olarak Gönder</a></li>
                    <li><a class="dropdown-item copy-link-button" href="#" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-link fa-fw me-2"></i> Bağlantıyı Kopyala</a></li>
                    <li><a class="dropdown-item whatsapp-share-button" href="#" data-post-id="<?php echo $post_id; ?>" data-post-caption="<?php echo htmlspecialchars($caption); ?>"><i class="fab fa-whatsapp fa-fw me-2 text-success"></i> WhatsApp'ta Paylaş</a></li>
                    
                    <?php if (!$is_owner) { ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger report-button" href="#" data-type="post" data-id="<?php echo $post_id; ?>"><i class="fas fa-flag fa-fw me-2"></i> Gönderiyi Şikayet Et</a></li>
                    <?php } ?>

                <?php } else { ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL.'login'; ?>"><i class="fas fa-sign-in-alt fa-fw me-2"></i> Seçenekler için giriş yap</a></li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <?php if (!empty($media) && is_array($media)) { ?>
    <div id="carousel-<?php echo $post_id; ?>" class="carousel slide" data-bs-interval="false">
        <div class="carousel-inner bg-light">
            <?php foreach ($media as $index => $media_item) { ?>
                <?php
                $media_type = $media_item['media_type'] ?? 'image';
                $media_url = $media_item['image_url'] ?? '';
                ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL.'post/'.$post_id; ?>">
                        <?php if ($media_type === 'video' && !empty($media_url)) { ?>
                            <video class="d-block w-100" style="max-height: 75vh; object-fit: contain; cursor: pointer;" controls>
                                <source src="<?php echo BASE_URL.'serve.php?path=posts/'.htmlspecialchars($media_url); ?>" type="video/mp4">
                            </video>
                        <?php } elseif (!empty($media_url)) { ?>
                            <img src="<?php echo BASE_URL.'serve.php?path=posts/'.htmlspecialchars($media_url); ?>" class="d-block w-100" style="max-height: 75vh; object-fit: contain; cursor: pointer;" alt="Gönderi Resmi">
                        <?php } ?>
                    </a>
                </div>
            <?php } ?>
        </div>
        
        <?php if (count($media) > 1) { ?>
            <!-- Masaüstü Ok Butonları -->
            <button class="carousel-control-prev d-none d-md-flex custom-carousel-control" type="button" data-bs-target="#carousel-<?php echo $post_id; ?>" data-bs-slide="prev">
                <i class="fas fa-chevron-left fa-2x"></i>
            </button>
            <button class="carousel-control-next d-none d-md-flex custom-carousel-control" type="button" data-bs-target="#carousel-<?php echo $post_id; ?>" data-bs-slide="next">
                <i class="fas fa-chevron-right fa-2x"></i>
            </button>

            <!-- Mobil Nokta Göstergeleri -->
            <div class="carousel-indicators d-md-none custom-carousel-indicators">
                <?php foreach ($media as $index => $media_item) { ?>
                    <button type="button" data-bs-target="#carousel-<?php echo $post_id; ?>" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php } else { ?>
        <div class="d-flex align-items-center justify-content-center bg-light" style="min-height: 200px;">
            <p class="text-muted">Bu gönderide medya bulunmuyor.</p>
        </div>
    <?php } ?>
    
    <div class="card-body p-3">
        <!-- Post Etkileşim Bölümü -->
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

        <div id="captionDisplay-<?php echo $post_id; ?>" class="mb-2 post-caption-display small">
            <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>" class="text-dark fw-bold text-decoration-none me-1"><?php echo htmlspecialchars($username); ?></a>
            <span><?php echo $linked_caption; ?></span>
        </div>

        <!-- Yorumlar AJAX ile yüklenecek alan -->
        <div class="comments-container mt-2" id="comments-<?php echo $post_id; ?>" style="display: none;">
            <div class="comment-list">
                <!-- Yorumlar buraya AJAX ile yüklenecek -->
            </div>
            
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
