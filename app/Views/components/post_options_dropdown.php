<?php
// --- GÜVENLİ VE TEMİZ BİLEŞEN ---
// Bu bileşen, Controller'dan gelen şu değişkenlere ihtiyaç duyar:
// $post, $is_logged_in, $current_user_id

// Değişkenlerin varlığını ve içeriğini en başta kontrol edelim.
if (!isset($post) || !is_array($post)) {
    return; // Post verisi yoksa hiçbir şey gösterme.
}

// Değişkenleri güvenli hale getirelim (null kontrolü)
$is_logged_in = $is_logged_in ?? false;
$current_user_id = $current_user_id ?? null;
$is_owner = ($is_logged_in && isset($post['user_id']) && $post['user_id'] == $current_user_id);

// Post verilerini de güvenli hale getirelim
$post_id = $post['id'] ?? 0;
$username = $post['username'] ?? '';
$caption = $post['caption'] ?? '';
?>

<div class="dropdown">
    <button class="btn btn-sm btn-icon border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-ellipsis-h text-muted"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="postOptionsDropdown-<?php echo $post_id; ?>">
        <?php if ($is_logged_in) { ?>
            
            <?php if ($is_owner) { ?>
                <li><a class="dropdown-item edit-caption-btn" href="#" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-edit fa-fw me-2"></i> Açıklamayı Düzenle</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-danger delete-post-btn" href="#" data-post-id="<?php echo $post_id; ?>" data-owner="<?php echo htmlspecialchars($username); ?>"><i class="fas fa-trash-alt fa-fw me-2"></i> Gönderiyi Sil</a></li>
            
            <?php } else { ?>
                <li><a class="dropdown-item" href="<?php echo BASE_URL.'profile/'.htmlspecialchars($username); ?>"><i class="fas fa-user fa-fw me-2"></i> Profile Git</a></li>
            <?php } ?>
            
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item share-via-message-button" href="#" data-bs-toggle="modal" data-bs-target="#shareViaMessageModal" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-paper-plane fa-fw me-2"></i> Mesaj Olarak Gönder</a></li>
            <li><a class="dropdown-item copy-link-button" href="#" data-post-id="<?php echo $post_id; ?>"><i class="fas fa-link fa-fw me-2"></i> Bağlantıyı Kopyala</a></li>
            <li><a class="dropdown-item whatsapp-share-button" href="#" data-post-id="<?php echo $post_id; ?>" data-post-caption="<?php echo htmlspecialchars($caption); ?>"><i class="fab fa-whatsapp fa-fw me-2 text-success"></i> WhatsApp'ta Paylaş</a></li>
            
            <?php if (!$is_owner) { ?>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-danger report-button" href="#" data-type="post" data-id="<?php echo $post_id; ?>"><i class="fas fa-flag fa-fw me-2"></i> Gönderiyi Şikayet Et</a></li>
            <?php } ?>

        <?php } else { ?>
            <li><a class="dropdown-item" href="<?php echo BASE_URL.'login'; ?>"><i class="fas fa-sign-in-alt fa-fw me-2"></i> Seçenekler için giriş yap</a></li>
        <?php } ?>
    </ul>
</div>