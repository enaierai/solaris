<?php
// public/pages/profile.php (NİHAİ VE TAM VERSİYON)
include_once __DIR__.'/../../includes/logic/profile.logic.php';
include_once __DIR__.'/../../includes/header.php';
?>

<div class="container my-4">
    <div class="profile-header">
        <div class="profile-cover-container">
            <div id="coverImageDisplayContainer" class="profile-cover" style="background-image: url('<?php echo $show_cover_photo ? $cover_picture_url : ''; ?>');"></div>
            <?php if ($is_owner) { ?>
                <button class="btn edit-cover-btn" id="changeCoverPictureBtn" data-bs-toggle="tooltip" title="Kapak fotoğrafını değiştir"><i class="fas fa-camera"></i></button>
            <?php } ?>
        </div>
        <div class="profile-main">
            <div class="profile-avatar-container">
                <img src="<?php echo BASE_URL.'uploads/profile_pictures/'.htmlspecialchars($user_data['profile_picture_url'] ?? 'default_profile.png'); ?>" class="rounded-circle profile-avatar" id="profileImageDisplay" alt="Profil Resmi">
                <?php if ($is_owner) { ?>
                    <button class="btn edit-avatar-btn" id="changeProfilePictureBtn" data-bs-toggle="tooltip" title="Profil resmini değiştir"><i class="fas fa-camera"></i></button>
                <?php } ?>
            </div>
            <h2 class="profile-username"><?php echo htmlspecialchars($user_data['username']); ?></h2>
            <div class="profile-stats">
                <a href="#" class="view-followers" data-userid="<?php echo $profile_user_id; ?>"><strong id="followerCount"><?php echo $follower_count; ?></strong> Takipçi</a>
                <a href="#" class="view-following" data-userid="<?php echo $profile_user_id; ?>"><strong id="followingCount"><?php echo $following_count; ?></strong> Takip</a>
                <span><strong><?php echo count($profile_posts); ?></strong> Gönderi</span>
            </div>
            <?php if (!$is_owner && $is_logged_in) { ?>
                <div class="profile-actions mt-3">
    <?php if (!$is_owner && $is_logged_in) { ?>
        <?php if (!$is_blocked) { ?>
            <button class="btn <?php echo $is_following ? 'btn-outline-secondary' : 'btn-primary'; ?> rounded-pill follow-button" 
                    data-following-id="<?php echo $profile_user_id; ?>" 
                    data-is-following="<?php echo $is_following ? 'true' : 'false'; ?>">
                <?php echo $is_following ? 'Takibi Bırak' : 'Takip Et'; ?>
            </button>
            <a href="<?php echo BASE_URL; ?>public/pages/messages.php?user=<?php echo htmlspecialchars($user_data['username']); ?>" class="btn btn-outline-primary rounded-pill">Mesaj Gönder</a>
        <?php } ?>

        <button class="btn btn-outline-danger rounded-pill block-user-button" 
                data-blocked-id="<?php echo $profile_user_id; ?>" 
                data-is-blocked="<?php echo $is_blocked ? 'true' : 'false'; ?>">
            <?php echo $is_blocked ? 'Engeli Kaldır' : 'Engelle'; ?>
        </button>
    <?php } ?>
</div>
            <?php } ?>
        </div>
    </div>
    
    <hr class="my-4">

    <ul class="nav nav-pills justify-content-center mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts-tab-pane" type="button"><i class="fas fa-th me-2"></i>Gönderiler</button>
        </li>
        <?php if ($is_owner) { ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="saved-tab" data-bs-toggle="tab" data-bs-target="#saved-tab-pane" type="button"><i class="far fa-bookmark me-2"></i>Kaydedilenler</button>
        </li>
        <?php } ?>
    </ul>

    <div class="tab-content" id="profileTabsContent">
        <div class="tab-pane fade show active" id="posts-tab-pane" role="tabpanel">
            <div class="row">
                <?php if (empty($profile_posts)) { ?>
                    <p class="text-center text-muted">Henüz hiç gönderi paylaşılmamış.</p>
                <?php } else { ?>
                    <?php foreach ($profile_posts as $post) { ?>
                        <?php include __DIR__.'/../../includes/templates/post_card_grid.php'; ?>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php if ($is_owner) { ?>
        <div class="tab-pane fade" id="saved-tab-pane" role="tabpanel">
            <div class="row">
                <?php if (empty($saved_posts)) { ?>
                    <p class="text-center text-muted">Henüz hiç gönderi kaydetmedin.</p>
                <?php } else { ?>
                    <?php foreach ($saved_posts as $post) { ?>
                        <?php include __DIR__.'/../../includes/templates/post_card_grid.php'; ?>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<input type="file" id="profilePictureInput" class="d-none" accept="image/*">
<input type="file" id="coverPictureInput" class="d-none" accept="image/*">
<style>
/* Keşfet Sayfası için Sade Stil */
.explore-card { transition: transform 0.2s ease-in-out; }
.explore-card:hover { transform: scale(1.03); }
.explore-card .card-img-top { width: 100%; height: 300px; object-fit: cover; }
.media-icon { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
</style>
<?php include_once __DIR__.'/../../includes/footer.php'; ?>