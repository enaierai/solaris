<?php

// public/ajax/remove_follower.php

session_start();
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

// Çıkarılacak takipçinin ID'si
$follower_id_to_remove = isset($_POST['follower_id']) ? (int) $_POST['follower_id'] : 0;
// Bizim (yani profil sahibinin) ID'si
$profile_owner_id = $_SESSION['user_id'];

if ($follower_id_to_remove <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID\'si.']);
    exit;
}

// Veritabanından ilgili takip kaydını sil
$stmt = $conn->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
if ($stmt) {
    $stmt->bind_param('ii', $follower_id_to_remove, $profile_owner_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Takipçi başarıyla çıkarıldı.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu.']);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı sorgusu hazırlanamadı.']);
}

$conn->close();
