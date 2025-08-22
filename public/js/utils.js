/**
 * utils.js (NİHAİ VERSİYON - TÜM ORTAK ETKİLEŞİM İŞLEVLERİ)
 * Proje genelinde kullanılan temel yardımcı fonksiyonları ve ortak etkileşim işlevlerini içerir.
 * Bu dosya, diğer tüm script dosyalarından önce yüklenmelidir.
 */

// BASE_URL, csrfToken ve userId gibi global değişkenlerin header.php/footer.php'den geldiğini varsayıyoruz.

/**
 * Sayfanın temasını (aydınlık/karanlık) uygular.
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
 * Yardımcı fonksiyon: AJAX istekleri için
 */
function sendAjaxRequest(url, data, successCallback, errorCallback, finallyCallback) {
    let formDataToSend = new FormData();
    for (let key in data) {
        formDataToSend.append(key, data[key]);
    }
    if (typeof csrfToken !== 'undefined' && csrfToken) {
        formDataToSend.append("csrf_token", csrfToken);
    }

    fetch(url, {
        method: 'POST',
        body: formDataToSend
    })
    .then(response => {
        if (!response.ok) {
            // Hata durumunda JSON yanıtını veya metni alıp Promise.reject ile fırlat
            return response.json().catch(() => response.text()).then(err => Promise.reject(err));
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
            let errorMessage = 'Sunucuyla iletişim kurulamadı. Lütfen tekrar deneyin.';
            // Eğer hata bir string ise (response.text() tarafından yakalanmışsa)
            if (typeof error === 'string') {
                errorMessage = error; // Doğrudan hata mesajını kullan
            } else if (typeof error === 'object' && error !== null && error.message) {
                // Eğer hata bir obje ise ve bir mesajı varsa
                errorMessage = error.message;
            }
            // Eğer JSON parse hatası ise ve HTML döndürülmüşse
            if (errorMessage.includes("Unexpected token '<'") || errorMessage.includes("Unexpected end of JSON input")) {
                errorMessage = "Sunucudan beklenmeyen bir yanıt alındı. Lütfen sayfanızı yenileyin. (Muhtemel PHP hatası)";
            }

            Swal.fire({
                icon: 'error',
                title: 'Bir Hata Oluştu!',
                text: errorMessage
            });
        }
    })
    .finally(() => {
        if (finallyCallback) finallyCallback();
    });
}

/**
 * Giriş modalını gösterir.
 */
function showLoginModal() {
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
}

// =========================================================================
// ORTAK ETKİLEŞİM İŞLEVLERİ (Tüm sayfalarda kullanılacak)
// =========================================================================

/**
 * Beğenme/Beğenmekten Vazgeçme İşlevi
 */
