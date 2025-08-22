/**
 * post.js (NİHAİ VERSİYON - SADELEŞTİRİLMİŞ)
 * Gönderi özel sayfası (post.php) için özel JavaScript işlevleri.
 * Ortak işlevler utils.js'e taşınmıştır.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sadece gönderi özel sayfasındaysak devam et
    if (!document.querySelector('.container-fluid.py-4 .row.justify-content-center .col-md-7')) {
        return;
    }

    // Yorum gönderme formları için dinleyicileri bağla
    function attachCommentFormHandlersForPost() {
        document.querySelectorAll('form.add-comment-form').forEach(form => {
            if (form.dataset.bound === "true") return; // Zaten bağlanmışsa tekrar bağlama
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
                const originalButtonHtml = submitButton.innerHTML;
    
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
                            const commentsListContainer = document.querySelector('.comments-list-container');
                            if (commentsListContainer) {
                                const noCommentsMessage = commentsListContainer.querySelector('.no-comments');
                                if (noCommentsMessage) noCommentsMessage.remove(); 
                                
                                commentsListContainer.insertAdjacentHTML('beforeend', data.comment_html); 
                                commentInput.value = ''; 
                                
                                const commentCountSpan = document.querySelector(`.comment-toggle-button[data-post-id="${postId}"] .comment-count`);
                                if (commentCountSpan) {
                                    commentCountSpan.textContent = parseInt(commentCountSpan.textContent) + 1;
                                }
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
                        submitButton.innerHTML = originalButtonHtml;
                    }
                );
            });
        });
    }

    // Sayfa yüklendiğinde yorum formlarını bağla
    attachCommentFormHandlersForPost();

    // Yorumları açma/kapama butonu için özel sayfa mantığı
    const commentToggleButton = document.querySelector('.comment-toggle-button');
    if (commentToggleButton) {
        // Post sayfasında yorumlar varsayılan olarak açık olmalı veya açma/kapama butonu her zaman çalışmalı
        // Eğer yorumlar başlangıçta kapalıysa ve tıklamayla açılıyorsa, ilk yüklemede yorumları çek
        const postId = commentToggleButton.dataset.postId;
        const commentsContainer = document.querySelector('.comments-list-container');
        
        // Post sayfasında yorumlar genellikle varsayılan olarak görünür olmalı
        if (commentsContainer && !commentsContainer.dataset.loaded) {
            const commentListDiv = commentsContainer.querySelector('.comment-list');
            if (commentListDiv) {
                commentListDiv.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
            }
            fetch(`${BASE_URL}post/get_comments?post_id=${postId}&csrf_token=${csrfToken}`)
                .then(response => {
                    if (!response.ok) { 
                        // Hata durumunda JSON yanıtını veya metni alıp Promise.reject ile fırlat
                        return response.text().then(text => { throw new Error(text); });
                    }
                    return response.text();
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
});
