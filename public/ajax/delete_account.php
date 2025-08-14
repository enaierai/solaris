<?php

// public/ajax/delete_account.php

session_start();
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen şifrenizi girin.']);
    exit;
}

// Kullanıcının şifresini doğrula
$stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Girdiğiniz şifre yanlış.']);
    exit;
}

// Şifre doğruysa, silme işlemine başla (TRANSACTION KULLANARAK)
$conn->begin_transaction();

try {
    // Kullanıcının gönderi ID'lerini al
    $post_ids_stmt = $conn->prepare('SELECT id FROM posts WHERE user_id = ?');
    $post_ids_stmt->bind_param('i', $user_id);
    $post_ids_stmt->execute();
    $post_ids_result = $post_ids_stmt->get_result();
    $post_ids = [];
    while ($row = $post_ids_result->fetch_assoc()) {
        $post_ids[] = $row['id'];
    }
    $post_ids_stmt->close();

    if (!empty($post_ids)) {
        $in_clause = implode(',', array_fill(0, count($post_ids), '?'));
        $types = str_repeat('i', count($post_ids));

        // Gönderi medyalarını ve dosyalarını sil
        $media_stmt = $conn->prepare("SELECT image_url FROM post_media WHERE post_id IN ($in_clause)");
        $media_stmt->bind_param($types, ...$post_ids);
        $media_stmt->execute();
        $media_result = $media_stmt->get_result();
        while ($media = $media_result->fetch_assoc()) {
            $file_path = __DIR__.'/../../uploads/posts/'.$media['image_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $media_stmt->close();

        $conn->query("DELETE FROM post_media WHERE post_id IN ($in_clause)");
        $conn->query("DELETE FROM comments WHERE post_id IN ($in_clause)");
        $conn->query("DELETE FROM likes WHERE post_id IN ($in_clause)");
        $conn->query("DELETE FROM notifications WHERE post_id IN ($in_clause)");
    }

    // Kullanıcının gönderilerini sil
    $conn->query("DELETE FROM posts WHERE user_id = $user_id");
    // Diğer tablolardan kullanıcıyla ilgili verileri sil
    $conn->query("DELETE FROM comments WHERE user_id = $user_id");
    $conn->query("DELETE FROM likes WHERE user_id = $user_id");
    $conn->query("DELETE FROM follows WHERE follower_id = $user_id OR following_id = $user_id");
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id OR from_user_id = $user_id");

    // Son olarak kullanıcıyı sil
    $conn->query("DELETE FROM users WHERE id = $user_id");

    $conn->commit();

    // Oturumu sonlandır
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Hesabınız kalıcı olarak silindi.', 'redirect_url' => BASE_URL.'public/']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Hesap silinirken bir hata oluştu: '.$e->getMessage()]);
}

$conn->close();
