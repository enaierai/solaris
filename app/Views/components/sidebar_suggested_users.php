<div class="card bg-white border-0 rounded-4 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-dark fw-bold mb-0">Önerilen Kullanıcılar</h6>
            <a href="<?php echo BASE_URL; ?>explore" class="text-decoration-none small text-primary">Tümünü Gör</a>
        </div>
        <?php if (!empty($suggested_users)) { ?>
            <?php foreach ($suggested_users as $user) { ?>
                <?php $user_avatar = getUserAvatar($user['username'], $user['profile_picture_url'] ?? null); ?>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($user['username']); ?>">
                            <img src="<?php echo $user_avatar; ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo htmlspecialchars($user['username']); ?>">
                        </a>
                        <div>
                            <a href="<?php echo BASE_URL.'profile/'.htmlspecialchars($user['username']); ?>" class="text-dark fw-bold text-decoration-none d-block"><?php echo htmlspecialchars($user['username']); ?></a>
                            <small class="text-muted"><?php echo $user['post_count']; ?> gönderi</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary follow-button" data-following-id="<?php echo htmlspecialchars($user['id']); ?>">Takip Et</button>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="text-muted small">Önerilecek kullanıcı bulunamadı.</p>
        <?php } ?>
    </div>
</div>