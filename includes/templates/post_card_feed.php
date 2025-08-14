<?php
/**
 * Şablon: post_card_feed.php (NİHAİ VE TAM VERSİYON)
 * Açıklama: Projedeki TÜM detaylı gönderi kartları için tek şablon.
 */
if (!isset($post)) {
    return;
}
$post_user_avatar = BASE_URL.'uploads/profile_pictures/'.($post['profile_picture_url'] ?? 'default_profile.png');
if (empty($post['profile_picture_url']) || $post['profile_picture_url'] == 'default_profile.png') {
    $post_user_avatar = 'https://ui-avatars.com/api/?name='.urlencode($post['username']).'&background=random&color=fff';
}
$is_owner = ($is_logged_in && $current_user_id == $post['user_id']);
?>

<div class="card mb-4 shadow-sm text-dark rounded-4 animate__animated animate__fadeInUp">
    <div class="card-body d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="<?php echo BASE_URL; ?>public/pages/profile.php?user=<?php echo htmlspecialchars($post['username']); ?>">
                <img src="<?php echo $post_user_avatar; ?>" class="rounded-circle me-3" width="45" height="45" alt="<?php echo htmlspecialchars($post['username']); ?>" style="object-fit: cover;">
            </a>
            <div>
                <a href="<?php echo BASE_URL.'public/pages/profile.php?user='.htmlspecialchars($post['username']); ?>" class="text-dark fw-bold text-decoration-none d-block">
                    <?php echo htmlspecialchars($post['username']); ?>
                </a>
            </div>
        </div>
        <?php if ($is_logged_in && !$is_owner) { ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-h text-muted"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item share-via-message-button" href="#" data-bs-toggle="modal" data-bs-target="#shareViaMessageModal" data-post-id="<?php echo $post['post_id']; ?>"><i class="fas fa-paper-plane fa-fw me-2"></i> Mesaj Olarak Gönder</a></li>
                    <li><a class="dropdown-item copy-link-button" href="#" data-post-id="<?php echo $post['post_id']; ?>"><i class="fas fa-link fa-fw me-2"></i> Linki Kopyala</a></li>
                    <li><a class="dropdown-item whatsapp-share-button" href="#" data-post-id="<?php echo $post['post_id']; ?>" data-post-caption="<?php echo htmlspecialchars($post['caption']); ?>"><i class="fab fa-whatsapp fa-fw me-2 text-success"></i> WhatsApp'ta Paylaş</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger report-button" href="#" data-type="post" data-id="<?php echo $post['post_id']; ?>"><i class="fas fa-flag fa-fw me-2"></i> Gönderiyi Şikayet Et</a></li>
                </ul>
            </div>
        <?php } ?>
    </div>

    <?php if (!empty($post['media'])) { ?>
                            <div class="card-img-top-container bg-light">
                                <?php if (count($post['media']) > 1) { ?>
                                    <div id="carousel-<?php echo $post['post_id']; ?>" class="carousel slide">
                                        <div class="carousel-inner">
                                            <?php foreach ($post['media'] as $index => $media) { ?>
                                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                    <?php if ($media['type'] === 'video') { ?>
                                                        <video controls class="d-block w-100" style="max-height:600px; object-fit:contain; background-color: #000;">
                                                            <source src="<?php echo BASE_URL; ?>uploads/posts/<?php echo htmlspecialchars($media['url']); ?>" type="video/mp4">
                                                            Tarayıcınız video etiketini desteklemiyor.
                                                        </video>
                                                    <?php } else { ?>
                                                        <img src="<?php echo BASE_URL; ?>uploads/posts/<?php echo htmlspecialchars($media['url']); ?>" class="d-block w-100" alt="Post Medyası" style="max-height:600px; object-fit:contain;">
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo $post['post_id']; ?>" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo $post['post_id']; ?>" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                                    </div>
                                <?php } else { ?>
                                    <?php $single_media = $post['media'][0]; ?>
                                    <?php if ($single_media['type'] === 'video') { ?>
                                        <video controls class="card-img-top rounded-0" style="max-height:600px; object-fit:contain; background-color: #000;">
                                            <source src="<?php echo BASE_URL; ?>uploads/posts/<?php echo htmlspecialchars($single_media['url']); ?>" type="video/mp4">
                                            Tarayıcınız video etiketini desteklemiyor.
                                        </video>
                                    <?php } else { ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/posts/<?php echo htmlspecialchars($single_media['url']); ?>" class="card-img-top rounded-0" alt="Gönderi" style="max-height:600px; object-fit:contain;">
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        <?php } ?>

    <div class="card-body">
        <div class="d-flex justify-content-start align-items-center gap-3 mb-2">
            <button type="button" class="btn btn-sm like-button" data-post-id="<?php echo $post['post_id']; ?>" data-liked="<?php echo $post['user_liked'] ? 'true' : 'false'; ?>">
                <i class="<?php echo $post['user_liked'] ? 'fas text-danger' : 'far'; ?> fa-heart me-1 heart-icon"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary comment-toggle-button" data-post-id="<?php echo $post['post_id']; ?>">
                <i class="far fa-comment-dots me-1"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary ms-auto save-post-button" data-post-id="<?php echo $post['post_id']; ?>" data-saved="<?php echo $post['user_saved'] ? 'true' : 'false'; ?>">
                <i class="<?php echo $post['user_saved'] ? 'fas' : 'far'; ?> fa-bookmark"></i>
            </button>
        </div>
        
        <a href="#" class="fw-bold text-dark text-decoration-none d-block mb-1 view-likers" data-post-id="<?php echo $post['post_id']; ?>">
            <span class="like-count"><?php echo (int) $post['like_count']; ?></span> beğeni
        </a>
        
        <p class="mb-1">
            <a href="<?php echo BASE_URL.'public/pages/profile.php?user='.htmlspecialchars($post['username']); ?>" class="text-dark fw-bold text-decoration-none me-2"><?php echo htmlspecialchars($post['username']); ?></a>
            <?php echo make_hashtags_clickable(htmlspecialchars($post['caption'])); ?>
        </p>
        
        <div class="comments-container mt-3" id="comments-<?php echo $post['post_id']; ?>" style="display: none;">
            </div>
    </div>
</div>