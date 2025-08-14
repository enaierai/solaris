<?php

// includes/logic/header.logic.php

// Bu dosya, sayfa mantık dosyaları (örn: index.logic.php) tarafından
// session_start() çağrıldıktan sonra dahil edilmelidir.

// Temel dosyaları dahil et
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php'; // get_unread_message_count burada

// Oturum durumunu ve kullanıcı bilgilerini değişkenlere ata
$is_logged_in = isset($_SESSION['user_id']);
$current_username = $is_logged_in ? $_SESSION['username'] : 'Misafir';

// --- OKUNMAMIŞ MESAJ VE GENEL BİLDİRİM SAYILARINI ÇEKİYORUZ ---
$unread_notifications_count = 0;
$unread_messages_count = 0;
if ($is_logged_in) {
    // Genel bildirim sayısını çek
    $stmt_unread = $conn->prepare('SELECT COUNT(id) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt_unread->bind_param('i', $_SESSION['user_id']);
    $stmt_unread->execute();
    $result_unread = $stmt_unread->get_result();
    $row_unread = $result_unread->fetch_assoc();
    $unread_notifications_count = $row_unread['unread_count'];
    $stmt_unread->close();

    // Okunmamış mesaj sayısını (konuşma bazında) çek
    $unread_messages_count = get_unread_message_count($conn, $_SESSION['user_id']);
}
// --- KOD SONU ---

// Meta etiketleri için varsayılan değerler
$meta_title = isset($meta_title) ? $meta_title : 'Solaris | Paylaş, Keşfet, Bağlan';
$meta_description = isset($meta_description) ? $meta_description : 'Solaris, yaratıcıların içeriklerini paylaşabileceği, topluluklarla etkileşim kurabileceği ve keşfedebileceği yeni nesil sosyal medya platformu.';
$meta_keywords = isset($meta_keywords) ? $meta_keywords : 'Solaris, sosyal medya, içerik paylaşımı, keşfet, topluluk, fotoğraf, video';
$meta_author = isset($meta_author) ? $meta_author : 'Solaris Ekibi';
$og_image = isset($og_image) ? $og_image : BASE_URL.'uploads/solar_logo_og.png';
$og_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
