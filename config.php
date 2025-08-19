<?php

// Uygulamanın kök dizin URL'si
// Kendi sunucu yapılandırmana göre bu değeri ayarla.
// Eğer projeniz 'http://localhost/solaris/' altında çalışıyorsa:
define('BASE_URL', 'http://localhost/solaris/'); // public/ klasörü olmadan!

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'solaris_db');
date_default_timezone_set('Europe/Istanbul');
