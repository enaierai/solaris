<?php
// public/pages/post.php (DÜZELTİLMİŞ)
// --- YENİ KONTROL BLOĞU ---
// Eğer logic dosyası bir gönderi bulamadıysa, hata mesajı göster ve devam etme.
if (!$post_data) {
    echo '<div class="container my-5"><div class="alert alert-danger text-center">Aradığınız gönderi bulunamadı veya kaldırılmış.</div></div>';

    return; // Sayfanın geri kalanının işlenmesini durdurur.
}
// --- KONTROL BLOĞU SONU --
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-sm rounded-4">
                <div class="row g-0">
                    <div class="col-md-7">
                        <?php if (!empty($post_media)) { ?>
                            <div id="postCarousel" class="carousel slide h-100" data-bs-ride="carousel">
                                <div class="carousel-inner h-100">
                                    <?php foreach ($post_media as $index => $media) { ?>
                                        <div class="carousel-item h-100 <?php echo $index === 0 ? 'active' : ''; ?>">
                                            <?php if ($media['media_type'] === 'video') { ?>
                                                <video controls class="d-block w-100 h-100" style="object-fit: cover;">
                                                    <source src="<?php echo BASE_URL.'uploads/posts/'.htmlspecialchars($media['image_url']); ?>" type="video/mp4">
                                                </video>
                                            <?php } else { ?>
                                                <img src="<?php echo BASE_URL.'uploads/posts/'.htmlspecialchars($media['image_url']); ?>" class="d-block w-100 h-100" alt="Gönderi Resmi" style="object-fit: cover;">
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php if (count($post_media) > 1) { ?>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">Görsel bulunamadı.</div>
                        <?php } ?>
                    </div>

                    <div class="col-md-5 d-flex flex-column">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo BASE_URL.'uploads/profile_pictures/'.htmlspecialchars($post_data['profile_picture_url'] ?? 'default_profile.png'); ?>" class="rounded-circle me-2" width="40" height="40" alt="Profil Resmi">
                                <a href="<?php echo BASE_URL.'public/pages/profile.php?user='.htmlspecialchars($post_data['username']); ?>" class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($post_data['username']); ?></a>
                            </div>
                            <?php if ($is_owner) { ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" id="editCaptionBtn" data-post-id="<?php echo $post_data['post_id']; ?>"><i class="fas fa-edit fa-fw me-2"></i> Açıklamayı Düzenle</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" id="deletePostBtn" data-post-id="<?php echo $post_data['post_id']; ?>" data-owner="<?php echo htmlspecialchars($post_data['username']); ?>"><i class="fas fa-trash-alt fa-fw me-2"></i> Gönderiyi Sil</a></li>
                                    </ul>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-body d-flex flex-column flex-grow-1">
                            <div id="captionContainer">
                                <p id="captionDisplay"><?php echo make_hashtags_clickable(htmlspecialchars($post_data['caption'])); ?></p>
                                <div id="captionEditForm" style="display:none;" class="mt-2">
                                    <textarea id="captionInput" class="form-control mb-2" rows="3"><?php echo htmlspecialchars($post_data['caption']); ?></textarea>
                                    <button id="saveCaptionBtn" class="btn btn-success btn-sm me-2">Kaydet</button>
                                    <button id="cancelCaptionBtn" type="button" class="btn btn-secondary btn-sm">İptal</button>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-3"><?php echo time_ago(strtotime($post_data['created_at'])); ?></small>
                            <div class="d-flex align-items-center gap-3 mb-3">
    <button type="button" class="btn btn-sm like-btn like-button" data-post-id="<?php echo $post_data['post_id']; ?>" data-liked="<?php echo $post_data['user_liked'] ? 'true' : 'false'; ?>">
        <i class="<?php echo $post_data['user_liked'] ? 'fas text-danger' : 'far'; ?> fa-heart me-1 heart-icon"></i>
        <span class="like-count"><?php echo $post_data['like_count']; ?></span>
    </button>

    <div class="btn btn-sm text-muted" id="comment-counter-<?php echo $post_data['post_id']; ?>">
        <i class="far fa-comment-dots me-1"></i>
        <span class="comment-count"><?php echo $post_data['comment_count']; ?></span>
    </div>

    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto save-post-button" data-post-id="<?php echo $post_data['post_id']; ?>" data-saved="<?php echo $post_data['user_saved'] ? 'true' : 'false'; ?>">
        <i class="<?php echo $post_data['user_saved'] ? 'fas' : 'far'; ?> fa-bookmark"></i>
    </button>
</div>
                            
                            <div class="comments-container flex-grow-1" id="comments-<?php echo $post_data['post_id']; ?>" style="display: block;">
                                <div class="comment-list">
                                    <?php if (empty($post_comments)) { ?>
                                        <p class="text-muted no-comments small">Henüz yorum yapılmamış.</p>
                                    <?php } else { ?>
                                        <?php foreach ($post_comments as $comment) { ?>
                                            <?php include __DIR__.'/../../includes/templates/comment_item_template.php'; ?>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>

                            <form class="add-comment-form mt-auto pt-3 border-top" data-post-id="<?php echo $post_data['post_id']; ?>">
                                <div class="input-group">
                                    <textarea name="comment_text" class="form-control comment-input" rows="1" placeholder="Yorum yaz..."></textarea>
                                    <button class="btn btn-primary comment-submit-button" type="submit"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Bu değişkenler post_manage.js ve feed.js tarafından kullanılacak
    const postOwnerUsername = <?php echo json_encode($post_data['username']); ?>;
    const postId = <?php echo json_encode($post_data['post_id']); ?>;
</script>

<?php
// Sayfa adını footer'a bildirelim
$page_name = 'post';
include_once __DIR__.'/../../includes/footer.php';
?>
