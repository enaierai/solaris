<?php

class AuthController extends Controller
{
    /**
     * Login sayfasını gösterir (GET isteği) ve
     * login formunu işler (POST isteği).
     */
    public function login()
    {
        // Model'i yükle
        $userModel = $this->model('UserModel');

        // Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
        if (isset($_SESSION['user_id'])) {
            header('Location: '.BASE_URL.'home');
            exit;
        }

        $message = ''; // Hata mesajı için boş bir değişken

        // Eğer form gönderilmişse (POST isteği ise)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // CSRF token kontrolü
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $message = '<div class="alert alert-danger">Güvenlik hatası. Lütfen tekrar deneyin.</div>';
            } else {
                // Formdan gelen veriyi temizle
                $username_or_email = trim($_POST['username_or_email']);
                $password = $_POST['password'];

                // Kullanıcıyı bul
                $user = $userModel->findByUsernameOrEmail($username_or_email);

                // HATA BURADAYDI: 'password_hash' yerine 'password' kullanılmalı
                if ($user && password_verify($password, $user['password'])) {
                    // Session'ları ayarla ve kullanıcıyı içeri al
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['profile_picture_url'] = $user['profile_picture_url'];

                    header('Location: '.BASE_URL.'home');
                    exit;
                } else {
                    $message = '<div class="alert alert-danger">Kullanıcı adı veya şifre hatalı.</div>';
                }
            }
        }

        // Yeni bir CSRF token oluştur
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // View'a gönderilecek verileri hazırla
        $data = [
            'meta' => [
                'meta_title' => 'Giriş Yap - Solaris',
                'meta_description' => 'Solaris hesabınıza giriş yapın ve paylaşmaya başlayın.',
            ],
            'message' => $message,
            'csrf_token' => $_SESSION['csrf_token'],
        ];

        // View'ları yükle
        $this->view('layouts/header', $data);
        $this->view('pages/login', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * Kullanıcıyı sistemden çıkarır (Logout).
     */
    public function logout()
    {
        // Tüm session verilerini temizle
        session_unset();
        session_destroy();

        // Ekstra güvenlik olarak session cookie'sini de temizle
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Ana sayfaya yönlendir ve script'in çalışmasını sonlandır
        header('Location: '.BASE_URL);
        exit;
    }
    // app/Controllers/AuthController.php içinde

    // ... logout() metodundan sonra ...

    /**
     * Kayıt sayfasını gösterir (GET) ve yeni kullanıcı kaydını işler (POST).
     */
    public function register()
    {
        // Model'i yükle
        $userModel = $this->model('UserModel');

        // Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
        if (isset($_SESSION['user_id'])) {
            header('Location: '.BASE_URL.'home');
            exit;
        }

        $message = '';
        $errors = [];
        $old_data = ['username' => '', 'email' => ''];

        // Eğer form gönderilmişse (POST isteği ise)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // CSRF token kontrolü
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $message = '<div class="alert alert-danger">Güvenlik hatası. Lütfen tekrar deneyin.</div>';
            } else {
                // Form verilerini al ve temizle
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $password_confirm = $_POST['password_confirm'];

                $old_data = ['username' => htmlspecialchars($username), 'email' => htmlspecialchars($email)];

                // --- Gelişmiş Doğrulama (Validation) ---
                if (empty($username) || empty($email) || empty($password)) {
                    $errors[] = 'Tüm alanların doldurulması zorunludur.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Lütfen geçerli bir e-posta adresi girin.';
                }
                if ($password !== $password_confirm) {
                    $errors[] = 'Şifreler eşleşmiyor.';
                }
                // Şifre Güçlülüğü Kontrolü (İsteğin Üzerine Eklendi)
                $password_regex = '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*.-]).{8,72}$/';
                if (!preg_match($password_regex, $password)) {
                    $errors[] = 'Şifre en az 8 karakter uzunluğunda olmalı; bir büyük harf, bir küçük harf, bir rakam ve bir özel karakter (#?!@$%^&*.-) içermelidir.';
                }

                if ($userModel->doesUserExist($username, $email)) {
                    $errors[] = 'Bu kullanıcı adı veya e-posta zaten kullanımda.';
                }

                // Eğer hiç hata yoksa, kaydı gerçekleştir
                if (empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    if ($userModel->create($username, $email, $hashed_password)) {
                        // Başarılı kayıt sonrası login sayfasına yönlendirip mesaj gösterelim
                        $_SESSION['success_message'] = 'Kaydınız başarıyla tamamlandı! Şimdi giriş yapabilirsiniz.';
                        header('Location: '.BASE_URL.'login');
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger">Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.</div>';
                    }
                }
            }
        }

        // Yeni bir CSRF token oluştur
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // View'a gönderilecek verileri hazırla
        $data = [
            'meta' => [
                'meta_title' => 'Kayıt Ol - Solaris',
                'meta_description' => 'Solaris\'e katılın, ilham alın ve ilham verin.',
            ],
            'message' => $message,
            'errors' => $errors,
            'old_data' => $old_data,
            'csrf_token' => $_SESSION['csrf_token'],
        ];

        // View'ları yükle
        $this->view('layouts/header', $data);
        $this->view('pages/register', $data);
        $this->view('layouts/footer', $data);
    }
}
