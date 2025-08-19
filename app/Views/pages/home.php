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
            <?php foreach ($posts as $post) { ?>
                <?php
                    // post_card_feed bileşenini çağırırken ona gerekli tüm verileri paslayalım
                    $this->view('components/post_card_feed', [
                        'post' => $post,
                        'is_logged_in' => $is_logged_in,
                        'current_user_id' => $current_user_id,
                    ]);
                ?>
            <?php } ?>
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