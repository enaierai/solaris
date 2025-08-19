<?php

// public/ajax/post_handler.php

require_once __DIR__.'/../../includes/init.php';
include_once __DIR__.'/../../includes/models/PostModel.php';
include_once __DIR__.'/../../includes/models/UserModel.php';
include_once __DIR__.'/../../includes/models/NotificationModel.php';
include_once __DIR__.'/../../includes/models/CommentModel.php';

header('Content-Type: application/json');

// Hem GET (yorumları çek) hem de POST (diğer her şey) isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

// AKILLI VERİ ALMA BLOĞU
$input_data = json_decode(file_get_contents('php://input'), true);
if (empty($input_data)) {
    $input_data = $_REQUEST;
}

// CSRF ve Oturum kontrolü
if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF doğrulama başarısız.']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$action = $input_data['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'like':
            $post_id = intval($input_data['post_id'] ?? 0);
            if ($post_id <= 0) {
                throw new Exception('Geçersiz gönderi ID.');
            }
            $post_owner_id = getPostOwnerId($conn, $post_id);
            if ($post_owner_id && checkBlockStatus($conn, $user_id, $post_owner_id)) {
                throw new Exception('Bu kullanıcıyla etkileşime giremezsiniz.');
            }
            $liked_before = isPostLikedByUser($conn, $user_id, $post_id);
            if ($liked_before) {
                unlikePost($conn, $user_id, $post_id);
                deleteNotification($conn, $post_owner_id, $user_id, 'like', $post_id);
                $action_taken = 'unliked';
            } else {
                likePost($conn, $user_id, $post_id);
                if ($user_id != $post_owner_id) {
                    createNotification($conn, $post_owner_id, $user_id, 'like', htmlspecialchars($_SESSION['username']).' gönderini beğendi.', $post_id);
                }
                $action_taken = 'liked';
            }
            $new_likes_count = getLikeCount($conn, $post_id);
            echo json_encode(['success' => true, 'new_likes' => $new_likes_count, 'action' => $action_taken]);
            break;

        case 'add_comment':
            $post_id = intval($input_data['post_id'] ?? 0);
            $comment_text = trim($input_data['comment_text'] ?? '');
            if ($post_id <= 0) {
                throw new Exception('Geçersiz gönderi ID.');
            }
            if (empty($comment_text)) {
                throw new Exception('Yorum boş bırakılamaz.');
            }
            $post_owner_id = getPostOwnerId($conn, $post_id);
            if ($post_owner_id && checkBlockStatus($conn, $user_id, $post_owner_id)) {
                throw new Exception('Bu kullanıcıyla etkileşime giremezsiniz.');
            }
            $comment_id = createComment($conn, $post_id, $user_id, $comment_text);
            if ($comment_id) {
                if ($post_owner_id && $user_id != $post_owner_id) {
                    createNotification($conn, $post_owner_id, $user_id, 'comment', htmlspecialchars($_SESSION['username']).' gönderinize yorum yaptı.', $post_id);
                }
                $comment = getSingleCommentById($conn, $comment_id);
                $is_logged_in = true;
                $current_user_id = $user_id;
                ob_start();
                include __DIR__.'/../../includes/templates/comment_item_template.php';
                $comment_html = ob_get_clean();
                echo json_encode(['success' => true, 'comment_html' => $comment_html]);
            }
            break;

        case 'save':
            $post_id = intval($input_data['post_id'] ?? 0);
            if ($post_id <= 0) {
                throw new Exception('Geçersiz gönderi ID.');
            }
            $is_currently_saved = isPostSavedByUser($conn, $user_id, $post_id);
            if ($is_currently_saved) {
                unsavePostForUser($conn, $user_id, $post_id);
                $action_taken = 'unsaved';
            } else {
                savePostForUser($conn, $user_id, $post_id);
                $action_taken = 'saved';
            }
            echo json_encode(['success' => true, 'action' => $action_taken]);
            break;

        case 'delete_comment':
            $comment_id = intval($input_data['comment_id'] ?? 0);
            if (!deleteComment($conn, $comment_id, $user_id)) {
                throw new Exception('Yorum silinemedi veya yetkiniz yok.');
            }
            echo json_encode(['success' => true]);
            break;

        case 'get_comments':
            header('Content-Type: text/html; charset=utf-8');

            $post_id = intval($input_data['post_id'] ?? 0);
            if ($post_id <= 0) {
                exit('<p class="text-danger p-3">Geçersiz gönderi.</p>');
            }

            $post_comments = getCommentsForPost($conn, $post_id);

            // Değişkenleri şablon için hazırla
            $is_logged_in = true;
            $current_user_id = $_SESSION['user_id'];
            $post = getPostDetailsById($conn, $post_id, $current_user_id); // Şablonun tüm ihtiyaçları için tam veri
            $page_name = 'post'; // Şablonun içindeki mantığın çalışması için

            // Kutsal şablonumuzu burada da kullanıyoruz!
            // Ama sadece yorumlar ve form kısmını alacağız.
            ob_start();
            include __DIR__.'/../../includes/templates/post_interactive_section.php';
            $full_html = ob_get_clean();

            // Bize sadece #comments-postID içeriği lazım, onu DOM ile ayıklayalım
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$full_html);
            $comments_div = $dom->getElementById('comments-'.$post_id);
            echo $dom->saveHTML($comments_div);

            exit;

        case 'delete_post':
            $post_id = intval($input_data['post_id'] ?? 0);
            if (!deletePost($conn, $post_id, $user_id)) {
                throw new Exception('Gönderi silinemedi veya yetkiniz yok.');
            }
            echo json_encode(['success' => true, 'message' => 'Gönderi başarıyla silindi.']);
            break;

        case 'update_caption':
            $post_id = intval($input_data['post_id'] ?? 0);
            $new_caption = trim($input_data['new_caption'] ?? '');
            $updated_caption_html = updatePostCaption($conn, $post_id, $user_id, $new_caption);
            if ($updated_caption_html === false) {
                throw new Exception('Açıklama güncellenemedi veya yetkiniz yok.');
            }
            echo json_encode(['success' => true, 'updated_caption_html' => $updated_caption_html]);
            break;

        default:
            throw new Exception('Geçersiz eylem: '.htmlspecialchars($action));
            break;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
