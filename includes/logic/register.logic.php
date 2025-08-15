<?php

// includes/logic/register.logic.php (YENİ HALİ)

// SİLİNDİ: init.php'de zaten çağrıldığı için tüm eski require/include'lar kaldırıldı.
include_once __DIR__.'/../models/UserModel.php';

// Oturum kontrolü. Eğer kullanıcı zaten giriş yapmışsa, onu ana sayfaya yönlendir.
if ($is_logged_in) {
    // DEĞİŞTİRİLDİ: Yönlendirme URL'si yeni yapıya uygun hale getirildi.
    header('Location: '.BASE_URL.'home');
    exit;
}

$brand_name = 'Solaris';
$message = '';
$old_username = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">Geçersiz güvenlik anahtarı. Lütfen sayfayı yenileyin.</div>';
    } else {
        // Formdan gelen verileri alalım
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Formdan gelen verileri hata durumunda tekrar göstermek için saklayalım
        $old_username = htmlspecialchars($username);
        $old_email = htmlspecialchars($email);

        // Kullanıcının mevcut olup olmadığını kontrol et
        if (doesUserExist($conn, $username, $email)) {
            $message = '<div class="alert alert-danger">Bu kullanıcı adı veya e-posta zaten kullanımda.</div>';
        } else {
            // Şifreyi hash'le
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Yeni kullanıcıyı oluştur
            if (createUser($conn, $username, $email, $hashed_password)) {
                // DEĞİŞTİRİLDİ: Başarı mesajındaki link yeni yapıya uygun hale getirildi.
                $message = '<div class="alert alert-success">Kaydınız başarıyla tamamlandı! Şimdi <a href="'.BASE_URL.'login" class="alert-link">giriş yapabilirsiniz</a>.</div>';
                $old_username = '';
                $old_email = '';
            } else {
                $message = '<div class="alert alert-danger">Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.</div>';
            }
        }
    }
}

// Her sayfa yüklemesinde yeni bir CSRF token oluştur
$csrf_token = generate_csrf_token();
