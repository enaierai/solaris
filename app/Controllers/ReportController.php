<?php

class ReportController extends Controller
{
    /**
     * İçerik şikayetini işler (gönderi, yorum, kullanıcı).
     * AJAX isteği ile çağrılır.
     */
    public function add_report()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            // CSRF kontrolü
            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
                echo json_encode($response);
                exit;
            }

            // Kullanıcı giriş yapmış mı kontrolü
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $reporter_user_id = $_SESSION['user_id'];
            $reported_content_type = $input_data['type'] ?? ''; // 'post', 'comment', 'user'
            $reported_content_id = (int) ($input_data['id'] ?? 0);
            $reason = $input_data['reason'] ?? '';

            // Gerekli alanların kontrolü
            if (empty($reported_content_type) || $reported_content_id <= 0 || empty($reason)) {
                $response['message'] = 'Tüm alanlar doldurulmalıdır.';
                echo json_encode($response);
                exit;
            }

            // Geçerli içerik tipleri
            $valid_content_types = ['post', 'comment', 'user'];
            if (!in_array($reported_content_type, $valid_content_types)) {
                $response['message'] = 'Geçersiz içerik tipi.';
                echo json_encode($response);
                exit;
            }

            $reportModel = $this->model('ReportModel'); // ReportModel'i yükle

            try {
                // Raporu veritabanına ekle
                if ($reportModel->addReport($reporter_user_id, $reported_content_type, $reported_content_id, $reason)) {
                    $response = ['success' => true, 'message' => 'Şikayetiniz başarıyla alındı.'];
                } else {
                    $response['message'] = 'Şikayet gönderilirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                // Hata yakalama
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
    }

    // Diğer raporlama metodları buraya eklenebilir
}
