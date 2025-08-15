<?php

// public/ajax/send_post_as_message.php (NİHAİ VE TAM VERSİYON)
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/MessageModel.php';
include_once __DIR__.'/../../includes/models/NotificationModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$post_id = intval($_POST['post_id'] ?? 0);

if ($receiver_id <= 0 || $post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler.']);
    exit;
}

// 1. İki kullanıcı arasındaki konuşmayı bul veya oluştur
$conversation_id = findOrCreateConversation($conn, $sender_id, $receiver_id);

if (!$conversation_id) {
    echo json_encode(['success' => false, 'message' => 'Konuşma oluşturulamadı.']);
    exit;
}

// 2. Gönderilecek mesajı (özel formatta) oluştur
$message_text = "[POST_EMBED:{$post_id}]";

// 3. Mesajı, bulunan veya oluşturulan konuşmaya gönder
$success = sendMessageToConversation($conn, $conversation_id, $sender_id, $message_text);

if ($success) {
    // Mesaj gönderildiğinde alıcıya bildirim de gönderelim
    $notification_text = htmlspecialchars($_SESSION['username']).' sana bir gönderi gönderdi.';
    createNotification($conn, $receiver_id, $sender_id, 'message', $notification_text);

    echo json_encode(['success' => true, 'message' => 'Gönderi başarıyla paylaşıldı.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Mesaj gönderilirken bir hata oluştu.']);
}

$conn->close();
