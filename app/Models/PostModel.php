<?php

class PostModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Feed için gönderi listesini alır.
     * Algoritma: Önce takip edilenlerin postlarını, sonra popüler postları getirir.
     * Boş ekran sorununu çözmek için tasarlanmıştır.
     */
    public function getFeedPosts(?int $user_id, int $limit = 10, int $offset = 0): array
    {
        $posts = [];

        // Senaryo 2: Kullanıcının takip ettiği kişilerin gönderileri
        if ($user_id) {
            $sql = 'SELECT p.*, u.username, u.profile_picture_url, 
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count, 
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                    EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                    EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved
                    FROM posts p 
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?) OR p.user_id = ?
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?';

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $posts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        // Senaryo 1 & 3: Kullanıcının hiç takip ettiği yoksa veya az post varsa
        // Genel olarak popüler gönderileri getir
        if (empty($posts)) {
            $sql = 'SELECT p.*, u.username, u.profile_picture_url, 
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count, 
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                    FALSE as user_liked,
                    FALSE as user_saved
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    ORDER BY (SELECT COUNT(*) FROM likes WHERE post_id = p.id) DESC, p.created_at DESC
                    LIMIT ? OFFSET ?';

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $posts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        // Her gönderi için medya verilerini ve etiketleri al
        foreach ($posts as $key => $post) {
            $posts[$key]['media'] = $this->getPostMedia($post['id']);
            $posts[$key]['tags'] = $this->getPostTags($post['id']);
        }

        return $posts;
    }

    /**
     * Tekil bir gönderinin tüm detaylarını alır.
     */
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
            $post['tags'] = $this->getPostTags($post['id']);
        }

        return $post;
    }

    /**
     * Bir kullanıcıya ait gönderileri alır.
     */
    public function getPostsByUserId(int $user_id, ?int $current_user_id = null): array
    {
        $sql = 'SELECT p.*, u.username, u.profile_picture_url, 
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count, 
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count';
        if ($current_user_id) {
            $sql .= ', EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked';
            $sql .= ', EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved';
        }
        $sql .= ' FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC';
        $stmt = $this->db->prepare($sql);
        if ($current_user_id) {
            $stmt->bind_param('iii', $current_user_id, $current_user_id, $user_id);
        } else {
            $stmt->bind_param('i', $user_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $posts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($posts as $key => $post) {
            $posts[$key]['media'] = $this->getPostMedia($post['id']);
            $posts[$key]['tags'] = $this->getPostTags($post['id']);
        }

        return $posts;
    }

    /**
     * Bir kullanıcı tarafından kaydedilen gönderileri alır.
     */
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

    /**
     * Bir gönderiye ait medya dosyalarını alır.
     */
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

    /**
     * Bir gönderiye ait etiketleri alır.
     */
    public function getPostTags(int $post_id): array
    {
        $sql = 'SELECT t.name FROM post_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.post_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tags = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_column($tags, 'name');
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

            // Kapsamdaki etiketleri ayrıştır ve kaydet
            preg_match_all('/#(\w+)/', $caption, $matches);
            if (!empty($matches[1])) {
                $tags = array_unique($matches[1]);
                $tag_ids = [];
                foreach ($tags as $tag_name) {
                    $stmt_tag = $this->db->prepare('SELECT id FROM tags WHERE name = ?');
                    $stmt_tag->bind_param('s', $tag_name);
                    $stmt_tag->execute();
                    $tag_result = $stmt_tag->get_result()->fetch_assoc();
                    $stmt_tag->close();

                    if ($tag_result) {
                        $tag_id = $tag_result['id'];
                    } else {
                        $stmt_tag_insert = $this->db->prepare('INSERT INTO tags (name) VALUES (?)');
                        $stmt_tag_insert->bind_param('s', $tag_name);
                        $stmt_tag_insert->execute();
                        $tag_id = $this->db->insert_id;
                        $stmt_tag_insert->close();
                    }
                    $tag_ids[] = $tag_id;
                }

                $stmt_post_tag = $this->db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
                foreach ($tag_ids as $tag_id) {
                    $stmt_post_tag->bind_param('ii', $post_id, $tag_id);
                    $stmt_post_tag->execute();
                }
                $stmt_post_tag->close();
            }

            $this->db->commit();

            return $post_id;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Post oluşturma hatası: '.$e->getMessage());

            return false;
        }
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

        // Silinecek fiziksel dosyaları al
        $media_files = $this->getPostMedia($post_id);

        $this->db->begin_transaction();
        try {
            // Veritabanı kayıtlarını sil
            $this->db->query("DELETE FROM comments WHERE post_id = $post_id");
            $this->db->query("DELETE FROM likes WHERE post_id = $post_id");
            $this->db->query("DELETE FROM notifications WHERE post_id = $post_id");
            $this->db->query("DELETE FROM saved_posts WHERE post_id = $post_id");
            $this->db->query("DELETE FROM post_tags WHERE post_id = $post_id");
            $this->db->query("DELETE FROM post_media WHERE post_id = $post_id");
            $this->db->query("DELETE FROM posts WHERE id = $post_id");

            // Fiziksel dosyaları sil
            foreach ($media_files as $media) {
                // Burada dosya yolunu projenin doğru yapısına göre güncelledim.
                $file_path = ROOT.'/storage/uploads/posts/'.$media['image_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Post silme hatası: '.$e->getMessage());

            return false;
        }
    }

    public function getPopularTags(int $limit = 10): array
    {
        $sql = 'SELECT t.name, COUNT(*) as count 
                FROM post_tags pt
                JOIN tags t ON pt.tag_id = t.id
                GROUP BY t.name 
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
}
