<?php

// public/ajax/long_poll_notifications.php

session_start();

include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Kullanıcı oturumu bulunamadı.']);
    exit;
}
if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Veritabanı bağlantısı kurulamadı.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = [
    'success' => true,
    'notifications' => [],
    'unread_messages' => 0,
    'error' => null,
];

// 1. Okunmamış Bildirimleri Çek
$stmt_notifications = $conn->prepare('SELECT id, notification_text as text, type FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC');
if ($stmt_notifications) {
    $stmt_notifications->bind_param('i', $user_id);
    $stmt_notifications->execute();
    $result = $stmt_notifications->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['notifications'][] = $row;
    }
    $stmt_notifications->close();
} else {
    $response['error'] = 'Bildirim sorgusu hazırlanamadı.';
}

// 2. Okunmamış Mesaj Sayısını Çek
if (function_exists('get_unread_message_count')) {
    $response['unread_messages'] = get_unread_message_count($conn, $user_id);
} else {
    if (!$response['error']) {
        $response['error'] = 'Mesaj sayacı fonksiyonu bulunamadı.';
    }
}

$conn->close();
echo json_encode($response);
exit;
