<?php

// --- GÜVENLİ VE ÇALIŞAN VERSİYON ---

// Adım 1: Kendi adresini (projenin kök dizinini) öğren.
define('ROOT', __DIR__);

// Adım 2: Ayar dosyalarını yeni adres bilgisiyle çağır.
require_once ROOT.'/config/config.php';

// Güvenlik: Sadece izin verilen klasörlerden dosya servisi yap
$allowed_dirs = [
    'profile_pictures' => ROOT.'/storage/uploads/profile_pictures/',
    'cover_pictures' => ROOT.'/storage/uploads/cover_pictures/',
    'posts' => ROOT.'/storage/uploads/posts/',
];

// Dosya yolunu al ve güvenlik kontrolü yap
$file_path = $_GET['path'] ?? '';

// Güvenlik: ../ gibi karakterlerle üst dizinlere çıkma girişimini engelle
if (strpos($file_path, '..') !== false) {
    http_response_code(403);
    exit('Forbidden');
}

// Gelen yolu, ana klasör ve dosya adı olarak ikiye ayır
$path_parts = explode('/', $file_path, 2);
$dir = $path_parts[0] ?? '';
$filename = $path_parts[1] ?? '';

// İstenen dizin izin verilenler listesinde değilse veya dosya adı boşsa işlemi durdur
if (!isset($allowed_dirs[$dir]) || empty($filename)) {
    http_response_code(403);
    exit('Forbidden');
}

$full_path = $allowed_dirs[$dir].$filename;

if (file_exists($full_path)) {
    // Dosya tipine göre doğru Content-Type başlığını gönder
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $full_path);
    finfo_close($finfo);

    header('Content-Type: '.$mime_type);
    header('Content-Length: '.filesize($full_path));

    // Dosyayı oku ve tarayıcıya gönder
    readfile($full_path);
    exit;
} else {
    // Dosya bulunamazsa 404 hatası ver
    http_response_code(404);
    exit('Not Found');
}
