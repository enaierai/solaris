<?php

// public/ajax/upload_profile_picture.php
require_once __DIR__.'/../../includes/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Bir hata oluştu.', 'new_profile_picture_url' => ''];

// Sadece POST isteklerini ve geçerli CSRF token'ı olan istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $response['message'] = 'Geçersiz istek veya CSRF doğrulama hatası. Sayfayı yenileyip tekrar deneyin.';
    echo json_encode($response);
    exit;
}

// Kullanıcının giriş yapmış olup olmadığını kontrol et
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Profil resmi değiştirmek için giriş yapmalısınız.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Yol düzeltmesi yapıldı.
$upload_dir = __DIR__.'/../../uploads/profile_pictures/';

// Klasör yoksa oluştur, daha güvenli izinlerle (0755)
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $response['message'] = 'Profil resimleri klasörü oluşturulamadı. Sunucu izinlerini kontrol edin.';
        error_log('Profil resimleri klasörü oluşturulamadı: '.$upload_dir);
        echo json_encode($response);
        exit;
    }
}

// Dosya yükleme kontrolü
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    // Hata mesajlarını burada detaylandırmaya devam edebilirsiniz...
    $response['message'] = 'Dosya yükleme hatası oluştu.';
    echo json_encode($response);
    exit;
}

$file_tmp_name = $_FILES['profile_picture']['tmp_name'];
$file_name = $_FILES['profile_picture']['name'];
$file_size = $_FILES['profile_picture']['size'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5 MB

// Dosya uzantısı kontrolü
if (!in_array($file_ext, $allowed_extensions)) {
    $response['message'] = 'Sadece JPG, JPEG, PNG ve GIF formatında resimler yükleyebilirsiniz.';
    echo json_encode($response);
    exit;
}

// Dosya boyutu kontrolü
if ($file_size > $max_file_size) {
    $response['message'] = 'Dosya boyutu 5MB\'tan küçük olmalıdır.';
    echo json_encode($response);
    exit;
}

// Güvenlik: Dosyanın gerçek bir resim olup olmadığını kontrol et
$image_info = getimagesize($file_tmp_name);
if ($image_info === false) {
    $response['message'] = 'Yüklenen dosya geçerli bir resim formatında değil.';
    echo json_encode($response);
    exit;
}

$new_file_name = uniqid('profile_', true).'.'.$file_ext;
$target_file = $upload_dir.$new_file_name;

// Eski profil resmini silme
$stmt_get_old_pic = $conn->prepare('SELECT profile_picture_url FROM users WHERE id = ?');
$stmt_get_old_pic->bind_param('i', $user_id);
$stmt_get_old_pic->execute();
$result_old_pic = $stmt_get_old_pic->get_result();
if ($result_old_pic->num_rows > 0) {
    $row_old_pic = $result_old_pic->fetch_assoc();
    $old_profile_picture = $row_old_pic['profile_picture_url'];

    // Sadece varsayılan resim değilse sil (tüm varsayılan isimleri kontrol edin)
    if (!empty($old_profile_picture) && $old_profile_picture !== 'default_profile.png' && $old_profile_picture !== 'default.jpg') {
        $old_file_path = $upload_dir.$old_profile_picture;
        if (file_exists($old_file_path)) {
            unlink($old_file_path);
            error_log('Eski profil resmi silindi: '.$old_file_path);
        }
    }
}
$stmt_get_old_pic->close();

if (move_uploaded_file($file_tmp_name, $target_file)) {
    $stmt_update = $conn->prepare('UPDATE users SET profile_picture_url = ? WHERE id = ?');
    $db_file_name = $new_file_name;
    $stmt_update->bind_param('si', $db_file_name, $user_id);

    if ($stmt_update->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profil resmi başarıyla güncellendi.';
        $response['new_profile_picture_url'] = BASE_URL.'uploads/profile_pictures/'.$new_file_name;
    } else {
        $response['message'] = 'Veritabanı güncellenirken hata oluştu: '.$conn->error;
        error_log('Veritabanı profil resmi güncelleme hatası: '.$conn->error);
        if (file_exists($target_file)) {
            unlink($target_file);
        }
    }
    $stmt_update->close();
} else {
    $response['message'] = 'Dosya yüklenirken bir hata oluştu. Lütfen klasör izinlerini kontrol edin.';
    error_log('Dosya taşıma hatası: '.$file_tmp_name.' to '.$target_file.' - error: '.error_get_last()['message']);
}

$conn->close();
echo json_encode($response);
exit;
