<?php

class NotificationController extends Controller
{
    /**
     * AJAX: Okunmamış bildirim ve mesaj sayılarını döndürür.
     * Bu metod, public/ajax/poll_updates.php dosyasının yerini alır.
     * URL: BASE_URL/notification/poll_updates.
     */
    public function poll_updates()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        // GET isteği bekliyoruz
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id = $_SESSION['user_id'];
            $notificationModel = $this->model('NotificationModel');
            $messageModel = $this->model('MessageModel'); // Mesaj sayısını almak için MessageModel'e ihtiyaç var

            try {
                $unread_notifications = $notificationModel->getUnreadNotificationCount($user_id);
                $unread_messages = $messageModel->getUnreadMessageCount($user_id); // MessageModel'e eklenecek metot

                $response = [
                    'success' => true,
                    'unread_notifications' => $unread_notifications,
                    'unread_messages' => $unread_messages,
                ];
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    // Diğer bildirimle ilgili metotlar buraya eklenebilir (örn: mark_as_read, get_all_notifications)
}
