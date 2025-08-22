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
     * Yeni bir bildirim kaydı ekler.
     *
     * @param int         $userId      bildirimi alacak kullanıcının ID'si
     * @param int         $fromUserId  bildirimi oluşturan kullanıcının ID'si
     * @param int|null    $postId      i̇lgili gönderinin ID'si (varsa)
     * @param string      $type        bildirim tipi ('like', 'comment', 'follow')
     * @param string|null $relatedText i̇lgili metin (örn: yorum metni)
     *
     * @return bool i̇şlem başarılıysa true, değilse false
     */
    public function createNotification(int $userId, int $fromUserId, ?int $postId, string $type, ?string $relatedText = null): bool
    {
        // from_user'ın kullanıcı adını çek
        global $conn; // Global bağlantıyı kullan
        $from_user_stmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
        $from_user_stmt->bind_param('i', $fromUserId);
        $from_user_stmt->execute();
        $from_user_result = $from_user_stmt->get_result()->fetch_assoc();
        $from_username = $from_user_result['username'] ?? 'Bilinmeyen Kullanıcı';
        $from_user_stmt->close();

        $notificationText = '';
        switch ($type) {
            case 'like':
                $notificationText = "{$from_username} gönderini beğendi.";
                break;
            case 'comment':
                $notificationText = "{$from_username} gönderinize yorum yaptı.";
                break;
            case 'follow':
                $notificationText = "{$from_username} sizi takip etmeye başladı.";
                break;
            default:
                $notificationText = 'Yeni bir bildiriminiz var.';
                break;
        }

        $sql = 'INSERT INTO notifications (user_id, from_user_id, post_id, type, notification_text, related_text) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        // Düzeltme: 'iiisss' olmalı, çünkü 6 parametre var: int, int, int/null, string, string, string/null
        $stmt->bind_param('iiisss', $userId, $fromUserId, $postId, $type, $notificationText, $relatedText);

        return $stmt->execute();
    }

      /**
     * Belirli bir kullanıcı için okunmamış bildirim sayısını döndürür.
     *
     * @param int $userId
     *
     * @return int
     */
    public function getUnreadNotificationCount(int $userId): int
    {
        $sql = 'SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['unread_count'] ?? 0;
    }

    // Diğer bildirim sorguları buraya eklenebilir (örn: getNotifications, markAsRead)
}
