<?php

class LikeModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    public function addLike(int $user_id, int $post_id): bool
    {
        // likes tablosuna kaydı ekle
        $stmt = $this->db->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $user_id, $post_id);

        return $stmt->execute();
    }

    public function removeLike(int $user_id, int $post_id): bool
    {
        // likes tablosundan kaydı sil
        $stmt = $this->db->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
        $stmt->bind_param('ii', $user_id, $post_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının belirli bir gönderiyi beğenip beğenmediğini kontrol eder.
     */
    public function isPostLikedByUser(int $user_id, int $post_id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ? LIMIT 1');
        $stmt->bind_param('ii', $user_id, $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }

    /**
     * Bir gönderinin toplam beğeni sayısını döndürür.
     */
    public function getLikeCount(int $post_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM likes WHERE post_id = ?');
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }
}
