<?php
// --- KONTROL BLOĞU ---
if (!$post_data) {
    echo '<div class="container my-5"><div class="alert alert-danger text-center">Aradığınız gönderi bulunamadı veya kaldırılmış.</div></div>';

    return;
}
// --- KONTROL BLOĞU SONU --
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-sm rounded-4">
                <div class="row g-0">
                    <div class="col-12 col-md-7">
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

                    <div class="col-12 col-md-5 d-flex flex-column">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo BASE_URL.'uploads/profile_pictures/'.htmlspecialchars($post_data['profile_picture_url'] ?? 'default_profile.png'); ?>" class="rounded-circle me-2" width="40" height="40" alt="Profil Resmi">
                                <a href="<?php echo BASE_URL.'profile?user='.htmlspecialchars($post_data['username']); ?>" class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($post_data['username']); ?></a>
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
                            <?php
                            // Değişkeni standart şablonun anlayacağı dile çeviriyoruz
                            $post = $post_data;

// Yorumları, butonları ve formu TEK BİR YERDEN çağırıyoruz.
// Not: $post_comments değişkeni post.logic.php'den geliyor.
include __DIR__.'/../../includes/templates/post_interactive_section.php';
?>
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