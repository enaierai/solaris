<?php

// public/ajax/delete_comment.php
require_once __DIR__.'/../../includes/init.php';

header('Content-Type: application/json');

// Gerekli kontroller
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek veya CSRF doğrulama hatası.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bu işlemi yapmak için giriş yapmalısınız.']);
    exit;
}

// Frontend'den gelen yorum ID'sini al
$comment_id = $_POST['comment_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (empty($comment_id)) {
    echo json_encode(['success' => false, 'message' => 'Silinecek yorum ID\'si bulunamadı.']);
    exit;
}

// 1. Silinmek istenen yorumun verilerini çek (sahibi ve ait olduğu postun sahibi)
$stmt = $conn->prepare('SELECT user_id, post_id FROM comments WHERE id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: '.$conn->error]);
    exit;
}
$stmt->bind_param('i', $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment_data = $result->fetch_assoc();
$stmt->close();

if (!$comment_data) {
    echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
    exit;
}

$comment_owner_id = $comment_data['user_id'];
$post_id_of_comment = $comment_data['post_id'];

// 2. Yorumun ait olduğu postun sahibini bul
$stmt_post_owner = $conn->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt_post_owner->bind_param('i', $post_id_of_comment);
$stmt_post_owner->execute();
$result_post_owner = $stmt_post_owner->get_result();
$post_owner_data = $result_post_owner->fetch_assoc();
$post_owner_id = $post_owner_data['user_id'];
$stmt_post_owner->close();

// 3. Yetki kontrolü yap: Yorumun sahibi misin, yoksa postun sahibi misin?
if ($user_id != $comment_owner_id && $user_id != $post_owner_id) {
    echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
    exit;
}

// 4. Yetki varsa yorumu sil
$stmt_delete = $conn->prepare('DELETE FROM comments WHERE id = ?');
$stmt_delete->bind_param('i', $comment_id);

if ($stmt_delete->execute()) {
    echo json_encode(['success' => true, 'message' => 'Yorum başarıyla silindi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Yorum silinirken bir hata oluştu: '.$stmt_delete->error]);
}
$stmt_delete->close();

exit;
