<?php

class HomeController extends Controller
{
    public function index()
    {
        $postModel = $this->model('PostModel');
        $userModel = $this->model('UserModel'); // UserModel'i yükle
        $tagModel = $this->model('TagModel'); // Yeni TagModel'i yüklüyoruz

        $current_user_id = $_SESSION['user_id'] ?? null;
        $is_logged_in = isset($current_user_id);
        $popular_tags = $tagModel->getPopularTags();

        // Varsayılan sayfalama ve filtreleme değerleri
        $page = 1; // İlk sayfa
        $limit = 10; // Her sayfada 10 gönderi
        $offset = ($page - 1) * $limit;
        $filter = $_GET['filter'] ?? 'new'; // URL'den filtreyi al, yoksa 'new'

        // Gönderileri çek
        $posts = $postModel->getFeedPosts($current_user_id, $limit, $offset, $filter);

        // Önerilen kullanıcıları çek (giriş yapmışsa)
        $suggested_users = [];
        if ($is_logged_in) {
            $suggested_users = $userModel->getSuggestedUsers($current_user_id);
        }

        $data = [
            'meta' => [
                'meta_title' => 'Solaris - İlhamını Paylaş',
                'meta_description' => 'Solaris, fotoğraf ve video paylaşımı yapabileceğiniz, yeni insanlarla tanışabileceğiniz bir sosyal medya platformudur.',
                'meta_keywords' => 'sosyal medya, fotoğraf, video, paylaşım, topluluk',
                'meta_author' => 'Solaris',
                'og_image' => BASE_URL.'public/uploads/solaris_og_image.png',
                'og_url' => BASE_URL,
            ],
            'posts' => $posts,
            'suggested_users' => $suggested_users,
            'popular_tags' => $popular_tags, // Popüler etiketleri View'a gönderiyoruz
            'is_logged_in' => $is_logged_in,
            'current_user_id' => $current_user_id,
            'current_filter' => $filter, // Aktif filtreyi View'a gönder
            'page_name' => 'home', // Sayfa bağlamını belirle
        ];

        $this->view('layouts/header', $data);
        $this->view('pages/home', $data);
        $this->view('layouts/footer', $data);
    }
}
