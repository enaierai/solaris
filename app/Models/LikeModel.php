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
        // Ana posts tablosundaki sayacı artır
        $this->db->query("UPDATE posts SET likes = likes + 1 WHERE id = $post_id");

        // likes tablosuna kaydı ekle
        $stmt = $this->db->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $user_id, $post_id);

        return $stmt->execute();
    }

    public function removeLike(int $user_id, int $post_id): bool
    {
        // Ana posts tablosundaki sayacı azalt (0'ın altına düşmemesini garantile)
        $this->db->query("UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id = $post_id");

        // likes tablosundan kaydı sil
        $stmt = $this->db->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
        $stmt->bind_param('ii', $user_id, $post_id);

        return $stmt->execute();
    }
}
