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
     */
    public function create(string $username, string $email, string $hashedPassword)
    {
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

        // Takipçi sayısı
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM follows WHERE following_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['followers'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        // Takip edilen sayısı
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM follows WHERE follower_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['following'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        return $stats;
    }

    /**
     * Bir kullanıcının başka bir kullanıcıyı takip edip etmediğini kontrol eder.
     */
    public function isFollowing(int $follower_id, int $following_id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
        $stmt->bind_param('ii', $follower_id, $following_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }

    /**
     * Bir kullanıcının başka bir kullanıcıyı takip etmesini sağlar.
     */
    public function followUser(int $follower_id, int $following_id): bool
    {
        if ($this->isFollowing($follower_id, $following_id)) {
            return true; // Zaten takip ediyorsa başarılı say
        }
        $stmt = $this->db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $follower_id, $following_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının başka bir kullanıcıyı takibi bırakmasını sağlar.
     */
    public function unfollowUser(int $follower_id, int $following_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
        $stmt->bind_param('ii', $follower_id, $following_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının takipçisini kaldırmasını sağlar (profil sahibi için).
     */
    public function removeFollower(int $profile_owner_id, int $follower_to_remove_id): bool
    {
        // Sadece profil sahibi, kendisini takip eden birini kaldırabilir.
        // Yani profile_owner_id, following_id olmalı ve follower_to_remove_id, follower_id olmalı.
        $stmt = $this->db->prepare('DELETE FROM follows WHERE following_id = ? AND follower_id = ?');
        $stmt->bind_param('ii', $profile_owner_id, $follower_to_remove_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının başka bir kullanıcıyı engelleyip engellemediğini kontrol eder.
     */
    public function isBlocked(int $blocker_id, int $blocked_id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ? LIMIT 1');
        $stmt->bind_param('ii', $blocker_id, $blocked_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }

    /**
     * Bir kullanıcının başka bir kullanıcıyı engellemesini sağlar.
     */
    public function blockUser(int $blocker_id, int $blocked_id): bool
    {
        if ($this->isBlocked($blocker_id, $blocked_id)) {
            return true; // Zaten engelliyorsa başarılı say
        }
        // Engelleme durumunda varsa takibi bırak ve takipçiliği kaldır
        $this->unfollowUser($blocker_id, $blocked_id); // Engelleyen, engellediğini takip ediyorsa takibi bırak
        $this->unfollowUser($blocked_id, $blocker_id); // Engellenen, engelleyeni takip ediyorsa takibi bırak (takipçiliği kaldır)

        $stmt = $this->db->prepare('INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $blocker_id, $blocked_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının başka bir kullanıcının engelini kaldırmasını sağlar.
     */
    public function unblockUser(int $blocker_id, int $blocked_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?');
        $stmt->bind_param('ii', $blocker_id, $blocked_id);

        return $stmt->execute();
    }

    /**
     * Bir kullanıcının takip ettiği kişilerin listesini döndürür.
     */
    public function getFollowingUsers(int $user_id, ?int $current_viewer_id = null): array
    {
        $sql = 'SELECT u.id, u.username, u.profile_picture_url';
        if ($current_viewer_id) {
            // Eğer current_viewer_id varsa, bu kullanıcının listedeki kişiyi takip edip etmediğini de döndür
            $sql .= ', EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND following_id = u.id) as is_followed_by_current_user';
        }
        $sql .= ' FROM users u
                JOIN follows f ON u.id = f.following_id
                WHERE f.follower_id = ?
                ORDER BY u.username ASC';

        $stmt = $this->db->prepare($sql);
        if ($current_viewer_id) {
            $stmt->bind_param('ii', $current_viewer_id, $user_id);
        } else {
            $stmt->bind_param('i', $user_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $users;
    }

    /**
     * Bir kullanıcının takipçilerinin listesini döndürür.
     */
    public function getFollowers(int $user_id, ?int $current_viewer_id = null): array
    {
        $sql = 'SELECT u.id, u.username, u.profile_picture_url';
        if ($current_viewer_id) {
            // Eğer current_viewer_id varsa, bu kullanıcının listedeki kişiyi takip edip etmediğini de döndür
            $sql .= ', EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND following_id = u.id) as is_followed_by_current_user';
        }
        $sql .= ' FROM users u
                JOIN follows f ON u.id = f.follower_id
                WHERE f.following_id = ?
                ORDER BY u.username ASC';

        $stmt = $this->db->prepare($sql);
        if ($current_viewer_id) {
            $stmt->bind_param('ii', $current_viewer_id, $user_id);
        } else {
            $stmt->bind_param('i', $user_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $users;
    }

    /**
     * Kullanıcının e-posta adresinin başka bir kullanıcı tarafından kullanılıp kullanılmadığını kontrol eder.
     */
    public function isEmailTakenByAnotherUser(string $email, int $currentUserId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->bind_param('si', $email, $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }

    /**
     * Kullanıcının e-posta adresini günceller.
     */
    public function updateUserEmail(int $userId, string $newEmail): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->bind_param('si', $newEmail, $userId);

        return $stmt->execute();
    }

    /**
     * Kullanıcının şifresini günceller.
     */
    public function updateUserPassword(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hashedPassword, $userId);

        return $stmt->execute();
    }

    /**
     * Kullanıcının profil resmini günceller.
     */
    public function updateProfilePicture(int $userId, array $file): array
    {
        return $this->handleFileUpload($userId, $file, 'profile_pictures', 'profile_picture_url', 'default_profile.png');
    }

    /**
     * Genel dosya yükleme ve veritabanı güncelleme mantığı.
     *
     * @param int    $userId          Kullanıcı ID'si
     * @param array  $file            $_FILES dizisinden gelen dosya bilgisi
     * @param string $uploadDirName   Yükleme yapılacak klasör adı (pluralsız)
     * @param string $dbColumnName    Veritabanında güncellenecek sütun adı
     * @param string $defaultFileName Varsayılan dosya adı (silinmeyecek olan)
     *
     * @return array ['success' => bool, 'message' => string, 'new_file_name' => string|null]
     */
    private function handleFileUpload(
        int $userId,
        array $file,
        string $uploadDirName,
        string $dbColumnName,
        string $defaultFileName
    ): array {
        $upload_dir = ROOT.'/storage/uploads/'.$uploadDirName.'/';
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            return ['success' => false, 'message' => 'Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.'];
        }
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'Dosya boyutu 5MB\'tan büyük olamaz.'];
        }

        $new_file_name = $uploadDirName.'_'.uniqid().'.'.$file_extension;
        $target_path = $upload_dir.$new_file_name;

        try {
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Eski dosya yolunu al
                $old_user_data = $this->findById($userId);
                $old_file_name = $old_user_data[$dbColumnName] ?? null;

                // Eski dosyayı sil (varsayılan değilse ve dosya varsa)
                if (!empty($old_file_name) && $old_file_name !== $defaultFileName) {
                    $old_file_path = $upload_dir.$old_file_name;
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        unlink($old_file_path);
                    }
                }

                // Veritabanını güncelle
                $sql = "UPDATE users SET {$dbColumnName} = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('si', $new_file_name, $userId);

                if ($stmt->execute()) {
                    return ['success' => true, 'message' => 'Dosya başarıyla güncellendi.', 'new_file_name' => $new_file_name];
                } else {
                    unlink($target_path); // DB güncellemesi başarısız olursa yüklenen dosyayı sil

                    return ['success' => false, 'message' => 'Veritabanı güncellenirken hata oluştu.'];
                }
            } else {
                return ['success' => false, 'message' => 'Dosya sunucuya taşınırken hata oluştu.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
