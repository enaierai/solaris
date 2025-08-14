<?php

// includes/logic/logout.logic.php

// Oturumu başlat, eğer zaten başlatılmamışsa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Adım: Tüm oturum değişkenlerini temizle.
// Bu, $_SESSION süper global dizisinin içini boşaltır.
$_SESSION = [];

// 2. Adım: Oturum çerezini sil.
// Bu, kullanıcının tarayıcısındaki session ID'sini geçersiz kılar.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3. Adım: Oturumu sunucu tarafında tamamen yok et.
session_destroy();

// 4. Adım: Yönlendirme için config dosyasını dahil et.
// Bu, session yok edildikten sonra yapılmalıdır.
include_once __DIR__.'/../config.php';

// 5. Adım: Kullanıcıyı giriş sayfasına yönlendir.
header('Location: '.BASE_URL.'public/pages/login.php');
exit; // Yönlendirmeden sonra script'in çalışmaya devam etmediğinden emin ol.
