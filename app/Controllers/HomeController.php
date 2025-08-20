<?php

class HomeController extends Controller
{
    public function index()
    {
        $postModel = $this->model('PostModel');
        $userModel = $this->model('UserModel');

        $current_user_id = $_SESSION['user_id'] ?? null;

        $posts = $postModel->getFeedPosts($current_user_id);
        $suggested_users = $userModel->getSuggestedUsers($current_user_id, 5);
        $popular_tags = $postModel->getPopularTags(10);

        $meta_data = [
            'meta_title' => 'Solaris - İlhamını Paylaş',
            'meta_description' => 'Solaris, fotoğraf ve video paylaşımı yapabileceğiniz, yeni insanlarla tanışabileceğiniz bir sosyal medya platformudur.',
            'meta_keywords' => 'sosyal medya, fotoğraf, video, paylaşım, topluluk',
            'meta_author' => 'Solaris',
            'og_image' => BASE_URL.'public/uploads/solaris_og_image.png',
            'og_url' => BASE_URL,
        ];

        $data = [
            'page_name' => 'home',
            'posts' => $posts,
            'suggested_users' => $suggested_users,
            'popular_tags' => $popular_tags,
            'is_logged_in' => isset($current_user_id),
            'current_user_id' => $current_user_id,
            'meta' => $meta_data,
        ];

        $this->view('layouts/header', $data);
        $this->view('pages/home', $data);
        $this->view('layouts/footer', $data);
    }
}
