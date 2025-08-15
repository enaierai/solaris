<?php

// includes/init.php

// Geliştirme aşamasında hataları görmek için
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. TEMEL AYARLAR VE YARDIMCILAR
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/helpers.php';

// 2. OTURUM KONTROLÜ
// Eğer bir oturum zaten aktif değilse, YENİ BİR TANE BAŞLAT.
// Bu sayede "session already active" hatası asla alınmaz.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
