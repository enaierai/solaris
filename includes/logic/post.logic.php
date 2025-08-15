<?php

// includes/logic/post.logic.php (YENİ HALİ)

// FAZ 2 NOTU: Bu Model'ler init.php'ye taşınarak bu dosya daha da temizlenebilir.
include_once __DIR__.'/../models/UserModel.php';
include_once __DIR__.'/../models/PostModel.php';
include_once __DIR__.'/../models/CommentModel.php';

// DEĞİŞTİRİLDİ: Post ID'si artık /post/123 şeklindeki URL'den alınıyor.
// Bu $parts değişkeni, ana index.php dosyasında tanımlanmıştı.
$post_id = isset($parts[1]) ? (int) $parts[1] : 0;

if ($post_id <= 0) {
    // DEĞİŞTİRİLDİ: Yönlendirme adresi yeni yapıya göre düzeltildi.
    header('Location: '.BASE_URL.'home');
    exit;
}

// 1. Gönderi detaylarını tek bir fonksiyonla modelden çek
$post_data = getPostDetailsById($conn, $post_id, $current_user_id);

// --- YENİ KONTROL ---
// Eğer gönderi bulunamazsa, HTML basmak yerine sadece $post_data değişkenini null bırakıyoruz.
// Sayfanın kendisi (post.php) bu durumu kontrol edip hatayı gösterecek.
if ($post_data) {
    // 2. Gönderinin medya dosyalarını modelden çek
    $post_media = getMediaForPost($conn, $post_id);

    // 3. Gönderinin yorumlarını modelden çek
    $post_comments = getCommentsForPost($conn, $post_id);

    // Sayfa sahibi kontrolü
    $is_owner = ($is_logged_in && $current_user_id == $post_data['user_id']);

    // Meta etiketlerini ayarla (Bu bilgiler header.logic.php tarafından kullanılabilir)
    $meta_title = htmlspecialchars($post_data['username']).': "'.htmlspecialchars(substr($post_data['caption'], 0, 50)).'..." | Solaris';
    $meta_description = htmlspecialchars($post_data['caption']);
    if (!empty($post_media)) {
        $og_image = BASE_URL.'uploads/posts/'.htmlspecialchars($post_media[0]['image_url']);
    }
}
