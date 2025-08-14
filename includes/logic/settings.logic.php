<?php

// includes/logic/settings.logic.php

session_start();
include_once __DIR__.'/../config.php';
include_once __DIR__.'/../db.php';
include_once __DIR__.'/../helpers.php';

// Kullanıcı giriş yapmamışsa yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$message = '';

// Formdan gelen bir mesaj varsa, onu göster ve session'dan temizle
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// POST isteği varsa form işlemlerini yap
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token doğrulaması
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['message'] = '<div class="alert alert-danger">Geçersiz güvenlik anahtarı.</div>';
        header('Location: '.BASE_URL.'public/pages/settings.php');
        exit;
    }

    // E-posta güncelleme işlemi
    if (isset($_POST['update_email'])) {
        $new_email = trim($_POST['email'] ?? '');

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Geçerli bir e-posta adresi giriniz.</div>';
        } else {
            $check_email_stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $check_email_stmt->bind_param('si', $new_email, $current_user_id);
            $check_email_stmt->execute();
            if ($check_email_stmt->get_result()->num_rows > 0) {
                $_SESSION['message'] = '<div class="alert alert-danger">Bu e-posta adresi zaten kullanımda.</div>';
            } else {
                $update_stmt = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
                $update_stmt->bind_param('si', $new_email, $current_user_id);
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = '<div class="alert alert-success">E-posta adresiniz başarıyla güncellendi.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
                }
                $update_stmt->close();
            }
            $check_email_stmt->close();
        }
    }

    // Şifre güncelleme işlemi
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($new_password_confirm)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Tüm şifre alanları doldurulmalıdır.</div>';
        } elseif ($new_password !== $new_password_confirm) {
            $_SESSION['message'] = '<div class="alert alert-danger">Yeni şifreler uyuşmuyor.</div>';
        } elseif (strlen($new_password) < 6) {
            $_SESSION['message'] = '<div class="alert alert-danger">Yeni şifreniz en az 6 karakter olmalıdır.</div>';
        } else {
            $check_password_stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $check_password_stmt->bind_param('i', $current_user_id);
            $check_password_stmt->execute();
            $user = $check_password_stmt->get_result()->fetch_assoc();
            $check_password_stmt->close();

            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update_stmt->bind_param('si', $hashed_new_password, $current_user_id);
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = '<div class="alert alert-success">Şifreniz başarıyla güncellendi.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Şifre güncellenirken bir hata oluştu.</div>';
                }
                $update_stmt->close();
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Mevcut şifreniz yanlış.</div>';
            }
        }
    }

    // İşlem sonrası mesajın gösterilmesi için sayfayı yeniden yönlendir
    header('Location: '.BASE_URL.'public/pages/settings.php');
    exit;
}

// Mevcut kullanıcı e-posta bilgisini formda göstermek için çek
$user_email = '';
$stmt = $conn->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user_data = $result->fetch_assoc();
    $user_email = htmlspecialchars($user_data['email']);
}
$stmt->close();

// Her sayfa yüklemesinde yeni bir CSRF token oluştur
$csrf_token = generate_csrf_token();
