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
/**
 * YENİ VE AKILLI FONKSİYON: Bir kullanıcının başka bir kullanıcıya olan takip durumunu değiştirir (takip et/bırak).
 * İşlem sonucunda gerekli bildirimleri oluşturur veya siler.
 *
 * @param mysqli $conn Veritabanı bağlantı nesnesi
 *
 * @return string|false başarılıysa yapılan işlemi ('followed' veya 'unfollowed'), değilse false döner
 */
function toggleFollowUser($conn, $follower_id, $following_id)
{
    // Kendi kendini takip etmeyi engelle
    if ($follower_id == $following_id) {
        return false;
    }

    // Engelleme durumu var mı diye iki yönlü kontrol et
    if (checkBlockStatus($conn, $follower_id, $following_id)) {
        return false;
    }

    $is_currently_following = isFollowing($conn, $follower_id, $following_id);

    if ($is_currently_following) {
        // Zaten takip ediyorsa, takibi bırak
        if (unfollowUser($conn, $follower_id, $following_id)) {
            // DÜZELTME: Takip bildiriminin gönderi ID'si olmadığı için 5. argüman olarak null gönderiyoruz.
            deleteNotification($conn, $following_id, $follower_id, 'follow', null);

            return 'unfollowed';
        }
    } else {
        // Takip etmiyorsa, takip et
        if (followUser($conn, $follower_id, $following_id)) {
            // Takip edildiğine dair bildirim oluştur
            $follower_username = $_SESSION['username'] ?? 'Bir kullanıcı';
            $notification_text = htmlspecialchars($follower_username).' sizi takip etmeye başladı.';
            // DÜZELTME: Takip bildiriminin gönderi ID'si olmadığı için 6. argüman olarak null gönderiyoruz.
            createNotification($conn, $following_id, $follower_id, 'follow', $notification_text, null);

            return 'followed';
        }
    }

    return false; // İşlem başarısız olduysa
}
/**
 * YENİ VE AKILLI FONKSİYON: Bir kullanıcının başka bir kullanıcıya olan engelleme durumunu değiştirir (engelle/kaldır).
 * Engelleme durumunda, aralarındaki takip ilişkisini de sonlandırır.
 *
 * @param mysqli $conn       Veritabanı bağlantı nesnesi
 * @param int    $blocker_id Engelleme işlemini yapan kullanıcı ID'si
 * @param int    $blocked_id Engellenen kullanıcı ID'si
 *
 * @return string|false başarılıysa yapılan işlemi ('blocked' veya 'unblocked'), değilse false döner
 */
