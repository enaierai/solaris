<?php

class CommentModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Bir gönderiye ait tüm yorumları, kullanıcı bilgileriyle birlikte getirir.
     */
    public function getCommentsForPost(int $post_id): array
    {
        $sql = 'SELECT c.*, u.username, u.profile_picture_url 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $comments;
    }

    /**
     * Veritabanına yeni bir yorum ekler.
     */
    public function createComment(int $post_id, int $user_id, string $comment_text): int|false
    {
        $stmt = $this->db->prepare('INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $post_id, $user_id, $comment_text);
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }

        return false;
    }

    /**
     * Bir yorumu, yetkili kullanıcı tarafından siler.
     */
    public function deleteComment(int $comment_id, int $user_id): bool
    {
        // Yetki kontrolü (Yorumun veya gönderinin sahibi mi?)
        $stmt = $this->db->prepare('SELECT c.user_id AS comment_owner_id, p.user_id AS post_owner_id FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.id = ?');
        $stmt->bind_param('i', $comment_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$data || ($user_id != $data['comment_owner_id'] && $user_id != $data['post_owner_id'])) {
            return false; // Yetki yok
        }

        // Yorumu sil
        $stmt_delete = $this->db->prepare('DELETE FROM comments WHERE id = ?');
        $stmt_delete->bind_param('i', $comment_id);

        return $stmt_delete->execute();
    }
}
