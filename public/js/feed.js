/**
 * feed.js (NİHAİ VERSİYON - SADELEŞTİRİLMİŞ)
 * Ana akış ve gönderi etkileşimlerini yönetir.
 * Ortak işlevler utils.js'e taşınmıştır.
 */
document.addEventListener("DOMContentLoaded", function() {

    // Yorum gönderme formları için dinleyicileri bağla
    // Bu fonksiyon, yeni yüklenen postlar için de çağrılacak.
    function attachCommentFormHandlersForFeed() {
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
                            // Yorumlar konteynerini bul
                            const commentsContainer = document.getElementById(`comments-${postId}`);
                            const commentList = commentsContainer ? commentsContainer.querySelector('.comment-list') : null;
                            
                            if (commentList) {
                                const noCommentsMessage = commentList.querySelector('.no-comments');
                                if (noCommentsMessage) noCommentsMessage.remove(); 
                                
                                commentList.insertAdjacentHTML('beforeend', data.comment_html); 
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
    attachCommentFormHandlersForFeed();

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

        // CSRF token'ı GET isteği ile gönderilmeli
        fetch(`${BASE_URL}notification/poll_updates?t=${Date.now()}&csrf_token=${csrfToken}`)
            .then(response => {
                if (!response.ok) {
                    // Hata durumunda JSON yanıtını veya metni alıp Promise.reject ile fırlat
                    return response.json().catch(() => response.text()).then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateNavbarBadges(data.unread_notifications || 0, data.unread_messages || 0);
                } else {
                    console.error('Bildirimler alınırken hata:', data.message || 'Bilinmeyen hata');
                }
            })
            .catch(error => {
                console.error('Bildirimler alınırken ağ hatası:', error);
                // Swal.fire({ icon: 'error', title: 'Bildirim Hatası!', text: 'Bildirimler yüklenirken bir sorun oluştu.' });
            });
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

            let url = `${BASE_URL}post/load_more?page=${currentPage}&context=${pageContext}&filter=${pageFilter}&csrf_token=${csrfToken}`;
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
                        attachCommentFormHandlersForFeed(); // Yeni yorum formlarını bağla
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
