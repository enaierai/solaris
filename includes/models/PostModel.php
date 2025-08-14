<?php

// includes/models/PostModel.php

include_once __DIR__.'/UserModel.php';

/**
 * Ana sayfa akışı için gönderileri tüm ilgili verilerle birlikte çeker.
 * Bu, video ve resim türlerini doğru şekilde işleyen güncellenmiş versiyondur.
 *
 * @param mysqli   $conn            Veritabanı bağlantı nesnesi
 * @param int|null $current_user_id Giriş yapmış kullanıcının ID'si, misafir ise null
 * @param array    $followed_ids    Giriş yapmış kullanıcının takip ettiği ID'ler
 *
 * @return array Gönderi verilerini içeren bir dizi
 */
function getFeedPosts($conn, $current_user_id, $followed_ids = [], $limit = 12, $offset = 0)
{
    $posts = [];
    $params = [];
    $param_types = '';

    // ANA SQL SORGUSU
    $sql = '
        SELECT 
            p.id AS post_id, p.user_id, p.caption, p.created_at, 
            u.username, u.profile_picture_url,
            p.likes AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    ';

    // DÜZELTME: Hem user_liked hem de user_saved bilgilerini ekliyoruz
    if ($current_user_id !== null) {
        $sql .= ', (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) > 0 AS user_liked';
        $sql .= ', (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) > 0 AS user_saved';

        // İki tane '?' için iki parametre ekliyoruz
        $params[] = $current_user_id;
        $params[] = $current_user_id;
        $param_types .= 'ii';
    }

    $sql .= ' FROM posts p JOIN users u ON p.user_id = u.id';

    // WHERE koşullarını bir dizide toplayalım
    $where_clauses = [];

    // Takip edilenler filtresi
    if ($current_user_id !== null && !empty($followed_ids)) {
        if (!in_array($current_user_id, $followed_ids)) {
            $followed_ids[] = $current_user_id;
        }
        $in_clause = implode(',', array_fill(0, count($followed_ids), '?'));
        $where_clauses[] = "p.user_id IN ($in_clause)";
        $params = array_merge($params, $followed_ids);
        $param_types .= str_repeat('i', count($followed_ids));
    }

    // Engellenenler filtresi
    if ($current_user_id) {
        $blocked_user_ids = getBlockedUserIds($conn, $current_user_id);
        if (!empty($blocked_user_ids)) {
            $blocked_in_clause = implode(',', array_fill(0, count($blocked_user_ids), '?'));
            $where_clauses[] = "p.user_id NOT IN ($blocked_in_clause)";
            $params = array_merge($params, $blocked_user_ids);
            $param_types .= str_repeat('i', count($blocked_user_ids));
        }
    }

    if (!empty($where_clauses)) {
        $sql .= ' WHERE '.implode(' AND ', $where_clauses);
    }

    $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';

    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($param_types)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $post_ids = [];
        while ($row = $result->fetch_assoc()) {
            if ($current_user_id === null) {
                $row['user_liked'] = false;
                $row['user_saved'] = false; // Giriş yapmamış kullanıcı için de ekliyoruz
            }
            $row['media'] = [];
            $posts[$row['post_id']] = $row;
            $post_ids[] = $row['post_id'];
        }
        $stmt->close();
    }

    if (empty($post_ids)) {
        return [];
    }

    // Medyaları çekme kısmı (değişiklik yok)
    $media_sql = 'SELECT post_id, image_url, media_type FROM post_media WHERE post_id IN ('.implode(',', array_fill(0, count($post_ids), '?')).') ORDER BY id ASC';
    $media_stmt = $conn->prepare($media_sql);
    $media_stmt->bind_param(str_repeat('i', count($post_ids)), ...$post_ids);
    $media_stmt->execute();
    $media_result = $media_stmt->get_result();

    while ($media_row = $media_result->fetch_assoc()) {
        $posts[$media_row['post_id']]['media'][] = ['url' => $media_row['image_url'], 'type' => $media_row['media_type']];
    }
    $media_stmt->close();

    return array_values($posts);
}

