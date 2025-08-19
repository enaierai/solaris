<?php

// includes/models/MessageModel.php (NİHAİ VE TAM VERSİYON)

/**
 * İki kullanıcı arasındaki mevcut konuşmayı bulur veya yenisini oluşturur.
 */
function findOrCreateConversation($conn, $user1_id, $user2_id)
{
    // Önce mevcut bir konuşma var mı diye kontrol et
    $stmt = $conn->prepare('
        SELECT id FROM conversations 
        WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)
    ');
    $stmt->bind_param('iiii', $user1_id, $user2_id, $user2_id, $user1_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    $stmt->close();

    // Yoksa yeni bir konuşma oluştur
    $stmt = $conn->prepare('INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user1_id, $user2_id);
    if ($stmt->execute()) {
        return $conn->insert_id;
    }

    return false;
}

/**
 * Belirli bir konuşmaya yeni bir mesaj ekler.
 */
function sendMessageToConversation($conn, $conversation_id, $sender_id, $message_text)
{
    if (empty(trim($message_text))) {
        return false;
    }
    // DÜZELTME: Senin veritabanı yapına uygun sorgu
    $stmt = $conn->prepare('INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $conversation_id, $sender_id, $message_text);

    return $stmt->execute();
}
/**
 * YENİ FONKSİYON: Bir konuşmanın tüm detaylarını (mesajlar, konuşma partneri) getirir.
 */
function getConversationDetails($conn, $conversation_id, $current_user_id)
{
    $response = [
        'success' => false,
        'messages' => [],
        'partner_info' => null,
        'current_user_id' => $current_user_id,
    ];

    // 1. Konuşma bilgilerini ve partner ID'sini al
    $stmt = $conn->prepare('SELECT * FROM conversations WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)');
    $stmt->bind_param('iii', $conversation_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $conversation_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$conversation_info) {
        return $response;
    }

    // 2. Partnerin kim olduğunu bul
    $partner_id = ($conversation_info['user_one_id'] == $current_user_id)
        ? $conversation_info['user_two_id']
        : $conversation_info['user_one_id'];

    // 3. Partnerin bilgilerini al
    $stmt = $conn->prepare('SELECT id, username, profile_picture_url FROM users WHERE id = ?');
    $stmt->bind_param('i', $partner_id);
    $stmt->execute();
    $response['partner_info'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 4. Tüm mesajları çek ve post önizlemelerini işle
    $stmt = $conn->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC');
    $stmt->bind_param('i', $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($msg = $result->fetch_assoc()) {
        if (preg_match('/\[POST_EMBED:(\d+)\]/', $msg['message_text'], $matches)) {
            $post_id = (int) $matches[1];
            $msg['post_preview'] = getPostPreviewById($conn, $post_id);
        }
        $response['messages'][] = $msg;
    }
    $stmt->close();

    $response['success'] = true;

    return $response;
}