function handleLike(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    button.disabled = true;
    const postId = button.dataset.postId;
    const heartIcon = button.querySelector(".heart-icon");
    // En yakın .post-actions container'ı bul
    const actionsContainer = button.closest('.post-actions');
    const likeCountSpan = actionsContainer ? actionsContainer.querySelector('.like-count') : null;
    
    const likedStatus = button.dataset.liked === "true";
    // Metin "X beğeni" formatında olduğu için sadece sayıyı al
    let currentLikes = likeCountSpan ? parseInt(likeCountSpan.textContent.split(' ')[0]) : 0;

    // Optimistic Update
    if (likedStatus) {
        heartIcon?.classList.replace("fas", "far");
        heartIcon?.classList.remove("text-danger"); // Kırmızı rengi kaldır
        if(likeCountSpan) likeCountSpan.textContent = `${Math.max(currentLikes - 1, 0)} beğeni`;
        button.dataset.liked = "false";
    } else {
        heartIcon?.classList.replace("far", "fas");
        heartIcon?.classList.add("text-danger"); // Kırmızı rengi ekle
        if(likeCountSpan) likeCountSpan.textContent = `${currentLikes + 1} beğeni`;
        button.dataset.liked = "true";
    }

    sendAjaxRequest(`${BASE_URL}post/like`, { post_id: postId },
        (data) => {
            if (data.success) {
                if (likeCountSpan) {
                    likeCountSpan.textContent = `${data.new_likes} beğeni`;
                }
                // Sunucudan gelen kesin duruma göre ikon ve dataset'i güncelle
                if (data.action === "liked") {
                    heartIcon.classList.remove("far");
                    heartIcon.classList.add("fas", "text-danger");
                    button.dataset.liked = "true";
                } else {
                    heartIcon.classList.remove("fas", "text-danger");
                    heartIcon.classList.add("far");
                    button.dataset.liked = "false";
                }
            } else {
                // Rollback optimistic update
                if (likedStatus) { 
                    heartIcon?.classList.replace("far", "fas");
                    heartIcon?.classList.add("text-danger");
                    if(likeCountSpan) likeCountSpan.textContent = `${currentLikes} beğeni`;
                    button.dataset.liked = "true";
                } else { 
                    heartIcon?.classList.replace("fas", "far");
                    heartIcon?.classList.remove("text-danger");
                    if(likeCountSpan) likeCountSpan.textContent = `${currentLikes} beğeni`;
                    button.dataset.liked = "false";
                }
                Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
            }
        },
        (error) => {
            // Rollback optimistic update
            if (likedStatus) {
                heartIcon?.classList.replace("far", "fas");
                heartIcon?.classList.add("text-danger");
                if(likeCountSpan) likeCountSpan.textContent = `${currentLikes} beğeni`;
                button.dataset.liked = "true";
            } else {
                heartIcon?.classList.replace("fas", "far");
                heartIcon?.classList.remove("text-danger");
                if(likeCountSpan) likeCountSpan.textContent = `${currentLikes} beğeni`;
                button.dataset.liked = "false";
            }
            console.error("Beğenme işlemi sırasında ağ hatası oluştu:", error);
            Swal.fire({ icon: 'error', title: 'Bağlantı Hatası!', text: 'Lütfen internet bağlantınızı kontrol edin.' });
        },
        () => {
            button.disabled = false;
        }
    );
}

/**
 * Yorumları açma/kapama ve yükleme işlevi
 */
function handleCommentToggle(button) {
    const postId = button.dataset.postId;
    // Yorum konteynerini bul (post.php için tekil, feed.php için gönderi bazında)
    const commentsContainer = document.getElementById(`comments-${postId}`);
    if (!commentsContainer) return;

    const isVisible = commentsContainer.style.display === 'block';
    if (isVisible) {
        commentsContainer.style.display = 'none';
    } else {
        commentsContainer.style.display = 'block';
        // Yorumları sadece ilk kez açıldığında yükle
        if (!commentsContainer.dataset.loaded) {
            const commentListDiv = commentsContainer.querySelector('.comment-list');
            if (commentListDiv) {
                commentListDiv.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
            }
            
            // Yorumları çekerken PostController'a yönlendiriyoruz
            // CSRF token'ı GET isteği ile gönderilmeli
            fetch(`${BASE_URL}post/get_comments?post_id=${postId}&csrf_token=${csrfToken}`)
                .then(response => {
                    if (!response.ok) { 
                        // Hata durumunda JSON yanıtını veya metni alıp Promise.reject ile fırlat
                        return response.text().then(text => { throw new Error(text); });
                    }
                    return response.text(); // HTML olarak bekliyoruz
                })
                .then(html => {
                    if (commentListDiv) {
                        commentListDiv.innerHTML = html; 
                        commentsContainer.dataset.loaded = "true";
                    }
                })
                .catch(error => {
                    console.error('Yorumlar yüklenemedi:', error);
                    let errorMessage = 'Yorumlar yüklenirken bir hata oluştu.';
                    if (typeof error === 'string') {
                        errorMessage = error;
                    } else if (typeof error === 'object' && error !== null && error.message) {
                        errorMessage = error.message;
                    }
                    if (errorMessage.includes("Unexpected token '<'") || errorMessage.includes("Unexpected end of JSON input")) {
                        errorMessage = "Sunucudan yorumlar yüklenirken beklenmeyen bir yanıt alındı. Lütfen sayfayı yenileyin.";
                    }
                    if (commentListDiv) {
                        commentListDiv.innerHTML = `<p class="text-center text-danger p-3">${errorMessage}</p>`;
                    }
                });
        }
    }
}

