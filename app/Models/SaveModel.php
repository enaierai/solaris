<?php

class SaveModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Bir gönderiyi kaydeder.
     */
    public function savePost(int $userId, int $postId): bool
    {
        // Önce gönderinin varlığını kontrol et
        $postExists = $this->db->prepare('SELECT 1 FROM posts WHERE id = ? LIMIT 1');
        $postExists->bind_param('i', $postId);
        $postExists->execute();
        $postExistsResult = $postExists->get_result();
        $postExists->close();

        if ($postExistsResult->num_rows === 0) {
            throw new Exception('Kaydedilmek istenen gönderi bulunamadı.');
        }

        // Zaten kaydedilmiş mi kontrol et
        $isSaved = $this->db->prepare('SELECT 1 FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1');
        $isSaved->bind_param('ii', $userId, $postId);
        $isSaved->execute();
        $isSavedResult = $isSaved->get_result();
        $isSaved->close();

        if ($isSavedResult->num_rows > 0) {
            return true; // Zaten kaydedilmişse başarılı say
        }

        $stmt = $this->db->prepare('INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $userId, $postId);

        return $stmt->execute();
    }

    /**
     * Kaydedilmiş bir gönderiyi siler.
     */
    public function unsavePost(int $userId, int $postId): bool
    {
        // Önce gönderinin varlığını kontrol et
        $postExists = $this->db->prepare('SELECT 1 FROM posts WHERE id = ? LIMIT 1');
        $postExists->bind_param('i', $postId);
        $postExists->execute();
        $postExistsResult = $postExists->get_result();
        $postExists->close();

        if ($postExistsResult->num_rows === 0) {
            throw new Exception('Kaydı kaldırılmak istenen gönderi bulunamadı.');
        }

        // Kayıt var mı kontrol et
        $isSaved = $this->db->prepare('SELECT 1 FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1');
        $isSaved->bind_param('ii', $userId, $postId);
        $isSaved->execute();
        $isSavedResult = $isSaved->get_result();
        $isSaved->close();

        if ($isSavedResult->num_rows === 0) {
            return true; // Zaten kaydedilmemişse başarılı say
        }

        $stmt = $this->db->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?');
        $stmt->bind_param('ii', $userId, $postId);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının belirli bir gönderiyi kaydedip kaydetmediğini kontrol eder.
     */
    public function isSaved(int $userId, int $postId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1');
        $stmt->bind_param('ii', $userId, $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }
}
