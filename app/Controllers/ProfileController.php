<?php

class ProfileController extends Controller
{
    /**
     * Kullanıcı profili sayfasını gösterir.
     * URL'den gelen kullanıcı adını parametre olarak alır.
     * Örnek: /profile/kullaniciadi.
     */
    public function index($profile_username = '')
    {
        // Eğer kullanıcı adı belirtilmemişse ana sayfaya yönlendir
        if (empty($profile_username)) {
            header('Location: '.BASE_URL);
            exit;
        }

        // Gerekli modelleri yükle
        $userModel = $this->model('UserModel');
        $postModel = $this->model('PostModel');

        // Görüntülenen kullanıcıyı bul
        $user_data = $userModel->findByUsername($profile_username);

        // Eğer kullanıcı bulunamazsa, 404 Not Found sayfası göster
        if (!$user_data) {
            $this->view('pages/errors/404');

            return;
        }

        // Temel değişkenleri hazırla
        $profile_user_id = $user_data['id'];
        $current_user_id = $_SESSION['user_id'] ?? null;
        $is_logged_in = isset($current_user_id);
        $is_owner = ($is_logged_in && $current_user_id == $profile_user_id);

        // Kullanıcı istatistiklerini ve takip durumunu modellerden al
        $stats = $userModel->getUserStats($profile_user_id);
        $is_following = $is_logged_in && !$is_owner ? $userModel->isFollowing($current_user_id, $profile_user_id) : false;
        $is_blocked_by_viewer = $is_logged_in ? $userModel->isBlocked($current_user_id, $profile_user_id) : false;
        $is_viewer_blocked_by_user = $is_logged_in ? $userModel->isBlocked($profile_user_id, $current_user_id) : false;

        // Gizli profil kontrolü
        $can_view_posts = true;
        // Eğer profil gizliyse ve takip etmiyorsak/sahibi değilsek gönderileri göremeyiz.
        // Bu mantık için 'is_private' sütununun users tablosunda olması gerekir.
        // Şimdilik bu kontrolü varsayımsal olarak ekliyorum.
        // if ($user_data['is_private'] && !$is_owner && !$is_following) {
        //     $can_view_posts = false;
        // }

        $profile_posts = [];
        $saved_posts = [];

        // Sadece gönderileri görüntüleme izni varsa postları çek
        if ($can_view_posts) {
            $profile_posts = $postModel->getPostsByUserId($profile_user_id, $current_user_id);
            if ($is_owner) {
                $saved_posts = $postModel->getSavedPostsByUser($current_user_id);
            }
        }

        // View'a gönderilecek tüm verileri tek bir dizide topla
        $data = [
            'meta' => [
                'meta_title' => htmlspecialchars($user_data['username']).' - Solaris',
                'meta_description' => 'Solaris\'te '.htmlspecialchars($user_data['username']).' adlı kullanıcının profilini ve gönderilerini keşfedin.',
            ],
            'user_data' => $user_data,
            'is_owner' => $is_owner,
            'is_logged_in' => $is_logged_in,
            'current_user_id' => $current_user_id,
            'follower_count' => $stats['followers'],
            'following_count' => $stats['following'],
            'post_count' => $stats['posts'],
            'is_following' => $is_following,
            'is_blocked_by_viewer' => $is_blocked_by_viewer, // Yeni: Engelleme durumu
            'is_viewer_blocked_by_user' => $is_viewer_blocked_by_user, // Yeni: Engelleme durumu
            'can_view_posts' => $can_view_posts, // Yeni: Gönderi görüntüleme izni
            'profile_posts' => $profile_posts,
            'saved_posts' => $saved_posts,
            'page_name' => 'profile', // Sayfa bağlamını belirle
        ];

        // View'ları yükle
        $this->view('layouts/header', $data);
        $this->view('pages/profile', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * AJAX: Profil fotoğrafı yükleme işlemi.
     */
    public function upload_profile_picture()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
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
            $userModel = $this->model('UserModel');

            if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'Dosya yüklenirken bir hata oluştu.';
                echo json_encode($response);
                exit;
            }

            $file = $_FILES['profile_picture'];
            $upload_dir = ROOT.'/storage/uploads/profile_pictures/';
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_types)) {
                $response['message'] = 'Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.';
                echo json_encode($response);
                exit;
            }
            if ($file['size'] > $max_size) {
                $response['message'] = 'Dosya boyutu 5MB\'tan büyük olamaz.';
                echo json_encode($response);
                exit;
            }

            $new_file_name = 'profile_'.uniqid().'.'.$file_extension;
            $target_path = $upload_dir.$new_file_name;

            try {
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Eski profil resmini sil (varsayılan değilse ve dosya varsa)
                    $old_user_data = $userModel->findById($user_id);
                    if ($old_user_data && !empty($old_user_data['profile_picture_url']) && $old_user_data['profile_picture_url'] !== 'default_profile.png') {
                        $old_file_path = $upload_dir.$old_user_data['profile_picture_url'];
                        if (file_exists($old_file_path) && is_file($old_file_path)) { // is_file kontrolü eklendi
                            unlink($old_file_path);
                        }
                    }

                    // Veritabanını güncelle
                    if ($userModel->updateProfilePicture($user_id, $new_file_name)) {
                        $_SESSION['profile_picture_url'] = $new_file_name; // Session'ı güncelle
                        $response = ['success' => true, 'message' => 'Profil fotoğrafı başarıyla güncellendi.'];
                    } else {
                        unlink($target_path); // DB güncellemesi başarısız olursa yüklenen dosyayı sil
                        $response['message'] = 'Veritabanı güncellenirken hata oluştu.';
                    }
                } else {
                    $response['message'] = 'Dosya sunucuya taşınırken hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Kapak fotoğrafı yükleme işlemi.
     */
    public function upload_cover_picture()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
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
            $userModel = $this->model('UserModel');

            if (!isset($_FILES['cover_picture']) || $_FILES['cover_picture']['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'Dosya yüklenirken bir hata oluştu.';
                echo json_encode($response);
                exit;
            }

            $file = $_FILES['cover_picture'];
            $upload_dir = ROOT.'/storage/uploads/cover_pictures/';
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_types)) {
                $response['message'] = 'Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.';
                echo json_encode($response);
                exit;
            }
            if ($file['size'] > $max_size) {
                $response['message'] = 'Dosya boyutu 5MB\'tan büyük olamaz.';
                echo json_encode($response);
                exit;
            }

            $new_file_name = 'cover_'.uniqid().'.'.$file_extension;
            $target_path = $upload_dir.$new_file_name;

            try {
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Eski kapak resmini sil (varsayılan değilse ve dosya varsa)
                    $old_user_data = $userModel->findById($user_id);
                    if ($old_user_data && !empty($old_user_data['cover_picture_url']) && $old_user_data['cover_picture_url'] !== 'default_cover.png') {
                        $old_file_path = $upload_dir.$old_user_data['cover_picture_url'];
                        if (file_exists($old_file_path) && is_file($old_file_path)) { // is_file kontrolü eklendi
                            unlink($old_file_path);
                        }
                    }

                    // Veritabanını güncelle
                    if ($userModel->updateCoverPicture($user_id, $new_file_name)) {
                        $response = ['success' => true, 'message' => 'Kapak fotoğrafı başarıyla güncellendi.'];
                    } else {
                        unlink($target_path); // DB güncellemesi başarısız olursa yüklenen dosyayı sil
                        $response['message'] = 'Veritabanı güncellenirken hata oluştu.';
                    }
                } else {
                    $response['message'] = 'Dosya sunucuya taşınırken hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Biyografi güncelleme işlemi.
     */
    public function update_bio()
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
            $new_bio = trim($input_data['bio'] ?? '');

            $userModel = $this->model('UserModel');

            try {
                if ($userModel->updateBio($user_id, $new_bio)) {
                    $response = ['success' => true, 'message' => 'Biyografi başarıyla güncellendi.', 'new_bio' => htmlspecialchars($new_bio)];
                } else {
                    $response['message'] = 'Biyografi güncellenirken hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }
}
