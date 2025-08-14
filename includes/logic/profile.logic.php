<?php

// includes/logic/profile.logic.php

include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$profile_username = $_GET['user'] ?? '';

if (empty($profile_username)) {
    header('Location: '.BASE_URL.'public/');
    exit;
}

// Görüntülenen kullanıcıyı model üzerinden bul
// Not: Bu fonksiyonu da UserModel'a taşıyabiliriz ama şimdilik kalsın.
$stmt = $conn->prepare('SELECT id, username, email, profile_picture_url, bio, cover_picture_url FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $profile_username);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_data) {
    include_once __DIR__.'/../header.php';
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">Aradığınız kullanıcı bulunamadı.</div></div>';
    include_once __DIR__.'/../footer.php';
    exit;
}

$profile_user_id = $user_data['id'];
$is_owner = ($is_logged_in && $current_user_id == $profile_user_id);

// Kapak fotoğrafı kontrolü
$show_cover_photo = false;
$cover_picture_url = '';
if (!empty($user_data['cover_picture_url']) && file_exists(__DIR__.'/../../uploads/cover_pictures/'.$user_data['cover_picture_url'])) {
    $show_cover_photo = true;
    $cover_picture_url = BASE_URL.'uploads/cover_pictures/'.htmlspecialchars($user_data['cover_picture_url']);
}

// İstatistikleri ve takip durumunu modellerden çek
$follower_count = getFollowerCount($conn, $profile_user_id);
$following_count = getFollowingCount($conn, $profile_user_id);
$is_following = $is_logged_in && !$is_owner ? isFollowing($conn, $current_user_id, $profile_user_id) : false;

// === YENİ BÖLÜM: ENGELLEME DURUMUNU KONTROL ET ===
$is_blocked = false;
if ($is_logged_in && !$is_owner) {
    // checkBlockStatus fonksiyonu iki yönlü kontrol yapar (sen onu veya o seni engelledi mi?)
    $is_blocked = checkBlockStatus($conn, $current_user_id, $profile_user_id);
}

// Profildeki gönderileri modelden çek
$profile_posts = getPostsByUserId($conn, $profile_user_id);

// === YENİ BÖLÜM: KAYDEDİLEN GÖNDERİLERİ ÇEK ===
$saved_posts = [];
// Sadece profil sahibi kendi profiline bakıyorsa kaydedilenleri çek
if ($is_owner) {
    $saved_posts = getSavedPostsByUser($conn, $current_user_id);
}
