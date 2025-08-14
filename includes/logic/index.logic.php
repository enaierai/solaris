<?php

// includes/logic/index.logic.php (NİHAİ VE TAM VERSİYON)

session_start();
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';
include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';
include_once __DIR__.'/../models/CommentModel.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// --- BURASI ÖNEMLİ ---
// Sayfa ilk açıldığında kaç gönderi yükleneceğini buradan ayarla.
// Deneme için 10 yaptın, bu harika. Normalde 20-25 arası idealdir.
define('INITIAL_POST_COUNT', 10);

$followed_ids = [];
if ($is_logged_in) {
    $followed_stmt = $conn->prepare('SELECT following_id FROM follows WHERE follower_id = ?');
    $followed_stmt->bind_param('i', $current_user_id);
    $followed_stmt->execute();
    $result = $followed_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $followed_ids[] = $row['following_id'];
    }
    $followed_stmt->close();
}

// DÜZELTME: İlk sayfa yüklemesi için 25 gönderi istiyoruz.
$initial_limit = 10;
$posts = getFeedPosts($conn, $current_user_id, $followed_ids, INITIAL_POST_COUNT, 0);

if (!empty($posts)) {
    $post_ids = array_map(fn ($p) => $p['post_id'], $posts);
    $comments_by_post = getCommentsForPosts($conn, $post_ids);

    foreach ($posts as &$post) {
        $post['comments'] = $comments_by_post[$post['post_id']] ?? [];
    }
    unset($post);
}
