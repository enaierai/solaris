<?php

class TagModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * En popüler etiketleri, en çok kullanılandan en aza doğru sıralayarak döndürür.
     * post_tags tablosu ile tags tablosunu birleştirerek çalışır.
     */
    public function getPopularTags(int $limit = 5): array
    {
        // tags tablosunu post_tags tablosuyla birleştirerek etiketleri sayar
        $sql = 'SELECT t.name, COUNT(pt.tag_id) as tag_count 
                FROM tags t 
                JOIN post_tags pt ON t.id = pt.tag_id 
                GROUP BY t.id, t.name 
                ORDER BY tag_count DESC LIMIT ?';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        $stmt->close();

        return $tags;
    }
}