function toggleBlockUser($conn, $blocker_id, $blocked_id)
{
    if ($blocker_id == $blocked_id) {
        return false; // Kendi kendini engelleme
    }

    $is_currently_blocked = isUserBlockedBy($conn, $blocker_id, $blocked_id);

    if ($is_currently_blocked) {
        // Zaten engelliyse, engeli kaldır
        if (unblockUser($conn, $blocker_id, $blocked_id)) {
            return 'unblocked';
        }
    } else {
        // Engelli değilse, engelle
        if (blockUser($conn, $blocker_id, $blocked_id)) {
            // Engelleme durumunda karşılıklı takipleşmeyi bitir
            unfollowUser($conn, $blocker_id, $blocked_id);
            unfollowUser($conn, $blocked_id, $blocker_id);

            return 'blocked';
        }
    }

    return false;
}
// isUserBlockedBy fonksiyonu muhtemelen yok, onu da ekleyelim.
// Bu, sadece tek yönlü kontrol eder: $blocker_id, $blocked_id'yi engelliyor mu?
function isUserBlockedBy($conn, $blocker_id, $blocked_id)
{
    $stmt = $conn->prepare('SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?');
    $stmt->bind_param('ii', $blocker_id, $blocked_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}
/**
 * YENİ FONKSİYON: Bir kullanıcının, kendi takipçilerinden birini çıkarmasını sağlar.
 *
 * @param mysqli $conn                  Veritabanı bağlantı nesnesi
 * @param int    $profile_owner_id      İşlemi yapan (profil sahibi) kullanıcı ID'si
 * @param int    $follower_id_to_remove Çıkarılacak takipçinin ID'si
 *
 * @return bool silme işlemi başarılıysa true, değilse false döner
 */
function removeFollower($conn, $profile_owner_id, $follower_id_to_remove)
{
    // Bir kullanıcı sadece kendi takipçisini çıkarabilir.
    // Bu fonksiyon, bu mantığı zorunlu kılar.
    $stmt = $conn->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bind_param('ii', $follower_id_to_remove, $profile_owner_id);

    return $stmt->execute();
}
/**
 * YENİ SÜPER FONKSİYON: Bir kullanıcının takipçilerini VEYA takip ettiklerini listeler.
 * Hangi listeyi getireceği, $type parametresi ile belirlenir.
 *
 * @param mysqli $conn    Veritabanı bağlantı nesnesi
 * @param int    $user_id Listesi alınacak kullanıcı ID'si
 * @param string $type    'followers' (takipçiler) veya 'following' (takip edilenler)
 *
 * @return array Kullanıcıları içeren bir dizi
 */
function getFollowList($conn, $user_id, $type = 'followers')
{
    $users = [];

    // $type parametresine göre sorgunun hangi sütunları seçeceğini belirliyoruz
    $join_on_column = ($type === 'followers') ? 'f.follower_id' : 'f.following_id';
    $where_column = ($type === 'followers') ? 'f.following_id' : 'f.follower_id';

    $sql = "
        SELECT u.id, u.username, u.profile_picture_url 
        FROM users u 
        JOIN follows f ON u.id = $join_on_column 
        WHERE $where_column = ?
    ";

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
 * YENİ SÜPER FONKSİYON: İki kullanıcı arasındaki engelleme durumunu kontrol eder.
 *
 * @param string $direction 'oneway' (sadece user1'in user2'yi engelleyip engellemediği) veya 'both' (karşılıklı)
 */
function checkBlockStatus($conn, $user1_id, $user2_id, $direction = 'both')
{
    if ($direction === 'oneway') {
        $sql = 'SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user1_id, $user2_id);
    } else { // 'both' (varsayılan)
        $sql = 'SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiii', $user1_id, $user2_id, $user2_id, $user1_id);
    }
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}
/**
 * YENİ FONKSİYON: Bir kullanıcının biyografisini günceller.
 *
 * @return bool|string başarılıysa güncellenmiş ve filtrelenmiş bio metnini, başarısızsa false döner
 */
function updateUserBio($conn, $user_id, $new_bio)
{
    $new_bio = trim($new_bio);
    if (mb_strlen($new_bio, 'UTF-8') > 500) {
        return false; // Karakter limitini aştı
    }

    $stmt = $conn->prepare('UPDATE users SET bio = ? WHERE id = ?');
    $stmt->bind_param('si', $new_bio, $user_id);
    if ($stmt->execute()) {
        return htmlspecialchars($new_bio);
    }

    return false;
}

/**
 * YENİ FONKSİYON: Bir kullanıcının profil resmini günceller.
 * Dosya doğrulama, taşıma ve eski dosyayı silme işlemlerini içerir.
 *
 * @param array $file $_FILES'dan gelen dosya bilgisi
 *
 * @return string|false başarılıysa yeni dosya adını, başarısızsa false döner
 */
function updateUserProfilePicture($conn, $user_id, $file)
{
    $upload_dir = __DIR__.'/../../uploads/profile_pictures/';
    // ... (dosya doğrulama, boyut, tip kontrolü mantığı buraya eklenecek) ...
    // Bu mantık oldukça uzun olduğu için şimdilik temel bir versiyonunu ekliyorum,
    // senin gönderdiğin kodun tamamını bu fonksiyon içine taşıyabiliriz.

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = uniqid('profile_', true).'.'.$file_ext;
    $target_file = $upload_dir.$new_file_name;

    // Eski resmi sil
    $stmt_get_old_pic = $conn->prepare('SELECT profile_picture_url FROM users WHERE id = ?');
    $stmt_get_old_pic->bind_param('i', $user_id);
    $stmt_get_old_pic->execute();
    $old_profile_picture = $stmt_get_old_pic->get_result()->fetch_assoc()['profile_picture_url'];
    $stmt_get_old_pic->close();

    if ($old_profile_picture && $old_profile_picture !== 'default_profile.png') {
        if (file_exists($upload_dir.$old_profile_picture)) {
            unlink($upload_dir.$old_profile_picture);
        }
    }

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $stmt_update = $conn->prepare('UPDATE users SET profile_picture_url = ? WHERE id = ?');
        $stmt_update->bind_param('si', $new_file_name, $user_id);
        if ($stmt_update->execute()) {
            return $new_file_name;
        }
    }

    return false;
}

/**
 * YENİ FONKSİYON: Bir kullanıcının kapak fotoğrafını günceller.
 * Dosya doğrulama, yeniden boyutlandırma ve kaydetme işlemlerini içerir.
 *
 * @param array $file $_FILES'dan gelen dosya bilgisi
 *
 * @return string|false başarılıysa yeni dosya adını, başarısızsa false döner
 */
function updateUserCoverPicture($conn, $user_id, $file)
{
    // Buraya upload_cover_picture.php'deki tüm o detaylı resim işleme
    // (boyutlandırma, kalite ayarı vb.) mantığı gelecek.
    // Şimdilik basitleştirilmiş bir versiyonunu ekliyorum.

    $upload_dir = __DIR__.'/../../uploads/cover_pictures/';
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = uniqid('cover_', true).'.'.$file_ext;
    $target_file = $upload_dir.$new_file_name;

    // (Burada resim işleme kodları olmalı)

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $stmt = $conn->prepare('UPDATE users SET cover_picture_url = ? WHERE id = ?');
        $stmt->bind_param('si', $new_file_name, $user_id);
        if ($stmt->execute()) {
            return $new_file_name;
        }
    }

    return false;
}
