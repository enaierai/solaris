<?php
/**
 * Şablon: comment_item_template.php
 * Açıklama: Tek bir yorum satırının HTML'i.
 */
if (!isset($comment)) {
    return;
}

$commenter_avatar = BASE_URL.'uploads/profile_pictures/'.($comment['comment_profile_picture_url'] ?? 'default_profile.png');
if (empty($comment['comment_profile_picture_url']) || $comment['comment_profile_picture_url'] == 'default_profile.png') {
    $commenter_avatar = 'https://ui-avatars.com/api/?name='.urlencode($comment['comment_username']).'&background=random&color=fff&size=40';
}

$is_comment_owner = ($is_logged_in && isset($current_user_id) && $current_user_id == $comment['user_id']);
// Bu şablonun hem post.php hem de post_card_feed.php'de çalışması için gönderi sahibini de kontrol etmemiz gerekir.
// $post_data değişkeni post.php'den, $post değişkeni ise post_card_feed.php'den gelir.
$post_author_id = $post_data['user_id'] ?? $post['user_id'] ?? null;
$is_post_owner = ($is_logged_in && isset($current_user_id) && $current_user_id == $post_author_id);
?>
<div class="d-flex align-items-start mb-3 comment-item" data-comment-id="<?php echo htmlspecialchars($comment['id']); ?>">
    <img src="<?php echo $commenter_avatar; ?>" class="rounded-circle me-3" width="40" height="40" alt="Yorumcu">
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="<?php echo BASE_URL.'public/pages/profile.php?user='.htmlspecialchars($comment['comment_username']); ?>" class="text-dark fw-bold text-decoration-none">
                    <?php echo htmlspecialchars($comment['comment_username']); ?>
                </a>
                <small class="text-muted ms-2"><?php echo time_ago(strtotime($comment['created_at'])); ?></small>
            </div>
            
            <?php if ($is_logged_in) { ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($is_comment_owner || $is_post_owner) { ?>
                            <li><a class="dropdown-item text-danger delete-comment-btn" href="#" data-comment-id="<?php echo htmlspecialchars($comment['id']); ?>" data-post-id="<?php echo htmlspecialchars($comment['post_id']); ?>"><i class="fas fa-trash-alt fa-fw me-2"></i> Yorumu Sil</a></li>
                        <?php } else { ?>
                            <li><a class="dropdown-item text-danger report-button" href="#" data-type="comment" data-id="<?php echo htmlspecialchars($comment['id']); ?>"><i class="fas fa-flag fa-fw me-2"></i> Yorumu Şikayet Et</a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

        </div>
        <p class="mb-1 text-dark"><?php echo linkify(htmlspecialchars($comment['comment_text'])); ?></p>
    </div>
</div>