/**
 * feed.js (NİHAİ VERSİYON - ÖNERİLEN KULLANICILAR TAKİP BUTONU DÜZELTİLDİ)
 * Ana akış ve gönderi etkileşimlerini yönetir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // =========================================================================
    // TÜM TIKLAMA OLAYLARI İÇİN TEK BİR MERKEZİ DİNLEYİCİ (Event Delegation)
    // Bu sayede dinamik olarak eklenen elementler de otomatik olarak çalışır.
    // =========================================================================
    document.body.addEventListener("click", function(event) {
        
        const likeButton = event.target.closest(".like-button");
        const commentToggleButton = event.target.closest('.comment-toggle-button');
        const saveButton = event.target.closest('.save-post-button');
        const reportButton = event.target.closest('.report-button');
        const copyLinkButton = event.target.closest('.copy-link-button');
        const whatsappButton = event.target.closest('.whatsapp-share-button');
        const editCaptionBtn = event.target.closest('.edit-caption-btn');
        const deletePostBtn = event.target.closest('.delete-post-btn');
        const sendShareMessageBtn = event.target.closest('.send-message-btn'); // Mesaj olarak gönder modalındaki buton
        const blockUserButton = event.target.closest('.block-user-button'); // Kullanıcı engelleme butonu
        const followButton = event.target.closest(".follow-button"); // Takip butonu

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

        if (followButton) { // Yeni eklenen takip butonu dinleyicisi
            handleFollowClick(followButton);
            return;
        }
    });

    // =========================================================================
    // OLAY YÖNETİCİ FONKSİYONLARI (Her bir eylem için ayrı fonksiyon)
    // =========================================================================

    function showLoginModal() {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
    }

    function handleLike(button) {
        if (typeof userId === 'undefined' || !userId) {
            showLoginModal();
            return;
        }

        button.disabled = true;
        const postId = button.dataset.postId;
        const heartIcon = button.querySelector(".heart-icon");
        const actionsContainer = button.closest('.post-actions');
        const likeCountSpan = actionsContainer ? actionsContainer.querySelector('.like-count') : null;
        
        const likedStatus = button.dataset.liked === "true";
        let currentLikes = likeCountSpan ? parseInt(likeCountSpan.textContent) : 0;

        // Optimistic Update
        if (likedStatus) {
            heartIcon?.classList.replace("fas", "far");
            if(likeCountSpan) likeCountSpan.textContent = Math.max(currentLikes - 1, 0);
            button.dataset.liked = "false";
        } else {
            heartIcon?.classList.replace("far", "fas");
            if(likeCountSpan) likeCountSpan.textContent = currentLikes + 1;
            button.dataset.liked = "true";
        }

        sendAjaxRequest(`${BASE_URL}post/like`, { post_id: postId },
            (data) => {
                if (data.success) {
                    if (likeCountSpan) {
                        likeCountSpan.textContent = data.new_likes;
                    }
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
                        if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                        button.dataset.liked = "true";
                    } else { 
                        heartIcon?.classList.replace("fas", "far");
                        if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                        button.dataset.liked = "false";
                    }
                    Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
                }
            },
            (error) => {
                // Rollback optimistic update
                if (likedStatus) {
                    heartIcon?.classList.replace("far", "fas");
                    if(likeCountSpan) likeCountSpan.textContent = currentLikes;
                    button.dataset.liked = "true";
                } else {
                    heartIcon?.classList.replace("fas", "far");
                    if(likeCountSpan) likeCountSpan.textContent = currentLikes;
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

    function handleCommentToggle(button) {
        const postId = button.dataset.postId;
        const commentsContainer = document.getElementById(`comments-${postId}`);
        if (!commentsContainer) return;

        const isVisible = commentsContainer.style.display === 'block';
        if (isVisible) {
            commentsContainer.style.display = 'none';
        } else {
            commentsContainer.style.display = 'block';
            if (!commentsContainer.dataset.loaded) {
                commentsContainer.querySelector('.comment-list').innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
                
                // Yorumları çekerken PostController'a yönlendiriyoruz
                fetch(`${BASE_URL}post/get_comments?post_id=${postId}&csrf_token=${csrfToken}`)
                    .then(response => {
                        if (!response.ok) { 
                            return response.text().then(text => { throw new Error(text); });
                        }
                        return response.text(); // HTML olarak bekliyoruz
                    })
                    .then(html => {
                        commentsContainer.querySelector('.comment-list').innerHTML = html; 
                        commentsContainer.dataset.loaded = "true";
                        attachDeleteCommentListeners(); 
                    })
                    .catch(error => {
                        console.error('Yorumlar yüklenemedi:', error);
                        commentsContainer.querySelector('.comment-list').innerHTML = `<p class="text-center text-danger p-3">Yorumlar yüklenemedi: ${error.message || 'Bilinmeyen Hata'}</p>`;
                    });
            }
        }
    }
    
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
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
                }
            }, null, () => { button.disabled = false; }
        );
    }

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
                sendAjaxRequest(`${BASE_URL}report/content`, { type: contentType, id: contentId, reason: result.value },
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

    function handleWhatsappShare(button, event) {
        event.preventDefault();
        const postId = button.dataset.postId;
        const postCaption = button.dataset.postCaption || "bu gönderiye bir bak";
        const postUrl = `${BASE_URL}post/${postId}`;
        const shareText = `Solaris'teki şu gönderiye bir bak: "${postCaption}"\n\n${postUrl}`;
        const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
        window.open(whatsappUrl, '_blank');
    }

    function handleEditCaption(button) {
        if (typeof userId === 'undefined' || !userId) {
            showLoginModal();
            return;
        }

        const postId = button.dataset.postId;
        const captionDisplay = document.getElementById(`captionDisplay-${postId}`);
        const originalCaptionText = captionDisplay.textContent.trim();

        if (document.getElementById(`editCaptionForm-${postId}`)) {
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

        const editForm = document.getElementById(`editCaptionForm-${postId}`);
        const captionInput = editForm.querySelector('.caption-input');
        const saveBtn = editForm.querySelector('.save-caption-btn');
        const cancelBtn = editForm.querySelector('.cancel-caption-btn');

        captionInput.focus();

        saveBtn.addEventListener('click', function() {
            const newCaption = captionInput.value.trim();
            if (newCaption === originalCaptionText) { 
                cancelBtn.click();
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

    function handleDeletePost(button) {
        if (typeof userId === 'undefined' || !userId) {
            showLoginModal();
            return;
        }

        const postId = button.dataset.postId;
        const postOwnerUsername = button.dataset.owner;

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
                                const postCard = document.querySelector(`.card[data-post-id="${postId}"]`); 
                                if (postCard) {
                                    postCard.remove();
                                } else {
                                    window.location.href = `${BASE_URL}profile/${postOwnerUsername}`;
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

    function handleSendShareMessage(button) {
        if (typeof userId === 'undefined' || !userId) {
            showLoginModal();
            return;
        }
        const receiverId = button.dataset.receiverId;
        const currentPostId = button.closest('.modal-content').querySelector('[data-post-id]').dataset.postId; 
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

    // TAKİP ETME/TAKİBİ BIRAKMA
    function handleFollowClick(button) { // Parametre olarak doğrudan butonu alıyoruz
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

        // AJAX isteğini User Controller'a yönlendiriyoruz
        const url = `${BASE_URL}user/toggle_follow`; 
        const formData = { following_id: followingId };

        sendAjaxRequest(url, formData,
            (data) => {
                if (data.success) {
                    // Profil sayfasındaki takipçi sayacını güncelle
                    const followerCountEl = document.getElementById("followerCount");
                    if (followerCountEl) {
                        followerCountEl.textContent = data.newFollowerCount;
                    }
            
                    if (data.action === "followed") {
                        button.classList.replace("btn-primary", "btn-outline-secondary");
                        button.innerHTML = 'Takip Ediliyor';
                        button.dataset.isFollowing = "true";
                    } else if (data.action === "unfollowed") {
                        button.classList.replace("btn-outline-secondary", "btn-primary");
                        button.innerHTML = 'Takip Et';
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


    // YORUM EKLEME (Form submit olayı)
    function attachCommentFormHandlers() {
        document.querySelectorAll('form.add-comment-form').forEach(form => {
            if (form.dataset.bound === "true") return;
            form.dataset.bound = "true";
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (typeof userId === 'undefined' || !userId) {
                    showLoginModal();
                    return;
                }

                const postId = this.dataset.postId;
                const commentInput = this.querySelector('.comment-input');
                const commentText = commentInput.value.trim();
                const submitButton = this.querySelector('.comment-submit-button, .btn[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
    
                if (commentText === '') return;
    
                submitButton.disabled = true;
                submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
    
                const formData = { 
                    post_id: postId, 
                    comment_text: commentText,
                };
    
                sendAjaxRequest(
                    `${BASE_URL}post/add_comment`, 
                    formData,
                    (data) => {
                        if (data.success && data.comment_html) {
                            const commentsContainer = document.getElementById(`comments-${postId}`);
                            const commentList = commentsContainer.querySelector('.comment-list');
                            
                            if (commentList) {
                                const noCommentsMessage = commentList.querySelector('.no-comments');
                                if (noCommentsMessage) noCommentsMessage.remove();
                                
                                commentList.insertAdjacentHTML('beforeend', data.comment_html); 
                                commentInput.value = '';
                                
                                const commentCountSpan = document.querySelector(`.comment-toggle-button[data-post-id="${postId}"] .comment-count`);
                                if (commentCountSpan) {
                                    commentCountSpan.textContent = parseInt(commentCountSpan.textContent) + 1;
                                }
                                
                                attachDeleteCommentListeners();
                            }
                        } else {
                            Swal.fire({ 
                                icon: 'error', 
                                title: 'Hata!', 
                                text: data.message || 'Yorum eklenemedi.' 
                            });
                        }
                    },
                    (error) => {
                        console.error('Yorum eklenirken hata oluştu:', error);
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Bağlantı Hatası!', 
                            text: 'Yorum eklenirken bir hata oluştu.' 
                        });
                    },
                    () => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                );
            });
        });
    }

    // YORUM SİLME (Event Delegation ile)
    function handleDeleteComment(event) {
        event.preventDefault();
        const button = event.target.closest('.delete-comment-btn'); 
        if (!button) return; 

        const commentId = button.dataset.commentId;
        const commentItem = button.closest('.comment-item');
        const postId = button.closest('.comments-container').id.replace('comments-', '');

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

    // Yorum silme dinleyicilerini bağlamak için yardımcı fonksiyon
    function attachDeleteCommentListeners() {
        document.querySelectorAll('.delete-comment-btn').forEach(button => {
            if (!button.dataset.listenerAttached) {
                button.addEventListener('click', handleDeleteComment);
                button.dataset.listenerAttached = "true";
            }
        });
    }

    // Sayfa yüklendiğinde ve yeni postlar eklendiğinde form ve butonları bağla
    attachCommentFormHandlers();
    attachDeleteCommentListeners();


    // -----------------------------------------------------------
    // BİLDİRİM VE MESAJ SAYACI (Header'daki rozetler için)
    // -----------------------------------------------------------
    function updateNavbarBadges(notificationCount, messageCount) {
        const notifBadges = [document.getElementById('unreadNotificationsBadge'), document.getElementById('unreadNotificationsBadgeMobile')];
        const msgBadges = [document.getElementById('unreadMessagesBadgeDesktop'), document.getElementById('unreadMessagesBadgeMobile')];

        notifBadges.forEach(b => {
            if (b) {
                b.textContent = notificationCount;
                b.classList.toggle('d-none', !notificationCount || notificationCount <= 0);
            }
        });

        const msgBadgeValue = messageCount > 9 ? '9+' : messageCount;
        msgBadges.forEach(b => {
            if (b) {
                b.textContent = msgBadgeValue;
                b.classList.toggle('d-none', !messageCount || messageCount <= 0);
            }
        });
    }

    function pollServerForUpdates() {
        if (typeof userId === 'undefined' || !userId) return;

        fetch(`${BASE_URL}notification/poll_updates?t=${Date.now()}`)
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                if (data.success) {
                    updateNavbarBadges(data.unread_notifications || 0, data.unread_messages || 0);
                }
            })
            .catch(error => console.error('Bildirimler alınırken hata:', error));
    }

    if (typeof userId !== 'undefined' && userId) {
        pollServerForUpdates();
        setInterval(pollServerForUpdates, 15000); // 15 saniyede bir kontrol et
    }

    // -----------------------------------------------------------
    // SONSUZ KAYDIRMA (INFINITE SCROLL)
    // -----------------------------------------------------------
    const trigger = document.getElementById('loading-trigger');
    const postsContainer = document.getElementById('posts-container-main'); 

    if (trigger && postsContainer) {
        let currentPage = 1; 
        let isLoading = false;
        let hasMore = true;

        const pageContext = postsContainer.dataset.context || 'home';
        const urlParams = new URLSearchParams(window.location.search);
        const pageFilter = urlParams.get('filter') || 'new';
        const username = postsContainer.dataset.username || '';

        const loadMorePosts = () => {
            if (isLoading || !hasMore) return;
            isLoading = true;
            trigger.style.display = 'block';

            let url = `${BASE_URL}post/load_more?page=${currentPage}&context=${pageContext}&filter=${pageFilter}`;
            if (username) {
                url += `&username=${username}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.posts.length > 0) {
                        data.posts.forEach(postHtml => { 
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = postHtml; 
                            const renderedPostCard = tempDiv.querySelector('.card'); 
                            if (renderedPostCard) {
                                postsContainer.appendChild(renderedPostCard);
                            }
                        });
                        currentPage++;
                        attachCommentFormHandlers();
                        attachDeleteCommentListeners();
                    } else {
                        hasMore = false;
                        trigger.innerHTML = '<p class="text-muted text-center my-4">Gösterilecek başka gönderi yok.</p>';
                    }
                })
                .catch(error => console.error("Gönderiler yüklenirken hata:", error))
                .finally(() => {
                    isLoading = false;
                    if(hasMore) trigger.style.display = 'none';
                });
        };

        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                loadMorePosts();
            }
        }, { threshold: 0.8 });

        observer.observe(trigger);
    }
});

// Yardımcı fonksiyon: AJAX istekleri için
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
