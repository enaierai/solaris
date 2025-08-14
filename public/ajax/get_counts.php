<?php

// Bu dosya, okunmamış bildirim ve mesaj sayısını JSON olarak döndürür.
// CSRF koruması için session başlatılır.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Güvenli dosya yolları
// 'ajax' klasöründen çıkıp ana 'solaris' klasöründeki 'includes' klasörüne erişiyoruz.
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';

header('Content-Type: application/json');

$response = [
    'notifications' => 0,
    'messages' => 0,
    'error' => null,
];

try {
    // Veritabanı bağlantısının başarılı olup olmadığını kontrol et
    if (!isset($conn) || $conn === null) {
        throw new Exception('Veritabanı bağlantısı kurulamadı.');
    }

    // Kullanıcı oturumunun açık olup olmadığını kontrol et
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Kullanıcı oturumu bulunamadı.');
    }

    $current_user_id = $_SESSION['user_id'];

    // Okunmamış genel bildirim sayısını çek
    $stmt_unread_notifications = $conn->prepare('SELECT COUNT(id) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    if ($stmt_unread_notifications) {
        $stmt_unread_notifications->bind_param('i', $current_user_id);
        $stmt_unread_notifications->execute();
        $result_notifications = $stmt_unread_notifications->get_result();
        $row_notifications = $result_notifications->fetch_assoc();
        // Eğer sonuç gelmezse varsayılan olarak 0 atarız
        $response['notifications'] = $row_notifications['unread_count'] ?? 0;
        $stmt_unread_notifications->close();
    } else {
        throw new Exception('Bildirim sorgusu hazırlanamadı: '.$conn->error);
    }

    // Okunmamış mesaj sayısını çek
    if (function_exists('get_unread_message_count')) {
        $response['messages'] = get_unread_message_count($conn, $current_user_id);
    } else {
        throw new Exception('get_unread_message_count fonksiyonu bulunamadı.');
    }
} catch (Exception $e) {
    // Hata oluştuğunda hata mesajını yanıt dizisine ekle
    $response['error'] = $e->getMessage();
} finally {
    // Bağlantıyı sadece tanımlı ve açık ise kapat
    if (isset($conn) && $conn !== null) {
        $conn->close();
    }
}

echo json_encode($response);
exit;
