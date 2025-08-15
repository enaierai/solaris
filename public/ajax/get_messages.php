<?php

// public/ajax/get_messages.php (NİHAİ VE TAM VERSİYON)
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/PostModel.php'; // getPostPreviewById için gerekli
include_once __DIR__.'/../../includes/models/MessageModel.php'; // Yeni modelimiz

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş gerekli.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;

if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz konuşma ID.']);
    exit;
}

// === DÜZELTME: TÜM ZOR İŞİ MODELE YAPTIRIYORUZ ===
// Bu fonksiyon hem mesajları, hem partner_info'yu, her şeyi getirir.
$response = getConversationDetails($conn, $conversation_id, $current_user_id);

echo json_encode($response);
$conn->close();
