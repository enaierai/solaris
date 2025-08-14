<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php'; // CSRF ve diğer yardımcı fonksiyonlar için

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Lütfen giriş yapın.';
    echo json_encode($response);
    exit;
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $response['message'] = 'Geçersiz CSRF token.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$upload_dir = __DIR__.'/../../uploads/cover_pictures/';
$max_file_size = 5 * 1024 * 1024; // 5 MB
$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!isset($_FILES['cover_picture'])) {
    $response['message'] = 'Kapak resmi dosyası bulunamadı.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['cover_picture'];

// Hata kontrolü
if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Dosya yüklenirken bir hata oluştu: '.$file['error'];
    echo json_encode($response);
    exit;
}

// Dosya boyutu kontrolü
if ($file['size'] > $max_file_size) {
    $response['message'] = 'Dosya boyutu çok büyük. Maksimum 5 MB olmalıdır.';
    echo json_encode($response);
    exit;
}

// Dosya tipi kontrolü
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowed_types)) {
    $response['message'] = 'Sadece JPG, JPEG, PNG, GIF ve WEBP dosyalarına izin verilir.';
    echo json_encode($response);
    exit;
}

// Benzersiz dosya adı oluşturma
$new_file_name = uniqid('cover_', true).'.'.$file_ext;
$target_file = $upload_dir.$new_file_name;

// Resmi yeniden boyutlandırma ve kaydetme
// Kapak resimleri için genişliği 1200px, yüksekliği otomatik olarak ayarlayabiliriz.
// Kaliteyi de düşürebiliriz.
$image_quality = 80; // %80 kalite
$max_width = 1200; // Maksimum genişlik
$max_height = 400; // Maksimum yükseklik (kapak için daha sabit bir oran)

try {
    list($width, $height) = getimagesize($file['tmp_name']);
    $original_aspect = $width / $height;

    // Yeni boyutları hesapla
    $new_width = $max_width;
    $new_height = (int) ($max_width / $original_aspect); // Buraya (int) eklendi

    if ($new_height > $max_height) {
        $new_height = $max_height;
        $new_width = (int) ($max_height * $original_aspect); // Buraya (int) eklendi
    }

    $new_image = imagecreatetruecolor((int) $new_width, (int) $new_height); // Buraya (int) eklendi

    switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'png':
            $source = imagecreatefrompng($file['tmp_name']);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case 'gif':
            $source = imagecreatefromgif($file['tmp_name']);
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) {
                $source = imagecreatefromwebp($file['tmp_name']);
            } else {
                throw new Exception('WEBP desteği sunucuda etkin değil.');
            }
            break;
        default:
            throw new Exception('Desteklenmeyen resim formatı.');
    }

    if (!$source) {
        throw new Exception('Resim kaynağı oluşturulamadı.');
    }

    // imagecopyresampled fonksiyonuna geçirmeden önce de (int) eklendi
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, (int) $new_width, (int) $new_height, $width, $height); // Buraya (int) eklendi

    switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($new_image, $target_file, $image_quality);
            break;
        case 'png':
            imagepng($new_image, $target_file, 9); // PNG için kalite 0-9 arası
            break;
        case 'gif':
            imagegif($new_image, $target_file);
            break;
        case 'webp':
            if (function_exists('imagewebp')) {
                imagewebp($new_image, $target_file, $image_quality);
            } else {
                throw new Exception('WEBP kaydetme desteği sunucuda etkin değil.');
            }
            break;
    }

    imagedestroy($new_image);
    imagedestroy($source);

    // Veritabanını güncelle
    $stmt = $conn->prepare('UPDATE users SET cover_picture_url = ? WHERE id = ?');
    $stmt->bind_param('si', $new_file_name, $current_user_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Kapak resmi başarıyla güncellendi!';
        $response['new_cover_url'] = BASE_URL.'uploads/cover_pictures/'.$new_file_name;
    } else {
        // Veritabanı hatası durumunda yüklenen dosyayı sil
        unlink($target_file);
        $response['message'] = 'Kapak resmi veritabanına kaydedilirken hata oluştu.';
    }
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Resim işlenirken hata oluştu: '.$e->getMessage();
    // Hata durumunda geçici dosyayı sil
    if (file_exists($file['tmp_name'])) {
        unlink($file['tmp_name']);
    }
}

echo json_encode($response);
$conn->close();
