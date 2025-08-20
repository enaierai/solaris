<?php

class PostController extends Controller
{
    /**
     * Tekil gönderi sayfasını gösterir.
     * URL'den gelen gönderi ID'sini parametre olarak alır.
     * Örnek: /post/123.
     */
    public function index($post_id = 0)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            header('Location: '.BASE_URL);
            exit;
        }

        // Gerekli modelleri yükle
        $postModel = $this->model('PostModel');
        $commentModel = $this->model('CommentModel');

        $current_user_id = $_SESSION['user_id'] ?? null;

        // Gönderi detaylarını modelden çek
        $post_data = $postModel->getPostDetailsById($post_id, $current_user_id);

        // Eğer gönderi bulunamazsa (veya engellenmişse), 404 sayfası göster
        if (!$post_data) {
            $this->view('pages/errors/404');

            return;
        }

        // Yorumları modelden çek
        $post_comments = $commentModel->getCommentsForPost($post_id);

        $is_owner = (isset($current_user_id) && $current_user_id == $post_data['user_id']);

        // View'a gönderilecek tüm verileri hazırla
        $data = [
            'meta' => [
                'meta_title' => htmlspecialchars($post_data['username']).': "'.substr(htmlspecialchars($post_data['caption']), 0, 50).'..."',
                'meta_description' => htmlspecialchars($post_data['caption']),
                'og_image' => !empty($post_data['media']) ? BASE_URL.'serve.php?path=posts/'.htmlspecialchars($post_data['media'][0]['image_url']) : '',
            ],
            'post_data' => $post_data,
            'post_comments' => $post_comments,
            'is_owner' => $is_owner,
            'is_logged_in' => isset($current_user_id),
            'current_user_id' => $current_user_id,
            'page_name' => 'post',
        ];

        // View'ları yükle
        $this->view('layouts/header', $data);
        $this->view('pages/post', $data);
        $this->view('layouts/footer', $data);
    }
}
