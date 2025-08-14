<?php

// includes/logic/search.logic.php
include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$search_query = trim($_GET['q'] ?? '');

$meta_title = 'Arama Sonuçları: '.htmlspecialchars($search_query).' | Solaris';
$meta_description = "Solaris'te '".htmlspecialchars($search_query)."' için kullanıcı ve gönderi arama sonuçları.";
$meta_keywords = 'Solaris arama, '.htmlspecialchars($search_query).', kullanıcı ara, gönderi ara, hashtag ara';

$found_users = [];
$found_posts = [];

// Sadece arama terimi varsa modelleri çalıştır
if (!empty($search_query)) {
    // Kullanıcıları model üzerinden ara
    $found_users = searchUsersByUsername($conn, $search_query);

    // Gönderileri model üzerinden ara
    $found_posts = searchPostsByCaption($conn, $search_query);
}
