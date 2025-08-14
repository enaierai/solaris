<?php

// includes/logic/security.logic.php

session_start();

include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

// Sayfa için gerekli değişkenleri hazırla
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$meta_title = 'Güvenlik Ayarları | Solaris';
$meta_description = 'Solaris hesabınızın güvenlik ayarlarını yönetin.';
