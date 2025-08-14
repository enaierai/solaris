<?php

// public/ajax/follow.php
session_start();
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';
include_once __DIR__.'/../../includes/models/UserModel.php';
include_once __DIR__.'/../../includes/models/NotificationModel.php'; // YENİ

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$follower_id = $_SESSION['user_id'];
$following_id = $_POST['following_id'] ?? 0;

// Zaten takip edip etmediğini model üzerinden kontrol et
if (isFollowing($conn, $follower_id, $following_id)) {
    echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyı zaten takip ediyorsunuz.']);
    exit;
}

// Takip etme işlemini model üzerinden yap
if (followUser($conn, $follower_id, $following_id)) {
    // BİLDİRİM OLUŞTURMA
    $notification_text = ($_SESSION['username'] ?? 'Bir kullanıcı').' sizi takip etmeye başladı.';
    createNotification($conn, $following_id, $follower_id, 'follow', $notification_text); // Fonksiyon adı ve parametre adı güncellendi

    echo json_encode(['success' => true, 'message' => 'Takip edildi.', 'action' => 'followed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Takip işlemi başarısız oldu.']);
}

$conn->close();
