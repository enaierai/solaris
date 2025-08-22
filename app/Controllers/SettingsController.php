<?php

class SettingsController extends Controller
{
    /**
     * Ayarlar sayfasını görüntüler.
     */
    public function index()
    {
        // Kullanıcı giriş yapmamışsa yönlendir
        if (!isset($_SESSION['user_id'])) {
            header('Location: '.BASE_URL.'login');
            exit;
        }

        $userModel = $this->model('UserModel');
        $settingsModel = $this->model('SettingsModel'); // Yeni SettingsModel'i yükle

        $current_user_id = $_SESSION['user_id'];
        $user_data = $userModel->findById($current_user_id);
        $user_links = $settingsModel->getUserLinks($current_user_id); // Kullanıcının linklerini çek

        // Eğer kullanıcı verisi çekilemezse veya bir sorun olursa
        if (!$user_data) {
            $_SESSION['message'] = '<div class="alert alert-danger">Kullanıcı verileri yüklenirken bir hata oluştu.</div>';
            header('Location: '.BASE_URL.'logout'); // Güvenlik için çıkış yap
            exit;
        }

        // Mesajları session'dan al ve temizle
        $message = '';
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
        }

        // Her sayfa yüklemesinde yeni bir CSRF token oluştur
        $csrf_token = generate_csrf_token(); // helpers.php'den gelen fonksiyon

        $data = [
            'meta' => [
                'meta_title' => 'Ayarlar - Solaris',
                'meta_description' => 'Hesap ayarlarınızı ve gizlilik tercihlerinizi yönetin.',
            ],
            'user_data' => $user_data,
            'user_links' => $user_links, // Link verilerini View'a aktar
            'message' => $message,
            'csrf_token' => $csrf_token,
            'is_logged_in' => true, // Zaten giriş yapmışız
            'current_user_id' => $current_user_id,
        ];

        $this->view('layouts/header', $data);
        $this->view('pages/settings', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * AJAX: Profil bilgilerini güncelleme işlemi.
     */
    public function update_profile_info()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'CSRF doğrulama başarısız.';
                echo json_encode($response);
                exit;
            }
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id = $_SESSION['user_id'];
            $userModel = $this->model('UserModel');
            $settingsModel = $this->model('SettingsModel');

            // Form verilerini al
            $name = trim($input_data['name'] ?? '');
            $username = trim($input_data['username'] ?? '');
            $pronouns = trim($input_data['pronouns'] ?? '');
            $bio = trim($input_data['bio'] ?? '');
            $gender = $input_data['gender'] ?? null;
            $business_email = trim($input_data['business_email'] ?? '');
            $business_phone = trim($input_data['business_phone'] ?? '');
            $whatsapp_number = trim($input_data['whatsapp_number'] ?? '');
            $display_contact_info = isset($input_data['display_contact_info']) ? 1 : 0;
            $links = $input_data['links'] ?? []; // Dinamik linkler

            // Kullanıcı adı değiştirme kontrolü (30 gün kuralı)
            $current_user_data = $userModel->findById($user_id);
            if ($current_user_data['username'] !== $username) {
                // Kullanıcı adı daha önce hiç değiştirilmemişse veya 30 günden fazla zaman geçmişse
                if ($settingsModel->canChangeUsername($user_id)) {
                    // Eski kullanıcı adını geçmişe ekle
                    $settingsModel->addUsernameToHistory($user_id, $current_user_data['username']);
                } else {
                    $response['message'] = 'Kullanıcı adınızı 30 günden daha sık değiştiremezsiniz.';
                    echo json_encode($response);
                    exit;
                }
            }

