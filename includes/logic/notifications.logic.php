<?php

// includes/logic/notifications.logic.php (NİHAİ VE TAM VERSİYON)

session_start();
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';
include_once __DIR__.'/../models/NotificationModel.php'; // Modelimizi dahil ediyoruz

if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// 1. Kullanıcının tüm bildirimlerini tek bir fonksiyonla modelden çek
$notifications = getNotificationsForUser($conn, $current_user_id);

// 2. Sayfa açıldığında tüm okunmamış bildirimleri "okundu" olarak işaretle
// Bu işlem, bildirim sayacının sıfırlanmasını sağlar.
$update_stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
if ($update_stmt) {
    $update_stmt->bind_param('i', $current_user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Meta etiketlerini ayarla
$meta_title = 'Bildirimler | Solaris';
