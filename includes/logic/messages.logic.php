<?php

// includes/logic/messages.logic.php

session_start();

// Gerekli dosyaları dahil et
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';

// Kullanıcının oturum açıp açmadığını kontrol et
if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// CSRF token'ı oluştur veya mevcut olanı kullan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Konuşma ID'si URL'den geldiyse, o konuşmanın 'updated_at' zamanını güncelleyerek
// konuşma listesinde en üste taşınmasını sağla.
if (isset($_GET['conversation_id'])) {
    $conversation_id_from_url = (int) $_GET['conversation_id'];
    $stmt = $conn->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)');
    if ($stmt) {
        $stmt->bind_param('iii', $conversation_id_from_url, $current_user_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Kullanıcının tüm konuşmalarını, son mesajları ve okunmamış mesaj sayılarını çek
$conversations = [];
$stmt = $conn->prepare('
    SELECT
        c.id,
        c.updated_at,
        u.id as partner_id,
        u.username,
        u.profile_picture_url,
        m.message_text AS last_message_text,
        m.created_at AS last_message_date,
        (SELECT COUNT(m_unread.id) FROM messages m_unread WHERE m_unread.conversation_id = c.id AND m_unread.is_read = 0 AND m_unread.sender_id != ?) AS unread_count
    FROM conversations c
    LEFT JOIN users u ON (c.user_one_id = ? AND u.id = c.user_two_id) OR (c.user_two_id = ? AND u.id = c.user_one_id)
    LEFT JOIN (
        SELECT conversation_id, message_text, created_at,
               ROW_NUMBER() OVER(PARTITION BY conversation_id ORDER BY created_at DESC) as rn
        FROM messages
    ) m ON m.conversation_id = c.id AND m.rn = 1
    WHERE c.user_one_id = ? OR c.user_two_id = ?
    GROUP BY c.id
    ORDER BY c.updated_at DESC
');
if ($stmt) {
    $stmt->bind_param('iiiii', $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    $stmt->close();
}
