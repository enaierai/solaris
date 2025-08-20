<?php

class MessageModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Kullanıcıya ait okunmamış mesaj sayısını döndürür.
     */
    public function getUnreadMessageCount(int $user_id): int
    {
        // Bu sorgu, mesaj istekleri veya doğrudan mesajlar için okunmamış sayısını döndürebilir.
        // Basitlik için, alıcı user_id'si olan ve is_read=0 olan mesajları sayıyoruz.
        // Daha karmaşık bir mesajlaşma sistemi için bu sorgu daha detaylı olabilir.
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['count'] ?? 0;
    }

    // Diğer mesajlaşma metotları buraya eklenebilir (örn: sendMessage, getMessagesBetweenUsers)
}
