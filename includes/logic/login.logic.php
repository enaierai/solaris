<?php

// includes/logic/login.logic.php

if (isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/');
    exit;
}

include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';
include_once __DIR__.'/../models/UserModel.php'; // YENİ MODELİ DAHİL ET

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
            // VERİTABANI SORGUSUNU FONKSİYON İLE DEĞİŞTİR
            $user = findUserByUsernameOrEmail($conn, $username_or_email);

            if ($user && password_verify($password, $user['password'])) {
                // Giriş başarılı, session değişkenlerini ayarla ve yönlendir
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: '.BASE_URL.'public/');
                exit;
            } else {
                // Kullanıcı bulunamadı veya şifre yanlış
                $message = '<div class="alert alert-danger">Bu bilgilere sahip bir kullanıcı bulunamadı veya şifre hatalı.</div>';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
