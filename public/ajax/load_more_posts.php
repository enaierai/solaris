<?php

// public/ajax/load_more_posts.php (NİHAİ VE TAM VERSİYON)
require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/PostModel.php';
include_once __DIR__.'/../../includes/models/CommentModel.php';

header('Content-Type: application/json');

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$context = $_GET['context'] ?? 'index';
$filter = $_GET['filter'] ?? 'new';

$limit_per_ajax = 8;
$offset = 0;
$posts = [];

// DÜZELTME: Artık offset hesabı çok daha basit ve doğru.
// Sayfa 1 istendiğinde, ilk 8'i atlayıp 9.'dan başlarız.
// Sayfa 2 istendiğinde, ilk 16'yı atlayıp 17.'den başlarız.
if ($context === 'index') {
    $initial_load_count = 10; // index.logic.php'deki sayı
    $offset = $initial_load_count + (($page - 1) * $limit_per_ajax);
    $posts = getFeedPosts($conn, $current_user_id, [], $limit_per_ajax, $offset);
} elseif ($context === 'explore') {
    $initial_explore_count = 9;
    $offset = $initial_explore_count + (($page - 1) * $limit_per_ajax);

    // DİKKAT: 'for-you' filtresi için doğru fonksiyonu çağır
    if ($filter === 'for-you' && $is_logged_in) {
        $posts = getForYouFeed($conn, $current_user_id, $limit_per_ajax, $offset);
    } else {
        $posts = getExplorePosts($conn, $filter, $current_user_id, $limit_per_ajax, $offset);
    }
}

// Yorumları ve diğer verileri ekleme...
if (!empty($posts)) {
    $post_ids = array_map(fn ($p) => $p['post_id'], $posts);
    $comments_by_post = getCommentsForPosts($conn, $post_ids);
    foreach ($posts as &$post) {
        $post['comments'] = $comments_by_post[$post['post_id']] ?? [];
    }
    unset($post);
}

echo json_encode(['success' => true, 'posts' => $posts]);
