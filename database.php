<?php

// config.php'den gelen veritabanı bilgilerini kullanalım
$db_host = DB_HOST;
$db_user = DB_USER;
$db_pass = DB_PASS;
$db_name = DB_NAME;

// Veritabanı bağlantısını oluştur
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Bağlantı hatası olursa, detayı göstermeden genel bir mesaj ver
    exit('Veritabanı bağlantı hatası. Lütfen daha sonra tekrar deneyin.');
}

// Bağlantıyı tüm uygulama tarafından kullanılabilir hale getirelim.
// Bu, global $conn; satırının işini yapar.
return $conn;
