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
                    unset($url[0]);
                    $routeHandled = true;
                    break;
                case 'post': // PostController için özel rota
                    $this->controller = 'PostController';
                    if (isset($url[1])) {
                        $this->method = $url[1]; // post/like, post/add_comment, post/get_comments, post/load_more gibi
                        unset($url[1]);
                    } else {
                        $this->method = 'index'; // post/123 -> index(123)
                    }
                    unset($url[0]);
                    $routeHandled = true;
                    break;
                case 'user': // User ile ilgili AJAX işlemleri için
                    $this->controller = 'UserController'; // Yeni UserController oluşturulacak
                    if (isset($url[1])) {
                        $this->method = $url[1];
                        unset($url[1]);
                    }
                    unset($url[0]);
                    $routeHandled = true;
                    break;
                case 'message': // Mesajlaşma AJAX işlemleri için
                    $this->controller = 'MessageController'; // Yeni MessageController oluşturulacak
                    if (isset($url[1])) {
                        $this->method = $url[1];
                        unset($url[1]);
                    }
                    unset($url[0]);
                    $routeHandled = true;
                    break;
                case 'notification': // Bildirim AJAX işlemleri için
                    $this->controller = 'NotificationController'; // Yeni NotificationController oluşturulacak
                    if (isset($url[1])) {
                        $this->method = $url[1];
                        unset($url[1]);
                    }
                    unset($url[0]);
                    $routeHandled = true;
                    break;
                case 'report': // Raporlama AJAX işlemleri için
                    $this->controller = 'ReportController'; // Yeni ReportController oluşturulacak
                    if (isset($url[1])) {
                        $this->method = $url[1];
                        unset($url[1]);
                    }
                    unset($url[0]);
                    $routeHandled = true;
                    break;
                    // Diğer özel rotalar buraya eklenebilir
            }
        }

        // 2. STANDART ROTALARI KONTROL ET (/controller/method)
        if (!$routeHandled && !empty($url[0]) && file_exists('app/Controllers/'.ucfirst($url[0]).'Controller.php')) {
            $this->controller = ucfirst($url[0]).'Controller';
            unset($url[0]);
        }

        // 3. CONTROLLER'I YÜKLE
        require_once 'app/Controllers/'.$this->controller.'.php';
        $this->controller = new $this->controller();

        // 4. METODU BELİRLE (Eğer özel rota tarafından zaten belirlenmediyse)
        if (!$routeHandled && isset($url[1]) && method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
        }

        // 5. PARAMETRELERİ AL
        $this->params = $url ? array_values($url) : [];

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
