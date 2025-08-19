<div class="card mb-4 border-0 rounded-4 shadow-sm">
    <div class="card-body">
        <h6 class="text-dark fw-bold mb-3">Popüler Etiketler</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!empty($popular_tags)) { ?>
                <?php foreach ($popular_tags as $tag) { ?>
                    <?php $tag_name = str_replace('#', '', $tag['name']); ?>
                    <a href="<?php echo BASE_URL.'search?q='.urlencode('#'.$tag_name); ?>" class="btn btn-sm btn-outline-secondary rounded-pill">#<?php echo htmlspecialchars($tag_name); ?></a>
                <?php } ?>
            <?php } else { ?>
                <p class="text-muted small">Henüz popüler etiket yok.</p>
            <?php } ?>
        </div>
    </div>
</div>