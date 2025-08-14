<?php

// includes/models/UserModel.php

/**
 * Verilen kullanıcı adı veya e-posta adresine sahip kullanıcıyı veritabanından bulur.
 *
 * @param mysqli $conn              Veritabanı bağlantı nesnesi
 * @param string $username_or_email Kullanıcının girdiği kullanıcı adı veya e-posta
 *
 * @return array|null kullanıcı bulunduysa verilerini içeren bir dizi, bulunamadıysa null döner
 */
function findUserByUsernameOrEmail($conn, $username_or_email)
{
    $stmt = $conn->prepare('SELECT id, username, password, profile_picture_url FROM users WHERE username = ? OR email = ? LIMIT 1');
    if (!$stmt) {
        // Hata durumunda loglama yapabilir veya false dönebiliriz.
        error_log('Sorgu hazırlanamadı: '.$conn->error);

        return null;
    }

    $stmt->bind_param('ss', $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return null;
}
/**
 * YENİ FONKSİYON 1: Kullanıcı adı veya e-postanın zaten var olup olmadığını kontrol eder.
 *
 * @param mysqli $conn     Veritabanı bağlantı nesnesi
 * @param string $username Kontrol edilecek kullanıcı adı
 * @param string $email    Kontrol edilecek e-posta adresi
 *
 * @return bool kullanıcı varsa true, yoksa false döner
 */
function doesUserExist($conn, $username, $email)
{
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

/**
 * YENİ FONKSİYON 2: Veritabanına yeni bir kullanıcı kaydı oluşturur.
 *
 * @param mysqli $conn            Veritabanı bağlantı nesnesi
 * @param string $username        Yeni kullanıcının adı
 * @param string $email           Yeni kullanıcının e-postası
 * @param string $hashed_password Yeni kullanıcının şifresinin hash'lenmiş hali
 *
 * @return bool ekleme başarılıysa true, değilse false döner
 */
function createUser($conn, $username, $email, $hashed_password)
{
    $insert_stmt = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $insert_stmt->bind_param('sss', $username, $email, $hashed_password);

    return $insert_stmt->execute();
}
/**
 * YENİ FONKSİYON 3: Bir e-postanın başka bir kullanıcı tarafından kullanılıp kullanılmadığını kontrol eder.
 *
 * @param mysqli $conn            Veritabanı bağlantı nesnesi
 * @param string $email           Kontrol edilecek e-posta
 * @param int    $current_user_id Mevcut kullanıcı ID'si (kontrol dışında tutmak için)
 *
 * @return bool E-posta başkası tarafından kullanılıyorsa true, değilse false döner
 */
function isEmailTakenByAnotherUser($conn, $email, $current_user_id)
{
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $stmt->bind_param('si', $email, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

/**
 * YENİ FONKSİYON 4: Bir kullanıcının e-posta adresini günceller.
 *
 * @param mysqli $conn      Veritabanı bağlantı nesnesi
 * @param int    $user_id   Güncellenecek kullanıcı ID'si
 * @param string $new_email Yeni e-posta adresi
 *
 * @return bool güncelleme başarılıysa true, değilse false döner
 */
function updateUserEmail($conn, $user_id, $new_email)
{
    $stmt = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
    $stmt->bind_param('si', $new_email, $user_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON 5: Bir kullanıcının şifresini doğrulamak için veritabanındaki hash'lenmiş halini getirir.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $user_id Kullanıcı ID'si
 *
 * @return string|null kullanıcının hash'lenmiş şifresi veya kullanıcı bulunamazsa null
 */
function getUserPasswordHash($conn, $user_id)
{
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? $result['password'] : null;
}

/**
 * YENİ FONKSİYON 6: Bir kullanıcının şifresini günceller.
 *
 * @param mysqli $conn                Veritabanı bağlantı nesnesi
 * @param int    $user_id             Güncellenecek kullanıcı ID'si
 * @param string $hashed_new_password Yeni şifrenin hash'lenmiş hali
 *
 * @return bool güncelleme başarılıysa true, değilse false döner
 */
function updateUserPassword($conn, $user_id, $hashed_new_password)
{
    $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $hashed_new_password, $user_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON 7: Bir kullanıcının e-posta adresini getirir.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $user_id Kullanıcı ID'si
 *
 * @return string|null E-posta adresi veya kullanıcı bulunamazsa null
 */
function getUserEmail($conn, $user_id)
{
    $stmt = $conn->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? $result['email'] : '';
}
/**
 * YENİ FONKSİYON 8: Bir kullanıcının takipçi sayısını döndürür.
 */
function getFollowerCount($conn, $user_id)
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS count FROM follows WHERE following_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['count'];
}

/**
 * YENİ FONKSİYON 9: Bir kullanıcının takip ettiği kişi sayısını döndürür.
 */
function getFollowingCount($conn, $user_id)
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS count FROM follows WHERE follower_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['count'];
}

/**
 * YENİ FONKSİYON 10: Giriş yapmış kullanıcının, görüntülenen profili takip edip etmediğini kontrol eder.
 */
function isFollowing($conn, $current_user_id, $profile_user_id)
{
    $stmt = $conn->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bind_param('ii', $current_user_id, $profile_user_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}
/**
 * YENİ FONKSİYON 11: Bir kullanıcının başka bir kullanıcıyı takip etmesini sağlar.
 *
 * @param mysqli $conn         Veritabanı bağlantı nesnesi
 * @param int    $follower_id  Takip eden kullanıcı ID'si
 * @param int    $following_id Takip edilen kullanıcı ID'si
 *
 * @return bool ekleme başarılıysa true, değilse false döner
 */
function followUser($conn, $follower_id, $following_id)
{
    // Kendi kendini takip etmeyi engelle
    if ($follower_id == $following_id) {
        return false;
    }

    $stmt = $conn->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $follower_id, $following_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON 12: Bir kullanıcının başka bir kullanıcıyı takipten çıkmasını sağlar.
 *
 * @param mysqli $conn         Veritabanı bağlantı nesnesi
 * @param int    $follower_id  Takipten çıkan kullanıcı ID'si
 * @param int    $following_id Takibi bırakılan kullanıcı ID'si
 *
 * @return bool silme başarılıysa true, değilse false döner
 */
function unfollowUser($conn, $follower_id, $following_id)
{
    $stmt = $conn->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bind_param('ii', $follower_id, $following_id);

    return $stmt->execute();
}
/**
 * YENİ FONKSİYON: Kullanıcı adına göre kullanıcıları arar ve listeler.
 *
 * @param mysqli $conn  Veritabanı bağlantı nesnesi
 * @param string $query Arama metni
 *
 * @return array Bulunan kullanıcıları içeren bir dizi
 */
function searchUsersByUsername($conn, $query)
{
    $users = [];
    $search_param = '%'.$query.'%';
    $stmt = $conn->prepare('SELECT id, username, profile_picture_url FROM users WHERE username LIKE ? LIMIT 10');
    $stmt->bind_param('s', $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return $users;
}
/**
 * YENİ FONKSİYON: Belirli bir gönderiyi beğenen kullanıcıları listeler.
 */
function getLikersForPost($conn, $post_id)
{
    $users = [];
    $sql = '
        SELECT u.id, u.username, u.profile_picture_url 
        FROM users u 
        JOIN likes l ON u.id = l.user_id 
        WHERE l.post_id = ?
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return $users;
}

/**
 * YENİ FONKSİYON: Belirli bir kullanıcının takipçilerini listeler.
 */
function getFollowersForUser($conn, $user_id)
{
    $users = [];
    $sql = '
        SELECT u.id, u.username, u.profile_picture_url 
        FROM users u 
        JOIN follows f ON u.id = f.follower_id 
        WHERE f.following_id = ?
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return $users;
}

/**
 * YENİ FONKSİYON: Belirli bir kullanıcının takip ettiği kişileri listeler.
 */
function getFollowingForUser($conn, $user_id)
{
    $users = [];
    $sql = '
        SELECT u.id, u.username, u.profile_picture_url 
        FROM users u 
        JOIN follows f ON u.id = f.following_id 
        WHERE f.follower_id = ?
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return $users;
}
/**
 * YENİ FONKSİYON: Bir kullanıcının başka bir kullanıcıyı engellemesini sağlar.
 */
function blockUser($conn, $blocker_id, $blocked_id)
{
    // Kendi kendini engellemeyi önle
    if ($blocker_id == $blocked_id) {
        return false;
    }
    $stmt = $conn->prepare('INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $blocker_id, $blocked_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON: Bir kullanıcının engelini kaldırır.
 */
function unblockUser($conn, $blocker_id, $blocked_id)
{
    $stmt = $conn->prepare('DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?');
    $stmt->bind_param('ii', $blocker_id, $blocked_id);

    return $stmt->execute();
}

/**
 * YENİ FONKSİYON: Bir kullanıcının diğeri tarafından engellenip engellenmediğini
 * (veya tam tersini) kontrol eder.
 */
function checkBlockStatus($conn, $user1_id, $user2_id)
{
    $stmt = $conn->prepare('
        SELECT blocker_id FROM blocks 
        WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)
    ');
    $stmt->bind_param('iiii', $user1_id, $user2_id, $user2_id, $user1_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}
/**
 * YENİ FONKSİYON: Bir kullanıcının engellediği ve onu engelleyen
 * tüm kullanıcıların ID'lerini bir dizi olarak döndürür.
 */
function getBlockedUserIds($conn, $current_user_id)
{
    $blocked_ids = [];
    $sql = '
        SELECT blocked_id FROM blocks WHERE blocker_id = ?
        UNION
        SELECT blocker_id FROM blocks WHERE blocked_id = ?
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $blocked_ids[] = $row['blocked_id'];
    }
    $stmt->close();

    return $blocked_ids;
}
// Gelecekte kullanıcıyla ilgili diğer fonksiyonlar buraya eklenebilir:
// function createUser(...) { ... }
// function updateUserEmail(...) { ... }
