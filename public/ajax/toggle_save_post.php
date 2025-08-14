<?php

// public/ajax/toggle_save_post.php

session_start();
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';
include_once __DIR__.'/../../includes/models/PostModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz gönderi ID.']);
    exit;
}

$is_currently_saved = isPostSavedByUser($conn, $user_id, $post_id);
$action_taken = '';

if ($is_currently_saved) {
    // Kayıtlıysa, kayıttan çıkar
    if (unsavePostForUser($conn, $user_id, $post_id)) {
        $action_taken = 'unsaved';
    }
} else {
    // Kayıtlı değilse, kaydet
    if (savePostForUser($conn, $user_id, $post_id)) {
        $action_taken = 'saved';
    }
}

if ($action_taken) {
    echo json_encode(['success' => true, 'action' => $action_taken]);
} else {
    echo json_encode(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu.']);
}

$conn->close();
