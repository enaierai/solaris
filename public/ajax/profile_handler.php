<?php

// public/ajax/profile_handler.php (NİHAİ HALİ)

require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/UserModel.php';

header('Content-Type: application/json');

// Akıllı veri alma bloğu (resim yükleme için $_POST ve $_FILES'a ihtiyaç duyarız)
$input_data = $_POST;

if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF doğrulama başarısız.']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$action = $input_data['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'update_bio':
            $new_bio = $input_data['bio'] ?? '';
            $result = updateUserBio($conn, $user_id, $new_bio);
            if ($result !== false) {
                echo json_encode(['success' => true, 'new_bio' => $result]);
            } else {
                throw new Exception('Biyografi güncellenemedi.');
            }
            break;

            // Not: Resim yükleme işlemleri için action belirlemedik, çünkü
            // JS tarafında farklı dosyalara istek atılıyor gibi görünüyor.
            // Ama onları da buraya alabiliriz. Şimdilik bu şekilde bırakıyorum.
            // Eğer tek bir handler'dan yöneteceksek, JS'in bir action göndermesi gerekir.

        default:
            // Resim yükleme işlemleri için ayrı bir kontrol
            if (isset($_FILES['profile_picture'])) {
                $result = updateUserProfilePicture($conn, $user_id, $_FILES['profile_picture']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Profil resmi güncellendi.']);
                } else {
                    throw new Exception('Profil resmi yüklenemedi.');
                }
            } elseif (isset($_FILES['cover_picture'])) {
                $result = updateUserCoverPicture($conn, $user_id, $_FILES['cover_picture']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Kapak fotoğrafı güncellendi.']);
                } else {
                    throw new Exception('Kapak fotoğrafı yüklenemedi.');
                }
            } else {
                throw new Exception('Geçersiz profil eylemi.');
            }
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