/**
 * Gönderiyi kaydetme/kaydetmekten vazgeçme işlevi
 */
function handleSave(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    button.disabled = true;
    const postId = button.dataset.postId;
    const bookmarkIcon = button.querySelector('i.fa-bookmark');

    sendAjaxRequest(`${BASE_URL}post/save`, { post_id: postId },
        (data) => {
            if (data.success) {
                if (data.action === 'saved') {
                    button.dataset.saved = 'true';
                    bookmarkIcon.classList.replace('far', 'fas');
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Gönderi kaydedildi!', showConfirmButton: false, timer: 1500 });
                } else if (data.action === 'unsaved') {
                    button.dataset.saved = 'false';
                    bookmarkIcon.classList.replace('fas', 'far');
                    Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Gönderi kaydedilenlerden çıkarıldı.', showConfirmButton: false, timer: 1500 });
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
            }
        }, null, () => { button.disabled = false; }
    );
}

/**
 * İçerik Raporlama İşlevi
 */
function handleReport(button, event) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    event.preventDefault();
    const contentType = button.dataset.type;
    const contentId = button.dataset.id;

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
        inputValidator: (value) => !value && 'Lütfen bir sebep seçin!'
    }).then((result) => {
        if (result.isConfirmed) {
            sendAjaxRequest(`${BASE_URL}report/add_report`, { type: contentType, id: contentId, reason: result.value },
                (data) => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Teşekkürler!', text: data.message });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Raporlama başarısız.' });
                    }
                }
            );
        }
    });
}

/**
 * Bağlantıyı Panoya Kopyalama İşlevi
 */
function handleCopyLink(button, event) {
    event.preventDefault();
    const postId = button.dataset.postId;
    const postUrl = `${BASE_URL}post/${postId}`;
    navigator.clipboard.writeText(postUrl).then(() => {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link panoya kopyalandı!', showConfirmButton: false, timer: 1500 });
    }).catch(err => {
        console.error('Link kopyalanamadı: ', err);
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Hata! Link kopyalanamadı.', showConfirmButton: false, timer: 2000 });
    });
}

/**
 * WhatsApp'ta Paylaşma İşlevi
 */
function handleWhatsappShare(button, event) {
    event.preventDefault();
    const postId = button.dataset.postId;
    const postCaption = button.dataset.postCaption || "bu gönderiye bir bak";
    const postUrl = `${BASE_URL}post/${postId}`;
    const shareText = `Solaris'teki şu gönderiye bir bak: "${postCaption}"\n\n${postUrl}`;
    const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
    window.open(whatsappUrl, '_blank');
}

/**
 * Gönderi Açıklamasını Düzenleme İşlevi
 */
