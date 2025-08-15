<?php

// Geliştirme aşamasında hataları görmek için
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. TEMEL AYARLAR VE OTURUM (Bütün proje için tek bir yerden yönetilir)
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/helpers.php';
session_start();

// 2. YÖNLENDİRME (ROUTING)
// htaccess'ten gelen 'path'i al. Eğer boşsa, varsayılan olarak 'home' kabul et.
$path = trim($_GET['path'] ?? 'home', '/');
$parts = explode('/', $path);
$page_name = strtolower($parts[0]);

// Oturumdaki kullanıcı ID'sini merkezi olarak alalım
$current_user_id = $_SESSION['user_id'] ?? null;

// 3. SAYFA İSKELETİNİ YÜKLE
// Her sayfada ortak olan Header'ı çağır
include_once __DIR__.'/includes/header.php';

// 4. SAYFA İÇERİĞİNİ YÜKLE (Orkestra Şefinin Solisti Seçtiği Yer)
// Gelen $page_name'e göre ilgili mantık ve görünüm dosyasını çağır.
switch ($page_name) {
    case 'home': // Ana sayfa (eski index.php)
        include __DIR__.'/includes/logic/index.logic.php';
        include __DIR__.'/public/home.php';
        break;

    case 'dashboard':
        include __DIR__.'/includes/logic/dashboard.logic.php';
        include __DIR__.'/public/pages/dashboard.php';
        break;

    case 'explore':
        include __DIR__.'/includes/logic/explore.logic.php';
        include __DIR__.'/public/pages/explore.php';
        break;

    case 'login':
        include __DIR__.'/includes/logic/login.logic.php';
        include __DIR__.'/public/pages/login.php';
        break;

    case 'logout':
        include __DIR__.'/includes/logic/logout.logic.php';
        include __DIR__.'/public/pages/logout.php';
        break;

    case 'messages':
        include __DIR__.'/includes/logic/messages.logic.php';
        include __DIR__.'/public/pages/messages.php';
        break;

    case 'notifications':
        include __DIR__.'/includes/logic/notifications.logic.php';
        include __DIR__.'/public/pages/notifications.php';
        break;

    case 'post':
        include __DIR__.'/includes/logic/post.logic.php';
        include __DIR__.'/public/pages/post.php';
        break;

    case 'profile':
        include __DIR__.'/includes/logic/profile.logic.php';
        include __DIR__.'/public/pages/profile.php';
        break;

    case 'register':
        include __DIR__.'/includes/logic/register.logic.php';
        include __DIR__.'/public/pages/register.php';
        break;

    case 'search':
        include __DIR__.'/includes/logic/search.logic.php';
        include __DIR__.'/public/pages/search.php';
        break;

    case 'security':
        include __DIR__.'/includes/logic/security.logic.php';
        include __DIR__.'/public/pages/security.php';
        break;

    case 'settings':
        include __DIR__.'/includes/logic/settings.logic.php';
        include __DIR__.'/public/pages/settings.php';
        break;

    case 'upload':
        // Not: Bu sayfa için bir logic dosyası bulunamadı, bu yüzden sadece görünüm dosyası dahil edildi.
        include __DIR__.'/public/pages/upload.php';
        break;

    default: // Eğer sayfa bulunamazsa 404 hatası ver
        http_response_code(404);
        include __DIR__.'/public/pages/errors/404.php';
        break;
}

// 5. SAYFA İSKELETİNİ KAPAT
// Her sayfada ortak olan Footer'ı çağır
include_once __DIR__.'/includes/footer.php';
