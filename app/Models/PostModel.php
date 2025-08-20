<?php

class PostModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    // --- TEMEL GÖNDERİ GETİRME METOTLARI ---

    public function getPostDetailsById(int $post_id, ?int $current_user_id = null): ?array
    {
        $sql = 'SELECT p.*, u.username, u.profile_picture_url,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count';
        if ($current_user_id) {
            $sql .= ', EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked';
            $sql .= ', EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved';
        }
        $sql .= ' FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?';
        $stmt = $this->db->prepare($sql);
        if ($current_user_id) {
            $stmt->bind_param('iii', $current_user_id, $current_user_id, $post_id);
        } else {
            $stmt->bind_param('i', $post_id);
        }
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($post) {
            $post['media'] = $this->getPostMedia($post['id']);
        }

        return $post;
    }

    public function getFeedPosts(?int $user_id, int $limit = 10, int $offset = 0): array
    {
        if (!$user_id) {
            $sql = 'SELECT p.*, u.username, u.profile_picture_url, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count, (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count, FALSE as user_liked, FALSE as user_saved FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
        } else {
            $sql = 'SELECT p.*, u.username, u.profile_picture_url, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count, (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count, EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked, EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?) OR p.user_id = ? ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $posts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($posts as $key => $post) {
            $posts[$key]['media'] = $this->getPostMedia($post['id']);
        }

        return $posts;
    }

    public function getPostsByUserId(int $user_id): array
    {
        $sql = 'SELECT p.*, u.username, u.profile_picture_url, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count, (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $posts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($posts as $key => $post) {
            $posts[$key]['media'] = $this->getPostMedia($post['id']);
        }

        return $posts;
    }

    public function getSavedPostsByUser(int $user_id): array
    {
        $sql = 'SELECT p.*, u.username, u.profile_picture_url FROM saved_posts sp JOIN posts p ON sp.post_id = p.id JOIN users u ON p.user_id = u.id WHERE sp.user_id = ? ORDER BY p.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $posts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($posts as $key => $post) {
            $posts[$key]['media'] = $this->getPostMedia($post['id']);
        }

        return $posts;
    }

    public function getPostMedia(int $post_id): array
    {
        $sql = 'SELECT * FROM post_media WHERE post_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $media = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $media;
    }

    // --- GÖNDERİ OLUŞTURMA, GÜNCELLEME VE SİLME ---

    public function createPost(int $user_id, string $caption, array $uploaded_files = []): int|false
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO posts (user_id, caption) VALUES (?, ?)');
            $stmt->bind_param('is', $user_id, $caption);
            $stmt->execute();
            $post_id = $this->db->insert_id;
            $stmt->close();

            if (!empty($uploaded_files)) {
                $media_stmt = $this->db->prepare('INSERT INTO post_media (post_id, image_url, media_type) VALUES (?, ?, ?)');
                foreach ($uploaded_files as $file) {
                    $media_stmt->bind_param('iss', $post_id, $file['url'], $file['type']);
                    $media_stmt->execute();
                }
                $media_stmt->close();
            }

            $this->db->commit();

            return $post_id;
        } catch (Exception $e) {
            $this->db->rollback();

            return false;
        }
    }

    public function getPopularTags(int $limit = 10): array
    {
        $sql = 'SELECT name, COUNT(*) as count 
                FROM tags 
                GROUP BY name 
                ORDER BY count DESC 
                LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $tags = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $tags;
    }

    public function deletePost(int $post_id, int $user_id): bool
    {
        // Yetki kontrolü
        $stmt = $this->db->prepare('SELECT id FROM posts WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $post_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return false;
        }
        $stmt->close();

        // Silinecek dosyaları al
        $media_files = $this->getPostMedia($post_id);

        $this->db->begin_transaction();
        try {
            $this->db->query("DELETE FROM comments WHERE post_id = $post_id");
            $this->db->query("DELETE FROM likes WHERE post_id = $post_id");
            $this->db->query("DELETE FROM notifications WHERE post_id = $post_id");
            $this->db->query("DELETE FROM saved_posts WHERE post_id = $post_id");
            $this->db->query("DELETE FROM post_tags WHERE post_id = $post_id");
            $this->db->query("DELETE FROM post_media WHERE post_id = $post_id");
            $this->db->query("DELETE FROM posts WHERE id = $post_id");

            // Fiziksel dosyaları sil
            foreach ($media_files as $media) {
                $file_path = ROOT.'/public/uploads/posts/'.$media['image_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollback();

            return false;
        }
    }
}