function handleEditCaption(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    const postId = button.dataset.postId;
    const postCard = button.closest('.card'); // Gönderi kartını bul
    const captionDisplay = postCard.querySelector(`#captionDisplay-${postId}`); // Doğru captionDisplay'i bul
    const originalCaptionText = captionDisplay.textContent.trim();

    // Eğer zaten düzenleme formu açıksa tekrar açma
    if (postCard.querySelector(`#editCaptionForm-${postId}`)) {
        return;
    }

    const editFormHtml = `
        <div class="edit-caption-form mt-2" id="editCaptionForm-${postId}">
            <div class="input-group">
                <textarea class="form-control caption-input" rows="2">${originalCaptionText}</textarea>
                <button class="btn btn-primary save-caption-btn" type="button" data-post-id="${postId}">Kaydet</button>
                <button class="btn btn-secondary cancel-caption-btn" type="button" data-post-id="${postId}">İptal</button>
            </div>
        </div>
    `;
    captionDisplay.insertAdjacentHTML('afterend', editFormHtml);
    captionDisplay.style.display = 'none';

    const editForm = postCard.querySelector(`#editCaptionForm-${postId}`);
    const captionInput = editForm.querySelector('.caption-input');
    const saveBtn = editForm.querySelector('.save-caption-btn');
    const cancelBtn = editForm.querySelector('.cancel-caption-btn');

    captionInput.focus();

    saveBtn.addEventListener('click', function() {
        const newCaption = captionInput.value.trim();
        if (newCaption === originalCaptionText) { 
            cancelBtn.click(); // Değişiklik yoksa iptal et
            return;
        }
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...`;

        sendAjaxRequest(`${BASE_URL}post/update_caption`, { post_id: postId, new_caption: newCaption },
            (data) => {
                if (data.success) {
                    captionDisplay.innerHTML = data.updated_caption_html;
                    captionDisplay.style.display = 'block';
                    editForm.remove();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Açıklama güncellendi!', showConfirmButton: false, timer: 1500 });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Açıklama güncellenemedi.' });
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Kaydet';
                }
            },
            (error) => {
                Swal.fire({ icon: 'error', title: 'Bağlantı Hatası!', text: 'Açıklama güncellenirken bir hata oluştu.' });
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Kaydet';
            }
        );
    });

    cancelBtn.addEventListener('click', function() {
        captionDisplay.style.display = 'block';
        editForm.remove();
    });
}

/**
 * Gönderi Silme İşlevi
 */
function handleDeletePost(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    const postId = button.dataset.postId;
    const postOwnerUsername = button.dataset.owner; // Gönderi sahibinin kullanıcı adı

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
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';

            sendAjaxRequest(`${BASE_URL}post/delete_post`, { post_id: postId },
                (data) => {
                    if (data.success) {
                        Swal.fire('Silindi!', data.message, 'success').then(() => {
                            // Eğer gönderi özel sayfasındaysak, profil sayfasına yönlendir
                            if (window.location.pathname.includes('/post/')) {
                                window.location.href = `${BASE_URL}profile/${postOwnerUsername}`;
                            } else {
                                // Ana sayfadaysak veya başka bir yerde isek gönderi kartını kaldır
                                const postCard = document.querySelector(`.card[data-post-id="${postId}"]`); 
                                if (postCard) {
                                    postCard.remove();
                                }
                            }
                        });
                    } else {
                        Swal.fire('Hata!', data.message, 'error');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-trash-alt"></i> Sil';
                    }
                },
                (error) => {
                    Swal.fire('Hata!', 'Sunucuya ulaşılamadı.', 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash-alt"></i> Sil';
                }
            );
        }
    });
}

/**
 * Mesaj Olarak Gönder İşlevi
 */
function handleSendShareMessage(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }
    const receiverId = button.dataset.receiverId;
    // Gönderi ID'sini en yakın .modal-content içinden bul
    const currentPostId = button.closest('.modal-content')?.querySelector('[data-post-id]')?.dataset.postId; 
    if (!currentPostId) {
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Gönderi ID bulunamadı.' });
        return;
    }

    const originalButtonText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

    const formData = {
        receiver_id: receiverId,
        post_id: currentPostId,
    };

    sendAjaxRequest(`${BASE_URL}message/send_post`, formData, 
        (data) => {
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> Gönderildi';
                button.classList.replace('btn-primary', 'btn-success');
            } else {
                button.innerHTML = originalButtonText;
                Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Gönderilemedi.' });
            }
        },
        (error) => { 
            button.innerHTML = originalButtonText;
            Swal.fire({ icon: 'error', title: 'Bağlantı Hatası!', text: 'Mesaj gönderilirken bir hata oluştu.' });
        }
    );
}

/**
 * Kullanıcı Engelleme/Engeli Kaldırma İşlevi
 */
function handleBlockUser(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    const blockedId = button.dataset.blockedId;
    const isBlocked = button.dataset.isBlocked === 'true';
    const username = button.dataset.username || 'bu kullanıcıyı';

    const confirmationText = isBlocked ?
        `<b>${username}</b> kullanıcısının engelini kaldırmak istediğinizden emin misiniz? Artık gönderilerinizi ve profilinizi görebilecek.` :
        `<b>${username}</b> kullanıcısını engellemek istediğinizden emin misiniz? Engellediğinizde birbirinizin gönderilerini ve profilini göremezsiniz.`;

    Swal.fire({
        title: 'Emin misin?',
        html: confirmationText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: isBlocked ? 'Evet, Engeli Kaldır!' : 'Evet, Engelle!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            button.disabled = true;
            
            sendAjaxRequest(`${BASE_URL}user/toggle_block`, { blocked_id: blockedId }, 
                (data) => {
                    if (data.success) {
                        Swal.fire('Başarılı!', data.action === 'blocked' ? 'Kullanıcı engellendi.' : 'Kullanıcının engeli kaldırıldı.', 'success')
                        .then(() => window.location.reload());
                    } else {
                        Swal.fire('Hata!', data.message || 'İşlem başarısız.', 'error');
                        button.disabled = false;
                    }
                },
                (error) => {
                    Swal.fire('Hata!', 'Sunucuya ulaşılamadı.', 'error');
                    button.disabled = false;
                }
            );
        }
    });
}

/**
 * Takip Etme/Takibi Bırakma İşlevi
 */
function handleFollowClick(button) {
    if (typeof userId === 'undefined' || !userId) {
        showLoginModal();
        return;
    }

    if (button.dataset.loading === "true") return;

    const followingId = button.dataset.followingId;
    if (!followingId) return;

    const isFollowing = button.dataset.isFollowing === "true";
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.dataset.loading = "true";
    button.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

    const url = `${BASE_URL}user/toggle_follow`; 
    const formData = { following_id: followingId };

    sendAjaxRequest(url, formData,
        (data) => {
            if (data.success) {
                const followerCountEl = document.getElementById("followerCount");
                if (followerCountEl) {
                    followerCountEl.textContent = data.newFollowerCount;
                }
        
                if (data.action === "followed") {
                    button.classList.replace("btn-primary", "btn-outline-secondary");
                    button.innerHTML = '<i class="fas fa-user-check"></i> Takip Ediliyor'; // İkonu ekle
                    button.dataset.isFollowing = "true";
                } else if (data.action === "unfollowed") {
                    button.classList.replace("btn-outline-secondary", "btn-primary");
                    button.innerHTML = '<i class="fas fa-user-plus"></i> Takip Et'; // İkonu ekle
                    button.dataset.isFollowing = "false";
                }
            } else {
                button.innerHTML = originalContent;
                Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
            }
        },
        (error) => { 
            button.innerHTML = originalContent; 
            Swal.fire({ icon: 'error', title: 'Bağlantı Hatası!', text: 'Sunucuya ulaşılamadı. Lütfen tekrar deneyin.' });
        },
        () => {
            button.disabled = false;
            button.dataset.loading = "false";
        }
    );
}

/**
 * Yorum Silme İşlevi
 */
function handleDeleteComment(event) {
    event.preventDefault();
    const button = event.target.closest('.delete-comment-btn'); 
    if (!button) return; 

    const commentId = button.dataset.commentId;
    const commentItem = button.closest('.comment-item');
    // postId'yi commentItem'ın üstündeki comments-list-container'dan veya post-actions'dan alabiliriz
    // Bu, yorumun hangi gönderiye ait olduğunu bulmak için dinamik bir yaklaşımdır.
    const postId = button.closest('.card')?.querySelector('.post-actions')?.dataset.postId || 
                   button.closest('.comments-list-container')?.dataset.postId; // post.php için comments-list-container'a data-post-id eklenmeli

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
            button.disabled = true;
            if (commentItem) commentItem.style.opacity = '0.5';

            sendAjaxRequest(`${BASE_URL}post/delete_comment`, { comment_id: commentId },
                (data) => {
                    if (data.success) {
                        if (commentItem) commentItem.remove();
                        // Yorum sayısını güncelle
                        const commentCountSpan = document.querySelector(`.comment-toggle-button[data-post-id="${postId}"] .comment-count`);
                        if (commentCountSpan) {
                            commentCountSpan.textContent = Math.max(0, parseInt(commentCountSpan.textContent) - 1);
                        }
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Yorum silindi', showConfirmButton: false, timer: 1500 });
                    } else {
                        if (commentItem) commentItem.style.opacity = '1';
                        button.disabled = false;
                        Swal.fire('Hata!', data.message || "Yorum silinemedi.", 'error');
                    }
                },
                (error) => {
                    if (commentItem) commentItem.style.opacity = '1';
                    button.disabled = false;
                    Swal.fire('Hata!', "Yorum silinirken bir sunucu hatası oluştu.", 'error');
                }
            );
        }
    });
}


// =========================================================================
// GLOBAL OLAY DİNLEYİCİSİ (Sadece bir kez DOMContentLoaded'da çalışacak)
// =========================================================================
document.addEventListener("DOMContentLoaded", function() {
    // Tüm tıklama olayları için tek bir merkezi dinleyici
    // Bu sayede dinamik olarak eklenen elementler de otomatik olarak çalışır.
    // Sadece bir kez dinleyici eklemek için bir flag kullanıyoruz.
    if (!document.body.dataset.globalInteractionListenerAttached) {
        document.body.addEventListener("click", function(event) {
            const likeButton = event.target.closest(".like-button");
            const commentToggleButton = event.target.closest('.comment-toggle-button');
            const saveButton = event.target.closest('.save-post-button');
            const reportButton = event.target.closest('.report-button');
            const copyLinkButton = event.target.closest('.copy-link-button');
            const whatsappButton = event.target.closest('.whatsapp-share-button');
            const editCaptionBtn = event.target.closest('.edit-caption-btn');
            const deletePostBtn = event.target.closest('.delete-post-btn');
            const sendShareMessageBtn = event.target.closest('.send-message-btn');
            const blockUserButton = event.target.closest('.block-user-button');
            const followButton = event.target.closest(".follow-button");
            const deleteCommentBtn = event.target.closest('.delete-comment-btn');

            if (likeButton) {
                handleLike(likeButton);
                return;
            }
            if (commentToggleButton) {
                handleCommentToggle(commentToggleButton);
                return;
            }
            if (saveButton) {
                handleSave(saveButton);
                return;
            }
            if (reportButton) {
                handleReport(reportButton, event);
                return;
            }
            if (copyLinkButton) {
                handleCopyLink(copyLinkButton, event);
                return;
            }
            if (whatsappButton) {
                handleWhatsappShare(whatsappButton, event);
                return;
            }
            if (editCaptionBtn) {
                handleEditCaption(editCaptionBtn);
                return;
            }
            if (deletePostBtn) {
                handleDeletePost(deletePostBtn);
                return;
            }
            if (sendShareMessageBtn) {
                handleSendShareMessage(sendShareMessageBtn);
                return;
            }
            if (blockUserButton) {
                handleBlockUser(blockUserButton);
                return;
            }
            if (followButton) {
                handleFollowClick(followButton);
                return;
            }
            if (deleteCommentBtn) {
                handleDeleteComment(event);
                return;
            }
        });
        document.body.dataset.globalInteractionListenerAttached = "true";
    }
});
