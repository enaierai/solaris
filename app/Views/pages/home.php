<div class="row">
    <div class="col-lg-7 col-xl-8">

        <?php if (!$is_logged_in) {
            // Giriş yapılmamışsa modal penceresi bileşenini çağır
            $this->view('components/login_modal');
        } ?>

        <?php if (empty($posts)) { ?>
            <div class="card mb-4 bg-light border-0 text-center shadow-sm">
                <div class="card-body p-5">
                    <h5 class="card-title text-dark">Akışınızda Henüz Gönderi Yok</h5>
                    <p class="card-text text-secondary">Yeni insanları ve etiketleri keşfederek akışınızı renklendirin!</p>
                    <a href="<?php echo BASE_URL; ?>explore" class="btn btn-primary">Keşfet'e Git</a>
                </div>
            </div>
        <?php } else { ?>
            <div id="posts-container-main">
            <?php
            // Her bir post için post_card_feed.php dosyasını doğrudan dahil ediyoruz.
            // Bu, değişken kapsamı sorunlarını ortadan kaldırır.
            foreach ($posts as $post) {
                // post_card_feed.php dosyasının içinde kullanacağı değişkenleri burada tanımlıyoruz.
                // Bu değişkenler, include edildiği zaman o dosyanın içinde erişilebilir olur.
                $post_data_for_card = $post; // Bileşene gönderilecek post verisi
                $is_logged_in_for_card = $is_logged_in; // Bileşene gönderilecek giriş durumu
                $current_user_id_for_card = $current_user_id; // Bileşene gönderilecek kullanıcı ID'si
                $page_name_for_card = $page_name ?? 'home'; // Hangi sayfada olduğunu bilsin

                // post_card_feed.php dosyasını dahil ediyoruz
                include __DIR__.'/../components/post_card_feed.php';
            }
            ?>
            </div>
            <div id="posts-container-ajax"></div>
            <div id="loading-trigger" class="text-center p-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>
        <?php } ?>

    </div>
    
    <div class="col-lg-5 col-xl-4">
        <div class="sticky-top pt-3" style="top: 80px;">
            <?php
                // Sağdaki bileşenleri, ihtiyaç duydukları verilerle birlikte çağır
                $this->view('components/sidebar_popular_tags', ['popular_tags' => $popular_tags]);
        if ($is_logged_in) {
            $this->view('components/sidebar_suggested_users', ['suggested_users' => $suggested_users]);
        }
        ?>
        </div>
    </div>
</div>
