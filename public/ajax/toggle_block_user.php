<?php

// public/ajax/toggle_block_user.php
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/UserModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$blocker_id = $_SESSION['user_id'];
$blocked_id = intval($_POST['blocked_id'] ?? 0);

if ($blocked_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
    exit;
}

// İki kullanıcı arasında zaten bir engel var mı diye kontrol et
$is_currently_blocked = checkBlockStatus($conn, $blocker_id, $blocked_id);
$action_taken = '';

if ($is_currently_blocked) {
    // Engelli ise, engeli kaldır
    if (unblockUser($conn, $blocker_id, $blocked_id)) {
        $action_taken = 'unblocked';
    }
} else {
    // Engelli değilse, engelle
    if (blockUser($conn, $blocker_id, $blocked_id)) {
        // Engelleme işlemi, genellikle iki taraflı takip ilişkisini de bitirir.
        unfollowUser($conn, $blocker_id, $blocked_id); // Takibi bırak
        unfollowUser($conn, $blocked_id, $blocker_id); // Takipçiyi çıkar
        $action_taken = 'blocked';
    }
}

if ($action_taken) {
    echo json_encode(['success' => true, 'action' => $action_taken]);
} else {
    echo json_encode(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu.']);
}

$conn->close();
