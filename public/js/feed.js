/**
 * feed.js (NİHAİ VERSİYON)
 * Ana akış ve gönderi etkileşimlerini yönetir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // =========================================================================
    // TÜM TIKLAMA OLAYLARI İÇİN TEK BİR MERKEZİ DİNLEYİCİ
    // =========================================================================
    document.body.addEventListener("click", function(event) {
        
        const likeButton = event.target.closest(".like-button");
        const commentToggleButton = event.target.closest('.comment-toggle-button');
        const saveButton = event.target.closest('.save-post-button');
        const reportButton = event.target.closest('.report-button');
        const copyLinkButton = event.target.closest('.copy-link-button');
        const whatsappButton = event.target.closest('.whatsapp-share-button');
        
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
    });

    // =========================================================================
    // OLAY YÖNETİCİ FONKSİYONLARI
    // =========================================================================

    function handleLike(button) {
        button.disabled = true;
        const postId = button.dataset.postId;
        const heartIcon = button.querySelector(".heart-icon");
        const actionsContainer = button.closest('.post-actions');
        if (!actionsContainer) {
            console.error('Post actions container (.post-actions) bulunamadı!');
            button.disabled = false;
            return;
        }
        const likeCountSpan = actionsContainer.querySelector('.like-count');

        sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, { action: 'like', post_id: postId },
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
                    Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
                }
            },
            (error) => {
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
                commentsContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
                
                fetch(`${BASE_URL}public/ajax/post_handler.php?action=get_comments&post_id=${postId}&csrf_token=${csrfToken}`)
                    .then(response => {
                        if (!response.ok) { throw new Error('Sunucu yanıtı başarısız.'); }
                        return response.text();
                    })
                    .then(html => {
                        commentsContainer.innerHTML = html;
                        commentsContainer.dataset.loaded = "true";
                        attachDeleteCommentListeners(); 
                    })
                    .catch(error => {
                        console.error('Yorumlar yüklenemedi:', error);
                        commentsContainer.innerHTML = '<p class="text-center text-danger p-3">Yorumlar yüklenemedi.</p>';
                    });
            }
        }
    }
    
    function handleSave(button) {
        button.disabled = true;
        const postId = button.dataset.postId;
        const bookmarkIcon = button.querySelector('i.fa-bookmark');

        sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, { action: 'save', post_id: postId },
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
                }
            }, null, () => { button.disabled = false; }
        );
    }

    function handleReport(button, event) {
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
                sendAjaxRequest(`${BASE_URL}public/ajax/report_content.php`, { type: contentType, id: contentId, reason: result.value },
                    (data) => {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Teşekkürler!', text: data.message });
                        }
                    }
                );
            }
        });
    }

    function handleCopyLink(button, event) {
        event.preventDefault();
        const postId = button.dataset.postId;
        const postUrl = `${BASE_URL}public/pages/post.php?id=${postId}`;
        navigator.clipboard.writeText(postUrl).then(() => {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link panoya kopyalandı!', showConfirmButton: false, timer: 2000 });
        }).catch(err => {
            console.error('Link kopyalanamadı: ', err);
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Hata! Link kopyalanamadı.', showConfirmButton: false, timer: 2000 });
        });
    }

    function handleWhatsappShare(button, event) {
        event.preventDefault();
        const postId = button.dataset.postId;
        const postCaption = button.dataset.postCaption || "bu gönderiye bir bak";
        const postUrl = `${BASE_URL}public/pages/post.php?id=${postId}`;
        const shareText = `Solaris'teki şu gönderiye bir bak: "${postCaption}"\n\n${postUrl}`;
        const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
        window.open(whatsappUrl, '_blank');
    }

    // TAKİP / TAKİPTEN ÇIKMA (Bu, genel bir click listener içinde olmamalı çünkü butona özel)
    document.querySelectorAll(".follow-button").forEach(btn => {
        btn.addEventListener("click", handleFollowClick);
    });

    function handleFollowClick(e) {
        const btn = e.currentTarget;
        if (btn.dataset.loading === "true") return;

        const followingId = btn.dataset.followingId;
        if (!followingId) return;

        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.dataset.loading = "true";
        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

        sendAjaxRequest(`${BASE_URL}public/ajax/user_handler.php`, { action: 'toggle_follow', following_id: followingId },
            (data) => {
                if (data.success) {
                    const followerCountEl = document.getElementById("followerCount");
                    if (followerCountEl) followerCountEl.textContent = data.newFollowerCount;
            
                    if (data.action === "followed") {
                        btn.classList.replace("btn-primary", "btn-outline-secondary");
                        btn.innerHTML = 'Takip Ediliyor';
                        btn.dataset.isFollowing = "true";
                    } else if (data.action === "unfollowed") {
                        btn.classList.replace("btn-outline-secondary", "btn-primary");
                        btn.innerHTML = 'Takip Et';
                        btn.dataset.isFollowing = "false";
                    }
                } else {
                    btn.innerHTML = originalContent;
                }
            },
            (error) => { btn.innerHTML = originalContent; },
            () => {
                btn.disabled = false;
                btn.dataset.loading = "false";
            }
        );
    }

    // YORUM EKLEME (Bu form submit olayı olduğu için ayrı kalmalı)
    function attachCommentFormHandlers() {
        document.querySelectorAll('form.add-comment-form').forEach(form => {
            if (form.dataset.bound === "true") return;
            form.dataset.bound = "true";
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const commentInput = this.querySelector('.comment-input');
                const commentText = commentInput.value.trim();
                const submitButton = this.querySelector('.comment-submit-button, .btn[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
        
                if (commentText === '') return;
                submitButton.disabled = true;
                submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
        
                sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, { action: 'add_comment', post_id: postId, comment_text: commentText },
                    (data) => {
                        if (data.success && data.comment_html) {
                            const commentList = document.querySelector(`#comments-${postId} .comment-list`); // `post.php` için
                            const commentsContainer = document.getElementById(`comments-${postId}`); // `feed.php` için
                            
                            if (commentList) { // post.php'deki yapı
                                const noCommentsMessage = commentList.querySelector('.no-comments');
                                if (noCommentsMessage) noCommentsMessage.remove();
                                commentList.insertAdjacentHTML('afterbegin', data.comment_html);
                            } else if (commentsContainer) { // feed.php'deki yapı
                                const noCommentsMessage = commentsContainer.querySelector('.no-comments');
                                if (noCommentsMessage) noCommentsMessage.remove();
                                commentsContainer.insertAdjacentHTML('afterbegin', data.comment_html);
                            }

                            commentInput.value = '';
                    
                            const commentCountSpan = document.querySelector(`.comment-toggle-button[data-post-id="${postId}"] .comment-count`);
                            if (commentCountSpan) {
                                let currentCount = parseInt(commentCountSpan.textContent, 10) || 0;
                                commentCountSpan.textContent = currentCount + 1;
                            }
                            attachDeleteCommentListeners();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Yorum eklenemedi.'});
                        }
                    }, null, () => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                );
            });
        });
    }

    // YORUM SİLME (Bu silme olayı olduğu için ayrı bir fonksiyon)
    function handleDeleteComment(event) {
        event.preventDefault();
        const button = this;
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

                sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, { action: 'delete_comment', comment_id: commentId },
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
                        }
                    },
                    (error) => {
                        if (commentItem) commentItem.style.opacity = '1';
                        button.disabled = false;
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

    // Sayfa ilk yüklendiğinde var olan form ve butonları bağla
    attachCommentFormHandlers();
    attachDeleteCommentListeners();
});