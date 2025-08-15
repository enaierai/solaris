<?php

// public/ajax/mark_as_read.php
require_once __DIR__.'/../../includes/init.php';
header('Content-Type: application/json');

// 1. Güvenlik: Kullanıcı oturumu açık mı?
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Yetkisiz erişim kodu
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// 2. Güvenli Yöntem: Kullanıcı ID'sini POST'tan değil, her zaman SESSION'dan al.
$user_id = $_SESSION['user_id'];

// 3. Veritabanı İşlemi: Tüm okunmamış bildirimleri okundu olarak işaretle (MySQLi ile)
$stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');

if ($stmt) {
    $stmt->bind_param('i', $user_id);

    if ($stmt->execute()) {
        // İşlem başarılıysa olumlu yanıt dön
        echo json_encode(['success' => true, 'message' => 'Bildirimler okundu olarak işaretlendi.']);
    } else {
        // Sunucu hatası durumunda
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Veritabanı işlemi sırasında bir hata oluştu.']);
    }
    $stmt->close();
} else {
    // Sorgu hazırlanamazsa
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı sorgusu hazırlanamadı.']);
}

$conn->close();
