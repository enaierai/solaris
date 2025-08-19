<?php

class Controller
{
    // Model yüklemek için yardımcı fonksiyon
    public function model($model)
    {
        require_once 'app/Models/'.$model.'.php';

        // Model'i bir sınıf olarak başlatıp döndür.
        return new $model();
    }

    // View (görünüm) yüklemek için yardımcı fonksiyon
    public function view($view, $data = [])
    {
        // Veri dizisini değişkenlere ayır, böylece view içinde $posts gibi kullanabiliriz.
        extract($data);

        if (file_exists('app/Views/'.$view.'.php')) {
            require_once 'app/Views/'.$view.'.php';
        } else {
            // View dosyası bulunamazsa hata ver.
            exit('View does not exist: '.$view);
        }
    }
}
