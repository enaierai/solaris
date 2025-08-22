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
     * Yeni bir gönderi oluşturur.
     */
    public function createPost(int $userId, ?string $caption): int
    {
        $stmt = $this->db->prepare('INSERT INTO posts (user_id, caption) VALUES (?, ?)');
        $stmt->bind_param('is', $userId, $caption);
        $stmt->execute();

        return $stmt->insert_id;
    }

    /**
     * Gönderiye medya ekler.
     */
    public function addMediaToPost(int $postId, string $imageUrl, string $mediaType): bool
    {
        $stmt = $this->db->prepare('INSERT INTO post_media (post_id, image_url, media_type) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $postId, $imageUrl, $mediaType);

        return $stmt->execute();
    }

    /**
     * Belirli bir gönderiyi ID'sine göre çeker.
     * Kullanıcının beğenip beğenmediği ve kaydedip kaydetmediği bilgisini de içerir.
     */
    public function getPostById(int $postId, ?int $currentUserId = null): ?array
    {
        $sql = 'SELECT
                    p.id,
                    p.user_id,
                    u.username,
                    u.profile_picture_url,
                    p.caption,
                    p.created_at,
                    p.likes AS like_count,
                    (SELECT COUNT(c.id) FROM comments c WHERE c.post_id = p.id) AS comment_count';

        if ($currentUserId !== null) {
            $sql .= ', EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked';
            $sql .= ', EXISTS(SELECT 1 FROM saved_posts s WHERE s.post_id = p.id AND s.user_id = ?) AS user_saved';
        }

        $sql .= ' FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id = ?';

        $stmt = $this->db->prepare($sql);

        if ($currentUserId !== null) {
            $stmt->bind_param('iii', $currentUserId, $currentUserId, $postId);
        } else {
            $stmt->bind_param('i', $postId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();

        if ($post) {
            // Medya ve etiketleri çek
            $post['media'] = $this->getPostMedia($postId);
            $post['tags'] = $this->getPostTags($postId);
        }

        return $post;
    }

    /**
     * Bir gönderinin medyasını çeker.
     */
    public function getPostMedia(int $postId): array
    {
        $media = [];
        $stmt = $this->db->prepare('SELECT image_url, media_type FROM post_media WHERE post_id = ? ORDER BY id ASC');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $media[] = $row;
        }
        $stmt->close();

        return $media;
    }

    /**
     * Bir gönderinin etiketlerini çeker.
     */
    public function getPostTags(int $postId): array
    {
        $tags = [];
        $stmt = $this->db->prepare('SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ? ORDER BY t.name ASC');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row; // Sadece tag adını döndürüyoruz
        }
        $stmt->close();

        return $tags;
    }

    /**
     * Kullanıcının ana akış gönderilerini çeker (takip edilenler ve popüler gönderiler).
     */
    public function getFeedPosts(?int $currentUserId, int $limit, int $offset, string $filter = 'new'): array
    {
        $posts = [];
        $sql = 'SELECT
                    p.id,
                    p.user_id,
                    u.username,
                    u.profile_picture_url,
                    p.caption,
                    p.created_at,
                    p.likes AS like_count,
                    (SELECT COUNT(c.id) FROM comments c WHERE c.post_id = p.id) AS comment_count';

        $bind_types = '';
        $bind_values = [];

        if ($currentUserId !== null) {
            // Parameters for EXISTS subqueries in SELECT clause
            $sql .= ', EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked';
            $sql .= ', EXISTS(SELECT 1 FROM saved_posts s WHERE s.post_id = p.id AND s.user_id = ?) AS user_saved';
            $bind_types .= 'ii';
            $bind_values[] = $currentUserId;
            $bind_values[] = $currentUserId;
        }

        $sql .= ' FROM posts p
                  JOIN users u ON p.user_id = u.id';

        $where_clauses = [];

        if ($currentUserId !== null) {
            // Kendi gönderilerini hariç tut
            $where_clauses[] = 'p.user_id != ?';
            $bind_types .= 'i';
            $bind_values[] = $currentUserId;

            // Engellenen kullanıcıların gönderilerini hariç tut
            $where_clauses[] = 'p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)';
            $bind_types .= 'i';
            $bind_values[] = $currentUserId;

            // Kendisini engelleyen kullanıcıların gönderilerini hariç tut
            $where_clauses[] = 'p.user_id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)';
            $bind_types .= 'i';
            $bind_values[] = $currentUserId;
        }

        if (!empty($where_clauses)) {
            $sql .= ' WHERE '.implode(' AND ', $where_clauses);
        }

        switch ($filter) {
            case 'new':
                $sql .= ' ORDER BY p.created_at DESC';
                break;
            case 'popular':
                $sql .= ' ORDER BY p.likes DESC, p.created_at DESC';
                break;
                // Diğer filtreler eklenebilir
        }

        $sql .= ' LIMIT ? OFFSET ?';
        $bind_types .= 'ii'; // These are for limit and offset
        $bind_values[] = $limit;
        $bind_values[] = $offset;

        $stmt = $this->db->prepare($sql);

        // Bind parameters dynamically
        if (!empty($bind_values)) {
            call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$bind_types], $bind_values)));
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['media'] = $this->getPostMedia($row['id']);
            $row['tags'] = $this->getPostTags($row['id']); // Etiketleri de çek
            $posts[] = $row;
        }
        $stmt->close();

        return $posts;
    }

    /**
     * Ana akış gönderilerinin toplam sayısını döndürür.
     */
    public function getFeedPostsCount(?int $currentUserId, string $filter = 'new'): int
    {
        $sql = 'SELECT COUNT(p.id) AS count FROM posts p JOIN users u ON p.user_id = u.id';

        $where_clauses = [];
        $param_types = '';
        $param_values = [];

        if ($currentUserId !== null) {
            $where_clauses[] = 'p.user_id != ?';
            $param_types .= 'i';
            $param_values[] = $currentUserId;

            $where_clauses[] = 'p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)';
            $param_types .= 'i';
            $param_values[] = $currentUserId;

            $where_clauses[] = 'p.user_id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)';
            $param_types .= 'i';
            $param_values[] = $currentUserId;
        }

        if (!empty($where_clauses)) {
            $sql .= ' WHERE '.implode(' AND ', $where_clauses);
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($param_values)) {
            call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$param_types], $param_values)));
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }

    /**
     * Belirli bir kullanıcıya ait gönderileri çeker.
     */
    public function getPostsByUserId(int $userId, ?int $currentViewerId = null, int $limit = 0, int $offset = 0): array
    {
        $posts = [];
        $sql = 'SELECT
                    p.id,
                    p.user_id,
                    u.username,
                    u.profile_picture_url,
                    p.caption,
                    p.created_at,
                    p.likes AS like_count,
                    (SELECT COUNT(c.id) FROM comments c WHERE c.post_id = p.id) AS comment_count';

        $bind_types = '';
        $bind_values = [];

        if ($currentViewerId !== null) {
            $sql .= ', EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked';
            $sql .= ', EXISTS(SELECT 1 FROM saved_posts s WHERE s.post_id = p.id AND s.user_id = ?) AS user_saved';
            $bind_types .= 'ii';
            $bind_values[] = $currentViewerId;
            $bind_values[] = $currentViewerId;
        }

        $sql .= ' FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = ?
                  ORDER BY p.created_at DESC';

        $bind_types .= 'i';
        $bind_values[] = $userId;

        if ($limit > 0) {
            $sql .= ' LIMIT ? OFFSET ?';
            $bind_types .= 'ii';
            $bind_values[] = $limit;
            $bind_values[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$bind_types], $bind_values)));
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['media'] = $this->getPostMedia($row['id']);
            $row['tags'] = $this->getPostTags($row['id']);
            $posts[] = $row;
        }
        $stmt->close();

        return $posts;
    }

    /**
     * Belirli bir kullanıcıya ait gönderilerin toplam sayısını döndürür.
     */
    public function getPostsByUserIdCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(id) AS count FROM posts WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }

    /**
     * Keşfet sayfasında gösterilecek gönderileri çeker.
     */
    public function getExplorePosts(?int $currentUserId, int $limit, int $offset): array
    {
        $posts = [];
        $sql = 'SELECT
                    p.id,
                    p.user_id,
                    u.username,
                    u.profile_picture_url,
                    p.caption,
                    p.created_at,
                    p.likes AS like_count,
                    (SELECT COUNT(c.id) FROM comments c WHERE c.post_id = p.id) AS comment_count';

        $bind_types = '';
        $bind_values = [];

        if ($currentUserId !== null) {
            $sql .= ', EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked';
            $sql .= ', EXISTS(SELECT 1 FROM saved_posts s WHERE s.post_id = p.id AND s.user_id = ?) AS user_saved';
            $bind_types .= 'ii';
            $bind_values[] = $currentUserId;
            $bind_values[] = $currentUserId;
        }

        $sql .= ' FROM posts p
                  JOIN users u ON p.user_id = u.id';

        $where_clauses = [];

        if ($currentUserId !== null) {
            // Kendi gönderilerini hariç tut
            $where_clauses[] = 'p.user_id != ?';
            $bind_types .= 'i';
            $bind_values[] = $currentUserId;

            // Engellenen kullanıcıların gönderilerini hariç tut
            $where_clauses[] = 'p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)';
            $bind_types .= 'i';
            $bind_values[] = $currentUserId;

            // Kendisini engelleyen kullanıcıların gönderilerini hariç tut
            $where_clauses[] = 'p.user_id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)';
            $bind_types .= 'i';
            $bind_values[] = $currentUserId;
        }

        if (!empty($where_clauses)) {
            $sql .= ' WHERE '.implode(' AND ', $where_clauses);
        }

        $sql .= ' ORDER BY p.likes DESC, p.created_at DESC LIMIT ? OFFSET ?';

        $bind_types .= 'ii';
        $bind_values[] = $limit;
        $bind_values[] = $offset;

        $stmt = $this->db->prepare($sql);

        if (!empty($bind_values)) {
            call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$bind_types], $bind_values)));
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['media'] = $this->getPostMedia($row['id']);
            $row['tags'] = $this->getPostTags($row['id']);
            $posts[] = $row;
        }
        $stmt->close();

        return $posts;
    }

    /**
     * Keşfet gönderilerinin toplam sayısını döndürür.
     */
    public function getExplorePostsCount(?int $currentUserId): int
    {
        $sql = 'SELECT COUNT(p.id) AS count FROM posts p JOIN users u ON p.user_id = u.id';

        $where_clauses = [];
        $param_types = '';
        $param_values = [];

        if ($currentUserId !== null) {
            $where_clauses[] = 'p.user_id != ?';
            $param_types .= 'i';
            $param_values[] = $currentUserId;

            $where_clauses[] = 'p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)';
            $param_types .= 'i';
            $param_values[] = $currentUserId;

            $where_clauses[] = 'p.user_id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)';
            $param_types .= 'i';
            $param_values[] = $currentUserId;
        }

        if (!empty($where_clauses)) {
            $sql .= ' WHERE '.implode(' AND ', $where_clauses);
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($param_values)) {
            call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$param_types], $param_values)));
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }

    /**
     * Kullanıcının kaydettiği gönderileri çeker.
     */
    public function getSavedPostsByUser(int $userId, int $limit = 0, int $offset = 0): array
    {
        $posts = [];
        $sql = 'SELECT
                    p.id,
                    p.user_id,
                    u.username,
                    u.profile_picture_url,
                    p.caption,
                    p.created_at,
                    p.likes AS like_count,
                    (SELECT COUNT(c.id) FROM comments c WHERE c.post_id = p.id) AS comment_count,
                    TRUE AS user_saved, -- Kaydedilmişse her zaman TRUE
                    EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked
                FROM saved_posts sp
                JOIN posts p ON sp.post_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE sp.user_id = ?
                ORDER BY sp.saved_at DESC';

        $bind_types = 'ii'; // for user_liked and sp.user_id
        $bind_values = [$userId, $userId];

        if ($limit > 0) {
            $sql .= ' LIMIT ? OFFSET ?';
            $bind_types .= 'ii';
            $bind_values[] = $limit;
            $bind_values[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$bind_types], $bind_values)));
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['media'] = $this->getPostMedia($row['id']);
            $row['tags'] = $this->getPostTags($row['id']);
            $posts[] = $row;
        }
        $stmt->close();

        return $posts;
    }

    /**
     * Kullanıcının kaydettiği gönderilerin toplam sayısını döndürür.
     */
    public function getSavedPostsByUserCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(id) AS count FROM saved_posts WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }

    /**
     * Bir gönderiyi beğenir.
     */
    public function likePost(int $userId, int $postId): bool
    {
        // Önce gönderinin varlığını kontrol et
        $postExists = $this->db->prepare('SELECT 1 FROM posts WHERE id = ? LIMIT 1');
        $postExists->bind_param('i', $postId);
        $postExists->execute();
        $postExistsResult = $postExists->get_result();
        $postExists->close();

        if ($postExistsResult->num_rows === 0) {
            throw new Exception('Beğenilmek istenen gönderi bulunamadı.');
        }

        // Zaten beğenilmiş mi kontrol et
        $isLiked = $this->db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ? LIMIT 1');
        $isLiked->bind_param('ii', $userId, $postId);
        $isLiked->execute();
        $isLikedResult = $isLiked->get_result();
        $isLiked->close();

        if ($isLikedResult->num_rows > 0) {
            return true; // Zaten beğenilmişse başarılı say
        }

        // Beğeni ekle
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $userId, $postId);
            if (!$stmt->execute()) {
                throw new Exception('Beğeni eklenirken veritabanı hatası.');
            }
            $stmt->close();

            // Gönderinin beğeni sayısını artır
            $stmt = $this->db->prepare('UPDATE posts SET likes = likes + 1 WHERE id = ?');
            $stmt->bind_param('i', $postId);
            if (!$stmt->execute()) {
                throw new Exception('Beğeni sayısı güncellenirken veritabanı hatası.');
            }
            $stmt->close();

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Like Post Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Bir gönderinin beğenisini kaldırır.
     */
    public function unlikePost(int $userId, int $postId): bool
    {
        // Önce gönderinin varlığını kontrol et
        $postExists = $this->db->prepare('SELECT 1 FROM posts WHERE id = ? LIMIT 1');
        $postExists->bind_param('i', $postId);
        $postExists->execute();
        $postExistsResult = $postExists->get_result();
        $postExists->close();

        if ($postExistsResult->num_rows === 0) {
            throw new Exception('Beğenisi kaldırılmak istenen gönderi bulunamadı.');
        }

        // Beğeni var mı kontrol et
        $isLiked = $this->db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ? LIMIT 1');
        $isLiked->bind_param('ii', $userId, $postId);
        $isLiked->execute();
        $isLikedResult = $isLiked->get_result();
        $isLiked->close();

        if ($isLikedResult->num_rows === 0) {
            return true; // Zaten beğenilmemişse başarılı say
        }

        // Beğeniyi kaldır
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
            $stmt->bind_param('ii', $userId, $postId);
            if (!$stmt->execute()) {
                throw new Exception('Beğeni silinirken veritabanı hatası.');
            }
            $stmt->close();

            // Gönderinin beğeni sayısını azalt
            $stmt = $this->db->prepare('UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id = ?');
            $stmt->bind_param('i', $postId);
            if (!$stmt->execute()) {
                throw new Exception('Beğeni sayısı güncellenirken veritabanı hatası.');
            }
            $stmt->close();

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Unlike Post Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Bir gönderinin mevcut beğeni sayısını döndürür.
     */
    public function getLikeCount(int $postId): int
    {
        $stmt = $this->db->prepare('SELECT likes FROM posts WHERE id = ?');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['likes'] ?? 0;
    }

    /**
     * Bir kullanıcının belirli bir gönderiyi beğenip beğenmediğini kontrol eder.
     */
    public function isLiked(int $userId, int $postId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ? LIMIT 1');
        $stmt->bind_param('ii', $userId, $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }

    /**
     * Bir gönderinin yorum sayısını artırır veya azaltır.
     */
    public function updateCommentCount(int $postId, int $change): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET comment_count = GREATEST(0, comment_count + ?) WHERE id = ?');
        $stmt->bind_param('ii', $change, $postId);

        return $stmt->execute();
    }

    /**
     * Bir gönderinin sahibinin ID'sini döndürür.
     */
    public function getPostOwnerId(int $postId): ?int
    {
        $stmt = $this->db->prepare('SELECT user_id FROM posts WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['user_id'] ?? null;
    }

    /**
     * Bir gönderinin açıklamasını günceller.
     */
    public function updatePostCaption(int $postId, string $newCaption): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET caption = ? WHERE id = ?');
        $stmt->bind_param('si', $newCaption, $postId);

        return $stmt->execute();
    }

    /**
     * Bir gönderiyi siler.
     */
    public function deletePost(int $postId): bool
    {
        // Medyayı sil (fiziksel dosyaları)
        $media = $this->getPostMedia($postId);
        foreach ($media as $media_item) {
            $file_path = ROOT.'/storage/uploads/posts/'.$media_item['image_url'];
            if (file_exists($file_path) && is_file($file_path)) {
                unlink($file_path);
            }
        }

        // Veritabanından gönderiyi sil (CASCADE ile bağlı tablolar da silinir)
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->bind_param('i', $postId);

        return $stmt->execute();
    }
}

// refValues fonksiyonu, bind_param için referansları doğru şekilde iletmek için kullanılır.
// Bu fonksiyonun functions.php'de veya global olarak erişilebilir olması gerekir.
if (!function_exists('refValues')) {
    function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) { // PHP 5.3+
            $refs = [];
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }

            return $refs;
        }

        return $arr;
    }
}
