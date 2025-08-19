<?php

// includes/models/NotificationModel.php

/**
 * Veritabanına yeni bir bildirim kaydı oluşturur. Bu, doğru veritabanı şemasına göre düzeltilmiş versiyondur.
 *
 * @param mysqli      $conn             Veritabanı bağlantı nesnesi
 * @param int         $userId           Bildirimin alıcı ID'si
 * @param int         $senderId         Bildirimi gönderen ID'si (from_user_id)
 * @param string      $type             Bildirim tipi (örn: 'follow', 'like', 'comment')
 * @param string      $notificationText Bildirim metni
 * @param int|null    $postId           İlgili gönderi ID'si (opsiyonel)
 * @param string|null $relatedText      İlgili metin, örn: yorum içeriği (opsiyonel)
 *
 * @return bool İşlem başarılıysa true, değilse false
 */
function createNotification($conn, $userId, $senderId, $type, $notificationText, $postId = null, $relatedText = null)
{
    // Kendine bildirim göndermeyi engelle
    if ($userId == $senderId) {
        return true;
    }

    // === ANA DÜZELTME: 'text' sütunu 'notification_text' olarak değiştirildi. ===
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, from_user_id, type, post_id, related_text, notification_text) VALUES (?, ?, ?, ?, ?, ?)');

    if (!$stmt) {
        error_log('Bildirim sorgusu hazırlanamadı: '.$conn->error);

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
 * YENİ FONKSİYON: Belirli bir kullanıcıya ait tüm bildirimleri,
 * gönderen kullanıcı bilgileriyle birlikte getirir.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $user_id Bildirimlerin gösterileceği kullanıcı ID'si
 *
 * @return array Kullanıcının bildirimlerini içeren bir dizi
 */
function getNotificationsForUser($conn, $user_id)
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

    $stmt = $conn->prepare($sql);
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
 * YENİ FONKSİYON: Belirli bir etkileşime (beğeni, yorum vb.) ait bildirimi siler.
 * Bu, genellikle bir beğeni geri çekildiğinde kullanılır.
 *
 * @param mysqli $conn      Veritabanı bağlantı nesnesi
 * @param int    $user_id   Bildirimi alan kullanıcı (gönderi sahibi)
 * @param int    $sender_id Bildirimi gönderen kullanıcı (beğeniyi yapan)
 * @param string $type      Bildirim tipi ('like', 'comment' vb.)
 * @param int    $post_id   İlgili gönderi ID'si
 *
 * @return bool silme başarılıysa true, değilse false döner
 */
function deleteNotification($conn, $user_id, $sender_id, $type, $post_id)
{
    $stmt = $conn->prepare('
        DELETE FROM notifications 
        WHERE user_id = ? 
        AND from_user_id = ? 
        AND type = ? 
        AND post_id = ?
    ');
    $stmt->bind_param('iisi', $user_id, $sender_id, $type, $post_id);

    return $stmt->execute();
}
