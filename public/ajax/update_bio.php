<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Yol düzeltmesi yapıldı
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php'; // CSRF fonksiyonları için

header('Content-Type: application/json');

// Sadece POST isteklerini ve geçerli CSRF token'ı olan istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek veya CSRF doğrulama hatası. Sayfayı yenileyip tekrar deneyin.']);
    exit;
}

// Kullanıcı giriş yapmamışsa izin verme
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$new_bio = $_POST['bio'] ?? '';

// Biyografi metnini temizle ve uzunluğunu kontrol et
$new_bio = trim($new_bio);
if (mb_strlen($new_bio, 'UTF-8') > 500) {
    echo json_encode(['success' => false, 'message' => 'Biyografi en fazla 500 karakter olabilir.']);
    exit;
}

// Veritabanını güncelle
try {
    $stmt = $conn->prepare('UPDATE users SET bio = ? WHERE id = ?');
    $stmt->bind_param('si', $new_bio, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Biyografi başarıyla güncellendi.', 'new_bio' => htmlspecialchars($new_bio)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Biyografi güncellenirken bir hata oluştu: '.$stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu: '.$e->getMessage()]);
}

$conn->close();
