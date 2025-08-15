<?php

// Bütün projeyi başlatan tek satır
require_once __DIR__.'/includes/init.php';

// --- YÖNLENDİRME (ROUTING) ---
$path = trim($_GET['path'] ?? 'home', '/');
$parts = explode('/', $path);
$page_name = strtolower($parts[0]);
$current_user_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($current_user_id);

// ==================================================================
// ## ADIM 1: ÖNCE MANTIĞI ÇALIŞTIR ##
// Yönlendirme gibi header işlemleri yapabilecek olan mantık dosyasını,
// HTML'den ÖNCE çalıştırıyoruz.
// ==================================================================
switch ($page_name) {
    case 'home':          include_once __DIR__.'/includes/logic/index.logic.php';
        break;
    case 'dashboard':     include_once __DIR__.'/includes/logic/dashboard.logic.php';
        break;
    case 'explore':       include_once __DIR__.'/includes/logic/explore.logic.php';
        break;
    case 'login':         include_once __DIR__.'/includes/logic/login.logic.php';
        break;
    case 'logout':        include_once __DIR__.'/includes/logic/logout.logic.php';
        break;
    case 'messages':      include_once __DIR__.'/includes/logic/messages.logic.php';
        break;
    case 'notifications': include_once __DIR__.'/includes/logic/notifications.logic.php';
        break;
    case 'post':          include_once __DIR__.'/includes/logic/post.logic.php';
        break;
    case 'profile':       include_once __DIR__.'/includes/logic/profile.logic.php';
        break;
    case 'register':      include_once __DIR__.'/includes/logic/register.logic.php';
        break;
    case 'search':        include_once __DIR__.'/includes/logic/search.logic.php';
        break;
    case 'security':      include_once __DIR__.'/includes/logic/security.logic.php';
        break;
    case 'settings':      include_once __DIR__.'/includes/logic/settings.logic.php';
        break;
        // 'upload' için logic dosyası yoksa bu case'i boş bırakabiliriz.
    case 'upload':        /* include_once __DIR__ . '/includes/logic/upload.logic.php'; */ break;
    default:
        // 404 sayfasının özel bir mantığı yok, bu yüzden boş bırakıyoruz.
        break;
}

// ==================================================================
// ## ADIM 2: HEADER'I YÜKLE ##
// Artık tüm yönlendirmeler ve mantık işlemleri bittiğine göre, HTML'i gönderebiliriz.
// ==================================================================
include_once __DIR__.'/includes/header.php';

// ==================================================================
// ## ADIM 3: SAYFA GÖRÜNÜMÜNÜ YÜKLE ##
// Sadece sayfanın HTML içeriğini basıyoruz.
// ==================================================================
switch ($page_name) {
    case 'home':          include_once __DIR__.'/public/home.php';
        break;
    case 'dashboard':     include_once __DIR__.'/public/pages/dashboard.php';
        break;
    case 'explore':       include_once __DIR__.'/public/pages/explore.php';
        break;
    case 'login':         include_once __DIR__.'/public/pages/login.php';
        break;
    case 'logout':        include_once __DIR__.'/public/pages/logout.php';
        break;
    case 'messages':      include_once __DIR__.'/public/pages/messages.php';
        break;
    case 'notifications': include_once __DIR__.'/public/pages/notifications.php';
        break;
    case 'post':          include_once __DIR__.'/public/pages/post.php';
        break;
    case 'profile':       include_once __DIR__.'/public/pages/profile.php';
        break;
    case 'register':      include_once __DIR__.'/public/pages/register.php';
        break;
    case 'search':        include_once __DIR__.'/public/pages/search.php';
        break;
    case 'security':      include_once __DIR__.'/public/pages/security.php';
        break;
    case 'settings':      include_once __DIR__.'/public/pages/settings.php';
        break;
    case 'upload':        include_once __DIR__.'/public/pages/upload.php';
        break;
    default:
        http_response_code(404);
        include_once __DIR__.'/public/pages/errors/404.php';
        break;
}

// ==================================================================
// ## ADIM 4: FOOTER'I YÜKLE ##
// ==================================================================
include_once __DIR__.'/includes/footer.php';
