<?php

// includes/logic/profile.logic.php (YENİ HALİ)

// FAZ 2 NOTU: Bu Model'ler init.php'ye taşınarak bu dosya daha da temizlenebilir.
include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';

// DEĞİŞTİRİLDİ: Kullanıcı adı artık /profile/username şeklindeki URL'den geliyor.
// Bu $parts değişkeni, ana index.php dosyasında tanımlanmıştı.
$profile_username = $_GET['user'] ?? '';

if (empty($profile_username)) {
    // DEĞİŞTİRİLDİ: Yönlendirme adresi yeni yapıya göre düzeltildi.
    header('Location: '.BASE_URL); // Ana sayfaya yönlendir
    exit;
}

// Görüntülenen kullanıcıyı veritabanından çekiyoruz
$stmt = $conn->prepare('SELECT id, username, email, profile_picture_url, bio, cover_picture_url FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $profile_username);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- EN ÖNEMLİ DEĞİŞİKLİK ---
// Eğer kullanıcı bulunamazsa, HTML basmak yerine sadece $user_data değişkenini null bırakıyoruz.
// Sayfanın kendisi (profile.php) bu durumu kontrol edip hatayı gösterecek.
if ($user_data) {
    $profile_user_id = $user_data['id'];
    $is_owner = ($is_logged_in && $current_user_id == $profile_user_id);

    // Kapak fotoğrafı kontrolü
    $show_cover_photo = false;
    $cover_picture_url = '';
    if (!empty($user_data['cover_picture_url']) && file_exists(__DIR__.'/../../uploads/cover_pictures/'.$user_data['cover_picture_url'])) {
        $show_cover_photo = true;
        $cover_picture_url = BASE_URL.'uploads/cover_pictures/'.htmlspecialchars($user_data['cover_picture_url']);
    }

    // İstatistikler ve takip durumu
    $follower_count = getFollowerCount($conn, $profile_user_id);
    $following_count = getFollowingCount($conn, $profile_user_id);
    $is_following = $is_logged_in && !$is_owner ? isFollowing($conn, $current_user_id, $profile_user_id) : false;

    // Engelleme durumu
    $is_blocked = false;
    if ($is_logged_in && !$is_owner) {
        $is_blocked = checkBlockStatus($conn, $current_user_id, $profile_user_id);
    }

    // Gönderiler
    $profile_posts = getPostsByUserId($conn, $profile_user_id);

    // Kaydedilen gönderiler
    $saved_posts = [];
    if ($is_owner) {
        $saved_posts = getSavedPostsByUser($conn, $current_user_id);
    }
}