            try {
                // UserModel'i kullanarak temel kullanıcı bilgilerini güncelle
                $update_success = $userModel->updateProfileInfo(
                    $user_id, $name, $username, $pronouns, $bio, $gender,
                    $business_email, $business_phone, $whatsapp_number, $display_contact_info
                );

                // Profil resmi yükleme (kapak resmi yükleme kaldırıldı)
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $profile_picture_upload_response = $userModel->updateProfilePicture($user_id, $_FILES['profile_picture']);

                    if (!$profile_picture_upload_response['success']) {
                        $response['message'] = $profile_picture_upload_response['message'];
                        echo json_encode($response);
                        exit;
                    } else {
                        $_SESSION['profile_picture_url'] = $profile_picture_upload_response['new_file_name'];
                    }
                }

                // Linkleri güncelle (SettingsModel üzerinden)
                $settingsModel->updateUserLinks($user_id, $links);

                if ($update_success) {
                    // Session'daki kullanıcı adını güncelle
                    $_SESSION['username'] = $username;

                    $response = ['success' => true, 'message' => 'Profil bilgileri başarıyla güncellendi.'];
                } else {
                    $response['message'] = 'Profil bilgileri güncellenirken bir hata oluştu.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: E-posta adresini güncelleme işlemi.
     */
    public function update_email()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'CSRF doğrulama başarısız.';
                echo json_encode($response);
                exit;
            }
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id = $_SESSION['user_id'];
            $new_email = trim($input_data['email'] ?? '');

            $userModel = $this->model('UserModel');

            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Geçerli bir e-posta adresi giriniz.';
            } elseif ($userModel->isEmailTakenByAnotherUser($new_email, $user_id)) {
                $response['message'] = 'Bu e-posta adresi zaten kullanımda.';
            } else {
                try {
                    if ($userModel->updateUserEmail($user_id, $new_email)) {
                        $response = ['success' => true, 'message' => 'E-posta adresiniz başarıyla güncellendi.'];
                    } else {
                        $response['message'] = 'E-posta güncellenirken bir hata oluştu.';
                    }
                } catch (Exception $e) {
                    $response['message'] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);
    }

    /**
     * AJAX: Şifre güncelleme işlemi.
     */
    public function update_password()
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Geçersiz istek.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_data = $_POST;

            if (!isset($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
                $response['message'] = 'CSRF doğrulama başarısız.';
                echo json_encode($response);
                exit;
            }
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Bu işlem için giriş yapmalısınız.';
                echo json_encode($response);
                exit;
            }

            $user_id = $_SESSION['user_id'];
            $current_password = $input_data['current_password'] ?? '';
            $new_password = $input_data['new_password'] ?? '';
            $new_password_confirm = $input_data['new_password_confirm'] ?? '';

            $userModel = $this->model('UserModel');

            if (empty($current_password) || empty($new_password) || empty($new_password_confirm)) {
                $response['message'] = 'Tüm şifre alanları doldurulmalıdır.';
            } elseif ($new_password !== $new_password_confirm) {
                $response['message'] = 'Yeni şifreler uyuşmuyor.';
            } elseif (strlen($new_password) < 8 || !preg_match('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*.-]).{8,72}$/', $new_password)) {
                $response['message'] = 'Şifre en az 8 karakter uzunluğunda olmalı; bir büyük harf, bir küçük harf, bir rakam ve bir özel karakter (#?!@$%^&*.-) içermelidir.';
            } else {
                try {
                    $user_data = $userModel->findById($user_id);
                    if ($user_data && password_verify($current_password, $user_data['password'])) {
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        if ($userModel->updateUserPassword($user_id, $hashed_new_password)) {
                            $response = ['success' => true, 'message' => 'Şifreniz başarıyla güncellendi.'];
                        } else {
                            $response['message'] = 'Şifre güncellenirken bir hata oluştu.';
                        }
                    } else {
                        $response['message'] = 'Mevcut şifreniz yanlış.';
                    }
                } catch (Exception $e) {
                    $response['message'] = $e->getMessage();
                }
            }
        }
        echo json_encode($response);
    }

    // Diğer ayar işlevleri buraya eklenecek (Gizlilik, Bildirimler vb.)
}
