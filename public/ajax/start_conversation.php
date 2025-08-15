<?php
/*
|--------------------------------------------------------------------------
| File: public/ajax/start_conversation.php
|--------------------------------------------------------------------------
| Description:
| Bu dosya, yeni bir kullanıcı aramak veya yeni bir konuşma başlatmak için kullanılır.
| AJAX ile POST isteği alır.
|
*/
require_once __DIR__.'/../../includes/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Oturum açmadınız.';
    echo json_encode($response);
    exit;
}

// CSRF token'ı doğrula
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['message'] = 'Geçersiz CSRF token.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'search_users') {
    $search_query = isset($_POST['search']) ? trim($_POST['search']) : '';

    if (strlen($search_query) < 2) {
        $response['success'] = true; // Boş sonuç dönmek hata değil
        $response['users'] = [];
        echo json_encode($response);
        exit;
    }

    $users = [];
    $stmt = $conn->prepare('SELECT id, username, profile_picture_url FROM users WHERE username LIKE ? AND id != ? LIMIT 10');
    $search_param = '%'.$search_query.'%';
    $stmt->bind_param('si', $search_param, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    $response['success'] = true;
    $response['users'] = $users;
    echo json_encode($response);
    exit;
} elseif ($action === 'start_chat') {
    $target_user_id = isset($_POST['target_user_id']) ? (int) $_POST['target_user_id'] : 0;

    if ($target_user_id <= 0 || $target_user_id == $current_user_id) {
        $response['message'] = 'Geçersiz kullanıcı ID\'si.';
        echo json_encode($response);
        exit;
    }

    // Kullanıcının zaten bu kişiyle bir konuşması var mı kontrol et
    $stmt = $conn->prepare('
        SELECT id FROM conversations
        WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)
    ');
    $stmt->bind_param('iiii', $current_user_id, $target_user_id, $target_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_conversation = $result->fetch_assoc();
    $stmt->close();

    if ($existing_conversation) {
        $response['success'] = true;
        $response['conversation_id'] = $existing_conversation['id'];
        $response['message'] = 'Mevcut konuşmaya yönlendiriliyor.';
        echo json_encode($response);
        exit;
    }

    // Yeni bir konuşma oluştur
    $stmt = $conn->prepare('INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)');
    if ($stmt) {
        $stmt->bind_param('ii', $current_user_id, $target_user_id);
        if ($stmt->execute()) {
            $new_conversation_id = $conn->insert_id;
            $response['success'] = true;
            $response['conversation_id'] = $new_conversation_id;
            $response['message'] = 'Yeni konuşma başarıyla başlatıldı.';
        } else {
            $response['message'] = 'Yeni konuşma başlatılırken bir veritabanı hatası oluştu.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Sorgu hatası.';
    }

    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Geçersiz eylem.';
    echo json_encode($response);
    exit;
}
