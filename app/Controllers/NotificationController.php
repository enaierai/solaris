<?php

class NotificationController extends Controller
{
    /**
     * Kullanıcı için okunmamış bildirim ve mesaj sayılarını döndürür.
     * AJAX isteği ile çağrılır.
     */
    public function poll_updates()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Giriş yapmalısınız.';
            echo json_encode($response);
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $notificationModel = $this->model('NotificationModel');
        $messageModel = $this->model('MessageModel');

        try {
            $unread_notifications = $notificationModel->getUnreadNotificationCount($user_id);
            $unread_messages = $messageModel->getUnreadMessageCount($user_id); // Bu metodun MessageModel'de olduğunu varsayıyorum

            $response = [
                'success' => true,
                'unread_notifications' => $unread_notifications,
                'unread_messages' => $unread_messages,
            ];
        } catch (Exception $e) {
            $response['message'] = 'Sunucu hatası: '.$e->getMessage();
        }

        echo json_encode($response);
        exit; // JSON çıktısından sonra başka çıktı olmadığından emin ol
    }

    // Diğer bildirim metodları buraya eklenebilir (örn: mark_as_read)
}
