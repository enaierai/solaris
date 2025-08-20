<div class="container my-4">
    <div class="profile-header mb-4">
        <div class="profile-cover" style="background-image: url('<?php echo BASE_URL.'public/uploads/cover_pictures/'.($user_data['cover_picture_url'] ?? 'default_cover.png'); ?>');">
            <?php if ($is_owner) { ?>
                <button class="btn btn-sm btn-light position-absolute bottom-0 end-0 m-2"><i class="fas fa-camera"></i> Değiştir</button>
            <?php } ?>
        </div>
        <div class="profile-main d-flex align-items-center p-3">
            <img src="<?php echo getUserAvatar($user_data['username'], $user_data['profile_picture_url']); ?>" class="profile-avatar rounded-circle border border-4 border-white" alt="Profil Resmi">
            <div class="ms-3">
                <h2 class="profile-username mb-0"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                <div class="profile-stats text-muted">
                    <span><strong><?php echo $post_count; ?></strong> Gönderi</span>
                    <a href="#" class="text-dark ms-3"><strong><?php echo $follower_count; ?></strong> Takipçi</a>
                    <a href="#" class="text-dark ms-3"><strong><?php echo $following_count; ?></strong> Takip</a>
                </div>
            </div>
            <?php if (!$is_owner && $is_logged_in) { ?>
                <div class="ms-auto">
                    <button class="btn <?php echo $is_following ? 'btn-secondary' : 'btn-primary'; ?> follow-button" data-following-id="<?php echo $user_data['id']; ?>">
                        <?php echo $is_following ? 'Takibi Bırak' : 'Takip Et'; ?>
                    </button>
                    <a href="#" class="btn btn-outline-secondary">Mesaj Gönder</a>
                </div>
            <?php } ?>
        </div>
    </div>
    
    <div class="profile-tabs-container border-top border-bottom">
        <ul class="nav nav-pills justify-content-center" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts-pane" type="button">
                    <i class="fas fa-th me-2"></i>GÖNDERİLER
                </button>
            </li>   
            <?php if ($is_owner) { ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="saved-tab" data-bs-toggle="tab" data-bs-target="#saved-pane" type="button">
                    <i class="fas fa-bookmark me-2"></i>KAYDEDİLENLER
                </button>
            </li>
            <?php } ?>
        </ul>
    </div>

    <div class="tab-content pt-4" id="profileTabsContent">
        <div class="tab-pane fade show active" id="posts-pane">
            <div class="row row-cols-3 g-1">
                <?php if (empty($profile_posts)) { ?>
                    <div class="col-12"><p class="text-center text-muted mt-5">Henüz hiç gönderi paylaşılmamış.</p></div>
                <?php } else { ?>
                    <?php
                    // --- NİHAİ ÇÖZÜM: "HAYALET"TEN ETKİLENMEYEN FOR DÖNGÜSÜ ---
                    $post_count_total = count($profile_posts);
                    for ($i = 0; $i < $post_count_total; ++$i) {
                        $post = $profile_posts[$i]; // Her döngüde elemanı indeksiyle alıyoruz
                        $this->view('components/post_card_grid', ['post' => $post]);
                    }
                    ?>
                <?php } ?>
            </div>
        </div>
        
        <?php if ($is_owner) { ?>
        <div class="tab-pane fade" id="saved-pane">
            <div class="row row-cols-3 g-1">
                <?php if (empty($saved_posts)) { ?>
                    <div class="col-12"><p class="text-center text-muted mt-5">Henüz hiç gönderi kaydetmedin.</p></div>
                <?php } else { ?>
                    <?php
                    // --- NİHAİ ÇÖZÜM: "HAYALET"TEN ETKİLENMEYEN FOR DÖNGÜSÜ ---
                    $saved_count_total = count($saved_posts);
                    for ($i = 0; $i < $saved_count_total; ++$i) {
                        $post = $saved_posts[$i];
                        $this->view('components/post_card_grid', ['post' => $post]);
                    }
                    ?>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>