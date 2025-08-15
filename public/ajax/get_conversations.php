<?php
/*
|--------------------------------------------------------------------------
| File: public/ajax/get_conversations.php
|--------------------------------------------------------------------------
| Description:
| Bu dosya, kullanıcının tüm konuşmalarını, son mesajlarını ve okunmamış mesaj sayılarını
| çeker. Ana sayfadaki konuşma listesini dinamik olarak güncellemek için kullanılır.
|
*/
require_once __DIR__.'/../../includes/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'conversations' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Oturum açmadınız.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Tüm konuşmaları, son mesajları ve okunmamış durumlarını çekme
$conversations = [];
$stmt = $conn->prepare('
    SELECT
        c.id,
        c.updated_at,
        u.id as partner_id,
        u.username,
        u.profile_picture_url,
        m.message_text AS last_message_text,
        m.created_at AS last_message_date,
        SUM(CASE WHEN m_unread.is_read = 0 AND m_unread.sender_id != ? THEN 1 ELSE 0 END) AS unread_count
    FROM conversations c
    LEFT JOIN users u ON
        (c.user_one_id = ? AND u.id = c.user_two_id) OR
        (c.user_two_id = ? AND u.id = c.user_one_id)
    LEFT JOIN (
        SELECT
            conversation_id,
            message_text,
            created_at,
            ROW_NUMBER() OVER(PARTITION BY conversation_id ORDER BY created_at DESC) as rn
        FROM messages
    ) m ON m.conversation_id = c.id AND m.rn = 1
    LEFT JOIN messages m_unread ON m_unread.conversation_id = c.id
    WHERE c.user_one_id = ? OR c.user_two_id = ?
    GROUP BY c.id
    ORDER BY c.updated_at DESC
');
if ($stmt) {
    $stmt->bind_param('iiiii', $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['is_unread'] = $row['unread_count'] > 0;
        unset($row['unread_count']);
        $conversations[] = $row;
    }
    $stmt->close();
}

$response['success'] = true;
$response['conversations'] = $conversations;

echo json_encode($response);
