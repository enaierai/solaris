<?php

// public/ajax/check_new_messages.php
require_once __DIR__.'/../../includes/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'unread_count' => 0];

if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Okunmamış mesaj sayısını çekme sorgusu düzeltildi.
// Sadece kullanıcının alıcı olduğu ve okunmamış olan mesajları sayar.
$stmt = $conn->prepare('
    SELECT COUNT(id) AS unread_count 
    FROM messages 
    WHERE receiver_id = ? AND is_read = 0
');

if ($stmt) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $unread_count = $row['unread_count'];
    $stmt->close();

    $response['success'] = true;
    $response['unread_count'] = $unread_count;
}

echo json_encode($response);
$conn->close();
