<?php

// public/ajax/get_follow_list.php (NİHAİ VE TAM VERSİYON)
require_once __DIR__.'/../../includes/init.php';
// YENİ: Artık doğrudan UserModel'ı kullanıyoruz.
include_once __DIR__.'/../../includes/models/UserModel.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$type = $_GET['type'] ?? ''; // 'followers' or 'following'

if ($user_id <= 0 || !in_array($type, ['followers', 'following'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler.']);
    exit;
}

$users = [];
if ($type === 'followers') {
    // Takipçileri modelden çek
    $users = getFollowersForUser($conn, $user_id);
} else {
    // Takip edilenleri modelden çek
    $users = getFollowingForUser($conn, $user_id);
}

echo json_encode(['success' => true, 'users' => $users]);

$conn->close();
