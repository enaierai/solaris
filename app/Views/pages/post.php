<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <!-- Sol taraf (media carousel) -->
        <div class="col-md-7">
            <div id="postCarousel" class="carousel slide bg-dark rounded shadow-sm" data-bs-ride="carousel">
                <div class="carousel-inner rounded">
                    <?php if (empty($post_data['media'])) { ?>
                        <div class="carousel-item active d-flex align-items-center justify-content-center" style="height: 85vh;">
                            <p class="text-white-50">Görsel bulunamadı.</p>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($post_data['media'] as $index => $media) { ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" style="height: 85vh;">
                                <?php if ($media['media_type'] === 'video') { ?>
                                    <video controls class="d-block w-100 h-100" style="object-fit: contain;">
                                        <source src="<?php echo BASE_URL.'serve.php?path=posts/'.htmlspecialchars($media['image_url']); ?>" type="video/mp4">
                                    </video>
                                <?php } else { ?>
                                    <img src="<?php echo BASE_URL.'serve.php?path=posts/'.htmlspecialchars($media['image_url']); ?>" class="d-block w-100 h-100" style="object-fit: contain;" alt="Gönderi Resmi">
                                <?php } ?>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <?php if (count($post_data['media']) > 1) { ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                <?php } ?>
            </div>
        </div>

        <!-- Sağ taraf (detaylar ve yorumlar) -->
        <div class="col-md-5 d-flex flex-column" style="max-height: 85vh;">
            <div class="card h-100 border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo getUserAvatar($post_data['username'], $post_data['profile_picture_url']); ?>" class="rounded-circle me-3" width="40" height="40">
                        <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($post_data['username']); ?>" class="fw-bold text-dark text-decoration-none">
                            <?php echo htmlspecialchars($post_data['username']); ?>
                        </a>
                    </div>
                    <?php if ($is_owner) { ?>
                        <?php $this->view('components/post_options_dropdown', ['post' => $post_data, 'is_logged_in' => $is_logged_in, 'current_user_id' => $current_user_id]); ?>
                    <?php } ?>
                </div>

                <div class="overflow-auto p-3" style="flex: 1;">
                    <!-- Yorumlar -->
<?php if (empty($post_comments)) { ?>
    <p class="text-center text-muted">Henüz yorum yok.</p>
<?php } else { ?>
    <?php
    // --- NİHAİ ÇÖZÜM: "HAYALET"TEN ETKİLENMEYEN FOR DÖNGÜSÜ ---
    $comment_count_total = count($post_comments);
    for ($i = 0; $i < $comment_count_total; ++$i) {
        $comment = $post_comments[$i];
        $this->view('components/comment_item_template', [
            'comment' => $comment,
            'is_logged_in' => $is_logged_in,
            'current_user_id' => $current_user_id,
            'post_owner_id' => $post_data['user_id'],
        ]);
    }
    ?>
<?php } ?>
                </div>

                <!-- Etkileşim Alanı -->
                <div class="border-top p-3">
                    <?php
                        $post = $post_data;
                    $this->view('components/post_interactive_section', [
                        'post' => $post,
                        'is_logged_in' => $is_logged_in,
                        'page_name' => 'post',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>