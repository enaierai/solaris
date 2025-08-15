<?php

// public/ajax/report_content.php
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/PostModel.php';
include_once __DIR__.'/../../includes/models/CommentModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$reporter_id = $_SESSION['user_id'];
$content_type = $_POST['type'] ?? '';
$content_id = intval($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($content_id <= 0 || empty($reason) || !in_array($content_type, ['post', 'comment'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler.']);
    exit;
}

$success = false;
if ($content_type === 'post') {
    $success = reportPost($conn, $content_id, $reporter_id, $reason);
} elseif ($content_type === 'comment') {
    $success = reportComment($conn, $content_id, $reporter_id, $reason);
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'İçerik başarıyla şikayet edildi. Geri bildiriminiz için teşekkür ederiz.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Şikayet kaydedilirken bir hata oluştu.']);
}

$conn->close();
