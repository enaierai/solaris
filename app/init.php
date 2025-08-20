<?php

// Gerekli çekirdek dosyaları yükle
require_once 'core/App.php';
require_once 'core/Controller.php';

// --- GÜNCELLEME ---
// Veritabanı ve config ayarlarını yeni "/config" klasöründen yükle
require_once ROOT.'/config/config.php';
require_once ROOT.'/config/database.php';

// Yardımcı fonksiyonları yükle
require_once 'Helpers/functions.php';
