<?php

session_start();
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php'; // CSRF fonksiyonları dahil

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF doğrulama başarısız.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$post_id = intval($input['post_id'] ?? 0);
$new_caption = trim($input['new_caption'] ?? '');

if ($post_id <= 0 || $new_caption === '') {
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz veriler.']);
    exit;
}

// Önce post sahibini al
$stmt = $conn->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Gönderi bulunamadı.']);
    exit;
}

if ($post['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Bu gönderiyi düzenleme yetkiniz yok.']);
    exit;
}

// Güncelleme sorgusu
$stmt_update = $conn->prepare('UPDATE posts SET caption = ? WHERE id = ?');
$stmt_update->bind_param('si', $new_caption, $post_id);

if ($stmt_update->execute()) {
    // Hashtag linkleme için helpers.php içinde fonksiyon varsa kullanabilirsin, yoksa basit regex:
    $updated_caption_html = htmlspecialchars($new_caption);
    $updated_caption_html = preg_replace('/(#\w+)/', '<a href="'.BASE_URL.'public/pages/search.php?q=$1" class="text-decoration-none text-primary-blue">$1</a>', $updated_caption_html);

    echo json_encode(['success' => true, 'message' => 'Açıklama güncellendi.', 'updated_caption_html' => $updated_caption_html]);
} else {
    echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız: '.$stmt_update->error]);
}
$stmt_update->close();
exit;
