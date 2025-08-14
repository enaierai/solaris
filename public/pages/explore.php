<?php
// public/pages/explore.php (NİHAİ VE TAM VERSİYON)
include_once __DIR__.'/../../includes/logic/explore.logic.php';
include_once __DIR__.'/../../includes/header.php';
?>

<div class="container my-4">

    <div class="filters d-flex justify-content-center gap-2 gap-md-3 mb-4 flex-wrap">
        <?php foreach ($filters as $key => $label) { ?>
            <a href="?filter=<?php echo $key; ?>" 
               class="btn <?php echo $filter === $key ? 'btn-primary' : 'btn-outline-primary'; ?> <?php echo ($key === 'following' && !$is_logged_in) ? 'disabled' : ''; ?>">
               <?php echo $label; ?>
            </a>
        <?php } ?>
    </div>

    <?php if (empty($posts)) { ?>
        <p class="text-center text-muted mt-5">Bu filtreye uygun gönderi bulunamadı.</p>
    <?php } else { ?>
        
        <div class="row" id="posts-container">
    <?php foreach ($posts as $post) {
        include __DIR__.'/../../includes/templates/post_card_grid.php';
    } ?>
</div>

        <div id="loading-trigger" class="text-center p-4">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div>
        </div>

    <?php } ?>
</div>

<style>
/* Keşfet Sayfası için Sade Stil */
.explore-card { transition: transform 0.2s ease-in-out; }
.explore-card:hover { transform: scale(1.03); }
.explore-card .card-img-top { width: 100%; height: 300px; object-fit: cover; }
.media-icon { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
</style>

<script>
    const PAGE_CONTEXT = 'explore';
</script>

<?php
$conn->close();
include_once __DIR__.'/../../includes/footer.php';
?>