<?php

class UserModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Bir kullanıcıyı kullanıcı adına göre bulur.
     *
     * @return array|null Kullanıcı verisi veya bulunamazsa null
     */
    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT * FROM users WHERE username = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user;
    }

    /**
     * Bir kullanıcıyı ID'sine göre bulur.
     *
     * @return array|null Kullanıcı verisi veya bulunamazsa null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user;
    }

    /**
     * Yeni bir kullanıcı oluşturur.
     *
     * @return int|false Eklenen kullanıcının ID'si veya hata durumunda false
     */
    public function create(string $username, string $email, string $hashedPassword)
    {
        // HATA BURADAYDI: "password_hash" yerine veritabanındaki doğru sütun adı olan "password" kullanılmalı.
        $sql = 'INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $username, $email, $hashedPassword);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }

        return false;
    }

    /**
     * Bir kullanıcıya takip etmediği ve en çok gönderisi olan kullanıcıları önerir.
     *
     * @return array Önerilen kullanıcıların dizisi
     */
    public function getSuggestedUsers(?int $current_user_id, int $limit = 5): array
    {
        if (!$current_user_id) {
            return []; // Giriş yapmamışsa öneri yok
        }

        $sql = 'SELECT u.id, u.username, u.profile_picture_url, COUNT(p.id) AS post_count 
                FROM users u 
                LEFT JOIN posts p ON u.id = p.user_id 
                WHERE u.id != ? AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) 
                GROUP BY u.id 
                ORDER BY post_count DESC, RAND()
                LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $current_user_id, $current_user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $users;
    }

    /**
     * Bir kullanıcıyı kullanıcı adı VEYA e-posta adresine göre bulur.
     *
     * @param string $identifier Kullanıcı adı veya e-posta
     *
     * @return array|null Kullanıcı verisi veya bulunamazsa null
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $sql = 'SELECT * FROM users WHERE username = ? OR email = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user;
    }

    /**
     * Verilen kullanıcı adı veya e-postanın zaten var olup olmadığını kontrol eder.
     */
    public function doesUserExist(string $username, string $email): bool
    {
        $sql = 'SELECT id FROM users WHERE username = ? OR email = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        // Eğer sonuç 0'dan büyükse, kullanıcı var demektir.
        return $result->num_rows > 0;
    }

    /**
     * Bir kullanıcının takipçi, takip edilen ve gönderi sayılarını döndürür.
     */
    public function getUserStats(int $user_id): array
    {
        $stats = ['posts' => 0, 'followers' => 0, 'following' => 0];

        // Gönderi sayısı
        $stmt = $this->db->prepare('SELECT COUNT(id) as count FROM posts WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['posts'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        // Takipçi sayısı - DÜZELTME: COUNT(id) yerine COUNT(*) kullanılıyor.
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM follows WHERE following_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['followers'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        // Takip edilen sayısı - DÜZELTME: COUNT(id) yerine COUNT(*) kullanılıyor.
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM follows WHERE follower_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['following'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        return $stats;
    }

    /**
     * Bir kullanıcının başka bir kullanıcıyı takip edip etmediğini kontrol eder.
     *
     * @param int $follower_id  Takip eden
     * @param int $following_id Takip edilen
     */
    public function isFollowing(int $follower_id, int $following_id): bool
    {
        // DÜZELTME: "SELECT id" yerine, sadece satırın varlığını kontrol eden "SELECT 1" kullanılıyor.
        // Bu hem hatayı giderir hem de daha performanslıdır.
        $stmt = $this->db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
        $stmt->bind_param('ii', $follower_id, $following_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }
}
