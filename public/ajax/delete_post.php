<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php'; // CSRF fonksiyonları burada olmalı

header('Content-Type: application/json');

// Sadece POST ve geçerli CSRF kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek veya CSRF doğrulama hatası. Sayfayı yenileyip tekrar deneyin.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bu işlemi yapmak için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (empty($post_id)) {
    echo json_encode(['success' => false, 'message' => 'Silinecek gönderi ID\'si bulunamadı.']);
    exit;
}

// Gönderi ve sahibi kontrolü
$stmt = $conn->prepare('SELECT id FROM posts WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Bu gönderi bulunamadı veya silme yetkiniz yok.']);
    $stmt->close();
    exit;
}
$stmt->close();

// Gönderiye ait medya dosyalarını çek
$media_stmt = $conn->prepare('SELECT image_url FROM post_media WHERE post_id = ?');
$media_stmt->bind_param('i', $post_id);
$media_stmt->execute();
$media_result = $media_stmt->get_result();

$images_to_delete = [];
while ($row = $media_result->fetch_assoc()) {
    $images_to_delete[] = $row['image_url'];
}
$media_stmt->close();

$conn->begin_transaction();

try {
    // Yorumları sil
    $delete_comments_stmt = $conn->prepare('DELETE FROM comments WHERE post_id = ?');
    $delete_comments_stmt->bind_param('i', $post_id);
    $delete_comments_stmt->execute();
    $delete_comments_stmt->close();

    // Beğenileri sil
    $delete_likes_stmt = $conn->prepare('DELETE FROM likes WHERE post_id = ?');
    $delete_likes_stmt->bind_param('i', $post_id);
    $delete_likes_stmt->execute();
    $delete_likes_stmt->close();

    // Bildirimleri sil
    $delete_notifications_stmt = $conn->prepare('DELETE FROM notifications WHERE post_id = ?');
    $delete_notifications_stmt->bind_param('i', $post_id);
    $delete_notifications_stmt->execute();
    $delete_notifications_stmt->close();

    // post_media kayıtlarını sil
    $delete_media_stmt = $conn->prepare('DELETE FROM post_media WHERE post_id = ?');
    $delete_media_stmt->bind_param('i', $post_id);
    $delete_media_stmt->execute();
    $delete_media_stmt->close();

    // Gönderiyi sil
    $delete_post_stmt = $conn->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $delete_post_stmt->bind_param('ii', $post_id, $user_id);
    $delete_post_stmt->execute();
    $delete_post_stmt->close();

    // Fiziksel dosyaları sil
    foreach ($images_to_delete as $image_file) {
        $file_path = __DIR__.'/../../uploads/posts/'.$image_file;
        if (file_exists($file_path) && $image_file != 'default_post_image.png') {
            unlink($file_path);
        }
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Gönderi başarıyla silindi.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gönderi silinirken hata oluştu: '.$e->getMessage()]);
}

exit;
