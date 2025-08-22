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
}
