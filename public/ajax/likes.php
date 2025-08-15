<?php

// public/ajax/likes.php (NİHAİ VE TAM VERSİYON)
require_once __DIR__.'/../../includes/init.php';
// YENİ: Artık tüm işlemleri modeller üzerinden yapacağız.
include_once __DIR__.'/../../includes/models/PostModel.php';
include_once __DIR__.'/../../includes/models/UserModel.php';
include_once __DIR__.'/../../includes/models/NotificationModel.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id']; // Beğeniyi yapan kişinin ID'si

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz gönderi ID.']);
    exit;
}

try {
    $post_owner_id = getPostOwnerId($conn, $post_id);

    // DÜZELTME: Doğru değişkeni ($user_id) kullanarak engelleme kontrolü yap
    if ($post_owner_id && checkBlockStatus($conn, $user_id, $post_owner_id)) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyla etkileşime giremezsiniz.']);
        exit;
    }

    $liked_before = isPostLikedByUser($conn, $user_id, $post_id);
    $action_taken = '';

    if ($liked_before) {
        if (unlikePost($conn, $user_id, $post_id)) {
            $action_taken = 'unliked';
            // İlgili beğenme bildirimini sil
            deleteNotification($conn, $post_owner_id, $user_id, 'like', $post_id);
        }
    } else {
        if (likePost($conn, $user_id, $post_id)) {
            $action_taken = 'liked';
            if ($user_id != $post_owner_id) {
                $notification_text = htmlspecialchars($_SESSION['username']).' gönderini beğendi.';
                createNotification($conn, $post_owner_id, $user_id, 'like', $notification_text, $post_id);
            }
        }
    }

    $new_likes_count = getLikeCount($conn, $post_id);
    echo json_encode(['success' => true, 'new_likes' => $new_likes_count, 'action' => $action_taken]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu: '.$e->getMessage()]);
}

$conn->close();
