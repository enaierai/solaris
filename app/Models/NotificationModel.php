<?php

class NotificationModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Veritabanına yeni bir bildirim kaydı oluşturur.
     */
    public function createNotification(int $userId, int $senderId, string $type, string $notificationText, ?int $postId = null, ?string $relatedText = null): bool
    {
        // Kendine bildirim göndermeyi engelle
        if ($userId == $senderId) {
            return true;
        }

        $stmt = $this->db->prepare('INSERT INTO notifications (user_id, from_user_id, type, post_id, related_text, notification_text) VALUES (?, ?, ?, ?, ?, ?)');

        if (!$stmt) {
            error_log('Bildirim sorgusu hazırlanamadı: '.$this->db->error);

            return false;
        }

        $stmt->bind_param('iisiss', $userId, $senderId, $type, $postId, $relatedText, $notificationText);

        $success = $stmt->execute();
        if (!$success) {
            error_log('Bildirim ekleme hatası: '.$stmt->error);
        }

        $stmt->close();

        return $success;
    }

    /**
     * Belirli bir etkileşime (beğeni, yorum vb.) ait bildirimi siler.
     * post_id artık NULL da olabilir.
     */
    public function deleteNotification(int $user_id, int $sender_id, string $type, ?int $post_id): bool
    {
        $sql = 'DELETE FROM notifications 
                WHERE user_id = ? 
                AND from_user_id = ? 
                AND type = ?';

        // Eğer post_id null ise, WHERE koşuluna post_id IS NULL ekle
        // Eğer post_id bir int ise, WHERE koşuluna post_id = ? ekle
        if ($post_id === null) {
            $sql .= ' AND post_id IS NULL';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iis', $user_id, $sender_id, $type);
        } else {
            $sql .= ' AND post_id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iisi', $user_id, $sender_id, $type, $post_id);
        }

        return $stmt->execute();
    }

    /**
     * Belirli bir kullanıcıya ait tüm bildirimleri,
     * gönderen kullanıcı bilgileriyle birlikte getirir.
     */
    public function getNotificationsForUser(int $user_id): array
    {
        $notifications = [];
        $sql = '
            SELECT 
                n.*, 
                u.username AS sender_username, 
                u.profile_picture_url AS sender_avatar
            FROM notifications n
            JOIN users u ON n.from_user_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();

        return $notifications;
    }

    /**
     * Kullanıcı için okunmamış bildirim sayısını döndürür.
     */
    public function getUnreadNotificationCount(int $user_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }
}
