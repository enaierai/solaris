<?php

// public/ajax/unfollow.php
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/UserModel.php'; // UserModel'i dahil et

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

// Takibi bırakma işlemini model üzerinden yap
if (unfollowUser($conn, $follower_id, $following_id)) {
    // Not: Takibi bırakınca bildirim silme gibi bir mantık eklenebilir.
    // Şimdilik sadece işlemi yapıyoruz.

    echo json_encode(['success' => true, 'message' => 'Takip bırakıldı.', 'action' => 'unfollowed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Takibi bırakma işlemi başarısız oldu.']);
}

$conn->close();
