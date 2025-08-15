<?php

// includes/logic/index.logic.php (YENİ HALİ)

// SİLİNDİ: init.php'de zaten çağrıldığı için tüm eski require/include'lar kaldırıldı.
// FAZ 2 NOTU: Bu Model'ler init.php'ye taşınarak bu dosya daha da temizlenebilir.
include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';
include_once __DIR__.'/../models/CommentModel.php';


define('INITIAL_POST_COUNT', 10);

$followed_ids = [];
if ($is_logged_in && $current_user_id) {
    $followed_stmt = $conn->prepare('SELECT following_id FROM follows WHERE follower_id = ?');
    $followed_stmt->bind_param('i', $current_user_id);
    $followed_stmt->execute();
    $result = $followed_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $followed_ids[] = $row['following_id'];
    }
    $followed_stmt->close();
}

// Ana sayfayı besleyecek olan gönderileri çekiyoruz.
$posts = getFeedPosts($conn, $current_user_id, $followed_ids, INITIAL_POST_COUNT, 0);

// Çekilen gönderilerin yorumlarını da alıp onlara ekliyoruz.
if (!empty($posts)) {
    $post_ids = array_map(fn ($p) => $p['post_id'], $posts);
    $comments_by_post = getCommentsForPosts($conn, $post_ids);

    foreach ($posts as &$post) {
        $post['comments'] = $comments_by_post[$post['post_id']] ?? [];
    }
    unset($post);
}
