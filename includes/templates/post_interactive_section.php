<?php
// Bu şablonun çalışması için `$post` ve (isteğe bağlı) `$post_comments` dizilerine ihtiyacı var.
if (!isset($post)) {
    return;
}

// Giriş yapmış kullanıcılar için yorumları, giriş yapmamışlar için boş bir dizi varsayalım.
$post_comments = $post_comments ?? [];
$is_logged_in = $is_logged_in ?? false; // Güvenlik için değişkenin varlığını kontrol edelim
?>

<div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-3 post-actions" data-post-id="<?php echo $post['post_id']; ?>">
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm like-button" data-post-id="<?php echo $post['post_id']; ?>" data-liked="<?php echo $post['user_liked'] ? 'true' : 'false'; ?>">
                <i class="heart-icon <?php echo $post['user_liked'] ? 'fas text-danger' : 'far'; ?> fa-heart"></i>
            </button>
            <a href="#" class="text-dark text-decoration-none view-likers" data-post-id="<?php echo $post['post_id']; ?>">
                <span class="like-count fw-bold"><?php echo (int) ($post['like_count'] ?? 0); ?></span>
                <span class="ms-1 d-none d-sm-inline">beğeni</span>
            </a>
        </div>
        <button class="btn btn-sm text-muted comment-toggle-button" type="button" data-post-id="<?php echo $post['post_id']; ?>">
            <i class="far fa-comment-dots me-1"></i>
            <span class="comment-count"><?php echo (int) ($post['comment_count'] ?? 0); ?></span>
        </button>
        </div>
    <button class="btn btn-sm text-muted save-post-button" data-post-id="<?php echo $post['post_id']; ?>" data-saved="<?php echo $post['user_saved'] ? 'true' : 'false'; ?>">
        <i class="fa-bookmark <?php echo $post['user_saved'] ? 'fas' : 'far'; ?>"></i>
    </button>
</div>

<div id="captionDisplay-<?php echo $post['post_id']; ?>" class="mb-2 post-caption-display">
    <a href="<?php echo BASE_URL; ?>profile?user=<?php echo htmlspecialchars($post['username']); ?>" class="text-dark fw-bold text-decoration-none me-1"><?php echo htmlspecialchars($post['username']); ?></a>
    <span><?php echo make_hashtags_clickable(htmlspecialchars($post['caption'])); ?></span>
</div>

<div class="comments-container mt-2" id="comments-<?php echo $post['post_id']; ?>" style="<?php echo ($page_name === 'post') ? 'display: block;' : 'display: none;'; ?>">
    <div class="comment-list border-top pt-2">
        <?php if ($page_name === 'post') { // Sadece tekil gönderi sayfasında yorumları başta yükle?>
            <?php if (empty($post_comments)) { ?>
                <p class="text-muted no-comments small text-center p-2">Henüz yorum yapılmamış.</p>
            <?php } else { ?>
                <?php foreach ($post_comments as $comment) {
                    include __DIR__.'/comment_item_template.php';
                } ?>
            <?php } ?>
        <?php } ?>
    </div>
    
    <?php if ($is_logged_in) { // Sadece giriş yapanlar yorum formunu görebilir?>
    <form class="add-comment-form mt-2 pt-2 border-top" data-post-id="<?php echo $post['post_id']; ?>">
        <div class="input-group">
            <textarea name="comment_text" class="form-control comment-input border-0 bg-light" rows="1" placeholder="Yorum ekle..." style="resize: none;"></textarea>
            <button class="btn btn-outline-secondary comment-submit-button" type="submit"><i class="fas fa-paper-plane"></i></button>
        </div>
    </form>
    <?php } ?>
</div>