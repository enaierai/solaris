<?php

// public/ajax/user_handler.php (NİHAİ VE TAM HALİ)

require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/UserModel.php';
include_once __DIR__.'/../../includes/models/NotificationModel.php';

header('Content-Type: application/json');

// Akıllı veri alma bloğu
$input_data = json_decode(file_get_contents('php://input'), true);
if (empty($input_data)) {
    $input_data = $_REQUEST;
}

// CSRF ve Oturum kontrolü
if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF doğrulama başarısız.']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$action = $input_data['action'] ?? '';
$user_id = $_SESSION['user_id']; // İşlemi YAPAN kullanıcı

try {
    switch ($action) {
        case 'toggle_follow':
            $following_id = intval($input_data['following_id'] ?? 0); // Takip EDİLEN kullanıcı

            // Model'deki akıllı fonksiyonu çağırıyoruz
            $result = toggleFollowUser($conn, $user_id, $following_id);

            if ($result) {
                // JS'in butonları ve sayaçları güncelleyebilmesi için yeni sayıları gönderiyoruz
                $newFollowerCount = getFollowerCount($conn, $following_id);
                $newFollowingCount = getFollowingCount($conn, $user_id);
                echo json_encode([
                    'success' => true,
                    'action' => $result,
                    'newFollowerCount' => $newFollowerCount,
                    'newFollowingCount' => $newFollowingCount,
                ]);
            } else {
                throw new Exception('Takip işlemi gerçekleştirilemedi.');
            }
            break;

        case 'toggle_block':
            $blocked_id = intval($input_data['blocked_id'] ?? 0); // Engellenen kullanıcı

            // Model'deki akıllı fonksiyonu çağırıyoruz
            $result = toggleBlockUser($conn, $user_id, $blocked_id);

            if ($result) {
                echo json_encode(['success' => true, 'action' => $result]);
            } else {
                throw new Exception('Engelleme işlemi gerçekleştirilemedi.');
            }
            break;

        case 'remove_follower':
            $follower_id_to_remove = intval($input_data['follower_id'] ?? 0); // Çıkarılan takipçi

            // Model'deki fonksiyonu çağırıyoruz
            if (removeFollower($conn, $user_id, $follower_id_to_remove)) {
                echo json_encode(['success' => true, 'message' => 'Takipçi çıkarıldı.']);
            } else {
                throw new Exception('Takipçi çıkarılamadı.');
            }
            break;

        default:
            throw new Exception('Geçersiz kullanıcı eylemi.');
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
