<?php

// public/ajax/get_likers.php (NİHAİ VE TAM VERSİYON)

session_start();
include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
// YENİ: Artık doğrudan UserModel'ı kullanıyoruz.
include_once __DIR__.'/../../includes/models/UserModel.php';

header('Content-Type: application/json');

$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz gönderi.']);
    exit;
}

// Veriyi doğrudan modelden çekiyoruz.
$likers = getLikersForPost($conn, $post_id);

echo json_encode(['success' => true, 'likers' => $likers]);

$conn->close();
