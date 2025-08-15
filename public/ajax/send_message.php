<?php
/*
|--------------------------------------------------------------------------
| File: public/ajax/send_message.php
|--------------------------------------------------------------------------
| Description:
| Bu dosya, bir konuşmaya yeni bir mesaj eklemek için kullanılır.
| AJAX ile POST isteği alır.
|
*/
require_once __DIR__.'/../../includes/init.php';
header('Content-Type: application/json');


$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Oturum açmadınız.';
    echo json_encode($response);
    exit;
}

// CSRF token'ı doğrula
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['message'] = 'Geçersiz CSRF token.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
$message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';

if ($conversation_id <= 0 || empty($message_text)) {
    $response['message'] = 'Geçersiz veri.';
    echo json_encode($response);
    exit;
}

// 1. Konuşmanın geçerli ve kullanıcının bu konuşmanın bir parçası olduğunu doğrula
$stmt = $conn->prepare('SELECT user_one_id, user_two_id FROM conversations WHERE id = ?');
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$result = $stmt->get_result();
$conversation_info = $result->fetch_assoc();
$stmt->close();

if (!$conversation_info || ($conversation_info['user_one_id'] != $current_user_id && $conversation_info['user_two_id'] != $current_user_id)) {
    $response['message'] = 'Bu konuşmaya mesaj gönderme izniniz yok.';
    echo json_encode($response);
    exit;
}

// 2. Mesajı veritabanına ekle
$stmt = $conn->prepare('INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)');
if ($stmt) {
    $stmt->bind_param('iis', $conversation_id, $current_user_id, $message_text);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Mesaj başarıyla gönderildi.';

        // Konuşmanın updated_at zamanını güncelle
        $update_stmt = $conn->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = ?');
        $update_stmt->bind_param('i', $conversation_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $response['message'] = 'Mesaj gönderilirken bir veritabanı hatası oluştu.';
    }
    $stmt->close();
} else {
    $response['message'] = 'Sorgu hatası.';
}

echo json_encode($response);
