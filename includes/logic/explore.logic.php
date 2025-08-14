<?php

// includes/logic/explore.logic.php (NİHAİ VE TAM VERSİYON)

session_start();
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';
include_once __DIR__.'/../models/PostModel.php';

$meta_title = 'Keşfet | Solaris';
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Filtreleme mantığı
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'for-you'; // Varsayılan olarak 'Senin İçin' gelsin
$filters = [
    'for-you' => 'Senin İçin', // YENİ FİLTRE
    'new' => 'Yeni Gönderiler',
    'popular' => 'Popüler',
    'following' => 'Takip Ettiklerim',
    'video' => 'Videolar',
];

if ($filter === 'for-you' && $is_logged_in) {
    $posts = getForYouFeed($conn, $current_user_id, 9, 0);
} else {
    // 'for-you' seçiliyken giriş yapılmamışsa 'new' filtresine yönlendir
    if ($filter === 'for-you') {
        $filter = 'new';
    }
    $posts = getExplorePosts($conn, $filter, $current_user_id, 9, 0);
}
