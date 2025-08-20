<?php

class ReportController extends Controller
{
    /**
     * AJAX: İçerik raporlama işlemi.
     * Bu metod, public/ajax/report_content.php dosyasının yerini alır.
     */
    public function content()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'CSRF doğrulama başarısız.';
                echo json_encode($response);
                exit;
            }
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id = $_SESSION['user_id'];
            $content_type = $input_data['type'] ?? ''; // 'post', 'comment'
            $content_id = (int) ($input_data['id'] ?? 0);
            $reason = $input_data['reason'] ?? '';

            if (empty($content_type) || $content_id <= 0 || empty($reason)) {
                $response['message'] = 'Eksik veya geçersiz rapor bilgisi.';
                echo json_encode($response);
                exit;
            }

            // Burada bir ReportModel oluşturup raporu veritabanına kaydedebiliriz.
            // Şimdilik sadece başarılı yanıt dönüyoruz.
            // Örnek: $reportModel = $this->model('ReportModel');
            //        $reportModel->createReport($user_id, $content_type, $content_id, $reason);

            $response = ['success' => true, 'message' => 'Raporunuz için teşekkür ederiz. En kısa sürede incelenecektir.'];
        }
        echo json_encode($response);
    }
}
