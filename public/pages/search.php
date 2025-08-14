<?php
// Sayfanın tüm mantığını (veri çekme, arama işlemleri vb.) çalıştırır.
include_once __DIR__.'/../../includes/logic/search.logic.php';

// Hazırlanan değişkenleri kullanarak sayfanın başlığını oluşturur.
include_once __DIR__.'/../../includes/header.php';
?>

<style>
    .search-tabs .nav-link {
        color: #6c757d;
        border-bottom: 2px solid transparent;
        border-radius: 0;
    }
    .search-tabs .nav-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background-color: transparent;
    }
    .user-list-item-hover:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
        transition: all 0.2s ease-in-out;
    }
</style>

<div class="container my-4">
    <h3 class="mb-4 text-dark text-center fw-bold">
        <i class="fas fa-search me-2 text-primary"></i>
        Arama Sonuçları: "<?php echo htmlspecialchars($search_query); ?>"
    </h3>
    <hr class="my-4">

    <?php if (empty($search_query)) { ?>
        <div class="text-center p-5 bg-light rounded-3">
            <i class="fas fa-keyboard fa-3x mb-3 text-muted"></i>
            <h4 class="fw-bold">Lütfen aramak istediğiniz bir terim girin.</h4>
            <p class="lead text-muted">Kullanıcı adları veya gönderi açıklamaları içinde arama yapabilirsiniz.</p>
        </div>
    <?php } else { ?>
        <ul class="nav nav-pills justify-content-center mb-4 search-tabs" id="searchTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="posts-tab" data-bs-toggle="pill" data-bs-target="#posts" type="button" role="tab">
                    <i class="fas fa-images me-2"></i>Gönderiler (<?php echo count($found_posts); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="pill" data-bs-target="#users" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Kullanıcılar (<?php echo count($found_users); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="searchTabsContent">
            <div class="tab-pane fade" id="users" role="tabpanel">
                <?php if (!empty($found_users)) { ?>
                    <div class="list-group mt-4">
                        <?php foreach ($found_users as $user) { ?>
                            <a href="<?php echo BASE_URL; ?>public/pages/profile.php?user=<?php echo htmlspecialchars($user['username']); ?>" class="list-group-item list-group-item-action d-flex align-items-center rounded-3 mb-2 user-list-item-hover">
                                <img src="<?php echo BASE_URL.'uploads/profile_pictures/'.htmlspecialchars($user['profile_picture_url'] ?: 'default_profile.png'); ?>" alt="Profil Resmi" class="rounded-circle me-3" width="50" height="50">
                                <strong class="fs-5"><?php echo htmlspecialchars($user['username']); ?></strong>
                                <i class="fas fa-chevron-right ms-auto text-muted"></i>
                            </a>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="text-center p-5 bg-light rounded-3 mt-4">
                        <p class="lead text-muted">"<?php echo htmlspecialchars($search_query); ?>" ile eşleşen kullanıcı bulunamadı.</p>
                    </div>
                <?php } ?>
            </div>

            <div class="tab-pane fade show active" id="posts" role="tabpanel">
                <?php if (!empty($found_posts)) { ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mt-4">
                        <?php foreach ($found_posts as $post) { ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <a href="<?php echo BASE_URL; ?>public/pages/post.php?id=<?php echo $post['post_id']; ?>">
                                        <img src="<?php echo BASE_URL; ?>uploads/posts/<?php echo htmlspecialchars($post['image_url']); ?>" class="card-img-top" alt="Gönderi Resmi" style="height: 250px; object-fit: cover;">
                                    </a>
                                    <div class="card-footer bg-white">
                                        <a href="<?php echo BASE_URL; ?>public/pages/profile.php?user=<?php echo htmlspecialchars($post['username']); ?>" class="d-flex align-items-center text-decoration-none text-dark">
                                            <img src="<?php echo BASE_URL.'uploads/profile_pictures/'.htmlspecialchars($post['user_profile_picture_url'] ?: 'default_profile.png'); ?>" alt="Profil Resmi" class="rounded-circle me-2" width="30" height="30">
                                            <small class="fw-bold"><?php echo htmlspecialchars($post['username']); ?></small>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                     <div class="text-center p-5 bg-light rounded-3 mt-4">
                        <p class="lead text-muted">"<?php echo htmlspecialchars($search_query); ?>" ile eşleşen gönderi bulunamadı.</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>

<?php
$conn->close();
include_once __DIR__.'/../../includes/footer.php';
?>
