<?php
// --- GÜVENLİ VE MERKEZİ BİLEŞEN ---
// Bu bileşen, Controller'dan gelen şu değişkenlere ihtiyaç duyar:
// $post, $is_logged_in

// Değişkenlerin varlığını ve içeriğini en başta kontrol edelim.
if (!isset($post) || !is_array($post)) {
    return; // Post verisi yoksa hiçbir şey gösterme.
}

// Değişkenleri güvenli hale getirelim (null kontrolü)
$is_logged_in = $is_logged_in ?? false;
$page_name = $page_name ?? 'home'; // Varsayılan olarak 'home' kabul edelim

// Post verilerini de güvenli hale getirelim
$post_id = $post['id'] ?? 0;
$username = $post['username'] ?? '';
$caption = $post['caption'] ?? '';
$user_liked = $post['user_liked'] ?? false;
$like_count = $post['like_count'] ?? 0;
$comment_count = $post['comment_count'] ?? 0;
$user_saved = $post['user_saved'] ?? false;
?>

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
    <span><?php echo linkify(htmlspecialchars($caption)); ?></span>
</div>

<div class="comments-container mt-2" id="comments-<?php echo $post_id; ?>" style="<?php echo ($page_name === 'post') ? 'display: block;' : 'display: none;'; ?>">
    <div class="comment-list">
        <?php
        // Yorumlar sadece tekil gönderi sayfasında (`post.php`) en başta yüklenir.
        // Diğer sayfalarda AJAX ile yükleneceği için burası başlangıçta boştur.
        // Bu mantık PostController'da ele alınacak.
?>
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