<?php

// public/ajax/add_comment.php (HTML ÜRETEN NİHAİ VERSİYON)
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/UserModel.php';
include_once __DIR__.'/../../includes/models/PostModel.php';
include_once __DIR__.'/../../includes/models/CommentModel.php';
include_once __DIR__.'/../../includes/models/NotificationModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$post_id = intval($_POST['post_id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Yorum boş bırakılamaz.']);
    exit;
}

$post_owner_id = getPostOwnerId($conn, $post_id);

if ($post_owner_id && checkBlockStatus($conn, $user_id, $post_owner_id)) {
    echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyla etkileşime giremezsiniz.']);
    exit;
}

$comment_id = createComment($conn, $post_id, $user_id, $comment_text);

if ($comment_id) {
    if ($post_owner_id && $user_id != $post_owner_id) {
        $notification_text = htmlspecialchars($_SESSION['username']).' gönderinize yorum yaptı.';
        createNotification($conn, $post_owner_id, $user_id, 'comment', $notification_text, $post_id);
    }

    // YENİ: Sunucu tarafında HTML'i oluştur
    $comment = getSingleCommentById($conn, $comment_id);
    $post_data = ['user_id' => $post_owner_id]; // Şablon için gerekli
    $is_logged_in = true;
    $current_user_id = $user_id;

    ob_start();
    include __DIR__.'/../../includes/templates/comment_item_template.php';
    $comment_html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'comment_html' => $comment_html,
    ]);
}

$conn->close();
