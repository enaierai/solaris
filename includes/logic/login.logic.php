<?php

// includes/logic/login.logic.php (YENİ HALİ)
// Bu işlemler artık projenin ana giriş noktası olan (root)/index.php'de yapılıyor.

// YENİ: Model'i çağırmak için doğru ve güvenilir dosya yolu kullanalım.
include_once __DIR__.'/../models/UserModel.php';

// YENİ: Oturum kontrolü. Eğer kullanıcı zaten giriş yapmışsa, onu ana sayfaya yönlendir.
// Bu kontrolün logic dosyasının en başında olması önemlidir.
if (isset($_SESSION['user_id'])) {
    // DEĞİŞTİRİLDİ: Yönlendirme URL'si yeni yapıya uygun hale getirildi.
    header('Location: '.BASE_URL.'home');
    exit;
}

$brand_name = 'Solaris';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">Geçersiz güvenlik anahtarı. Lütfen sayfayı yenileyin.</div>';
    } else {
        $username_or_email = $_POST['username_or_email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username_or_email) || empty($password)) {
            $message = '<div class="alert alert-danger">Kullanıcı adı/e-posta ve şifre alanları boş bırakılamaz.</div>';
        } else {
            $user = findUserByUsernameOrEmail($conn, $username_or_email);

            if ($user && password_verify($password, $user['password'])) {
                // Giriş başarılı, session değişkenlerini ayarla
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // DEĞİŞTİRİLDİ: Yönlendirme URL'si yeni yapıya uygun hale getirildi.
                header('Location: '.BASE_URL.'home');
                exit;
            } else {
                $message = '<div class="alert alert-danger">Bu bilgilere sahip bir kullanıcı bulunamadı veya şifre hatalı.</div>';
            }
        }
    }
}

// CSRF token'ı oluşturma işlemi aynı kalıyor.
$csrf_token = generate_csrf_token();