function getPostsByUserId($conn, $user_id)
{
    $posts = [];
    $stmt = $conn->prepare('
        SELECT 
            p.id AS post_id, 
            p.likes AS likes_count, 
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
            (SELECT image_url FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS image_url,
            (SELECT media_type FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS media_type,
            (SELECT COUNT(*) FROM post_media WHERE post_id = p.id) AS media_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();

    return $posts;
}

function getPostOwnerId($conn, $post_id)
{
    $stmt = $conn->prepare('SELECT user_id FROM posts WHERE id = ?');
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? $result['user_id'] : null;
}

function isPostLikedByUser($conn, $user_id, $post_id)
{
    $stmt = $conn->prepare('SELECT id FROM likes WHERE user_id = ? AND post_id = ?');
    $stmt->bind_param('ii', $user_id, $post_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

function likePost($conn, $user_id, $post_id)
{
    $update_stmt = $conn->prepare('UPDATE posts SET likes = likes + 1 WHERE id = ?');
    $update_stmt->bind_param('i', $post_id);
    $update_stmt->execute();

    $insert_stmt = $conn->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
    $insert_stmt->bind_param('ii', $user_id, $post_id);

    return $insert_stmt->execute();
}

function unlikePost($conn, $user_id, $post_id)
{
    $update_stmt = $conn->prepare('UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id = ?');
    $update_stmt->bind_param('i', $post_id);
    $update_stmt->execute();

    $delete_stmt = $conn->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
    $delete_stmt->bind_param('ii', $user_id, $post_id);

    return $delete_stmt->execute();
}

function getLikeCount($conn, $post_id)
{
    $stmt = $conn->prepare('SELECT likes FROM posts WHERE id = ?');
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? $result['likes'] : 0;
}

function searchPostsByCaption($conn, $query)
{
    $posts = [];
    $search_param = '%'.$query.'%';
    $stmt = $conn->prepare('
        SELECT 
            p.id as post_id, 
            u.username, 
            u.profile_picture_url AS user_profile_picture_url,
            (SELECT image_url FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) as image_url
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.caption LIKE ? 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ');
    $stmt->bind_param('s', $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();

    return $posts;
}

/**
 * Keşfet sayfası için gönderileri çeker.
 */
function getExplorePosts($conn, $filter = 'new', $current_user_id = null, $limit = 24, $offset = 0)
{
    $posts = [];
    $sql_base = '
        SELECT 
            p.id AS post_id, p.caption,
            (SELECT image_url FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS first_media_url,
            (SELECT media_type FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS first_media_type,
            (SELECT COUNT(*) FROM post_media WHERE post_id = p.id) AS media_count
        FROM posts p
    ';

    $sql_join = ' JOIN users u ON p.user_id = u.id ';
    $sql_where = ' WHERE 1=1 ';
    $sql_order = '';
    $params = [];
    $param_types = '';

    // DÜZELTME: Parametreler DOĞRU SIRAYLA ekleniyor.
    switch ($filter) {
        case 'popular':
            $sql_order = ' ORDER BY p.likes DESC, p.created_at DESC ';
            break;
        case 'following':
            if ($current_user_id !== null) {
                $sql_join .= ' JOIN follows f ON f.following_id = p.user_id AND f.follower_id = ? ';
                $params[] = $current_user_id;
                $param_types .= 'i';
            }
            $sql_order = ' ORDER BY p.created_at DESC ';
            break;
        case 'video':
            $sql_where = ' WHERE EXISTS (SELECT 1 FROM post_media pm WHERE pm.post_id = p.id AND pm.media_type = "video") ';
            $sql_order = ' ORDER BY p.created_at DESC ';
            break;
        case 'new':
        default:
            $sql_order = ' ORDER BY p.created_at DESC ';
            break;
    }

    $final_sql = $sql_base.$sql_join.$sql_where.$sql_order.' LIMIT ? OFFSET ?';

    // Limit ve Offset en sona ekleniyor.
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = $conn->prepare($final_sql);
    if ($stmt) {
        if (!empty($param_types)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        $stmt->close();
    }

    return $posts;
}
/**
 * YENİ FONKSİYON 1: Bir metindeki hashtag'leri ayıklar.
 *
 * @param string $text Gönderi açıklaması
 *
 * @return array Ayıklanmış hashtag'lerin bir dizisi (örn: ['seyahat', 'doğa'])
 */
function extractHashtags($text)
{
    preg_match_all('/#(\w+)/u', $text, $matches);

    return $matches[1]; // Sadece etiket isimlerini (hash işareti olmadan) döndür
}

/**
 * YENİ FONKSİYON 2: Bir gönderi için etiketleri işler ve veritabanına kaydeder.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $post_id İşlem yapılacak gönderinin ID'si
 * @param string $caption Gönderi açıklaması
 */
function processPostTags($conn, $post_id, $caption)
{
    $tags = extractHashtags($caption);
    if (empty($tags)) {
        return; // Etiket yoksa işlem yapma
    }

    // Önce bu gönderiye ait eski etiket bağlantılarını temizle (güncelleme durumları için)
    $delete_stmt = $conn->prepare('DELETE FROM post_tags WHERE post_id = ?');
    $delete_stmt->bind_param('i', $post_id);
    $delete_stmt->execute();

    foreach ($tags as $tagName) {
        $tagName = strtolower($tagName); // Etiketleri küçük harfe çevirerek tutarlılığı sağla

        // 1. Etiket 'tags' tablosunda var mı diye kontrol et
        $tag_stmt = $conn->prepare('SELECT id FROM tags WHERE name = ?');
        $tag_stmt->bind_param('s', $tagName);
        $tag_stmt->execute();
        $result = $tag_stmt->get_result();
        $tag_id = null;

        if ($result->num_rows > 0) {
            // Etiket varsa, ID'sini al
            $tag_id = $result->fetch_assoc()['id'];
        } else {
            // Etiket yoksa, 'tags' tablosuna ekle ve yeni ID'sini al
            $insert_tag_stmt = $conn->prepare('INSERT INTO tags (name) VALUES (?)');
            $insert_tag_stmt->bind_param('s', $tagName);
            if ($insert_tag_stmt->execute()) {
                $tag_id = $conn->insert_id;
            }
        }

        // 2. 'post_tags' tablosuna gönderi ve etiket bağlantısını ekle
        if ($tag_id) {
            $insert_post_tag_stmt = $conn->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
            // Olası "duplicate key" hatalarını görmezden gel
            if ($insert_post_tag_stmt) {
                $insert_post_tag_stmt->bind_param('ii', $post_id, $tag_id);
                $insert_post_tag_stmt->execute();
            }
        }
    }
}
/**
 * YENİ FONKSİYON: Kullanıcı bir gönderiyi beğendiğinde, o gönderinin etiketlerine
 * olan ilgi puanını artırır.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $user_id Beğeniyi yapan kullanıcı ID'si
 * @param int    $post_id Beğenilen gönderi ID'si
 */
function updateUserTagInteractionsOnLike($conn, $user_id, $post_id)
{
    // 1. Beğenilen gönderiye ait tüm etiket ID'lerini bul
    $tag_ids = [];
    $stmt = $conn->prepare('SELECT tag_id FROM post_tags WHERE post_id = ?');
    $stmt->bind_param('i', $post_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $tag_ids[] = $row['tag_id'];
        }
    }
    $stmt->close();

    if (empty($tag_ids)) {
        return; // Gönderinin etiketi yoksa işlem yapma
    }

    // 2. Her bir etiket için kullanıcının ilgi puanını artır
    // ON DUPLICATE KEY UPDATE sayesinde, kayıt varsa puanı artırır, yoksa yeni kayıt oluşturur.
    $sql = 'INSERT INTO user_tag_interactions (user_id, tag_id, interaction_score) VALUES ';
    $placeholders = [];
    $params = [];
    $param_types = '';

    foreach ($tag_ids as $tag_id) {
        $placeholders[] = '(?, ?, 1)'; // Beğeni için +1 puan
        $params[] = $user_id;
        $params[] = $tag_id;
        $param_types .= 'ii';
    }

    $sql .= implode(', ', $placeholders);
    $sql .= ' ON DUPLICATE KEY UPDATE interaction_score = interaction_score + 1, last_interacted_at = NOW()';

    $update_stmt = $conn->prepare($sql);
    if ($update_stmt) {
        $update_stmt->bind_param($param_types, ...$params);
        $update_stmt->execute();
        $update_stmt->close();
    }
}
/**
 * YENİ FONKSİYON: Kullanıcı için kişiselleştirilmiş bir "Senin İçin" akışı oluşturur.
 * Gönderileri, kullanıcının etiketlere olan ilgisine, gönderinin popülerliğine ve
 * yeniliğine göre bir "alaka puanı" ile sıralar.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $user_id Akışın oluşturulacağı kullanıcı ID'si
 * @param int    $limit   Sayfa başına gönderi sayısı
 * @param int    $offset  Atlanacak gönderi sayısı
 *
 * @return array Kişiselleştirilmiş gönderi dizisi
 */
function getForYouFeed($conn, $user_id, $limit = 24, $offset = 0)
{
    $posts = [];

    // Bu sorgu, her gönderi için bir "alaka puanı" hesaplar.
    // Puanlama Mantığı:
    // 1. Kullanıcının etkileşimde bulunduğu her etiket için +50 puan.
    // 2. Gönderinin her beğenisi için +1 puan.
    // 3. Son 3 günde atılan gönderiler için ekstra +20 "yenilik" puanı.
    $sql = '
        SELECT 
            p.id AS post_id,
            p.caption,
            (SELECT image_url FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS first_media_url,
            (SELECT media_type FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS first_media_type,
            (SELECT COUNT(*) FROM post_media WHERE post_id = p.id) AS media_count,
            (
                -- İlgi Alanı Puanı
                IFNULL((
                    SELECT SUM(uti.interaction_score) * 50
                    FROM post_tags pt
                    JOIN user_tag_interactions uti ON pt.tag_id = uti.tag_id
                    WHERE pt.post_id = p.id AND uti.user_id = ?
                ), 0)
                
                +

                -- Popülerlik Puanı (Beğeni Sayısı)
                p.likes
                
                +

                -- Yenilik Puanı
                IF(p.created_at >= NOW() - INTERVAL 3 DAY, 20, 0)

            ) AS relevance_score
        FROM posts p
        WHERE 
            p.user_id != ? -- Kendi gönderilerini gösterme
        ORDER BY 
            relevance_score DESC, p.created_at DESC
        LIMIT ? OFFSET ?
    ';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $user_id, $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();

    return $posts;
}
/**
 * YENİ FONKSİYON: Bir gönderiyi kullanıcı için kaydeder.
 */
function savePostForUser($conn, $user_id, $post_id)
{
    $stmt = $conn->prepare('INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user_id, $post_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON: Bir gönderinin kullanıcı tarafından kaydını kaldırır.
 */
function unsavePostForUser($conn, $user_id, $post_id)
{
    $stmt = $conn->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?');
    $stmt->bind_param('ii', $user_id, $post_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON: Bir gönderinin kullanıcı tarafından kaydedilip kaydedilmediğini kontrol eder.
 */
function isPostSavedByUser($conn, $user_id, $post_id)
{
    $stmt = $conn->prepare('SELECT user_id FROM saved_posts WHERE user_id = ? AND post_id = ?');
    $stmt->bind_param('ii', $user_id, $post_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}
/**
 * YENİ FONKSİYON: Belirli bir kullanıcının kaydettiği tüm gönderileri getirir.
 */
function getSavedPostsByUser($conn, $user_id)
{
    $posts = [];
    $stmt = $conn->prepare('
        SELECT 
            p.id AS post_id,
            p.likes AS likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
            (SELECT image_url FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS image_url,
            (SELECT media_type FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) AS media_type,
            (SELECT COUNT(*) FROM post_media WHERE post_id = p.id) AS media_count
        FROM saved_posts sp
        JOIN posts p ON sp.post_id = p.id
        WHERE sp.user_id = ?
        ORDER BY sp.saved_at DESC
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();

    return $posts;
}
/**
 * YENİ FONKSİYON: En çok kullanılan etiketleri (popüler etiketleri) getirir.
 *
 * @param mysqli $conn  Veritabanı bağlantı nesnesi
 * @param int    $limit Getirilecek etiket sayısı
 *
 * @return array Popüler etiketleri ve sayılarını içeren bir dizi
 */
function getPopularTags($conn, $limit = 10)
{
    $tags = [];
    $sql = '
        SELECT 
            t.name, 
            COUNT(pt.tag_id) as tag_count
        FROM post_tags pt
        JOIN tags t ON pt.tag_id = t.id
        GROUP BY 
            pt.tag_id
        ORDER BY 
            tag_count ASC
        LIMIT ?
    ';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    $stmt->close();

    return $tags;
}
/**
 * YENİ FONKSİYON: ID'ye göre tek bir gönderinin tüm detaylarını getirir.
 * Yazar bilgisi, beğeni/yorum sayısı ve mevcut kullanıcının etkileşimlerini içerir.
 */
function getPostDetailsById($conn, $post_id, $current_user_id = null)
{
    $sql = '
        SELECT 
            p.id AS post_id, p.user_id, p.caption, p.created_at,
            u.username, u.profile_picture_url,
            p.likes AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    ';
    if ($current_user_id) {
        $sql .= ', (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) > 0 AS user_liked';
        $sql .= ', (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) > 0 AS user_saved';
    }
    $sql .= ' FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?';

    $stmt = $conn->prepare($sql);
    if ($current_user_id) {
        $stmt->bind_param('iii', $current_user_id, $current_user_id, $post_id);
    } else {
        $stmt->bind_param('i', $post_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();

    // === YENİ KONTROL: Gönderi sahibi engellenmiş mi? ===
    if ($current_user_id) {
        $is_blocked = checkBlockStatus($conn, $current_user_id, $post['user_id']);
        if ($is_blocked) {
            return null; // Eğer gönderi sahibi engellenmişse, gönderi "yokmuş" gibi davran.
        }
    }
    // ===================================================

    // Eğer giriş yapılmamışsa, etkileşim alanlarını varsayılan olarak false yap
    if (!$current_user_id && $post) {
        $post['user_liked'] = false;
        $post['user_saved'] = false;
    }

    return $post;
}

/**
 * YENİ FONKSİYON: Bir gönderiye ait tüm medya dosyalarını getirir.
 */
function getMediaForPost($conn, $post_id)
{
    $media = [];
    $stmt = $conn->prepare('SELECT image_url, media_type FROM post_media WHERE post_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $media[] = $row;
    }
    $stmt->close();

    return $media;
}
/**
 * YENİ FONKSİYON: Veritabanına yeni bir gönderi, medyaları ve etiketleri ile birlikte ekler.
 * Tüm işlemleri bir transaction içinde yaparak veri bütünlüğünü sağlar.
 *
 * @param mysqli $conn           Veritabanı bağlantı nesnesi
 * @param int    $user_id        Gönderiyi oluşturan kullanıcı ID'si
 * @param string $caption        Gönderi açıklaması
 * @param array  $uploaded_files Yüklenen dosyaların bilgilerini içeren dizi
 *
 * @return int|false başarılıysa yeni gönderinin ID'sini, değilse false döner
 */
function createPost($conn, $user_id, $caption, $uploaded_files = [])
{
    // Veri bütünlüğü için transaction başlat
    $conn->begin_transaction();

    try {
        // 1. Ana gönderiyi 'posts' tablosuna ekle
        $stmt = $conn->prepare('INSERT INTO posts (user_id, caption) VALUES (?, ?)');
        if (!$stmt) {
            throw new Exception('Sorgu hazırlanamadı: '.$conn->error);
        }
        $stmt->bind_param('is', $user_id, $caption);
        if (!$stmt->execute()) {
            throw new Exception('Gönderi eklenemedi: '.$stmt->error);
        }
        $post_id = $conn->insert_id;
        $stmt->close();

        // 2. Medya dosyalarını 'post_media' tablosuna ekle
        if (!empty($uploaded_files)) {
            $media_stmt = $conn->prepare('INSERT INTO post_media (post_id, image_url, media_type) VALUES (?, ?, ?)');
            if (!$media_stmt) {
                throw new Exception('Medya sorgusu hazırlanamadı: '.$conn->error);
            }
            foreach ($uploaded_files as $file) {
                $media_stmt->bind_param('iss', $post_id, $file['url'], $file['type']);
                if (!$media_stmt->execute()) {
                    throw new Exception('Medya eklenemedi: '.$media_stmt->error);
                }
            }
            $media_stmt->close();
        }

        // 3. Etiketleri işle (zaten var olan fonksiyonumuzu çağırıyoruz)
        processPostTags($conn, $post_id, $caption);

        // Her şey yolundaysa, değişiklikleri onayla
        $conn->commit();

        return $post_id;
    } catch (Exception $e) {
        // Herhangi bir hata olursa, tüm değişiklikleri geri al
        $conn->rollback();
        error_log($e->getMessage()); // Hataları log dosyasına yaz

        return false;
    }
}
/**
 * YENİ FONKSİYON: Bir gönderiyi şikayet olarak kaydeder.
 */
function reportPost($conn, $post_id, $reporter_id, $reason)
{
    $stmt = $conn->prepare("INSERT INTO reports (reporter_user_id, reported_content_type, reported_content_id, reason) VALUES (?, 'post', ?, ?)");
    $stmt->bind_param('iis', $reporter_id, $post_id, $reason);

    return $stmt->execute();
}
/**
 * YENİ FONKSİYON: Mesaj içinde önizleme göstermek için bir gönderinin
 * temel bilgilerini (resim, başlık, kullanıcı adı) getirir.
 */
function getPostPreviewById($conn, $post_id)
{
    $sql = '
        SELECT 
            p.id, p.caption, u.username,
            (SELECT image_url FROM post_media WHERE post_id = p.id ORDER BY id ASC LIMIT 1) as image_url
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_preview = $result->fetch_assoc();
    $stmt->close();

    return $post_preview;
}
