<?php
// public/pages/notifications.php (NİHAİ VE TAM VERSİYON)
include_once __DIR__.'/../../includes/logic/notifications.logic.php';
include_once __DIR__.'/../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm rounded-4">
                <div class="card-header bg-white p-3">
                    <h4 class="mb-0">Bildirimler</h4>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($notifications)) { ?>
                            <li class="list-group-item text-center p-5">
                                <p class="text-muted">Henüz hiç bildiriminiz yok.</p>
                            </li>
                        <?php } else { ?>
                            <?php foreach ($notifications as $notification) {
                                // DÜZELTME: Doğru değişken isimlerini kullanıyoruz.
                                $sender_username = htmlspecialchars($notification['sender_username']);
                                $sender_avatar_url = htmlspecialchars($notification['sender_avatar'] ?? 'default_profile.png');
                                $sender_avatar = BASE_URL.'uploads/profile_pictures/'.$sender_avatar_url;

                                // Bildirim tipine göre ikon ve link belirleme
                                $icon = '';
                                $link = '#';
                                switch ($notification['type']) {
                                    case 'like':
                                        $icon = 'fas fa-heart text-danger';
                                        if ($notification['post_id']) {
                                            $link = BASE_URL.'public/pages/post.php?id='.$notification['post_id'];
                                        }
                                        break;
                                    case 'comment':
                                        $icon = 'fas fa-comment text-primary';
                                        if ($notification['post_id']) {
                                            $link = BASE_URL.'public/pages/post.php?id='.$notification['post_id'];
                                        }
                                        break;
                                    case 'follow':
                                        $icon = 'fas fa-user-plus text-success';
                                        $link = BASE_URL.'public/pages/profile.php?user='.$sender_username;
                                        break;
                                }
                                ?>
                                <li class="list-group-item notification-item <?php echo $notification['is_read'] == 0 ? 'notification-unread' : ''; ?>">
                                    <a href="<?php echo $link; ?>" class="text-decoration-none d-flex align-items-center">
                                        <div class="notification-icon me-3">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <img src="<?php echo $sender_avatar; ?>" class="rounded-circle me-3" width="50" height="50" alt="<?php echo $sender_username; ?>">
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-dark">
                                                <strong class="text-primary"><?php echo $sender_username; ?></strong>
                                                <?php echo htmlspecialchars($notification['notification_text']); ?>
                                            </p>
                                            <small class="text-muted"><?php echo time_ago(strtotime($notification['created_at'])); ?></small>
                                        </div>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__.'/../../includes/footer.php'; ?>

<style>
    .notification-item {
        transition: background-color 0.3s ease;
    }
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    [data-theme="dark"] .notification-item:hover {
        background-color: var(--bg-input);
    }
    .notification-unread {
        background-color: #f1f6ff;
        border-left: 4px solid var(--primary-color);
    }
    [data-theme="dark"] .notification-unread {
        background-color: rgba(90, 24, 154, 0.1);
    }
    .notification-icon {
        font-size: 1.5rem;
    }
</style>