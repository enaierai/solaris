<?php

include_once __DIR__.'/config.php'; // config.php dosyasını dahil et

// Veritabanı bağlantısı oluşturma
$conn = new mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    // Hata mesajını logla veya güvenli bir şekilde göster
    error_log('Veritabanı bağlantısı başarısız: '.$conn->connect_error);
    exit('Veritabanı bağlantısı başarısız oldu.');
}
// Karakter setini UTF-8 olarak ayarla
$conn->set_charset('utf8mb4');
