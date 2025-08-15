<?php

// includes/logic/security.logic.php

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'login');
    exit;
}

// Sayfa için gerekli değişkenleri hazırla
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$meta_title = 'Güvenlik Ayarları | Solaris';
$meta_description = 'Solaris hesabınızın güvenlik ayarlarını yönetin.';
