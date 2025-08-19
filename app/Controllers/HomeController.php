<?php

class HomeController extends Controller
{
    public function index()
    {
        // 1. Gerekli Modelleri Yükle ve Başlat
        $postModel = $this->model('PostModel');
        $userModel = $this->model('UserModel');

        $current_user_id = $_SESSION['user_id'] ?? null;

        // 2. Modellerin Metotlarını Kullanarak Verileri Çek
        $posts = $postModel->getFeedPosts($current_user_id);
        $suggested_users = $userModel->getSuggestedUsers($current_user_id, 5);
        $popular_tags = $postModel->getPopularTags(10);

        // --- YENİ EKLENEN BÖLÜM: META VERİLERİ ---
        // Sayfaya özel SEO ve paylaşım bilgilerini burada tanımlıyoruz.
        $meta_data = [
            'meta_title' => 'Solaris - İlhamını Paylaş',
            'meta_description' => 'Solaris, fotoğraf ve video paylaşımı yapabileceğiniz, yeni insanlarla tanışabileceğiniz bir sosyal medya platformudur.',
            'meta_keywords' => 'sosyal medya, fotoğraf, video, paylaşım, topluluk',
            'meta_author' => 'Solaris',
            'og_image' => BASE_URL.'public/uploads/solaris_og_image.png', // Varsayılan paylaşım resmi
            'og_url' => BASE_URL,
        ];

        // 3. Tüm Verileri View'a Göndermek İçin Hazırla
        $data = [
            'page_name' => 'home',
            'posts' => $posts,
            'suggested_users' => $suggested_users,
            'popular_tags' => $popular_tags,
            'is_logged_in' => isset($current_user_id),
            'current_user_id' => $current_user_id,
            'meta' => $meta_data, // Meta verilerini de data dizisine ekle
        ];

        // 4. View'ları Yükle
        $this->view('layouts/header', $data);
        $this->view('pages/home', $data);
        $this->view('layouts/footer', $data);
    }
}
