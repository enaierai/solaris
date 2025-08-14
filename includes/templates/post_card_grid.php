<?php
/**
 * Şablon: post_card_grid.php
 * Açıklama: Keşfet, Profil gibi grid yapılarında kullanılan görsel odaklı kart.
 * * Bu şablonun doğru çalışması için, dahil edildiği yerden bir `$post`
 * değişkeni alması gerekmektedir.
 */
if (!isset($post)) {
    echo 'Hata: Gönderi verisi bulunamadı.';

    return;
}

// Gelen verinin hem profile.php hem de explore.php ile uyumlu olmasını sağlıyoruz
$image_url = $post['image_url'] ?? $post['first_media_url'];
$media_type = $post['media_type'] ?? $post['first_media_type'];
$media_count = $post['media_count'] ?? 1;
?>

<div class="col-lg-4 col-md-6 mb-4">
    <a href="<?php echo BASE_URL; ?>public/pages/post.php?id=<?php echo $post['post_id']; ?>" class="card-link">
        <div class="card shadow-sm rounded-3 overflow-hidden position-relative explore-card">
            <div class="media-wrapper position-relative">
                <?php if ($media_type === 'video') { ?>
                    <video muted preload="metadata" class="card-img-top">
                        <source src="<?php echo BASE_URL.'uploads/posts/'.htmlspecialchars($image_url); ?>" type="video/mp4">
                    </video>
                    <span class="media-icon"><i class="fas fa-play"></i></span>
                <?php } else { ?>
                    <img src="<?php echo BASE_URL.'uploads/posts/'.htmlspecialchars($image_url); ?>" alt="Gönderi" loading="lazy" class="card-img-top">
                <?php } ?>

                <?php if ($media_count > 1) { ?>
                    <span class="media-icon collage-icon"><i class="fas fa-clone"></i></span>
                <?php } ?>
            </div>
        </div>
    </a>
</div>