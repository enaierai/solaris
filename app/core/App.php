<?php

class App
{
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        $routeHandled = false;

        // 1. ÖZEL ROTALARI KONTROL ET (En Öncelikli)
        if (!empty($url[0])) {
            switch ($url[0]) {
                case 'login':
                case 'register':
                case 'logout':
                    $this->controller = 'AuthController';
                    $this->method = $url[0];
                    array_shift($url); // Remove the controller segment
                    $routeHandled = true;
                    break;
                case 'post': // PostController için özel rota
                    $this->controller = 'PostController';
                    array_shift($url); // 'post' kısmını URL dizisinden kaldır

                    // Tanımlanmış AJAX metodları
                    $post_ajax_methods = [
                        'like', 'unlike', 'save', 'unsave', 'add_comment',
                        'get_comments', 'update_caption', 'delete_post', 'load_more',
                    ];

                    if (isset($url[0])) {
                        // Eğer bir sonraki segment tanımlı bir AJAX metodu ise
                        if (in_array($url[0], $post_ajax_methods)) {
                            $this->method = $url[0]; // Metodu ayarla
                            array_shift($url); // Metot adını URL dizisinden kaldır
                            $this->params = $url ? array_values($url) : []; // Kalan tüm segmentler parametre
                        } elseif (is_numeric($url[0])) {
                            // Eğer bir sonraki segment sayısal bir ID ise (gönderi detay sayfası)
                            $this->method = 'index'; // index metodunu çağır
                            $this->params = [(int) $url[0]]; // ID'yi parametre olarak geçir
                            array_shift($url); // ID'yi URL dizisinden kaldır
                        } else {
                            // Ne bir AJAX metodu ne de sayısal bir ID ise, varsayılan index metodunu çağır
                            // ve bu segmenti parametre olarak geçir (örn: post/some_invalid_string)
                            $this->method = 'index';
                            $this->params = [$url[0]];
                            array_shift($url);
                        }
                    } else {
                        // Sadece 'post' varsa, varsayılan olarak index metodunu çağır (örn: post/)
                        $this->method = 'index';
                    }
                    $routeHandled = true;
                    break;
                case 'user': // User ile ilgili AJAX işlemleri için
                    $this->controller = 'UserController';
                    array_shift($url); // Remove the controller segment
                    if (isset($url[0])) {
                        $this->method = $url[0];
                        array_shift($url); // Remove the method segment
                    }
                    $this->params = $url ? array_values($url) : []; // Remaining segments are parameters
                    $routeHandled = true;
                    break;
                case 'message': // Mesajlaşma AJAX işlemleri için
                    $this->controller = 'MessageController';
                    array_shift($url); // Remove the controller segment
                    if (isset($url[0])) {
                        $this->method = $url[0];
                        array_shift($url); // Remove the method segment
                    }
                    $this->params = $url ? array_values($url) : []; // Remaining segments are parameters
                    $routeHandled = true;
                    break;
                case 'notification': // Bildirim AJAX işlemleri için
                    $this->controller = 'NotificationController';
                    array_shift($url); // Remove the controller segment
                    if (isset($url[0])) {
                        $this->method = $url[0];
                        array_shift($url); // Remove the method segment
                    }
                    $this->params = $url ? array_values($url) : []; // Remaining segments are parameters
                    $routeHandled = true;
                    break;
                case 'report': // Raporlama AJAX işlemleri için
                    $this->controller = 'ReportController';
                    array_shift($url); // Remove the controller segment
                    if (isset($url[0])) {
                        $this->method = $url[0]; // report/add_report gibi
                        array_shift($url); // Remove the method segment
                    } else {
                        $this->method = 'index'; // report/ -> index()
                    }
                    $this->params = $url ? array_values($url) : []; // Remaining segments are parameters
                    $routeHandled = true;
                    break;
                case 'settings': // Ayarlar sayfası ve işlemleri için
                    $this->controller = 'SettingsController';
                    array_shift($url); // Remove the controller segment
                    if (isset($url[0])) {
                        $this->method = $url[0];
                        array_shift($url); // Remove the method segment
                    } else {
                        $this->method = 'index';
                    }
                    $this->params = $url ? array_values($url) : []; // Remaining segments are parameters
                    $routeHandled = true;
                    break;
                    // Diğer özel rotalar buraya eklenebilir
            }
        }

        // 2. STANDART ROTALARI KONTROL ET (/controller/method)
        if (!$routeHandled && !empty($url[0]) && file_exists('app/Controllers/'.ucfirst($url[0]).'Controller.php')) {
            $this->controller = ucfirst($url[0]).'Controller';
            array_shift($url); // Remove the controller segment
        }

        // 3. CONTROLLER'I YÜKLE
        require_once 'app/Controllers/'.$this->controller.'.php';
        $this->controller = new $this->controller();

        // 4. METODU BELİRLE (Eğer özel rota tarafından zaten belirlenmediyse)
        // Eğer özel rota parametreleri zaten belirlediyse, bu adımı atla
        if (!$routeHandled && isset($url[0]) && method_exists($this->controller, $url[0])) {
            $this->method = $url[0];
            array_shift($url); // Remove the method segment
        }

        // 5. PARAMETRELERİ AL
        // Eğer özel rota zaten params'ı belirlemediyse, kalan URL segmentlerini al
        if (empty($this->params)) {
            $this->params = $url ? array_values($url) : [];
        }

        // 6. ÇALIŞTIR
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl()
    {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }

        return [];
    }
}
