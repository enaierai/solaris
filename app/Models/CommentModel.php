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
     * Belirli bir gönderiye ait yorumları çeker.
     * Her yoruma yorum sahibinin profil resmi ve kullanıcı adını dahil eder.
     */
    public function getCommentsByPostId(int $postId, ?int $currentUserId = null): array
    {
        $comments = [];
        $sql = 'SELECT 
                    c.id, 
                    c.user_id, 
                    u.username, 
                    u.profile_picture_url, 
                    c.comment_text, 
                    c.created_at 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? 
                ORDER BY c.created_at ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        $stmt->close();

        return $comments;
    }

    /**
     * Yeni bir yorum ekler.
     */
    public function addComment(int $userId, int $postId, string $commentText): ?int
    {
        $stmt = $this->db->prepare('INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $userId, $postId, $commentText);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }

        return null;
    }

    /**
     * Bir yorumu ID'sine göre çeker.
     */
    public function getCommentById(int $commentId): ?array
    {
        $stmt = $this->db->prepare('SELECT id, user_id, post_id, comment_text, created_at FROM comments WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $commentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result;
    }

    /**
     * Bir yorumu siler.
     */
    public function deleteComment(int $commentId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->bind_param('i', $commentId);

        return $stmt->execute();
    }
}
