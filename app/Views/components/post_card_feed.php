<?php
// --- GÜVENLİ VE STANDART İSKELET ---
// Bu bileşen, Controller'dan gelen şu değişkenlere ihtiyaç duyar:
// $post, $is_logged_in, $current_user_id

// Değişkenlerin varlığını ve içeriğini en başta kontrol edelim.
if (!isset($post) || !is_array($post)) {
    return; // Post verisi yoksa hiçbir şey gösterme.
}

// Gerekli diğer değişkenleri de güvenli hale getirelim
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null;

// Avatarı güvenli bir şekilde alalım.
$user_avatar = getUserAvatar(
    $post['username'] ?? 'bilinmeyen',
    $post['profile_picture_url'] ?? null
);
?>
<div class="card mb-4 border-0 shadow-sm rounded-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom-0">
        <div class="d-flex align-items-center">
            <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($post['username'] ?? ''); ?>">
                <img src="<?php echo $user_avatar; ?>" class="rounded-circle me-3" width="40" height="40" alt="<?php echo htmlspecialchars($post['username'] ?? ''); ?>" style="object-fit: cover;">
            </a>
            <div>
                <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($post['username'] ?? ''); ?>" class="text-dark fw-bold text-decoration-none d-block"><?php echo htmlspecialchars($post['username'] ?? 'Kullanıcı'); ?></a>
                <small class="text-muted"><?php echo isset($post['created_at']) ? time_ago($post['created_at']) : ''; ?></small>
            </div>
        </div>
        
        <?php
        // 2. KISIM: Üç Nokta Menüsü Bileşenini Çağır
        // İhtiyaç duyduğu tüm verileri ona paslıyoruz.
        $this->view('components/post_options_dropdown', [
            'post' => $post,
            'is_logged_in' => $is_logged_in,
            'current_user_id' => $current_user_id,
        ]);
?>
    </div>

    <?php if (!empty($post['media']) && is_array($post['media'])) { ?>
    <div id="carousel-<?php echo $post['id']; ?>" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner bg-light">
            <?php foreach ($post['media'] as $index => $media) { ?>
                <?php
        $media_type = $media['media_type'] ?? 'image';
                $image_url = $media['image_url'] ?? '';
                ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL.'post/'.$post['id']; ?>">
                        <?php if ($media_type === 'video' && !empty($image_url)) { ?>
                            <video class="d-block w-100" style="max-height: 75vh; object-fit: contain; cursor: pointer;">
                                <source src="<?php echo BASE_URL.'public/uploads/posts/'.htmlspecialchars($image_url); ?>" type="video/mp4">
                            </video>
                        <?php } elseif (!empty($image_url)) { ?>
                            <img src="<?php echo BASE_URL.'public/uploads/posts/'.htmlspecialchars($image_url); ?>" class="d-block w-100" style="max-height: 75vh; object-fit: contain; cursor: pointer;" alt="Gönderi Resmi">
                        <?php } ?>
                    </a>
                </div>
            <?php } ?>
        </div>
        <?php if (count($post['media']) > 1) { ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo $post['id']; ?>" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo $post['id']; ?>" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
        <?php } ?>
    </div>
    <?php } ?>
    
    <div class="card-body p-3">
        <?php
        // İhtiyaç duyduğu tüm verileri ona paslıyoruz.
        $this->view('components/post_interactive_section', [
            'post' => $post,
            'is_logged_in' => $is_logged_in,
            'page_name' => $page_name ?? 'home', // Hangi sayfada olduğunu bilsin
        ]);
?>
    </div>
</div>