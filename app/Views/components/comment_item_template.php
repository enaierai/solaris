<?php
// comment_item_template.php
// Bu bileşen, Controller'dan gelen $comment, $is_logged_in, $current_user_id, $post_owner_id değişkenlerini bekler.

if (!isset($comment) || !is_array($comment)) {
    return; // Yorum verisi yoksa hiçbir şey gösterme.
}

// Güvenli varsayılan değerler atayalım
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null;
$post_owner_id = $post_owner_id ?? null;

$comment_id = $comment['id'] ?? 0;
$comment_user_id = $comment['user_id'] ?? 0;
$username = $comment['username'] ?? 'Bilinmeyen Kullanıcı';
$profile_picture_url = $comment['profile_picture_url'] ?? null;
$comment_text = htmlspecialchars($comment['comment_text'] ?? '');
$created_at = $comment['created_at'] ?? '';

$user_avatar = getUserAvatar($username, $profile_picture_url);
$time_ago_text = time_ago($created_at);

// Yorumu silme yetkisi: Yorum sahibi veya gönderi sahibi silebilir
$can_delete_comment = $is_logged_in && ($current_user_id == $comment_user_id || $current_user_id == $post_owner_id);
?>

<div class="comment-item d-flex align-items-start mb-3" id="comment-<?php echo $comment_id; ?>">
    <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>" class="flex-shrink-0">
        <img src="<?php echo $user_avatar; ?>" class="rounded-circle me-2" width="30" height="30" alt="<?php echo htmlspecialchars($username); ?>" style="object-fit: cover;">
    </a>
    <div class="flex-grow-1">
        <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>" class="fw-bold text-dark text-decoration-none me-1"><?php echo htmlspecialchars($username); ?></a>
        <span><?php echo nl2br($comment_text); ?></span>
        <div class="d-flex align-items-center mt-1">
            <small class="text-muted me-2"><?php echo $time_ago_text; ?></small>
            <?php if ($can_delete_comment) { ?>
                <button class="btn btn-link btn-sm text-danger p-0 delete-comment-btn" data-comment-id="<?php echo $comment_id; ?>" title="Yorumu Sil">
                    <i class="fas fa-trash-alt"></i>
                </button>
            <?php } ?>
        </div>
    </div>
</div>
