<?php
// --- GÜVENLİ VE NİHAİ YORUM BİLEŞENİ ---
if (!isset($comment) || !is_array($comment)) {
    return;
}

// Değişkenleri en başta, güvenli bir şekilde alalım.
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null;
$post_owner_id = $post_owner_id ?? null; // Bunu post.php'den alacağız.

// Yorum verilerini, Model'den gelen doğru sütun adlarıyla alalım.
$comment_id = $comment['id'] ?? 0;
$comment_user_id = $comment['user_id'] ?? 0;
$comment_username = $comment['username'] ?? 'Bilinmeyen Kullanıcı';
$comment_text = $comment['comment_text'] ?? '';
$comment_avatar = getUserAvatar($comment_username, $comment['profile_picture_url'] ?? null);

// Silme ve şikayet etme yetkilerini net bir şekilde belirleyelim.
$is_comment_owner = ($is_logged_in && $current_user_id == $comment_user_id);
$is_post_owner = ($is_logged_in && $current_user_id == $post_owner_id);
$can_delete = ($is_comment_owner || $is_post_owner);
?>
<div class="d-flex align-items-start mb-3 comment-item" data-comment-id="<?php echo $comment_id; ?>">
    <img src="<?php echo $comment_avatar; ?>" class="rounded-circle me-3" width="40" height="40" alt="Yorumcu">
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($comment_username); ?>" class="text-dark fw-bold text-decoration-none">
                    <?php echo htmlspecialchars($comment_username); ?>
                </a>
                <small class="text-muted ms-2"><?php echo time_ago($comment['created_at']); ?></small>
            </div>
            
            <?php if ($is_logged_in) { ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h text-muted fa-xs"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($can_delete) { ?>
                            <li><a class="dropdown-item text-danger delete-comment-btn" href="#" data-comment-id="<?php echo $comment_id; ?>"><i class="fas fa-trash-alt fa-fw me-2"></i> Yorumu Sil</a></li>
                        <?php } else { ?>
                            <li><a class="dropdown-item report-button" href="#" data-type="comment" data-id="<?php echo $comment_id; ?>"><i class="fas fa-flag fa-fw me-2"></i> Yorumu Şikayet Et</a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

        </div>
        <p class="mb-1 text-dark"><?php echo linkify(htmlspecialchars($comment_text)); ?></p>
    </div>
</div>