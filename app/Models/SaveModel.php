<?php

class SaveModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    public function savePost(int $user_id, int $post_id): bool
    {
        $stmt = $this->db->prepare('INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $user_id, $post_id);

        return $stmt->execute();
    }

    public function unsavePost(int $user_id, int $post_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?');
        $stmt->bind_param('ii', $user_id, $post_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının belirli bir gönderiyi kaydedip kaydetmediğini kontrol eder.
     */
    public function isPostSavedByUser(int $user_id, int $post_id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1');
        $stmt->bind_param('ii', $user_id, $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }
}
