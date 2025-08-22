<?php

class UserController extends Controller
{
    /**
     * AJAX: Kullanıcı takip etme/takibi bırakma işlemi.
     */
    public function toggle_follow()
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

            $follower_id = $_SESSION['user_id']; // Takip eden kişi (mevcut kullanıcı)
            $following_id = (int) ($input_data['following_id'] ?? 0); // Takip edilecek kişi

            if ($following_id <= 0 || $follower_id === $following_id) {
                $response['message'] = 'Geçersiz kullanıcı ID veya kendini takip edemezsin.';
                echo json_encode($response);
                exit;
            }

            $userModel = $this->model('UserModel');
            $notificationModel = $this->model('NotificationModel');

            try {
                $is_following = $userModel->isFollowing($follower_id, $following_id);

                if ($is_following) {
                    $userModel->unfollowUser($follower_id, $following_id);
                    $notificationModel->deleteNotification($following_id, $follower_id, 'follow', null);
                    $action_taken = 'unfollowed';
                } else {
                    $userModel->followUser($follower_id, $following_id);
                    $notificationModel->createNotification($following_id, $follower_id, 'follow', htmlspecialchars($_SESSION['username']).' seni takip etti.', null);
                    $action_taken = 'followed';
                }

                $new_follower_count = $userModel->getUserStats($following_id)['followers'];

                $response = ['success' => true, 'action' => $action_taken, 'newFollowerCount' => $new_follower_count];
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Bir takipçiyi kaldırma işlemi (profil sahibi için).
     */
    public function remove_follower()
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

            $current_user_id = $_SESSION['user_id']; // İşlemi yapan (profil sahibi)
            $follower_to_remove_id = (int) ($input_data['follower_id'] ?? 0); // Kaldırılacak takipçi

            if ($follower_to_remove_id <= 0 || $current_user_id === $follower_to_remove_id) {
                $response['message'] = 'Geçersiz kullanıcı ID veya kendini kaldıramazsın.';
                echo json_encode($response);
                exit;
            }

            $userModel = $this->model('UserModel');
            $notificationModel = $this->model('NotificationModel');

            try {
                if ($userModel->removeFollower($current_user_id, $follower_to_remove_id)) {
                    $notificationModel->deleteNotification($current_user_id, $follower_to_remove_id, 'follow', null);
                    $response = ['success' => true, 'message' => 'Takipçi başarıyla kaldırıldı.'];
                } else {
                    $response['message'] = 'Takipçi kaldırılamadı veya yetkiniz yok.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Kullanıcı engelleme/engel kaldırma işlemi.
     */
    public function toggle_block()
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

            $blocker_id = $_SESSION['user_id']; // Engelleyen kişi
            $blocked_id = (int) ($input_data['blocked_id'] ?? 0); // Engellenecek kişi

            if ($blocked_id <= 0 || $blocker_id === $blocked_id) {
                $response['message'] = 'Geçersiz kullanıcı ID veya kendini engelleyemezsin.';
                echo json_encode($response);
                exit;
            }

            $userModel = $this->model('UserModel');

            try {
                $is_blocked = $userModel->isBlocked($blocker_id, $blocked_id);

                if ($is_blocked) {
                    $userModel->unblockUser($blocker_id, $blocked_id);
                    $action_taken = 'unblocked';
                } else {
                    $userModel->blockUser($blocker_id, $blocked_id);
                    $action_taken = 'blocked';
                }

                $response = ['success' => true, 'action' => $action_taken, 'message' => ($action_taken === 'blocked' ? 'Kullanıcı başarıyla engellendi.' : 'Kullanıcının engeli kaldırıldı.')];
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Kullanıcının takip ettiği kişilerin listesini döndürür (mesajlaşma ve modal için).
     */
    public function get_following_list()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id_to_fetch = (int) ($_GET['user_id'] ?? 0); // Hangi kullanıcının takip ettiklerini istiyoruz
            if ($user_id_to_fetch <= 0) {
                $response['message'] = 'Geçersiz kullanıcı ID.';
                echo json_encode($response);
                exit;
            }

            $userModel = $this->model('UserModel');
            $current_viewer_id = $_SESSION['user_id'] ?? null; // Modalı açan kişi

            try {
                $following_users = $userModel->getFollowingUsers($user_id_to_fetch, $current_viewer_id);
                $response = ['success' => true, 'users' => $following_users];
            } catch (Exception $e) {
                // Hata durumunda bile JSON yanıtı döndür
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX METODU: Kullanıcının takipçilerinin listesini döndürür (modal için).
     */
    public function get_followers()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id_to_fetch = (int) ($_GET['user_id'] ?? 0); // Hangi kullanıcının takipçilerini istiyoruz
            if ($user_id_to_fetch <= 0) {
                $response['message'] = 'Geçersiz kullanıcı ID.';
                echo json_encode($response);
                exit;
            }

            $userModel = $this->model('UserModel');
            $current_viewer_id = $_SESSION['user_id'] ?? null; // Modalı açan kişi

            try {
                $followers = $userModel->getFollowers($user_id_to_fetch, $current_viewer_id);
                $response = ['success' => true, 'users' => $followers];
            } catch (Exception $e) {
                // Hata durumunda bile JSON yanıtı döndür
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }
}
