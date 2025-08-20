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
            header('Location: ' . BASE_URL);
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

        // Yorumlar artık AJAX ile yükleneceği için burada çekmiyoruz.
        $post_comments = []; 

        $is_owner = (isset($current_user_id) && $current_user_id == $post_data['user_id']);

        // View'a gönderilecek tüm verileri hazırla
        $data = [
            'meta' => [
                'meta_title' => htmlspecialchars($post_data['username']) . ': "' . substr(htmlspecialchars($post_data['caption']), 0, 50) . '..."',
                'meta_description' => htmlspecialchars($post_data['caption']),
                'og_image' => !empty($post_data['media']) ? BASE_URL . 'serve.php?path=posts/' . htmlspecialchars($post_data['media'][0]['image_url']) : '',
            ],
            'post_data' => $post_data,
            'post_comments' => $post_comments, // Boş gönderiyoruz, AJAX dolduracak
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

    /**
     * AJAX: Gönderiyi beğenme veya beğeniyi geri çekme işlemi.
     */
    public function like()
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
            $post_id = (int) ($input_data['post_id'] ?? 0);

            if ($post_id <= 0) {
                $response['message'] = 'Geçersiz gönderi ID.';
                echo json_encode($response);
                exit;
            }

            $likeModel = $this->model('LikeModel');
            $notificationModel = $this->model('NotificationModel'); // Yazım hatası düzeltildi
            $postModel = $this->model('PostModel');

            try {
                $post_data = $postModel->getPostDetailsById($post_id);
                if (!$post_data) {
                    throw new Exception('Gönderi bulunamadı.');
                }
                $post_owner_id = $post_data['user_id'];

                // Engelleme kontrolü eklenecek (şimdilik varsayılıyor)
                // if (checkBlockStatus($conn, $user_id, $post_owner_id)) {
                //     throw new Exception('Bu kullanıcıyla etkileşime giremezsiniz.');
                // }

                $liked_before = $likeModel->isPostLikedByUser($user_id, $post_id);

                if ($liked_before) {
                    $likeModel->removeLike($user_id, $post_id);
                    $notificationModel->deleteNotification($post_owner_id, $user_id, 'like', $post_id);
                    $action_taken = 'unliked';
                } else {
                    $likeModel->addLike($user_id, $post_id);
                    if ($user_id != $post_owner_id) {
                        $notificationModel->createNotification($post_owner_id, $user_id, 'like', htmlspecialchars($_SESSION['username']) . ' gönderini beğendi.', $post_id);
                    }
                    $action_taken = 'liked';
                }

                $new_likes_count = $likeModel->getLikeCount($post_id);
                $response = ['success' => true, 'new_likes' => $new_likes_count, 'action' => $action_taken];

            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Yorum ekleme işlemi.
     */
    public function add_comment()
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
            $post_id = (int) ($input_data['post_id'] ?? 0);
            $comment_text = trim($input_data['comment_text'] ?? '');

            if ($post_id <= 0) {
                $response['message'] = 'Geçersiz gönderi ID.';
                echo json_encode($response);
                exit;
            }
            if (empty($comment_text)) {
                $response['message'] = 'Yorum boş bırakılamaz.';
                echo json_encode($response);
                exit;
            }

            $commentModel = $this->model('CommentModel');
            $notificationModel = $this->model('NotificationModel');
            $postModel = $this->model('PostModel');

            try {
                $post_data = $postModel->getPostDetailsById($post_id);
                if (!$post_data) {
                    throw new Exception('Gönderi bulunamadı.');
                }
                $post_owner_id = $post_data['user_id'];

                // Engelleme kontrolü eklenecek (şimdilik varsayılıyor)
                // if (checkBlockStatus($conn, $user_id, $post_owner_id)) {
                //     throw new Exception('Bu kullanıcıyla etkileşime giremezsiniz.');
                // }

                $comment_id = $commentModel->createComment($post_id, $user_id, $comment_text);

                if ($comment_id) {
                    if ($post_owner_id && $user_id != $post_owner_id) {
                        $notificationModel->createNotification($post_owner_id, $user_id, 'comment', htmlspecialchars($_SESSION['username']) . ' gönderinize yorum yaptı.', $post_id);
                    }

                    // Yeni eklenen yorumu çek ve HTML olarak döndür
                    $comment = $commentModel->getSingleCommentById($comment_id);

                    ob_start();
                    // comment_item_template.php'nin ihtiyaç duyduğu değişkenleri burada tanımlıyoruz
                    $comment_data = $comment; // Şablonda $comment olarak kullanılacak
                    $is_logged_in_for_comment = true;
                    $current_user_id_for_comment = $user_id;
                    $post_owner_id_for_comment = $post_owner_id;

                    include __DIR__ . '/../Views/components/comment_item_template.php';
                    $comment_html = ob_get_clean();

                    $response = ['success' => true, 'comment_html' => $comment_html];
                } else {
                    $response['message'] = 'Yorum eklenirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Bir gönderiye ait yorumları çeker ve HTML olarak döndürür.
     */
    public function get_comments()
    {
        header('Content-Type: text/html; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $post_id = (int) ($_GET['post_id'] ?? 0);
            $csrf_token = $_GET['csrf_token'] ?? '';

            if (!verify_csrf_token($csrf_token)) {
                echo '<p class="text-danger p-3">CSRF doğrulama başarısız.</p>';
                exit;
            }

            if ($post_id <= 0) {
                echo '<p class="text-danger p-3">Geçersiz gönderi ID.</p>';
                exit;
            }

            $commentModel = $this->model('CommentModel');
            $postModel = $this->model('PostModel');

            try {
                $post_comments = $commentModel->getCommentsForPost($post_id);
                $post_data = $postModel->getPostDetailsById($post_id);

                if (empty($post_comments)) {
                    echo '<p class="text-center text-muted p-3 no-comments">Henüz yorum yok.</p>';
                    exit;
                }

                foreach ($post_comments as $comment) {
                    // comment_item_template.php'nin ihtiyaç duyduğu değişkenleri burada tanımlıyoruz
                    $comment_data = $comment; // Şablonda $comment olarak kullanılacak
                    $is_logged_in_for_comment = isset($_SESSION['user_id']);
                    $current_user_id_for_comment = $_SESSION['user_id'] ?? null;
                    $post_owner_id_for_comment = $post_data['user_id'] ?? null;

                    include __DIR__ . '/../Views/components/comment_item_template.php';
                }

            } catch (Exception $e) {
                echo '<p class="text-danger p-3">Yorumlar yüklenirken bir hata oluştu: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p class="text-danger p-3">Geçersiz istek metodu.</p>';
        }
    }

    /**
     * AJAX: Gönderiyi silme işlemi.
     */
    public function delete_post()
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
            $post_id = (int) ($input_data['post_id'] ?? 0);

            if ($post_id <= 0) {
                $response['message'] = 'Geçersiz gönderi ID.';
                echo json_encode($response);
                exit;
            }

            $postModel = $this->model('PostModel');

            try {
                if ($postModel->deletePost($post_id, $user_id)) {
                    $response = ['success' => true, 'message' => 'Gönderi başarıyla silindi.'];
                } else {
                    $response['message'] = 'Gönderi silinemedi veya yetkiniz yok.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Gönderi açıklamasını güncelleme işlemi.
     */
    public function update_caption()
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
            $post_id = (int) ($input_data['post_id'] ?? 0);
            $new_caption = trim($input_data['new_caption'] ?? '');

            if ($post_id <= 0 || empty($new_caption)) {
                $response['message'] = 'Geçersiz veri.';
                echo json_encode($response);
                exit;
            }

            $postModel = $this->model('PostModel');

            try {
                if ($postModel->updatePostCaption($post_id, $user_id, $new_caption)) {
                    $response = ['success' => true, 'message' => 'Açıklama başarıyla güncellendi.', 'updated_caption_html' => linkify(htmlspecialchars($new_caption))];
                } else {
                    $response['message'] = 'Açıklama güncellenemedi veya yetkiniz yok.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Gönderiyi kaydetme veya kaydı geri çekme işlemi.
     */
    public function save()
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
            $post_id = (int) ($input_data['post_id'] ?? 0);

            if ($post_id <= 0) {
                $response['message'] = 'Geçersiz gönderi ID.';
                echo json_encode($response);
                exit;
            }

            $saveModel = $this->model('SaveModel');

            try {
                $is_currently_saved = $saveModel->isPostSavedByUser($user_id, $post_id);

                if ($is_currently_saved) {
                    $saveModel->unsavePost($user_id, $post_id);
                    $action_taken = 'unsaved';
                } else {
                    $saveModel->savePost($user_id, $post_id);
                    $action_taken = 'saved';
                }
                $response = ['success' => true, 'action' => $action_taken];

            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Sonsuz kaydırma için daha fazla gönderi yükler.
     */
    public function load_more()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $page = (int) ($_GET['page'] ?? 1);
            $context = $_GET['context'] ?? 'home'; // home, explore, profile
            $filter = $_GET['filter'] ?? 'new'; // new, popular
            $username = $_GET['username'] ?? ''; // Profil sayfası için

            $limit = 10;
            $offset = ($page - 1) * $limit;

            $postModel = $this->model('PostModel');
            $userModel = $this->model('UserModel'); // Profil bağlamında user_id almak için

            $current_user_id = $_SESSION['user_id'] ?? null;
            $posts = [];

            try {
                if ($context === 'home') {
                    $posts = $postModel->getFeedPosts($current_user_id, $limit, $offset);
                } elseif ($context === 'explore') {
                    // Explore için ayrı bir metot veya filtreleme mantığı eklenecek
                    // Şimdilik getFeedPosts'u genel olarak kullanabiliriz
                    $posts = $postModel->getFeedPosts($current_user_id, $limit, $offset); 
                } elseif ($context === 'profile' && !empty($username)) {
                    $profile_user_data = $userModel->findByUsername($username);
                    if ($profile_user_data) {
                        $posts = $postModel->getPostsByUserId($profile_user_data['id'], $current_user_id);
                        // Profil sayfasında sonsuz kaydırma için offset ve limit eklenmeli
                        // Şimdilik tüm postları çekiyor, bu da performans sorunu yaratabilir.
                    }
                }

                $post_html_array = [];
                foreach ($posts as $post) {
                    ob_start();
                    // post_card_feed.php'nin ihtiyaç duyduğu değişkenleri burada tanımlıyoruz
                    $post_data_for_card = $post;
                    $is_logged_in_for_card = isset($current_user_id);
                    $current_user_id_for_card = $current_user_id;
                    $page_name_for_card = $context; // page_name'i context'e göre ayarla

                    include __DIR__ . '/../Views/components/post_card_feed.php';
                    $post_html_array[] = ob_get_clean();
                }

                $response = ['success' => true, 'posts' => $post_html_array];

            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        } else {
            $response['message'] = 'Geçersiz istek metodu.';
        }
        echo json_encode($response);
    }
}
