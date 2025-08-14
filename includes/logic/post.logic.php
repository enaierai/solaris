<?php

// includes/logic/post.logic.php (NİHAİ VE TAM VERSİYON)

include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';
include_once __DIR__.'/../models/CommentModel.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($post_id <= 0) {
    header('Location: '.BASE_URL.'public/index.php');
    exit;
}

// 1. Gönderi detaylarını tek bir fonksiyonla modelden çek
$post_data = getPostDetailsById($conn, $post_id, $current_user_id);

if (!$post_data) {
    // Gönderi bulunamadıysa bir hata sayfası gösterilebilir.
    // Şimdilik ana sayfaya yönlendirelim.
    header('Location: '.BASE_URL.'public/index.php');
    exit;
}

// 2. Gönderinin medya dosyalarını modelden çek
$post_media = getMediaForPost($conn, $post_id);

// 3. Gönderinin yorumlarını modelden çek
$post_comments = getCommentsForPost($conn, $post_id);

// Sayfa sahibi kontrolü
$is_owner = ($is_logged_in && $current_user_id == $post_data['user_id']);

// Meta etiketlerini ayarla
$meta_title = htmlspecialchars($post_data['username']).': "'.htmlspecialchars(substr($post_data['caption'], 0, 50)).'..." | Solaris';
$meta_description = htmlspecialchars($post_data['caption']);
if (!empty($post_media)) {
    $og_image = BASE_URL.'uploads/posts/'.htmlspecialchars($post_media[0]['image_url']);
}
