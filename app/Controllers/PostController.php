<?php

class PostController extends Controller
{
    /**
     * Gönderi detay sayfasını gösterir.
     * URL'den gelen gönderi ID'sini parametre olarak alır.
     * Örnek: /post/123.
     */
    public function index($post_id = 0)
    {
        // Eğer gönderi ID'si belirtilmemişse veya geçersizse ana sayfaya yönlendir
        if ($post_id <= 0) {
            header('Location: '.BASE_URL);
            exit;
        }

        // Gerekli modelleri yükle
        $postModel = $this->model('PostModel');
        $commentModel = $this->model('CommentModel'); // Yorumlar için CommentModel'i yükle
        $userModel = $this->model('UserModel'); // Avatar için UserModel'i yükle

        $current_user_id = $_SESSION['user_id'] ?? null;
        $is_logged_in = isset($current_user_id);

        // Gönderi verilerini çek
        $post_data = $postModel->getPostById((int) $post_id, $current_user_id); // post_id'yi int'e cast et

        // Eğer gönderi bulunamazsa, 404 Not Found sayfası göster
        if (!$post_data) {
            $this->view('pages/errors/404');

            return;
        }

        // Gönderi sahibinin ID'si
        $post_owner_id = $post_data['user_id'];
        $is_owner = ($is_logged_in && $current_user_id == $post_owner_id);

        // Yorumları çek (Bu kısım artık AJAX ile yükleneceği için burada çekmiyoruz, sadece JS'e bırakıyoruz)
        // $post_comments = $commentModel->getCommentsByPostId((int)$post_id, $current_user_id);

        // View'a gönderilecek tüm verileri tek bir dizide topla
        $data = [
            'meta' => [
                'meta_title' => 'Gönderi - '.htmlspecialchars(substr($post_data['caption'] ?? 'Gönderi', 0, 50)).' - Solaris',
                'meta_description' => htmlspecialchars(substr($post_data['caption'] ?? 'Gönderi', 0, 150)),
            ],
            'post_data' => $post_data,
            'post_comments' => [], // Yorumları burada boş gönderiyoruz, AJAX ile yüklenecek
            'is_owner' => $is_owner,
            'is_logged_in' => $is_logged_in,
            'current_user_id' => $current_user_id,
        ];

        $this->view('layouts/header', $data);
        $this->view('pages/post', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * AJAX: Gönderiyi beğenme/beğenmekten vazgeçme.
     */
    public function like()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
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
                $response['message'] = 'Geçersiz gönderi ID\'si.';
                echo json_encode($response);
                exit;
            }

            $postModel = $this->model('PostModel');
            $notificationModel = $this->model('NotificationModel'); // Bildirim için

            try {
                if ($postModel->isLiked($user_id, $post_id)) {
                    // Zaten beğenmişse beğeniyi kaldır
                    if ($postModel->unlikePost($user_id, $post_id)) {
                        $new_likes = $postModel->getLikeCount($post_id);
                        $response = ['success' => true, 'action' => 'unliked', 'new_likes' => $new_likes];
                    } else {
                        $response['message'] = 'Beğeni kaldırılamadı.';
                    }
                } else {
                    // Beğenmemişse beğen
                    if ($postModel->likePost($user_id, $post_id)) {
                        $new_likes = $postModel->getLikeCount($post_id);
                        $response = ['success' => true, 'action' => 'liked', 'new_likes' => $new_likes];

                        // Bildirim oluştur
                        $post_owner_id = $postModel->getPostOwnerId($post_id);
                        if ($post_owner_id && $post_owner_id != $user_id) {
                            $notificationModel->createNotification($post_owner_id, $user_id, $post_id, 'like');
                        }
                    } else {
                        $response['message'] = 'Gönderi beğenilemedi.';
                    }
                }
            } catch (Exception $e) {
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Gönderiyi kaydetme/kaydetmekten vazgeçme.
     */
    public function save()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
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
                $response['message'] = 'Geçersiz gönderi ID\'si.';
                echo json_encode($response);
                exit;
            }

            $saveModel = $this->model('SaveModel'); // SaveModel'i yükle

            try {
                if ($saveModel->isSaved($user_id, $post_id)) {
                    // Zaten kaydetmişse kaydı kaldır
                    if ($saveModel->unsavePost($user_id, $post_id)) {
                        $response = ['success' => true, 'action' => 'unsaved', 'message' => 'Gönderi kaydedilenlerden çıkarıldı.'];
                    } else {
                        $response['message'] = 'Kaydetme kaldırılamadı.';
                    }
                } else {
                    // Kaydetmemişse kaydet
                    if ($saveModel->savePost($user_id, $post_id)) {
                        $response = ['success' => true, 'action' => 'saved', 'message' => 'Gönderi kaydedildi.'];
                    } else {
                        $response['message'] = 'Gönderi kaydedilemedi.';
                    }
                }
            } catch (Exception $e) {
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Yorum ekleme.
     */
    public function add_comment()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
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

            if ($post_id <= 0 || empty($comment_text)) {
                $response['message'] = 'Geçersiz gönderi ID\'si veya yorum metni boş.';
                echo json_encode($response);
                exit;
            }

            $commentModel = $this->model('CommentModel');
            $postModel = $this->model('PostModel'); // Yorum sayısını güncellemek için
            $userModel = $this->model('UserModel'); // Yorumu yapan kullanıcının bilgilerini almak için
            $notificationModel = $this->model('NotificationModel'); // Bildirim için

            try {
                $comment_id = $commentModel->addComment($user_id, $post_id, $comment_text);
                if ($comment_id) {
                    // Yorum sayısını güncelle
                    $postModel->updateCommentCount($post_id, 1);

                    // Yeni yorumun HTML'ini oluşturmak için verileri çek
                    $new_comment_data = $commentModel->getCommentById($comment_id);
                    $comment_user_data = $userModel->findById($new_comment_data['user_id']);

                    // comment_item_template.php'yi kullanarak HTML'i render et
                    ob_start(); // Çıktı tamponlamayı başlat
                    $this->view('components/comment_item_template', [
                        'comment' => [
                            'id' => $new_comment_data['id'],
                            'user_id' => $comment_user_data['id'],
                            'username' => $comment_user_data['username'],
                            'profile_picture_url' => $comment_user_data['profile_picture_url'],
                            'comment_text' => htmlspecialchars($new_comment_data['comment_text']),
                            'created_at' => $new_comment_data['created_at'],
                        ],
                        'is_logged_in' => true, // Yorumu yapan kişi giriş yapmış
                        'current_user_id' => $user_id,
                        'post_owner_id' => $postModel->getPostOwnerId($post_id),
                    ]);
                    $comment_html = ob_get_clean(); // Tamponlanan çıktıyı al ve temizle

                    $response = [
                        'success' => true,
                        'message' => 'Yorum başarıyla eklendi.',
                        'comment_html' => $comment_html,
                        // post.js'deki TypeError için gerekli olan profile_picture_url'i doğrudan ekleyelim
                        'comment' => [
                            'username' => $comment_user_data['username'],
                            'profile_picture_url' => getUserAvatar($comment_user_data['username'], $comment_user_data['profile_picture_url']),
                            'comment_text' => htmlspecialchars($new_comment_data['comment_text']),
                            'time_ago' => time_ago(strtotime($new_comment_data['created_at'])),
                        ],
                    ];

                    // Bildirim oluştur
                    $post_owner_id = $postModel->getPostOwnerId($post_id);
                    if ($post_owner_id && $post_owner_id != $user_id) {
                        $notificationModel->createNotification($post_owner_id, $user_id, $post_id, 'comment', $comment_text);
                    }
                } else {
                    $response['message'] = 'Yorum eklenirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Gönderi yorumlarını HTML olarak döndürür.
     */
    public function get_comments()
    {
        // header('Content-Type: application/json'); // Artık HTML döndüreceğiz

        // JSON değil, HTML dönecek, bu yüzden $response dizisini kullanmayacağız.
        // Hata durumunda doğrudan HTML hata mesajı basacağız.

        if ($_SERVER['REQUEST_METHOD'] === 'GET') { // GET isteği olarak alıyoruz
            $input_data = $_GET;

            // CSRF kontrolü (GET isteklerinde genellikle daha gevşek olabilir, ama güvenlik için tutmak iyi)
            // Ancak AJAX ile çekilen yorumlar için CSRF token'ı URL'de taşınabilir.
            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                echo '<p class="text-center text-danger p-3">Güvenlik hatası. Yorumlar yüklenemedi.</p>';
                exit;
            }

            $post_id = (int) ($input_data['post_id'] ?? 0);

            if ($post_id <= 0) {
                echo '<p class="text-center text-danger p-3">Geçersiz gönderi ID\'si.</p>';
                exit;
            }

            $commentModel = $this->model('CommentModel');
            $postModel = $this->model('PostModel'); // Post sahibini belirlemek için

            $current_user_id = $_SESSION['user_id'] ?? null;
            $is_logged_in = isset($current_user_id);
            $post_owner_id = $postModel->getPostOwnerId($post_id);

            try {
                $comments = $commentModel->getCommentsByPostId($post_id, $current_user_id);

                // Debugging için yorum sayısını logla
                error_log('get_comments: Fetched '.count($comments).' comments for post_id: '.$post_id);

                if (empty($comments)) {
                    echo '<p class="text-center text-muted no-comments">Henüz yorum yok.</p>';
                } else {
                    foreach ($comments as $comment) {
                        $this->view('components/comment_item_template', [
                            'comment' => $comment,
                            'is_logged_in' => $is_logged_in,
                            'current_user_id' => $current_user_id,
                            'post_owner_id' => $post_owner_id,
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Hata durumunda daha detaylı bilgi loglayalım
                error_log('get_comments error: '.$e->getMessage().' on post_id: '.$post_id);
                echo '<p class="text-center text-danger p-3">Yorumlar yüklenirken sunucu hatası oluştu: '.htmlspecialchars($e->getMessage()).'</p>';
            }
        } else {
            echo '<p class="text-center text-danger p-3">Geçersiz istek metodu.</p>';
        }
        exit; // Sadece HTML çıktısı verilecek
    }

    /**
     * AJAX: Yorum silme.
     */
    public function delete_comment()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try { // Tüm mantığı try-catch içine alalım
                $input_data = $_POST;

                if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                    $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
                    echo json_encode($response);
                    exit;
                }
                if (!isset($_SESSION['user_id'])) {
                    $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                    echo json_encode($response);
                    exit;
                }

                $user_id = $_SESSION['user_id'];
                $comment_id = (int) ($input_data['comment_id'] ?? 0);

                if ($comment_id <= 0) {
                    $response['message'] = 'Geçersiz yorum ID\'si.';
                    echo json_encode($response);
                    exit;
                }

                $commentModel = $this->model('CommentModel');
                $postModel = $this->model('PostModel'); // Yorum sayısını güncellemek için

                $comment_data = $commentModel->getCommentById($comment_id);
                if (!$comment_data) {
                    $response['message'] = 'Yorum bulunamadı.';
                    echo json_encode($response);
                    exit;
                }

                // Yorumu silme yetkisi kontrolü: Yorum sahibi veya gönderi sahibi silebilir
                $post_owner_id = $postModel->getPostOwnerId($comment_data['post_id']);
                if ($user_id !== $comment_data['user_id'] && $user_id !== $post_owner_id) {
                    $response['message'] = 'Bu yorumu silme yetkiniz yok.';
                    echo json_encode($response);
                    exit;
                }

                if ($commentModel->deleteComment($comment_id)) {
                    // Yorum sayısını güncelle
                    $postModel->updateCommentCount($comment_data['post_id'], -1);
                    $response = ['success' => true, 'message' => 'Yorum başarıyla silindi.'];
                } else {
                    $response['message'] = 'Yorum silinirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                // Herhangi bir PHP hatasını yakala ve JSON olarak döndür
                error_log('delete_comment error: '.$e->getMessage()); // Hatayı logla
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
        exit; // Her zaman JSON çıktısı verdiğimizden emin olalım
    }

    /**
     * AJAX: Gönderi açıklamasını güncelleme.
     */
    public function update_caption()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
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

            if ($post_id <= 0) {
                $response['message'] = 'Geçersiz gönderi ID\'si.';
                echo json_encode($response);
                exit;
            }

            $postModel = $this->model('PostModel');

            try {
                $post_owner_id = $postModel->getPostOwnerId($post_id);
                if ($post_owner_id !== $user_id) {
                    $response['message'] = 'Bu gönderiyi düzenleme yetkiniz yok.';
                    echo json_encode($response);
                    exit;
                }

                if ($postModel->updatePostCaption($post_id, $new_caption)) {
                    $response = [
                        'success' => true,
                        'message' => 'Açıklama başarıyla güncellendi.',
                        'updated_caption_html' => linkify(htmlspecialchars($new_caption)),
                    ];
                } else {
                    $response['message'] = 'Açıklama güncellenirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Gönderiyi silme.
     */
    public function delete_post()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try { // Tüm mantığı try-catch içine alalım
                $input_data = $_POST;

                if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                    $response['message'] = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
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
                    $response['message'] = 'Geçersiz gönderi ID\'si.';
                    echo json_encode($response);
                    exit;
                }

                $postModel = $this->model('PostModel');

                $post_owner_id = $postModel->getPostOwnerId($post_id);
                if ($post_owner_id !== $user_id) {
                    $response['message'] = 'Bu gönderiyi silme yetkiniz yok.';
                    echo json_encode($response);
                    exit;
                }

                if ($postModel->deletePost($post_id)) {
                    $response = ['success' => true, 'message' => 'Gönderi başarıyla silindi.'];
                } else {
                    $response['message'] = 'Gönderi silinirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                error_log('delete_post error: '.$e->getMessage()); // Hatayı logla
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
        exit; // Her zaman JSON çıktısı verdiğimizden emin olalım
    }

    /**
     * AJAX: Daha fazla gönderi yükleme (sonsuz kaydırma için).
     */
    public function load_more()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.', 'posts' => []];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            try { // Tüm mantığı try-catch içine alalım
                $input_data = $_GET;

                $page = (int) ($input_data['page'] ?? 1);
                $context = $input_data['context'] ?? 'home'; // 'home', 'profile', 'explore', 'saved_posts'
                $filter = $input_data['filter'] ?? 'new'; // 'new', 'popular'
                $username = $input_data['username'] ?? ''; // Profil sayfası için

                $current_user_id = $_SESSION['user_id'] ?? null;
                $is_logged_in = isset($current_user_id);

                $postModel = $this->model('PostModel');
                $userModel = $this->model('UserModel');

                $limit = 9; // Her seferinde yüklenecek gönderi sayısı
                $offset = ($page - 1) * $limit;

                $posts = [];
                $total_posts = 0;

                switch ($context) {
                    case 'home':
                        $posts = $postModel->getFeedPosts($current_user_id, $limit, $offset, $filter);
                        $total_posts = $postModel->getFeedPostsCount($current_user_id, $filter);
                        break;
                    case 'profile':
                        $profile_user_id = null;
                        if (!empty($username)) {
                            $user_data = $userModel->findByUsername($username);
                            if ($user_data) {
                                $profile_user_id = $user_data['id'];
                            }
                        }
                        if ($profile_user_id) {
                            $posts = $postModel->getPostsByUserId($profile_user_id, $current_user_id, $limit, $offset);
                            $total_posts = $postModel->getPostsByUserIdCount($profile_user_id);
                        }
                        break;
                    case 'explore':
                        $posts = $postModel->getExplorePosts($current_user_id, $limit, $offset);
                        $total_posts = $postModel->getExplorePostsCount($current_user_id);
                        break;
                    case 'saved_posts':
                        if ($is_logged_in) {
                            $posts = $postModel->getSavedPostsByUser($current_user_id, $limit, $offset);
                            $total_posts = $postModel->getSavedPostsByUserCount($current_user_id);
                        }
                        break;
                }

                $post_html_array = [];
                foreach ($posts as $post) {
                    ob_start();
                    $this->view('components/post_card_feed', [
                        'post' => $post,
                        'is_logged_in' => $is_logged_in,
                        'current_user_id' => $current_user_id,
                    ]);
                    $post_html_array[] = ob_get_clean();
                }

                $response['success'] = true;
                $response['posts'] = $post_html_array;
                $response['has_more'] = ($offset + count($posts)) < $total_posts;
            } catch (Exception $e) {
                error_log('load_more error: '.$e->getMessage()); // Hatayı logla
                $response['message'] = 'Sunucu hatası: '.$e->getMessage();
            }
        }
        echo json_encode($response);
        exit; // Her zaman JSON çıktısı verdiğimizden emin olalım
    }
}
