
/**
 * Bu sihirli fonksiyon, DİĞER TÜM FONKSİYONLARIN DIŞINDA, global alanda durmalıdır.
 * Sunucudan gelen post verisinden bir HTML kartı oluşturur.
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
    const profileAvatar = post.profile_picture_url && post.profile_picture_url !== 'default_profile.png' ? `${BASE_URL}uploads/profile_pictures/${post.profile_picture_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(post.username)}&background=random&color=fff`;

    return `
    <div class="card mb-4 shadow-sm text-dark rounded-4 animate__animated animate__fadeInUp">
        <div class="card-body d-flex align-items-center"><a href="${BASE_URL}public/pages/profile.php?user=${post.username}"><img src="${profileAvatar}" class="rounded-circle me-2" width="45" height="45" alt="${post.username}" style="object-fit: cover;"></a><div><a href="${BASE_URL}public/pages/profile.php?user=${post.username}" class="text-dark fw-bold text-decoration-none d-block">${post.username}</a></div></div>
        <div class="card-img-top-container bg-light">${mediaHtml}</div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-2">
                <button type="button" class="btn btn-sm like-btn like-button" data-post-id="${post.post_id}" data-liked="${post.user_liked}"><i class="${likedClass} fa-heart me-1 heart-icon"></i><span class="like-count">${post.like_count}</span></button>
                <button type="button" class="btn btn-sm btn-outline-secondary comment-btn comment-toggle-button" data-post-id="${post.post_id}"><i class="far fa-comment-dots me-1"></i><span class="comment-count">${post.comment_count}</span></button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto save-post-button" data-post-id="${post.post_id}"><i class="far fa-bookmark"></i></button>
            </div>
            <p class="mb-1"><a href="${BASE_URL}public/pages/profile.php?user=${post.username}" class="text-dark fw-bold text-decoration-none me-2">${post.username}</a> ${post.caption}</p>
        </div>
    </div>`;
}

/**
 * KEŞFET SAYFASI için bir gönderi kartı HTML'i oluşturur.
 * Bu fonksiyon da DOMContentLoaded'in DIŞINDA olmalıdır.
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
                </div>
            </div>
        </a>
    </div>`;
}

document.addEventListener("DOMContentLoaded", function () {
    // -----------------------------------------------------------
    // TEMEL AYARLAR VE YARDIMCI FONKSİYONLAR
    // -----------------------------------------------------------
    if (typeof BASE_URL === "undefined") { console.error("BASE_URL tanımlı değil."); return; }
    const csrfTokenField = document.getElementById("csrf_token_field");
    const csrfToken = csrfTokenField ? csrfTokenField.value : "";
    if (!csrfToken) { console.error("CSRF Token bulunamadı."); return; }

    function sendAjaxRequest(method, url, data, successCallback, errorCallback, finallyCallback) {
        let formDataToSend = new FormData();
        if (data instanceof FormData) { formDataToSend = data; } 
        else { for (let key in data) { formDataToSend.append(key, data[key]); } }
        if (!formDataToSend.has("csrf_token")) { formDataToSend.append("csrf_token", csrfToken); }
        fetch(url, { method: method, body: formDataToSend })
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => { if (successCallback) successCallback(data); })
            .catch(error => { console.error("AJAX Hatası:", error); if (errorCallback) errorCallback(error); })
            .finally(() => { if (finallyCallback) finallyCallback(); });
    }

  // -----------------------------------------------------------
  // 1. BEĞENİ SİSTEMİ
  // -----------------------------------------------------------
  document.body.addEventListener("click", function (event) {
    const button = event.target.closest(".like-button");
    if (!button || button.disabled || event.target.closest('.view-likers')) return;
    
    const postId = button.dataset.postId;
    if (!postId) return;

    const likedStatus = button.dataset.liked === "true";
    const likeCountSpan = button.querySelector(".like-count");
    const heartIcon = button.querySelector("i.fa-heart");
    let currentLikes = likeCountSpan ? parseInt(likeCountSpan.textContent) : 0;

    // Optimistic Update: Kullanıcı butona bastığı anda arayüzü anında güncelle
    button.disabled = true;
    if (likedStatus) {
        heartIcon?.classList.replace("fas", "far");
        if(likeCountSpan) likeCountSpan.textContent = Math.max(currentLikes - 1, 0);
        button.dataset.liked = "false";
    } else {
        heartIcon?.classList.replace("far", "fas");
        if(likeCountSpan) likeCountSpan.textContent = currentLikes + 1;
        button.dataset.liked = "true";
    }

    const formData = new FormData();
    formData.append("post_id", postId);

    // Arka planda sunucuyla senkronize et
    sendAjaxRequest(
        BASE_URL + "public/ajax/likes.php", 
        formData,
        (data) => { // Başarı durumunda
            if (data.success) {
                // Sunucudan gelen en güncel beğeni sayısını ve durumu tekrar ayarla
                if (likeCountSpan) likeCountSpan.textContent = data.new_likes;
                button.dataset.liked = data.action === "liked" ? "true" : "false";
                heartIcon.className = `fa-heart me-1 heart-icon ${data.action === "liked" ? 'fas text-danger' : 'far'}`;
            } else {
                // Eğer sunucuda bir hata olursa, yapılan işlemi geri al
                if (likedStatus) {
                    heartIcon?.classList.replace("far", "fas");
                    if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                    button.dataset.liked = "true";
                } else {
                    heartIcon?.classList.replace("fas", "far");
                    if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                    button.dataset.liked = "false";
                }
                // Ve kullanıcıyı şık bir şekilde bilgilendir
                Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
            }
        },
        (error) => { // Sunucuya ulaşılamazsa
            // Hata durumunda da yapılan işlemi geri al
            if (likedStatus) {
                 heartIcon?.classList.replace("far", "fas");
                 if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                 button.dataset.liked = "true";
            } else {
                 heartIcon?.classList.replace("fas", "far");
                 if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                 button.dataset.liked = "false";
            }
            Swal.fire({ icon: 'error', title: 'Bağlantı Hatası!', text: 'Sunucuya ulaşılamadı. Lütfen tekrar deneyin.' });
        },
        () => { // Her durumda çalışacak olan
            button.disabled = false;
        }
    );
});

  // -----------------------------------------------------------
  // 2. YORUM SİSTEMİ
  // -----------------------------------------------------------
  function attachCommentFormHandlers() {
    document.querySelectorAll('form.add-comment-form').forEach(form => {
        if (form.dataset.bound === "true") return;
        form.dataset.bound = "true";
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const commentInput = this.querySelector('.comment-input');
            const commentText = commentInput.value.trim();
            const submitButton = this.querySelector('.comment-submit-button, .btn[type="submit"]');
            const originalButtonText = submitButton.innerHTML;

            if (commentText === '') return;
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('comment_text', commentText);

            sendAjaxRequest(BASE_URL + 'public/ajax/add_comment.php', formData,
                (data) => {
                    if (data.success && data.comment_html) {
                        const commentList = document.querySelector(`#comments-${postId} .comment-list`);
                        const noCommentsMessage = commentList.querySelector('.no-comments');
                        if (noCommentsMessage) noCommentsMessage.remove();

                        // ARTIK SADECE HAZIR GELEN HTML'İ EKLİYORUZ!
                        commentList.insertAdjacentHTML('afterbegin', data.comment_html);
                        
                        commentInput.value = '';
                        const commentCountSpan = document.querySelector(`.comment-toggle-button[data-post-id="${postId}"] .comment-count, #post.php .comment-count`);
                        if (commentCountSpan) {
                            commentCountSpan.textContent = parseInt(commentCountSpan.textContent) + 1;
                        }
                        attachDeleteCommentListeners(); // Yeni eklenen yorumun silme butonunu aktif et
                    } else {
                        Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Yorum eklenemedi.'});
                    }
                },
                null, // Hata callback'i
                () => { // Finally callback'i
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            );
        });
    });
}

function handleDeleteComment() {
    const commentId = this.dataset.commentId;
    const commentItem = this.closest('.comment-item');
    const postId = commentItem.closest('.comments-container').id.replace('comments-', '');

    // ESKİ confirm() YERİNE YENİ SWEETALERT2 ONAY PENCERESİ
    Swal.fire({
        title: 'Emin misin?',
        text: "Bu yorum kalıcı olarak silinecek!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kullanıcı "Evet, sil!" dediğinde çalışacak kod
            this.disabled = true;
            if (commentItem) commentItem.style.opacity = '0.5';

            sendAjaxRequest('POST', BASE_URL + "public/ajax/delete_comment.php", { comment_id: commentId },
                (data) => {
                    if (data.success) {
                        if (commentItem) commentItem.remove();
                        // Yorum sayacını da düşürelim
                        const commentCountSpan = document.querySelector(`.comment-toggle-button[data-post-id="${postId}"] .comment-count`);
                        if (commentCountSpan) {
                            commentCountSpan.textContent = Math.max(0, parseInt(commentCountSpan.textContent) - 1);
                        }
                        // BAŞARI DURUMUNDA KÜÇÜK BİR BİLDİRİM (TOAST)
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Yorum silindi',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire('Hata!', data.message || "Yorum silinemedi.", 'error');
                        if (commentItem) commentItem.style.opacity = '1';
                        this.disabled = false;
                    }
                },
                (error) => {
                    Swal.fire('Hata!', "Yorum silinirken bir sunucu hatası oluştu.", 'error');
                    if (commentItem) commentItem.style.opacity = '1';
                    this.disabled = false;
                }
            );
        }
    });
}


function attachDeleteCommentListeners() {
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        if (!button.dataset.listenerAttached) {
            button.addEventListener('click', handleDeleteComment);
            button.dataset.listenerAttached = "true";
        }
    });
}

attachCommentFormHandlers();
attachDeleteCommentListeners();

  // -----------------------------------------------------------
// 3. PROFİL SAYFASI İŞLEVLERİ (Resim Yükleme, Bio vb.)
// -----------------------------------------------------------

// Bu fonksiyonu, hem profil hem de kapak resmi için yeniden kullanılabilir hale getirelim.
if (document.querySelector('.profile-header')) {
        
    // Bu fonksiyonu global hale getiriyoruz ki diğer scriptler de erişebilsin
    window.updateFollowerCount = function(change) {
        const el = document.getElementById("followerCount");
        if(el) {
            let count = parseInt(el.textContent) || 0;
            el.textContent = Math.max(0, count + change);
        }
    }

    // Profil ve kapak resmi yükleme mantığı
    function setupImageUpload(triggerBtnId, inputId, ajaxUrl, imageType) {
        const triggerBtn = document.getElementById(triggerBtnId);
        const input = document.getElementById(inputId);
        if (!triggerBtn || !input) return;

        triggerBtn.addEventListener('click', () => input.click());

        input.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append(imageType === 'avatar' ? 'profile_picture' : 'cover_picture', file);
                formData.append('csrf_token', csrfToken);
                
                Swal.fire({
                    title: 'Yükleniyor...',
                    text: 'Lütfen bekleyin',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Başarılı!', 'Fotoğrafınız güncellendi.', 'success').then(() => {
                                window.location.reload(); // Sayfayı yenileyerek en güncel halini göster
                            });
                        } else {
                            Swal.fire('Hata!', data.message || 'Yükleme başarısız.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Hata!', 'Bir sunucu hatası oluştu.', 'error');
                    });
            }
        });
    }

    setupImageUpload('changeProfilePictureBtn', 'profilePictureInput', `${BASE_URL}public/ajax/upload_profile_picture.php`, 'avatar');
    setupImageUpload('changeCoverPictureBtn', 'coverPictureInput', `${BASE_URL}public/ajax/upload_cover_picture.php`, 'cover');

    // Sekmelerin URL'de görünmesini sağlayan kod
    const profileTabs = document.querySelectorAll('#profileTabs .nav-link');
    if (profileTabs.length > 0) {
        const hash = window.location.hash;
        if (hash) {
            const tabToActivate = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }
        profileTabs.forEach(tabEl => {
            tabEl.addEventListener('show.bs.tab', function (event) {
                const hash = event.target.getAttribute('data-bs-target');
                if (history.pushState) {
                    history.pushState(null, null, hash);
                } else {
                    window.location.hash = hash;
                }
            });
        });
    }
}

  // -----------------------------------------------------------
  // 4. BİYOGRAFİ DÜZENLEME
  // -----------------------------------------------------------
  const editBioBtn = document.getElementById("editBioBtn");
  if (editBioBtn) {
      const bioDisplay = document.getElementById("bioDisplay");
      const bioEditForm = document.getElementById("bioEditForm");
      const bioInput = document.getElementById("bioInput");
      const saveBioBtn = document.getElementById("saveBioBtn");
      const cancelBioBtn = document.getElementById("cancelBioBtn");

      editBioBtn.addEventListener("click", function () {
          bioDisplay.style.display = "none";
          editBioBtn.style.display = "none";
          bioEditForm.style.display = "block";
          bioInput.focus();
          bioInput.value = bioDisplay.textContent.trim();
      });

      cancelBioBtn.addEventListener("click", function () {
          bioEditForm.style.display = "none";
          bioDisplay.style.display = "block";
          editBioBtn.style.display = "inline-block";
      });

      saveBioBtn.addEventListener("click", function () {
          const newBio = bioInput.value.trim();
          const originalSaveBioBtnText = saveBioBtn.innerHTML;
          saveBioBtn.disabled = true;
          saveBioBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...';

          sendAjaxRequest('POST', BASE_URL + "public/ajax/update_bio.php", { bio: newBio },
              (data) => {
                  if (data.success) {
                      bioDisplay.innerHTML = data.new_bio.replace(/\n/g, "<br>");
                      bioEditForm.style.display = "none";
                      bioDisplay.style.display = "block";
                      editBioBtn.style.display = "inline-block";
                  } else {
                      alert(data.message);
                  }
              },
              (error) => { alert("Biyografi güncellenirken bir hata oluştu."); },
              () => {
                  saveBioBtn.disabled = false;
                  saveBioBtn.innerHTML = originalSaveBioBtnText;
              }
          );
      });
  }
  
  // -----------------------------------------------------------
  // 5. TAKİP SİSTEMİ
  // -----------------------------------------------------------
  // Not: updateFollowerCount fonksiyonunun profile.php içinde tanımlı olduğu varsayılmıştır.
  function handleFollowClick(e) {
    const btn = e.currentTarget;
    if (btn.dataset.loading === "true") return;

    const followingId = btn.dataset.followingId;
    if (!followingId) return;

    const isFollowing = btn.dataset.isFollowing === "true";
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.dataset.loading = "true";
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

    const url = isFollowing ? `${BASE_URL}public/ajax/unfollow.php` : `${BASE_URL}public/ajax/follow.php`;
    const formData = new FormData();
    formData.append("following_id", followingId);

    sendAjaxRequest("POST", url, formData,
        (data) => { // Başarı callback'i
            if (data.success) {
                if (data.action === "followed") {
                    btn.classList.replace("btn-primary", "btn-outline-secondary");
                    btn.innerHTML = '<i class="fas fa-user-check"></i> Takip Ediliyor';
                    btn.dataset.isFollowing = "true";
                    if (typeof updateFollowerCount === 'function') updateFollowerCount(1);
                } else if (data.action === "unfollowed") {
                    btn.classList.replace("btn-outline-secondary", "btn-primary");
                    btn.innerHTML = '<i class="fas fa-user-plus"></i> Takip Et';
                    btn.dataset.isFollowing = "false";
                    if (typeof updateFollowerCount === 'function') updateFollowerCount(-1);
                }
            } else {
                // HATA DURUMUNDA ŞIK BİR UYARI
                btn.innerHTML = originalContent; // Butonu eski haline getir
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: data.message || 'İşlem gerçekleştirilemedi.'
                });
            }
        },
        (error) => { // Hata callback'i
            btn.innerHTML = originalContent; // Butonu eski haline getir
            Swal.fire({
                icon: 'error',
                title: 'Bağlantı Hatası!',
                text: 'Sunucuya ulaşılamadı. Lütfen tekrar deneyin.'
            });
        },
        () => { // Finally callback'i (her durumda çalışır)
            btn.disabled = false;
            btn.dataset.loading = "false";
        }
    );
}

// Olay dinleyicilerini tüm .follow-button'lara bağla
document.querySelectorAll(".follow-button").forEach(btn => {
    btn.addEventListener("click", handleFollowClick);
});

  // -----------------------------------------------------------
  // 6. GÖNDERİ DÜZENLEME (post.php)
  // -----------------------------------------------------------
  const editCaptionBtn = document.getElementById('editCaptionBtn');
  if (editCaptionBtn) {
      const captionDisplay = document.getElementById('captionDisplay');
      const captionEditForm = document.getElementById('captionEditForm');
      const captionInput = document.getElementById('captionInput');
      const saveCaptionBtn = document.getElementById('saveCaptionBtn');
      const cancelCaptionBtn = document.getElementById('cancelCaptionBtn');
      
      editCaptionBtn.addEventListener('click', () => {
          captionEditForm.style.display = 'block';
          captionDisplay.style.display = 'none';
          captionInput.value = captionDisplay.textContent.trim();
      });

      cancelCaptionBtn.addEventListener('click', () => {
          captionEditForm.style.display = 'none';
          captionDisplay.style.display = 'block';
      });

      saveCaptionBtn.addEventListener('click', () => {
          const newCaption = captionInput.value.trim();
          if (newCaption === '') return;

          saveCaptionBtn.disabled = true;

          fetch(BASE_URL + 'public/ajax/update_post_caption.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  post_id: postId,
                  new_caption: newCaption,
                  csrf_token: csrfToken
              }),
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  captionDisplay.innerHTML = data.updated_caption_html;
                  captionEditForm.style.display = 'none';
                  captionDisplay.style.display = 'block';
              } else {
                  alert('Hata: ' + data.message);
              }
          })
          .finally(() => { saveCaptionBtn.disabled = false; });
      });
  }

  // -----------------------------------------------------------
  // 7. GÖNDERİ SİLME (post.php)
  // -----------------------------------------------------------
  const deletePostBtn = document.getElementById('deletePostBtn');
if (deletePostBtn) {
    deletePostBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // ESKİ confirm() YERİNE YENİ SWEETALERT2 ONAY PENCERESİ
        Swal.fire({
            title: 'Bu gönderiyi silmek istediğine emin misin?',
            text: "Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Kullanıcı "Evet, Sil!" dediğinde çalışacak kod
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';

                const formData = new FormData();
                formData.append('post_id', postId); // postId'nin bu scope'ta tanımlı olduğunu varsayıyoruz (post.php'den)
                formData.append('csrf_token', csrfToken);
                
                fetch(BASE_URL + 'public/ajax/delete_post.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // BAŞARI DURUMUNDA ŞIK BİR BİLDİRİM VE YÖNLENDİRME
                        Swal.fire('Silindi!', data.message, 'success').then(() => {
                            window.location.href = BASE_URL + 'public/pages/profile.php?user=' + postOwnerUsername;
                        });
                    } else {
                        // HATA DURUMUNDA ŞIK BİR UYARI
                        Swal.fire('Hata!', data.message, 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    }
                })
                .catch(error => {
                    Swal.fire('Hata!', 'Sunucuya ulaşılamadı.', 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-trash-alt"></i>';
                });
            }
        });
    });
}

   // -----------------------------------------------------------
    // 8. GENEL MODAL SİSTEMİ (Beğenenler, Takipçiler, Takip Edilenler)
    // -----------------------------------------------------------
    const generalModalElement = document.getElementById('generalModal');
    if (generalModalElement) {
        const generalModal = new bootstrap.Modal(generalModalElement);
        const generalModalLabel = document.getElementById('generalModalLabel');
        const generalModalBody = document.getElementById('generalModalBody');

        // Modal'ı açan ana olay dinleyici
        document.body.addEventListener('click', function (event) {
            const likersTarget = event.target.closest('.view-likers');
            const followersTarget = event.target.closest('.view-followers');
            const followingTarget = event.target.closest('.view-following');
            let target = null, url = '', title = '', listType = '';

            if (likersTarget) {
                target = likersTarget;
                const postId = target.dataset.postId;
                if (!postId) return;
                url = `${BASE_URL}public/ajax/get_likers.php?post_id=${postId}`;
                title = 'Beğenenler';
                listType = 'likers';
            } else if (followersTarget) {
                target = followersTarget;
                const userId = target.dataset.userid;
                if (!userId) return;
                url = `${BASE_URL}public/ajax/get_follow_list.php?user_id=${userId}&type=followers`;
                title = 'Takipçiler';
                listType = 'followers';
            } else if (followingTarget) {
                target = followingTarget;
                const userId = target.dataset.userid;
                if (!userId) return;
                url = `${BASE_URL}public/ajax/get_follow_list.php?user_id=${userId}&type=following`;
                title = 'Takip Edilenler';
                listType = 'following';
            }

            if (!target) return;
            event.preventDefault();

            generalModalLabel.textContent = title;
            generalModalBody.innerHTML = `<div class="d-flex justify-content-center p-4"><div class="spinner-border" role="status"></div></div>`;
            generalModal.show();

            fetch(url).then(res => res.json()).then(data => {
                const userList = data.likers || data.users;
                if (data.success && userList) {
                    generalModalBody.innerHTML = '';
                    if (userList.length === 0) {
                        generalModalBody.innerHTML = `<p class="text-center text-muted">Gösterilecek kimse bulunamadı.</p>`;
                    } else {
                        userList.forEach(user => {
                            const profilePic = user.profile_picture_url ? `${BASE_URL}uploads/profile_pictures/${user.profile_picture_url}` : `${BASE_URL}uploads/profile_pictures/default_profile.png`;
                            
                            let buttonHtml = '';
                            // Sadece profil sahibiysek butonları göster
                            if (typeof isProfileOwner !== 'undefined' && isProfileOwner) {
                                if (listType === 'following') {
                                    buttonHtml = `<button class="btn btn-sm btn-outline-secondary ms-auto modal-unfollow-btn" data-user-id="${user.id}">Takipten Çık</button>`;
                                } else if (listType === 'followers') {
                                    buttonHtml = `<button class="btn btn-sm btn-outline-danger ms-auto modal-remove-follower-btn" data-user-id="${user.id}">Çıkar</button>`;
                                }
                            }

                            const userHtml = `
                                <div class="d-flex align-items-center mb-3" id="modal-user-row-${user.id}">
                                    <a href="${BASE_URL}public/pages/profile.php?user=${user.username}" class="d-flex align-items-center text-decoration-none text-dark">
                                        <img src="${profilePic}" class="rounded-circle me-3" width="40" height="40" alt="${user.username}" style="object-fit: cover;">
                                        <span class="fw-bold">${user.username}</span>
                                    </a>
                                    ${buttonHtml}
                                </div>`;
                            generalModalBody.insertAdjacentHTML('beforeend', userHtml);
                        });
                    }
                } else {
                    generalModalBody.innerHTML = `<p class="text-center text-danger">${data.message || 'Liste yüklenemedi.'}</p>`;
                }
            });
        });

        // Modal İÇİNDEKİ butonlar için olay dinleyici (event delegation)
        generalModalBody.addEventListener('click', function(event) {
            const unfollowBtn = event.target.closest('.modal-unfollow-btn');
            const removeFollowerBtn = event.target.closest('.modal-remove-follower-btn');

            if (unfollowBtn) {
                const userIdToUnfollow = unfollowBtn.dataset.userId;
                unfollowBtn.disabled = true;
                sendAjaxRequest('POST', `${BASE_URL}public/ajax/unfollow.php`, { following_id: userIdToUnfollow }, (data) => {
                    if (data.success) {
                        document.getElementById(`modal-user-row-${userIdToUnfollow}`).remove();
                        // Ana sayfadaki takipçi sayısını da güncelle
                        if (typeof updateFollowerCount === 'function') {
                           // Bu bizim Takip ETTİĞİMİZ kişi sayısı, o yüzden bu fonksiyonu çağırmıyoruz.
                           // Gerekirse followingCount için ayrı bir fonksiyon yazılabilir.
                        }
                    } else {
                        alert(data.message);
                        unfollowBtn.disabled = false;
                    }
                });
            }

            if (removeFollowerBtn) {
                if (!confirm("Bu takipçiyi çıkarmak istediğinizden emin misiniz?")) return;
                const userIdToRemove = removeFollowerBtn.dataset.userId;
                removeFollowerBtn.disabled = true;
                sendAjaxRequest('POST', `${BASE_URL}public/ajax/remove_follower.php`, { follower_id: userIdToRemove }, (data) => {
                    if (data.success) {
                        document.getElementById(`modal-user-row-${userIdToRemove}`).remove();
                        if (typeof updateFollowerCount === 'function') updateFollowerCount(-1);
                    } else {
                        alert(data.message);
                        removeFollowerBtn.disabled = false;
                    }
                });
            }
        });
    }

    // -----------------------------------------------------------
    // 9. BİLDİRİM VE MESAJ SAYACI GÜNCELLEYİCİ
    // -----------------------------------------------------------
  // Hem bildirim hem de mesaj rozetlerini günceller.
  function updateNavbarBadges(notificationCount, messageCount) {
    // ID'lerin doğru olduğundan emin ol
    const notifBadges = [document.getElementById('unreadNotificationsBadge'), document.getElementById('unreadNotificationsBadgeMobile')];
    const msgBadges = [document.getElementById('unreadMessagesBadgeDesktop'), document.getElementById('unreadMessagesBadgeMobile')];

    notifBadges.forEach(b => {
        if(b) {
            b.textContent = notificationCount;
            b.classList.toggle('d-none', notificationCount <= 0);
        }
    });

    const msgBadgeValue = messageCount > 5 ? '5+' : messageCount;
    msgBadges.forEach(b => {
        if(b) {
            b.textContent = msgBadgeValue;
            b.classList.toggle('d-none', messageCount <= 0);
        }
    });
}

// Sunucudan yeni verileri periyodik olarak çeken ana fonksiyon.
function pollServerForUpdates() {
    // userId değişkeni header.php'den global olarak tanımlanmış olmalı
    if (typeof userId === 'undefined' || !userId) return; 

    fetch(`${BASE_URL}public/ajax/long_poll_notifications.php?t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const notificationCount = data.notifications ? data.notifications.length : 0;
                const messageCount = data.unread_messages || 0;
                updateNavbarBadges(notificationCount, messageCount);
            } else if (data.error) {
                console.error('Sunucudan hata döndü:', data.error);
            }
        })
        .catch(error => console.error('Güncellemeler alınırken hata:', error));
}

// Sayfa ilk yüklendiğinde ve sonra her 7 saniyede bir kontrol et
pollServerForUpdates();
setInterval(pollServerForUpdates, 7000); 



 // -----------------------------------------------------------
    // 10. HESAP SİLME İŞLEVİ (YENİ EKLENDİ)
    // -----------------------------------------------------------
    const deleteAccountBtn = document.getElementById('confirmDeleteBtn');
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function() {
            const passwordInput = document.getElementById('deleteConfirmPassword');
            const password = passwordInput.value;
            const errorDiv = document.getElementById('deleteAccountError');

            if (password === '') {
                errorDiv.textContent = 'Onay için şifrenizi girmelisiniz.';
                return;
            }

            this.disabled = true;
            this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Siliniyor...`;
            errorDiv.textContent = '';

            const formData = new FormData();
            formData.append('password', password);
            // CSRF token sendAjaxRequest tarafından otomatik eklenir.

            sendAjaxRequest('POST', `${BASE_URL}public/ajax/delete_account.php`, formData, 
                (data) => {
                    if (data.success) {
                        alert(data.message);
                        window.location.href = data.redirect_url;
                    } else {
                        errorDiv.textContent = data.message;
                    }
                },
                (error) => {
                    errorDiv.textContent = 'Bir sunucu hatası oluştu. Lütfen tekrar deneyin.';
                },
                () => {
                    this.disabled = false;
                    this.innerHTML = 'Hesabımı Kalıcı Olarak Sil';
                    passwordInput.value = '';
                }
            );
        });
    }
    // =========================================================================
    // 11. SONSUZ KAYDIRMA (INFINITE SCROLL)
    // =========================================================================
   
    const trigger = document.getElementById('loading-trigger');
    const postsContainer = document.getElementById('posts-container-ajax') || document.getElementById('posts-container');
    
    if (trigger && postsContainer) { // Sadece ilgili sayfalarda çalışsın
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
    
        const pageContext = typeof PAGE_CONTEXT !== 'undefined' ? PAGE_CONTEXT : 'index';
        const urlParams = new URLSearchParams(window.location.search);
        const pageFilter = urlParams.get('filter') || 'new';
    
        const loadMorePosts = () => {
            if (isLoading || !hasMore) return;
            isLoading = true;
            trigger.style.display = 'block';
    
            const url = `${BASE_URL}public/ajax/load_more_posts.php?page=${currentPage}&context=${pageContext}&filter=${pageFilter}`;
    
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.posts.length > 0) {
                        data.posts.forEach(post => {
                            const postCardHtml = (pageContext === 'explore') 
                                ? createExploreCard(post) 
                                : createPostCard(post);
                            postsContainer.insertAdjacentHTML('beforeend', postCardHtml);
                        });
                        currentPage++;
                    } else {
                        hasMore = false;
                        trigger.innerHTML = '<p class="text-muted my-3">Gösterilecek başka gönderi yok.</p>';
                    }
                })
                .catch(error => console.error("Gönderiler yüklenirken hata:", error))
                .finally(() => {
                    isLoading = false;
                });
        };
    
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                loadMorePosts();
            }
        }, { threshold: 1.0 });
    
        observer.observe(trigger);
    } 

      // =========================================================================
    // 12. KARANLIK MOD / TEMA DEĞİŞTİRİCİ
    // =========================================================================
    const themeToggler = document.getElementById('theme-toggler');
    const themeIconDark = document.querySelector('.theme-icon-dark');
    const themeIconLight = document.querySelector('.theme-icon-light');
    const themeText = document.getElementById('theme-text');
    const docBody = document.body;

    const applyTheme = (theme) => {
        docBody.setAttribute('data-theme', theme);
        if (theme === 'dark') {
            themeIconDark.style.display = 'none';
            themeIconLight.style.display = 'inline-block';
            if (themeText) themeText.textContent = 'Aydınlık Mod';
        } else {
            themeIconDark.style.display = 'inline-block';
            themeIconLight.style.display = 'none';
            if (themeText) themeText.textContent = 'Karanlık Mod';
        }
    };

    // Sayfa yüklendiğinde hafızadaki temayı uygula
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    if (themeToggler) {
        themeToggler.addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = docBody.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    }

// =========================================================================
    // 13.: GÖNDERİ KAYDETME (BOOKMARK) SİSTEMİ
    // =========================================================================
    document.body.addEventListener('click', function(event) {
        const saveButton = event.target.closest('.save-post-button');
        if (!saveButton) return;

        saveButton.disabled = true; // Butona tekrar tıklanmasını engelle
        const postId = saveButton.dataset.postId;
        const isSaved = saveButton.dataset.saved === 'true';
        const bookmarkIcon = saveButton.querySelector('i.fa-bookmark');

        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('csrf_token', document.getElementById('csrf_token_field').value);

        // AJAX isteğini gönder
        fetch(`${BASE_URL}public/ajax/toggle_save_post.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.action === 'saved') {
                    saveButton.dataset.saved = 'true';
                    bookmarkIcon.classList.replace('far', 'fas');
                    // İsteğe bağlı: Başarı mesajı gösterebilirsin
                    // Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Gönderi kaydedildi!', showConfirmButton: false, timer: 1500 });
                } else if (data.action === 'unsaved') {
                    saveButton.dataset.saved = 'false';
                    bookmarkIcon.classList.replace('fas', 'far');
                }
            } else {
                alert(data.message || 'Bir hata oluştu.');
            }
        })
        .catch(error => console.error('Kaydetme hatası:', error))
        .finally(() => {
            saveButton.disabled = false; // Butonu tekrar aktif et
        });
    });

    // =========================================================================
    // 14.: PROFİL SAYFASI SEKMELERİ İÇİN JS
    // =========================================================================
    const profileTabs = document.querySelectorAll('#profileTabs .nav-link');
    if (profileTabs.length > 0) {
        // Sayfa yüklendiğinde URL'deki hash'e göre doğru sekmeyi aç
        const hash = window.location.hash;
        if (hash) {
            const tabToActivate = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }

        // Bir sekmeye tıklandığında URL'yi güncelle
        profileTabs.forEach(tabEl => {
            tabEl.addEventListener('show.bs.tab', function (event) {
                const hash = event.target.getAttribute('data-bs-target');
                if (history.pushState) {
                    history.pushState(null, null, hash);
                } else {
                    window.location.hash = hash;
                }
            });
        });
    }

  // =========================================================================
    // 15: ŞİKAYET ETME SİSTEMİ
    // =========================================================================
    document.body.addEventListener('click', function(event) {
        const reportButton = event.target.closest('.report-button');
        if (!reportButton) return;

        event.preventDefault();

        const contentType = reportButton.dataset.type; // 'post', 'comment' vs.
        const contentId = reportButton.dataset.id;
        
        Swal.fire({
            title: 'Bu içeriği neden şikayet ediyorsun?',
            input: 'select',
            inputOptions: {
                'spam': 'Spam veya yanıltıcı',
                'nudity': 'Çıplaklık veya cinsel içerik',
                'hate_speech': 'Nefret söylemi veya sembolleri',
                'violence': 'Şiddet veya tehlikeli organizasyonlar',
                'harassment': 'Taciz veya zorbalık',
                'other': 'Diğer'
            },
            inputPlaceholder: 'Bir sebep seçin...',
            showCancelButton: true,
            confirmButtonText: 'Gönder',
            cancelButtonText: 'İptal',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary me-2'
            },
            buttonsStyling: false,
            inputValidator: (value) => {
                if (!value) {
                    return 'Lütfen bir sebep seçin!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                
                const formData = new FormData();
                formData.append('type', contentType);
                formData.append('id', contentId);
                formData.append('reason', reason);
                // CSRF token'ı sendAjaxRequest fonksiyonu otomatik ekleyecek

                // Daha önce yazdığımız yardımcı fonksiyonu kullanalım
                sendAjaxRequest('POST', `${BASE_URL}public/ajax/report_content.php`, formData, 
                    (data) => { // Başarı durumunda
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Teşekkürler!', text: data.message });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Bir hata oluştu.' });
                        }
                    },
                    (error) => { // Hata durumunda
                        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Sunucuya ulaşılamadı.' });
                    }
                );
            }
        });
    });

// =========================================================================
    // 16: GÖNDERİ LİNKİNİ KOPYALAMA SİSTEMİ
    // =========================================================================
    document.body.addEventListener('click', function(event) {
        const copyLinkButton = event.target.closest('.copy-link-button');
        if (!copyLinkButton) return;

        event.preventDefault();

        const postId = copyLinkButton.dataset.postId;
        const postUrl = `${BASE_URL}public/pages/post.php?id=${postId}`;

        // Modern tarayıcılar için Pano API'sini kullan
        navigator.clipboard.writeText(postUrl).then(() => {
            // Başarılı olunca SweetAlert2 ile şık bir bildirim göster
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Link panoya kopyalandı!',
                showConfirmButton: false,
                timer: 2000 // 2 saniye sonra kendi kendine kapansın
            });
        }).catch(err => {
            console.error('Link kopyalanamadı: ', err);
            // Hata durumunda da kullanıcıyı bilgilendir
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Hata! Link kopyalanamadı.',
                showConfirmButton: false,
                timer: 2000
            });
        });
    });

// =========================================================================
    // 16.2: MESAJ OLARAK GÖNDERME SİSTEMİ
    // =========================================================================
    const shareModalElement = document.getElementById('shareViaMessageModal');
    if (shareModalElement) {
        const shareModal = new bootstrap.Modal(shareModalElement);
        const userListContainer = document.getElementById('shareUserList');
        const searchInput = document.getElementById('shareUserSearch');
        let currentPostId = null;

        // Modal açıldığında tetiklenir
        shareModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Modalı açan buton
            currentPostId = button.dataset.postId;
            
            // Kullanıcı listesini yükle
            userListContainer.innerHTML = `<div class="text-center p-4"><div class="spinner-border"></div></div>`;
            fetch(`${BASE_URL}public/ajax/get_follow_list.php?user_id=${userId}&type=following`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.users) {
                        renderUserList(data.users);
                    } else {
                        userListContainer.innerHTML = '<p class="text-muted text-center">Takip ettiğin kimse bulunamadı.</p>';
                    }
                });
        });

        // Arama kutusuna yazıldığında listeyi filtreler
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const users = userListContainer.querySelectorAll('.share-user-item');
            users.forEach(user => {
                const username = user.dataset.username.toLowerCase();
                user.style.display = username.includes(searchTerm) ? 'flex' : 'none';
            });
        });

        // Kullanıcı listesini HTML olarak oluşturur
        function renderUserList(users) {
            userListContainer.innerHTML = ''; // Listeyi temizle
            if (users.length === 0) {
                 userListContainer.innerHTML = '<p class="text-muted text-center">Takip ettiğin kimse bulunamadı.</p>';
                 return;
            }
            users.forEach(user => {
                const profilePic = user.profile_picture_url ? `${BASE_URL}uploads/profile_pictures/${user.profile_picture_url}` : `https://ui-avatars.com/api/?name=${user.username}&background=random&color=fff`;
                const userHtml = `
                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded share-user-item" data-username="${user.username}">
                        <div class="d-flex align-items-center">
                            <img src="${profilePic}" class="rounded-circle me-3" width="40" height="40" alt="${user.username}">
                            <span class="fw-bold">${user.username}</span>
                        </div>
                        <button class="btn btn-sm btn-primary send-message-btn" data-receiver-id="${user.id}">Gönder</button>
                    </div>`;
                userListContainer.insertAdjacentHTML('beforeend', userHtml);
            });
        }

        // "Gönder" butonuna tıklandığında çalışır (event delegation)
        userListContainer.addEventListener('click', function(event) {
            const sendButton = event.target.closest('.send-message-btn');
            if (!sendButton) return;

            const receiverId = sendButton.dataset.receiverId;
            const originalButtonText = sendButton.innerHTML;
            sendButton.disabled = true;
            sendButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('post_id', currentPostId);

            sendAjaxRequest('POST', `${BASE_URL}public/ajax/send_post_as_message.php`, formData,
                (data) => {
                    if (data.success) {
                        sendButton.innerHTML = '<i class="fas fa-check"></i> Gönderildi';
                        sendButton.classList.replace('btn-primary', 'btn-success');
                    } else {
                        sendButton.innerHTML = originalButtonText;
                        alert(data.message || 'Hata!');
                    }
                },
                null,
                () => { /* finally callback boş kalabilir */ }
            );
        });
    }

