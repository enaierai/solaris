<?php

class SettingsModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Kullanıcının profil bilgilerini günceller.
     */
    public function updateProfileInfo(
        int $userId,
        ?string $name,
        string $username,
        ?string $pronouns,
        ?string $bio,
        ?string $gender,
        ?string $business_email,
        ?string $business_phone,
        ?string $whatsapp_number,
        bool $display_contact_info
    ): bool {
        $sql = 'UPDATE users SET 
                    name = ?, 
                    username = ?, 
                    pronouns = ?, 
                    bio = ?, 
                    gender = ?, 
                    business_email = ?, 
                    business_phone = ?, 
                    whatsapp_number = ?, 
                    display_contact_info = ? 
                WHERE id = ?';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssssssii',
            $name,
            $username,
            $pronouns,
            $bio,
            $gender,
            $business_email,
            $business_phone,
            $whatsapp_number,
            $display_contact_info,
            $userId
        );

        return $stmt->execute();
    }

    /**
     * Kullanıcının geçmiş kullanıcı adını kaydeder.
     */
    public function addUsernameToHistory(int $userId, string $oldUsername): bool
    {
        $stmt = $this->db->prepare('INSERT INTO username_history (user_id, old_username) VALUES (?, ?)');
        $stmt->bind_param('is', $userId, $oldUsername);

        return $stmt->execute();
    }

    /**
     * Kullanıcının kullanıcı adını değiştirip değiştiremeyeceğini kontrol eder (örn: 30 gün kuralı).
     */
    public function canChangeUsername(int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT changed_at FROM username_history WHERE user_id = ? ORDER BY changed_at DESC LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            $last_change_timestamp = strtotime($result['changed_at']);
            $thirty_days_ago = strtotime('-30 days');

            return $last_change_timestamp < $thirty_days_ago;
        }

        return true; // Daha önce hiç değiştirmemişse değiştirebilir
    }

    /**
     * Kullanıcının mevcut linklerini çeker.
     */
    public function getUserLinks(int $userId): array
    {
        $links = [];
        $stmt = $this->db->prepare('SELECT id, url, title FROM user_links WHERE user_id = ? ORDER BY created_at ASC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        $stmt->close();

        return $links;
    }

    /**
     * Kullanıcının linklerini günceller (mevcutları silip yenilerini ekler).
     */
    public function updateUserLinks(int $userId, array $links): bool
    {
        $this->db->begin_transaction();
        try {
            // Mevcut linkleri sil
            $stmt = $this->db->prepare('DELETE FROM user_links WHERE user_id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();

            // Yeni linkleri ekle
            foreach ($links as $link) {
                if (!empty($link['url'])) {
                    $stmt = $this->db->prepare('INSERT INTO user_links (user_id, url, title) VALUES (?, ?, ?)');
                    $stmt->bind_param('iss', $userId, $link['url'], $link['title'] ?? null);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Link güncelleme hatası: '.$e->getMessage());

            return false;
        }
    }

    // Diğer ayar veritabanı işlemleri buraya eklenecek (gizlilik, bildirimler vb.)
}
