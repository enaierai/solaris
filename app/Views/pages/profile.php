<div class="container my-4">
    <div class="profile-header mb-4">
        <div class="profile-cover" style="background-image: url('<?php echo BASE_URL.'serve.php?path=cover_pictures/'.($user_data['cover_picture_url'] ?? 'default_cover.png'); ?>');">
            <?php if ($is_owner) { ?>
                <button class="btn btn-sm btn-light position-absolute bottom-0 end-0 m-2" id="changeCoverPictureBtn"><i class="fas fa-camera"></i> Değiştir</button>
                <input type="file" id="coverPictureInput" class="d-none" accept="image/*">
            <?php } ?>
        </div>
        <div class="profile-main d-flex align-items-center p-3">
            <div class="profile-avatar-container position-relative">
                <img src="<?php echo getUserAvatar($user_data['username'], $user_data['profile_picture_url']); ?>" class="profile-avatar rounded-circle border border-4 border-white" alt="Profil Resmi">
                <?php if ($is_owner) { ?>
                    <button class="btn btn-sm btn-light position-absolute bottom-0 end-0 rounded-circle" id="changeProfilePictureBtn" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-camera"></i></button>
                    <input type="file" id="profilePictureInput" class="d-none" accept="image/*">
                <?php } ?>
            </div>
            <div class="ms-3">
                <h2 class="profile-username mb-0"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                <p id="bioDisplay" class="text-muted small mt-1 mb-2"><?php echo nl2br(htmlspecialchars($user_data['bio'] ?? '')); ?></p>
                <?php if ($is_owner) { ?>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1" id="editBioBtn"><i class="fas fa-edit me-1"></i> Biyografiyi Düzenle</button>
                    <div id="bioEditForm" style="display: none;" class="mt-2">
                        <textarea class="form-control mb-2" id="bioInput" rows="3" placeholder="Biyografinizi yazın..."></textarea>
                        <button class="btn btn-primary btn-sm" id="saveBioBtn">Kaydet</button>
                        <button class="btn btn-secondary btn-sm" id="cancelBioBtn">İptal</button>
                    </div>
                <?php } ?>

                <div class="profile-stats text-muted mt-3">
                    <span><strong><?php echo $post_count; ?></strong> Gönderi</span>
                    <a href="#" class="text-dark ms-3 view-followers" data-bs-toggle="modal" data-bs-target="#generalModal" data-userid="<?php echo $user_data['id']; ?>"><strong><span id="followerCount"><?php echo $follower_count; ?></span></strong> Takipçi</a>
                    <a href="#" class="text-dark ms-3 view-following" data-bs-toggle="modal" data-bs-target="#generalModal" data-userid="<?php echo $user_data['id']; ?>"><strong><span id="followingCount"><?php echo $following_count; ?></span></strong> Takip</a>
                </div>
            </div>
            <?php if (!$is_owner && $is_logged_in) { ?>
                <div class="ms-auto d-flex flex-column align-items-end gap-2">
                    <button class="btn <?php echo $is_following ? 'btn-outline-secondary' : 'btn-primary'; ?> follow-button" data-following-id="<?php echo $user_data['id']; ?>" data-is-following="<?php echo $is_following ? 'true' : 'false'; ?>">
                        <?php echo $is_following ? '<i class="fas fa-user-check"></i> Takip Ediliyor' : '<i class="fas fa-user-plus"></i> Takip Et'; ?>
                    </button>
                    <a href="<?php echo BASE_URL.'messages?user='.$user_data['username']; ?>" class="btn btn-outline-secondary"><i class="fas fa-envelope"></i> Mesaj Gönder</a>
                    <button class="btn <?php echo $is_blocked_by_viewer ? 'btn-danger' : 'btn-outline-danger'; ?> block-user-button" data-blocked-id="<?php echo $user_data['id']; ?>" data-is-blocked="<?php echo $is_blocked_by_viewer ? 'true' : 'false'; ?>" data-username="<?php echo htmlspecialchars($user_data['username']); ?>">
                        <i class="fas fa-ban"></i> <?php echo $is_blocked_by_viewer ? 'Engeli Kaldır' : 'Engelle'; ?>
                    </button>
                </div>
            <?php } elseif ($is_owner && $is_logged_in) { ?>
                <div class="ms-auto">
                    <a href="<?php echo BASE_URL; ?>settings" class="btn btn-outline-secondary"><i class="fas fa-cog"></i> Ayarlar</a>
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
            <?php if (!$can_view_posts) { ?>
                <div class="text-center p-5 bg-light rounded-3">
                    <p class="lead text-muted">Bu hesap gizli. Gönderileri görmek için takip etmelisiniz.</p>
                </div>
            <?php } elseif (empty($profile_posts)) { ?>
                <div class="col-12"><p class="text-center text-muted mt-5">Henüz hiç gönderi paylaşılmamış.</p></div>
            <?php } else { ?>
                <div class="row row-cols-3 g-1" id="profile-posts-grid" data-context="profile" data-username="<?php echo htmlspecialchars($user_data['username']); ?>">
                    <?php
                    foreach ($profile_posts as $post) {
                        $post_data_for_grid = $post;
                        include __DIR__.'/../components/post_card_grid.php';
                    }
                ?>
                </div>
                <!-- Sonsuz kaydırma için tetikleyici -->
                <div id="loading-trigger" class="text-center p-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            <?php } ?>
        </div>
        
        <?php if ($is_owner) { ?>
        <div class="tab-pane fade" id="saved-pane">
            <?php if (empty($saved_posts)) { ?>
                <div class="col-12"><p class="text-center text-muted mt-5">Henüz hiç gönderi kaydetmedin.</p></div>
            <?php } else { ?>
                <div class="row row-cols-3 g-1" id="saved-posts-grid" data-context="saved_posts" data-username="<?php echo htmlspecialchars($user_data['username']); ?>">
                    <?php
                foreach ($saved_posts as $post) {
                    $post_data_for_grid = $post;
                    include __DIR__.'/../components/post_card_grid.php';
                }
                ?>
                </div>
                <!-- Sonsuz kaydırma için tetikleyici (kaydedilenler için ayrı bir tetikleyici gerekebilir) -->
                <div id="loading-trigger-saved" class="text-center p-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<!-- Genel Modal (Beğenenler, Takipçiler, Takip Edilenler için) -->
<div class="modal fade" id="generalModal" tabindex="-1" aria-labelledby="generalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generalModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="generalModalBody">
                <!-- İçerik JavaScript ile doldurulacak -->
            </div>
            <div class="modal-footer d-none" id="modalSearchFooter">
                <input type="text" class="form-control" id="modalUserSearchInput" placeholder="Kullanıcı ara...">
            </div>
        </div>
    </div>
</div>

<script>
    // isProfileOwner JavaScript değişkenini tanımla
    // profile.js'de kullanılacak
    const isProfileOwner = <?php echo $is_owner ? 'true' : 'false'; ?>;
</script>
