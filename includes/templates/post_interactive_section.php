<?php
// Bu şablonun çalışması için `$post` adında bir diziye ve
// yorumlar için `$post_comments` adında bir diziye ihtiyacı var.
if (!isset($post)) {
    return;
}
?>

<div class="d-flex align-items-center gap-3 mb-2 post-actions" data-post-id="<?php echo $post['post_id']; ?>">
    <div class="d-flex align-items-center gap-1">
        <button type="button" class="btn btn-sm like-button" data-post-id="<?php echo $post['post_id']; ?>" data-liked="<?php echo $post['user_liked'] ? 'true' : 'false'; ?>">
            <i class="<?php echo $post['user_liked'] ? 'fas text-danger' : 'far'; ?> fa-heart heart-icon"></i>
        </button>
        <a href="#" class="text-dark text-decoration-none view-likers" data-post-id="<?php echo $post['post_id']; ?>">
            <span class="like-count fw-bold"><?php echo (int) ($post['like_count'] ?? 0); ?></span>
            <span class="ms-1">beğeni</span>
        </a>
    </div>
    
    <button class="btn btn-sm text-muted comment-toggle-button" type="button" data-post-id="<?php echo $post['post_id']; ?>">
        <i class="far fa-comment-dots me-1"></i>
        <span class="comment-count"><?php echo $post['comment_count'] ?? 0; ?></span>
    </button>
    
    <button class="btn btn-sm btn-outline-secondary ms-auto save-post-button" data-post-id="<?php echo $post['post_id']; ?>" data-saved="<?php echo $post['user_saved'] ? 'true' : 'false'; ?>">
        <i class="<?php echo $post['user_saved'] ? 'fas' : 'far'; ?> fa-bookmark"></i>
    </button>
</div>

<p class="mb-1">
    <a href="<?php echo BASE_URL; ?>profile?user=<?php echo htmlspecialchars($post['username']); ?>" class="text-dark fw-bold text-decoration-none me-2"><?php echo htmlspecialchars($post['caption']); ?></a>
</p>

<div class="comments-container mt-3" id="comments-<?php echo $post['post_id']; ?>" 
     style="<?php echo ($page_name === 'post') ? 'display: block;' : 'display: none;'; ?>">
    
    <?php if ($page_name === 'post') { // Eğer tekli gönderi sayfasındaysak, yorumları doğrudan basalım?>
        <div class="comment-list">
             <?php if (empty($post_comments)) { ?>
                 <p class="text-muted no-comments small">Henüz yorum yapılmamış.</p>
             <?php } else { ?>
                 <?php foreach ($post_comments as $comment) {
                     include __DIR__.'/comment_item_template.php';
                 } ?>
             <?php } ?>
        </div>
        <form class="add-comment-form mt-3 pt-3 border-top" data-post-id="<?php echo $post['post_id']; ?>">
            <div class="input-group">
                <textarea name="comment_text" class="form-control comment-input" rows="1" placeholder="Yorum ekle..."></textarea>
                <button class="btn btn-primary comment-submit-button" type="submit"><i class="fas fa-paper-plane"></i></button>
            </div>
        </form>
    <?php } else { // Değilse (ana akışta), burası AJAX ile doldurulacak?>
        <?php } ?>
</div>