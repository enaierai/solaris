<?php

// includes/logic/register.logic.php
if (isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/');
    exit;
}
include_once __DIR__.'/../models/UserModel.php'; // MODELİ DAHİL ET

$brand_name = 'Solaris';
$message = '';
$old_username = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">Geçersiz güvenlik anahtarı. Lütfen sayfayı yenileyin.</div>';
    } else {
        // VERİTABANI KONTROLÜNÜ YENİ FONKSİYONLA YAP
        if (doesUserExist($conn, $username, $email)) {
            $message = '<div class="alert alert-danger">Bu kullanıcı adı veya e-posta zaten kullanımda.</div>';
        } else {
            // Şifreyi hash'le
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // KULLANICI OLUŞTURMA İŞLEMİNİ YENİ FONKSİYONLA YAP
            if (createUser($conn, $username, $email, $hashed_password)) {
                $message = '<div class="alert alert-success">Kaydınız başarıyla tamamlandı! Şimdi <a href="'.BASE_URL.'public/pages/login.php" class="alert-link">giriş yapabilirsiniz</a>.</div>';
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
