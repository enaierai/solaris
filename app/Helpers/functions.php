<?php

/**
 * Gönderi açıklamasındaki hashtagleri tıklanabilir bağlantılara dönüştürür.
 *
 * @param string $caption gönderi açıklaması metni
 *
 * @return string tıklanabilir hashtagler içeren HTML
 */
function linkify($caption)
{
    $pattern = '/#(\p{L}|\p{N})+/u';

    $caption = preg_replace_callback($pattern, function ($matches) {
        $hashtag = $matches[0];
        // BASE_URL kullanımını kontrol et
        $base_url = defined('BASE_URL') ? BASE_URL : '/';

        return '<a href="'.$base_url.'public/pages/search.php?q='.urlencode($hashtag).'" class="hashtag-link text-primary-blue text-decoration-none">'.htmlspecialchars($hashtag).'</a>';
    }, $caption);

    return $caption;
}

/**
 * Verilen zamandan ne kadar süre geçtiğini döndürür.
 * Belirli bir süreden sonra (örn: 24 saat), tam tarihi ve saati gösterir.
 *
 * @param int|string $timestamp geçmiş Unix zaman damgası veya tarih stringi
 *
 * @return string süre veya tarih stringi
 */
function time_ago($timestamp)
{
    if (!is_numeric($timestamp)) {
        $timestamp = strtotime($timestamp);
    }

    if ($timestamp === false || $timestamp < 0) {
        return 'geçersiz tarih';
    }

    $current_time = time();
    $diff = $current_time - $timestamp;

    if ($diff < 60) {
        return $diff.' saniye önce';
    }

    if ($diff < 3600) {
        $minutes = round($diff / 60);

        return $minutes.' dakika önce';
    }

    if ($diff < 86400) {
        $hours = round($diff / 3600);

        return $hours.' saat önce';
    }

    if ($diff < 604800) {
        $days = round($diff / 86400);

        return $days.' gün önce';
    }

    // Yaklaşık ay ve yıl hesaplamaları için tam saniye değerleri
    $month_seconds = 2629743;
    $year_seconds = 31556926;

    if ($diff < $month_seconds) {
        $weeks = round($diff / 604800);

        return $weeks.' hafta önce';
    }

    if ($diff < $year_seconds) {
        $months = round($diff / $month_seconds);

        return $months.' ay önce';
    }

    return date('d M Y H:i', $timestamp);
}

/**
 * CSRF token oluşturur ve oturumda saklar.
 *
 * @return string oluşturulan CSRF token
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Gönderilen CSRF token'ı doğrular.
 *
 * @param string $token doğrulanacak token
 *
 * @return bool token geçerliyse true, değilse false
 */
function verify_csrf_token($token)
{
    if (empty($_SESSION['csrf_token']) || empty($token) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }

    return true;
}

/**
 * Kullanıcı için okunmamış mesaj sayısını döndürür.
 *
 * @param mysqli $conn    veritabanı bağlantı nesnesi
 * @param int    $user_id mesajlarını sayılacak kullanıcının ID'si
 *
 * @return int okunmamış mesaj sayısı
 */
function get_unread_message_count($conn, $user_id)
{
    try {
        $stmt = $conn->prepare('
            SELECT COUNT(DISTINCT m.conversation_id) as unread_count
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE m.is_read = 0
            AND m.sender_id != ?
            AND (c.user_one_id = ? OR c.user_two_id = ?)
        ');
        if (!$stmt) {
            error_log('Mesaj sorgusu hazırlanamadı: '.$conn->error);

            return 0;
        }
        $stmt->bind_param('iii', $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count_row = $result->fetch_assoc();
        $stmt->close();

        return $count_row['unread_count'] ?? 0;
    } catch (Exception $e) {
        error_log('Mesaj sayısı alınırken bir hata oluştu: '.$e->getMessage());

        return 0;
    }
}

/**
 * Kullanıcının profil resmini veya varsayılan avatarını döndürür.
 *
 * @param string  $username            kullanıcının adı (varsayılan avatar için)
 * @param ?string $profile_picture_url veritabanındaki profil resmi dosya adı
 *
 * @return string tam avatar URL'si
 */
function getUserAvatar(string $username, ?string $profile_picture_url): string
{
    // Eğer kullanıcıya ait bir profil resmi varsa (ve bu default resim değilse), onu kullan.
    if (!empty($profile_picture_url) && $profile_picture_url !== 'default_profile.png') {
        // Güvenli `serve.php` script'ini çağırıyoruz.
        return BASE_URL.'serve.php?path=profile_pictures/'.htmlspecialchars($profile_picture_url);
    } else {
        // --- DÜZELTME ---
        // Profil resmi yoksa, ui-avatars yerine bizim kendi default resmimizi göster.
        return BASE_URL.'serve.php?path=profile_pictures/default_profile.png';
    }
}