// =========================================================================
    // 16.3: KULLANICI ENGELLEME SİSTEMİ
    // =========================================================================
    document.body.addEventListener('click', function(event) {
        const blockButton = event.target.closest('.block-user-button');
        if (!blockButton) return;

        const blockedId = blockButton.dataset.blockedId;
        const isBlocked = blockButton.dataset.isBlocked === 'true';

        const confirmationText = isBlocked 
            ? "Bu kullanıcının engelini kaldırmak istediğinizden emin misiniz? Artık gönderilerinizi ve profilinizi görebilecek."
            : "Bu kullanıcıyı engellemek istediğinizden emin misiniz? Engellediğinizde birbirinizin gönderilerini ve profilini göremezsiniz.";

        Swal.fire({
            title: 'Emin misin?',
            text: confirmationText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: isBlocked ? 'Evet, Engeli Kaldır!' : 'Evet, Engelle!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                blockButton.disabled = true;
                
                const formData = new FormData();
                formData.append('blocked_id', blockedId);

                sendAjaxRequest('POST', `${BASE_URL}public/ajax/toggle_block_user.php`, formData, 
                    (data) => { // Başarı durumunda
                        if (data.success) {
                            Swal.fire('Başarılı!', data.action === 'blocked' ? 'Kullanıcı engellendi.' : 'Kullanıcının engeli kaldırıldı.', 'success')
                            .then(() => window.location.reload()); // Sayfayı yenileyerek en güncel durumu göster
                        } else {
                            Swal.fire('Hata!', data.message || 'Bir hata oluştu.', 'error');
                            blockButton.disabled = false;
                        }
                    },
                    (error) => { // Hata durumunda
                        Swal.fire('Hata!', 'Sunucuya ulaşılamadı.', 'error');
                        blockButton.disabled = false;
                    }
                );
            }
        });
    });

 // =========================================================================
    // 16.4: WHATSAPP'TA PAYLAŞMA SİSTEMİ
    // =========================================================================
    document.body.addEventListener('click', function(event) {
        const whatsappButton = event.target.closest('.whatsapp-share-button');
        if (!whatsappButton) return;

        event.preventDefault();

        const postId = whatsappButton.dataset.postId;
        const postCaption = whatsappButton.dataset.postCaption;
        const postUrl = `${BASE_URL}public/pages/post.php?id=${postId}`;
        
        // WhatsApp için paylaşım metnini oluşturalım
        const shareText = `Solaris'teki şu gönderiye bir bak: "${postCaption}"\n\n${postUrl}`;
        
        // WhatsApp'ın web linkini oluşturalım
        const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
        
        // Yeni bir sekmede WhatsApp'ı aç
        window.open(whatsappUrl, '_blank');
    });



});
