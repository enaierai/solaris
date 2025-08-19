<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__.'/../../includes/config.php';
include_once __DIR__.'/../../includes/db.php';
include_once __DIR__.'/../../includes/helpers.php'; // CSRF fonksiyonları için

// Kullanıcı giriş yapmamışsa, giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: '.BASE_URL.'public/pages/login.php');
    exit;
}

$meta_title = 'Yeni Gönderi Paylaş | Solaris';
$meta_description = "Solaris'e yeni fotoğraflar ve videolar yükleyin ve paylaşın.";
$meta_keywords = 'Solaris fotoğraf yükle, resim paylaş, sosyal medya yükleme, yeni gönderi';

include_once __DIR__.'/../..//includes/header.php';
?>
<style>
/* Upload Page Specific Styles */
.upload-file-input {
    background-color: #f8f9fa !important;
    border: 1px solid #ced4da !important;
    color: #495057 !important;
    padding: 0.75rem 1rem !important;
    transition: all 0.2s ease;
}

.upload-file-input:focus {
    border-color: var(--primary-blue) !important;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25) !important;
    background-color: #f8f9fa !important;
}

.upload-file-input::file-selector-button {
    background-color: var(--primary-blue);
    color: var(--text-light);
    border: none;
    padding: 0.75rem 1rem;
    margin-right: 1rem;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.upload-file-input::file-selector-button:hover {
    background-color: var(--hover-blue);
}

.caption-textarea {
    background-color: #f8f9fa !important;
    border: 1px solid #ced4da !important;
    color: #495057 !important;
    resize: vertical;
    min-height: 100px;
    transition: all 0.2s ease;
}

.caption-textarea:focus {
    border-color: var(--primary-blue) !important;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25) !important;
    background-color: #f8f9fa !important;
}

/* Hashtag Buttons */
.hashtag-btn {
    border-color: var(--primary-blue) !important;
    color: var(--primary-blue) !important;
    background-color: transparent !important;
    font-size: 0.85rem;
    padding: 0.35rem 0.8rem;
    transition: all 0.2s ease;
}

.hashtag-btn:hover {
    background-color: var(--primary-blue) !important;
    color: var(--text-light) !important;
    transform: translateY(-2px);
}

/* Upload Button */
.upload-button:hover {
    box-shadow: 0 6px 15px rgba(0, 123, 255, 0.5);
    transform: translateY(-2px);
}
</style>
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h3 class="mb-4 text-dark text-center fw-bold"><i class="fas fa-upload me-2 text-primary"></i>Yeni Gönderi Paylaş</h3>
            <p class="text-secondary text-center mb-5">Görsel hikayenizi ve videolarınızı paylaşın! Birden fazla dosya yükleyebilirsiniz.</p>

            <div id="upload-message-container"></div>

            <div class="card bg-white text-dark shadow-lg rounded-3">
                <div class="card-body p-4">
                    <form id="upload-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <div class="mb-4">
                            <label for="mediaFile" class="form-label fw-bold"><i class="fas fa-image me-2"></i>Fotoğraf/Video Seçin:</label>
                            <input type="file" class="form-control form-control-lg upload-file-input" id="mediaFile" name="media[]" accept="image/*,video/*" multiple required>
                            <div class="form-text text-muted mt-2">JPEG, PNG, GIF, MP4, MOV, WEBP formatları kabul edilir. Birden fazla dosya seçebilirsiniz.</div>
                        </div>
                        <div class="mb-4">
                            <label for="caption" class="form-label fw-bold"><i class="fas fa-edit me-2"></i>Açıklama (Opsiyonel):</label>
                            <textarea class="form-control caption-textarea" id="caption" name="caption" rows="4" placeholder="Gönderiniz hakkında bir şeyler yazın..."></textarea>
                            <div class="form-text text-muted mt-2">Açıklamanızda #hashtag kullanarak gönderinizi keşfedilebilir yapabilirsiniz.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fas fa-hashtag me-2"></i>Popüler Hashtagler:</label><br>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php
                                $popular_hashtags = ['#doğa', '#seyahat', '#sanat', '#teknoloji', '#yemek', '#spor', '#moda', '#müzik', '#kitap', '#film', '#gündoğumu', '#günbatımı', '#fotoğrafçılık', '#mutluluk', '#anıyakala'];
foreach ($popular_hashtags as $tag) {
    echo '<button type="button" class="btn btn-outline-primary btn-sm rounded-pill hashtag-btn" data-tag="'.$tag.'">'.$tag.'</button>';
}
?>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg upload-button" id="upload-button">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Yükle ve Paylaş
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__.'/../../includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('upload-form');
    const uploadButton = document.getElementById('upload-button');
    const messageContainer = document.getElementById('upload-message-container');
    const captionTextarea = document.getElementById('caption');

    function showMessage(type, text) {
        messageContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${text}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    document.querySelectorAll('.hashtag-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tag = this.dataset.tag;
            if (captionTextarea.value.length > 0 && captionTextarea.value.slice(-1) !== ' ' && !captionTextarea.value.endsWith(tag)) {
                captionTextarea.value += ' ';
            }
            if (!captionTextarea.value.includes(tag)) {
                captionTextarea.value += tag + ' ';
            }
            captionTextarea.focus();
        });
    });

    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault(); 

        uploadButton.disabled = true;
        uploadButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Yükleniyor...`;
        messageContainer.innerHTML = '';

        const formData = new FormData(uploadForm);

        try {
            const response = await fetch('<?php echo BASE_URL; ?>public/ajax/process_photo.php', {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                showMessage('success', result.message);
                uploadForm.reset();
            } else {
                showMessage('danger', result.message);
            }
        } catch (error) {
            console.error('Hata:', error);
            showMessage('danger', 'Sunucuya bağlanılamadı. Lütfen internet bağlantınızı kontrol edin.');
        } finally {
            uploadButton.disabled = false;
            uploadButton.innerHTML = `<i class="fas fa-cloud-upload-alt me-2"></i>Yükle ve Paylaş`;
        }
    });
});
</script>
