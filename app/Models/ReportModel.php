<?php

class ReportModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Yeni bir şikayet kaydı ekler.
     */
    public function addReport(int $reporterUserId, string $contentType, int $contentId, string $reason): bool
    {
        $sql = 'INSERT INTO reports (reporter_user_id, reported_content_type, reported_content_id, reason) VALUES (?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isis', $reporterUserId, $contentType, $contentId, $reason);

        return $stmt->execute();
    }

    // Diğer raporlama sorguları buraya eklenebilir (örn: getReports, updateReportStatus)
}
