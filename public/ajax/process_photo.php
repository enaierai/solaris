<?php

// public/ajax/process_photo.php (NİHAİ VE TAM VERSİYON)

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php';
// YENİ: Sadece PostModel'i dahil etmemiz yeterli olacak.
include_once __DIR__.'/../../includes/models/PostModel.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Bir hata oluştu.'];

// ... (CSRF ve Session kontrol kodları aynı kalacak) ...
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    $response['message'] = 'Geçersiz istek.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$caption = trim($_POST['caption'] ?? '');
$uploaded_files = [];

// === Dosya Yükleme Mantığı (Bu kısım AJAX dosyasında kalmalı) ===
if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
    $file_count = count($_FILES['media']['name']);

    // Yol düzeltmesi yapıldı.
    $upload_dir = __DIR__.'/../../uploads/posts/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $response['message'] = 'Yükleme klasörü oluşturulamadı. Sunucu izinlerini kontrol edin.';
            echo json_encode($response);
            exit;
        }
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'webp'];
    $max_file_size = 50 * 1024 * 1024; // Çoklu dosyalar için 50 MB limit

    for ($i = 0; $i < $file_count; ++$i) {
        if ($_FILES['media']['error'][$i] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION));
            $file_size = $_FILES['media']['size'][$i];
            $file_tmp_name = $_FILES['media']['tmp_name'][$i];

            if (!in_array($file_extension, $allowed_extensions)) {
                $response['message'] = 'Desteklenmeyen dosya türü: '.$file_extension;
                echo json_encode($response);
                exit;
            }
            if ($file_size > $max_file_size) {
                $response['message'] = 'Dosya boyutu 50MB\'tan büyük olamaz.';
                echo json_encode($response);
                exit;
            }

            $media_type = (in_array($file_extension, ['mp4', 'mov'])) ? 'video' : 'image';

            if ($media_type === 'image' && getimagesize($file_tmp_name) === false) {
                $response['message'] = 'Yüklenen dosya geçerli bir resim değil.';
                echo json_encode($response);
                exit;
            }

            $unique_filename = uniqid('post_', true).'.'.$file_extension;
            $target_file = $upload_dir.$unique_filename;

            if (move_uploaded_file($file_tmp_name, $target_file)) {
                $uploaded_files[] = ['url' => $unique_filename, 'type' => $media_type];
            } else {
                $response['message'] = 'Dosya yüklenirken bir hata oluştu.';
                echo json_encode($response);
                exit;
            }
        }
    }
}

if (empty($caption) && empty($uploaded_files)) {
    $response['message'] = 'Gönderi içeriği veya medya dosyası boş olamaz.';
    echo json_encode($response);
    exit;
}

// === Veritabanı İşlemini Tek Fonksiyonla Modele Devret ===
$post_id = createPost($conn, $current_user_id, $caption, $uploaded_files);

if ($post_id) {
    $response['success'] = true;
    $response['message'] = 'Gönderi başarıyla paylaşıldı.';
    $response['post_id'] = $post_id;
} else {
    http_response_code(500); // Sunucu hatası
    $response['message'] = 'Gönderi kaydedilirken bir veritabanı hatası oluştu. Lütfen tekrar deneyin.';
}

echo json_encode($response);
$conn->close();
