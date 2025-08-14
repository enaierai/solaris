<?php

// includes/logic/dashboard.logic.php

// Kullanıcı oturumu açık değilse giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini veritabanından güvenli bir şekilde çek
$stmt = $conn->prepare('SELECT username, profile_picture_url FROM users WHERE id = ?');
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Kullanıcının istatistiklerini tek bir verimli sorgu ile çek
$stats_stmt = $conn->prepare('
    SELECT
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
        (SELECT COUNT(*) FROM likes WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)) AS comment_count,
        (SELECT COUNT(*) FROM follows WHERE following_id = ?) AS follower_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ?) AS following_count,
        (SELECT MAX(created_at) FROM posts WHERE user_id = ?) AS last_post_date
');

$stats_stmt->bind_param('iiiiii', $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Değişkenleri ata
$post_count = $stats['post_count'];
$like_count = $stats['like_count'];
$comment_count = $stats['comment_count'];
$follower_count = $stats['follower_count'];
$following_count = $stats['following_count'];
$last_post_date = $stats['last_post_date'];

// Gelecekteki özellikler için placeholder
$locked_count = 0;
