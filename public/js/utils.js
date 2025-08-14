/**
 * utils.js
 * Proje genelinde kullanılan yardımcı fonksiyonları içerir.
 * Bu dosya, diğer tüm script dosyalarından önce yüklenmelidir.
 */

// BASE_URL ve csrfToken gibi global değişkenlerin var olduğunu varsayıyoruz.
// Bu değişkenler genellikle ana HTML dosyasında <script> tag'i içinde tanımlanır.
const csrfToken = document.getElementById("csrf_token_field") ? document.getElementById("csrf_token_field").value : "";

/**
 * Sunucuya standart bir AJAX isteği gönderir.
 * @param {string} url - İstek yapılacak URL.
 * @param {FormData|Object} data - Gönderilecek veri.
 * @param {function} successCallback - Başarı durumunda çalışacak fonksiyon.
 * @param {function} errorCallback - Hata durumunda çalışacak fonksiyon.
 * @param {function} finallyCallback - Her durumda çalışacak fonksiyon.
 */
function sendAjaxRequest(url, data, successCallback, errorCallback, finallyCallback) {
    let formDataToSend = new FormData();
    if (data instanceof FormData) {
        formDataToSend = data;
    } else {
        for (let key in data) {
            formDataToSend.append(key, data[key]);
        }
    }
    if (!formDataToSend.has("csrf_token")) {
        formDataToSend.append("csrf_token", csrfToken);
    }

    fetch(url, {
            method: 'POST',
            body: formDataToSend
        })
        .then(response => {
            if (!response.ok) {
                // Hata durumunda sunucudan gelen mesajı yakalamaya çalış
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (successCallback) successCallback(data);
        })
        .catch(error => {
            console.error("AJAX Hatası:", error);
            if (errorCallback) errorCallback(error);
            else {
                // Genel hata yönetimi
                Swal.fire({
                    icon: 'error',
                    title: 'Bir Hata Oluştu!',
                    text: error.message || 'Sunucuyla iletişim kurulamadı. Lütfen tekrar deneyin.'
                });
            }
        })
        .finally(() => {
            if (finallyCallback) finallyCallback();
        });
}


/**
 * Sunucudan gelen post verisinden bir HTML gönderi kartı oluşturur.
 * @param {object} post - Gönderi bilgilerini içeren nesne.
 * @returns {string} - Oluşturulan HTML içeriği.
 */
function createPostCard(post) {
    let mediaHtml = '';
    if (post.media && post.media.length > 0) {
        if (post.media.length > 1) {
            const carouselItems = post.media.map((media, index) => {
                const mediaTag = media.type === 'video' ? `<video controls class="d-block w-100"><source src="${BASE_URL}uploads/posts/${media.url}" type="video/mp4"></video>` : `<img src="${BASE_URL}uploads/posts/${media.url}" class="d-block w-100" alt="Post Medyası">`;
                return `<div class="carousel-item ${index === 0 ? 'active' : ''}">${mediaTag}</div>`;
            }).join('');
            mediaHtml = `<div id="carousel-ajax-${post.post_id}" class="carousel slide"><div class="carousel-inner">${carouselItems}</div><button class="carousel-control-prev" type="button" data-bs-target="#carousel-ajax-${post.post_id}" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button><button class="carousel-control-next" type="button" data-bs-target="#carousel-ajax-${post.post_id}" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button></div>`;
        } else {
            const singleMedia = post.media[0];
            mediaHtml = singleMedia.type === 'video' ? `<video controls class="card-img-top rounded-0"><source src="${BASE_URL}uploads/posts/${singleMedia.url}" type="video/mp4"></video>` : `<img src="${BASE_URL}uploads/posts/${singleMedia.url}" class="card-img-top rounded-0" alt="Gönderi">`;
        }
    }

    const likedClass = post.user_liked ? 'fas text-danger' : 'far';
    const savedClass = post.user_saved ? 'fas' : 'far';
    const profileAvatar = post.profile_picture_url && post.profile_picture_url !== 'default_profile.png' ? `${BASE_URL}uploads/profile_pictures/${post.profile_picture_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(post.username)}&background=random&color=fff`;

    return `
    <div class="card mb-4 shadow-sm text-dark rounded-4 animate__animated animate__fadeInUp">
        <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
            <a href="${BASE_URL}public/pages/profile.php?user=${post.username}" class="d-flex align-items-center text-dark text-decoration-none">
                <img src="${profileAvatar}" class="rounded-circle me-2" width="45" height="45" alt="${post.username}" style="object-fit: cover;">
                <span class="fw-bold">${post.username}</span>
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item copy-link-button" href="#" data-post-id="${post.post_id}">Bağlantıyı Kopyala</a></li>
                    <li><a class="dropdown-item whatsapp-share-button" href="#" data-post-id="${post.post_id}" data-post-caption="${post.caption}">WhatsApp'ta Paylaş</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item report-button text-danger" href="#" data-type="post" data-id="${post.post_id}">Şikayet Et</a></li>
                </ul>
            </div>
        </div>
        <div class="card-img-top-container bg-light">${mediaHtml}</div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-2">
                <button type="button" class="btn btn-sm like-btn like-button" data-post-id="${post.post_id}" data-liked="${post.user_liked}">
                    <i class="${likedClass} fa-heart me-1 heart-icon"></i>
                    <span class="like-count">${post.like_count}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary comment-btn comment-toggle-button" data-post-id="${post.post_id}">
                    <i class="far fa-comment-dots me-1"></i>
                    <span class="comment-count">${post.comment_count}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#shareViaMessageModal" data-post-id="${post.post_id}">
                    <i class="far fa-paper-plane"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto save-post-button" data-post-id="${post.post_id}" data-saved="${post.user_saved}">
                    <i class="${savedClass} fa-bookmark"></i>
                </button>
            </div>
            <p class="mb-1"><a href="${BASE_URL}public/pages/profile.php?user=${post.username}" class="text-dark fw-bold text-decoration-none me-2">${post.username}</a> ${post.caption}</p>
            <a href="#" class="text-muted small view-likers" data-post-id="${post.post_id}">${post.like_count} beğeni</a>
        </div>
        <div class="card-footer bg-white border-0 p-0">
             <div class="comments-container" id="comments-${post.post_id}" style="display: none;">
                <!-- Yorumlar AJAX ile buraya yüklenecek -->
             </div>
        </div>
    </div>`;
}


/**
 * KEŞFET SAYFASI için bir gönderi kartı HTML'i oluşturur.
 * @param {object} post - Gönderi bilgilerini içeren nesne.
 * @returns {string} - Oluşturulan HTML içeriği.
 */
function createExploreCard(post) {
    const mediaTag = post.first_media_type === 'video' ? `<video muted preload="metadata" class="card-img-top"><source src="${BASE_URL}uploads/posts/${post.first_media_url}" type="video/mp4"></video><span class="media-icon"><i class="fas fa-play"></i></span>` : `<img src="${BASE_URL}uploads/posts/${post.first_media_url}" alt="Gönderi" loading="lazy" class="card-img-top">`;
    const collageIcon = post.media_count > 1 ? `<span class="media-icon collage-icon"><i class="fas fa-clone"></i></span>` : '';

    return `
    <div class="col-lg-4 col-md-6 mb-4">
        <a href="${BASE_URL}public/pages/post.php?id=${post.post_id}" class="card-link">
            <div class="card shadow-sm rounded-3 overflow-hidden position-relative explore-card">
                <div class="media-wrapper position-relative">
                    ${mediaTag}
                    ${collageIcon}
                    <div class="overlay">
                        <div class="overlay-text">
                            <span><i class="fas fa-heart"></i> ${post.like_count}</span>
                            <span><i class="fas fa-comment"></i> ${post.comment_count}</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>`;
}

/**
 * Sayfanın temasını (aydınlık/karanlık) uygular.
 * @param {string} theme - 'light' veya 'dark'.
 */
function applyTheme(theme) {
    const docBody = document.body;
    const themeIconDark = document.querySelector('.theme-icon-dark');
    const themeIconLight = document.querySelector('.theme-icon-light');
    const themeText = document.getElementById('theme-text');

    docBody.setAttribute('data-theme', theme);

    if (theme === 'dark') {
        if (themeIconDark) themeIconDark.style.display = 'none';
        if (themeIconLight) themeIconLight.style.display = 'inline-block';
        if (themeText) themeText.textContent = 'Aydınlık Mod';
    } else {
        if (themeIconDark) themeIconDark.style.display = 'inline-block';
        if (themeIconLight) themeIconLight.style.display = 'none';
        if (themeText) themeText.textContent = 'Karanlık Mod';
    }
}

/**
 * Profil sayfasındaki takipçi sayısını günceller.
 * @param {number} change - Değişim miktarı (+1 veya -1).
 */
function updateFollowerCount(change) {
    const el = document.getElementById("followerCount");
    if (el) {
        let count = parseInt(el.textContent) || 0;
        el.textContent = Math.max(0, count + change);
    }
}
