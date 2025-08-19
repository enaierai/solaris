<?php

// includes/logic/settings.logic.php

session_start();
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';
include_once __DIR__.'/../models/UserModel.php'; // MODELİ DAHİL EDİYORUZ

if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['message'] = '<div class="alert alert-danger">Geçersiz güvenlik anahtarı.</div>';
    }
    // E-posta güncelleme işlemi
    elseif (isset($_POST['update_email'])) {
        $new_email = trim($_POST['email'] ?? '');

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Geçerli bir e-posta adresi giriniz.</div>';
        } elseif (isEmailTakenByAnotherUser($conn, $new_email, $current_user_id)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Bu e-posta adresi zaten kullanımda.</div>';
        } else {
            if (updateUserEmail($conn, $current_user_id, $new_email)) {
                $_SESSION['message'] = '<div class="alert alert-success">E-posta adresiniz başarıyla güncellendi.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
            }
        }
    }
    // Şifre güncelleme işlemi
    elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($new_password_confirm)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Tüm şifre alanları doldurulmalıdır.</div>';
        } elseif ($new_password !== $new_password_confirm) {
            $_SESSION['message'] = '<div class="alert alert-danger">Yeni şifreler uyuşmuyor.</div>';
        } elseif (strlen($new_password) < 6) {
            $_SESSION['message'] = '<div class="alert alert-danger">Yeni şifreniz en az 6 karakter olmalıdır.</div>';
        } else {
            $password_hash_from_db = getUserPasswordHash($conn, $current_user_id);
            if ($password_hash_from_db && password_verify($current_password, $password_hash_from_db)) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                if (updateUserPassword($conn, $current_user_id, $hashed_new_password)) {
                    $_SESSION['message'] = '<div class="alert alert-success">Şifreniz başarıyla güncellendi.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Şifre güncellenirken bir hata oluştu.</div>';
                }
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Mevcut şifreniz yanlış.</div>';
            }
        }
    }

    header('Location: '.BASE_URL.'public/pages/settings.php');
    exit;
}

// Mevcut kullanıcı e-posta bilgisini formda göstermek için çek
$user_email = htmlspecialchars(getUserEmail($conn, $current_user_id));

// Her sayfa yüklemesinde yeni bir CSRF token oluştur
$csrf_token = generate_csrf_token();
