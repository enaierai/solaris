<?php

// --- GELİŞTİRME AŞAMASI İÇİN HATA RAPORLAMAYI AÇ ---
// Bu, beyaz ekran yerine bize hata mesajlarını gösterir.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session'ı başlat
session_start();

// Projenin kök dizinini tanımla
define('ROOT', __DIR__);

// Uygulamayı başlatan ana dosyayı çağır
require_once ROOT.'/app/init.php';

// Uygulamayı çalıştır
$app = new App();
