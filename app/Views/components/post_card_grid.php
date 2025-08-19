<?php
// --- GÜVENLİ VE AKILLI GRID KARTI ---
if (!isset($post) || !is_array($post)) {
    return;
}

// Post'un medya dizisinden ilk elemanı alalım.
$first_media = !empty($post['media']) ? $post['media'][0] : null;
$thumbnail_url = BASE_URL.'public/images/placeholder.png'; // Medyası olmayan gönderiler için varsayılan görsel
$media_type_icon = '';

if ($first_media && !empty($first_media['image_url'])) {
    $thumbnail_url = BASE_URL.'public/uploads/posts/'.htmlspecialchars($first_media['image_url']);
    if ($first_media['media_type'] === 'video') {
        $media_type_icon = '<i class="fas fa-play media-icon"></i>';
    } elseif (count($post['media']) > 1) {
        $media_type_icon = '<i class="fas fa-clone media-icon"></i>'; // Birden fazla medya varsa
    }
}
?>
<div class="col mb-2">
    <a href="<?php echo BASE_URL.'post/'.($post['id'] ?? 0); ?>" class="post-grid-card">
        <img src="<?php echo $thumbnail_url; ?>" alt="<?php echo htmlspecialchars($post['caption'] ?? 'Gönderi'); ?>" class="img-fluid">
        <?php echo $media_type_icon; ?>
        <div class="post-grid-overlay">
            <div class="post-grid-stats">
                <span><i class="fas fa-heart"></i> <?php echo $post['like_count'] ?? 0; ?></span>
                <span><i class="fas fa-comment"></i> <?php echo $post['comment_count'] ?? 0; ?></span>
            </div>
        </div>
    </a>
</div>