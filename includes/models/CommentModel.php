<?php

// includes/models/CommentModel.php

/**
 * Verilen gönderi ID listesine ait tüm yorumları verimli bir şekilde çeker.
 *
 * @param mysqli $conn     Veritabanı bağlantı nesnesi
 * @param array  $post_ids yorumları alınacak gönderilerin ID'lerini içeren dizi
 *
 * @return array gönderi ID'sine göre gruplandırılmış yorumları içeren bir dizi
 */
function getCommentsForPosts($conn, $post_ids)
{
    if (empty($post_ids)) {
        return [];
    }

    $comments_by_post = [];

    $in_clause = implode(',', array_fill(0, count($post_ids), '?'));
    $sql = "
        SELECT 
            c.id, c.post_id, c.user_id, c.comment_text, c.created_at, 
            u.username AS comment_username, 
            u.profile_picture_url AS comment_profile_picture_url 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id IN ($in_clause) 
        ORDER BY c.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($post_ids)), ...$post_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($comment = $result->fetch_assoc()) {
        // Her yorumu kendi post_id'sinin altına bir dizi olarak ekliyoruz.
        $comments_by_post[$comment['post_id']][] = $comment;
    }
    $stmt->close();

    return $comments_by_post;
}
/**
 * YENİ FONKSİYON: Veritabanına yeni bir yorum ekler.
 *
 * @param mysqli $conn         Veritabanı bağlantı nesnesi
 * @param int    $post_id      Yorumun yapıldığı gönderi ID'si
 * @param int    $user_id      Yorumu yapan kullanıcı ID'si
 * @param string $comment_text Yorum metni
 *
 * @return int|false ekleme başarılıysa yeni yorumun ID'sini, değilse false döner
 */
function createComment($conn, $post_id, $user_id, $comment_text)
{
    $stmt = $conn->prepare('INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('iis', $post_id, $user_id, $comment_text);
        if ($stmt->execute()) {
            return $conn->insert_id;
        }
    }

    return false;
}
/**
 * YENİ FONKSİYON: Belirli bir gönderiye ait tüm yorumları kullanıcı bilgileriyle birlikte getirir.
 */
function getCommentsForPost($conn, $post_id)
{
    $comments = [];
    $stmt = $conn->prepare('
        SELECT c.*, u.username AS comment_username, u.profile_picture_url AS comment_profile_picture_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ');
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();

    return $comments;
}
/**
 * YENİ FONKSİYON: Bir yorumu şikayet olarak kaydeder.
 */
function reportComment($conn, $comment_id, $reporter_id, $reason)
{
    $stmt = $conn->prepare("INSERT INTO reports (reporter_user_id, reported_content_type, reported_content_id, reason) VALUES (?, 'comment', ?, ?)");
    $stmt->bind_param('iis', $reporter_id, $comment_id, $reason);

    return $stmt->execute();
}
/**
 * YENİ FONKSİYON: ID'ye göre tek bir yorumun tüm detaylarını getirir.
 */
function getSingleCommentById($conn, $comment_id)
{
    $stmt = $conn->prepare('
        SELECT c.*, u.username AS comment_username, u.profile_picture_url AS comment_profile_picture_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ');
    $stmt->bind_param('i', $comment_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result;
}
/**
 * YENİ FONKSİYON: Bir yorumu, sadece yetkili kullanıcıların silebilmesini sağlayarak veritabanından kaldırır.
 * Yetki Kontrolü: İşlemi yapan kullanıcı ya yorumun sahibi olmalı ya da yorumun yapıldığı gönderinin sahibi olmalı.
 *
 * @param mysqli $conn       Veritabanı bağlantı nesnesi
 * @param int    $comment_id Silinecek yorumun ID'si
 * @param int    $user_id    Silme işlemini yapan kullanıcının ID'si
 *
 * @return bool silme işlemi başarılıysa true, değilse false döner
 */
function deleteComment($conn, $comment_id, $user_id)
{
    // 1. Yorumun ve ait olduğu gönderinin sahibinin ID'lerini tek bir sorguda al
    $stmt = $conn->prepare('
        SELECT c.user_id AS comment_owner_id, p.user_id AS post_owner_id
        FROM comments c
        JOIN posts p ON c.post_id = p.id
        WHERE c.id = ?
    ');
    $stmt->bind_param('i', $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if (!$data) {
        return false; // Yorum bulunamadı
    }

    // 2. Yetki kontrolü: Kullanıcı, yorumun sahibi veya gönderinin sahibi mi?
    if ($user_id != $data['comment_owner_id'] && $user_id != $data['post_owner_id']) {
        return false; // Yetki yok
    }

    // 3. Yetki varsa, yorumu sil
    $stmt_delete = $conn->prepare('DELETE FROM comments WHERE id = ?');
    $stmt_delete->bind_param('i', $comment_id);
    $success = $stmt_delete->execute();
    $stmt_delete->close();

    return $success;
}
