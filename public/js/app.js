/**
 * app.js
 * Genel site işlevlerini ve başlatıcıları içerir.
 * Bu dosya, utils.js'den sonra her sayfada yüklenmelidir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // -----------------------------------------------------------
    // BİLDİRİM VE MESAJ SAYACI
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
        // userId değişkeninin header.php'den global olarak tanımlandığını varsayıyoruz
        if (typeof userId === 'undefined' || !userId) return;

        fetch(`${BASE_URL}public/ajax/long_poll_notifications.php?t=${Date.now()}`)
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                if (data.success) {
                    updateNavbarBadges(data.unread_notifications || 0, data.unread_messages || 0);
                }
            })
            .catch(error => console.error('Bildirimler alınırken hata:', error));
    }

    // Sadece giriş yapmış kullanıcılar için sayacı çalıştır
    if (typeof userId !== 'undefined' && userId) {
        pollServerForUpdates();
        setInterval(pollServerForUpdates, 15000); // 15 saniyede bir kontrol et
    }


    // -----------------------------------------------------------
    // HESAP SİLME (settings.php)
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

            sendAjaxRequest(`${BASE_URL}public/ajax/delete_account.php`, { password: password },
                (data) => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Hesap Silindi',
                            text: data.message,
                            icon: 'success',
                            allowOutsideClick: false,
                            confirmButtonText: 'Tamam'
                        }).then(() => {
                            window.location.href = data.redirect_url;
                        });
                    } else {
                        errorDiv.textContent = data.message;
                    }
                },
                (error) => {
                    errorDiv.textContent = error.message || 'Bir sunucu hatası oluştu. Lütfen tekrar deneyin.';
                },
                () => {
                    this.disabled = false;
                    this.innerHTML = 'Hesabımı Kalıcı Olarak Sil';
                    passwordInput.value = '';
                }
            );
        });
    }

    // -----------------------------------------------------------
    // SONSUZ KAYDIRMA (INFINITE SCROLL)
    // -----------------------------------------------------------
    const trigger = document.getElementById('loading-trigger');
    const postsContainer = document.getElementById('posts-container-ajax') || document.getElementById('posts-container');

    if (trigger && postsContainer) {
        let currentPage = 2; // Genellikle ilk sayfa zaten PHP ile basılmıştır.
        let isLoading = false;
        let hasMore = true;

        // Sayfa bağlamını ve filtresini al (index, explore, profile vs.)
        const pageContext = postsContainer.dataset.context || 'index';
        const urlParams = new URLSearchParams(window.location.search);
        const pageFilter = urlParams.get('filter') || 'new';
        const username = postsContainer.dataset.username || '';

        const loadMorePosts = () => {
            if (isLoading || !hasMore) return;
            isLoading = true;
            trigger.style.display = 'block';

            let url = `${BASE_URL}public/ajax/load_more_posts.php?page=${currentPage}&context=${pageContext}&filter=${pageFilter}`;
            if (username) {
                url += `&user=${username}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.posts.length > 0) {
                        data.posts.forEach(post => {
                            // utils.js'deki fonksiyonları kullan
                            const postCardHtml = (pageContext === 'explore') ?
                                createExploreCard(post) :
                                createPostCard(post);
                            postsContainer.insertAdjacentHTML('beforeend', postCardHtml);
                        });
                        currentPage++;
                    } else {
                        hasMore = false;
                        trigger.innerHTML = '<p class="text-muted text-center my-4">Gösterilecek başka gönderi yok.</p>';
                    }
                })
                .catch(error => console.error("Gönderiler yüklenirken hata:", error))
                .finally(() => {
                    isLoading = false;
                    // Eğer trigger görünmüyorsa bir sonraki yüklemeyi tetiklemek için görünür yap
                    if(hasMore) trigger.style.display = 'none'; setTimeout(()=> trigger.style.display = 'block', 50);
                });
        };

        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                loadMorePosts();
            }
        }, { threshold: 0.8 });

        observer.observe(trigger);
    }

    // -----------------------------------------------------------
    // TEMA DEĞİŞTİRİCİ BAŞLATMA
    // -----------------------------------------------------------
    const themeToggler = document.getElementById('theme-toggler');
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme); // utils.js'deki fonksiyon

    if (themeToggler) {
        themeToggler.addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = document.body.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    }
    
    // -----------------------------------------------------------
    // MESAJ OLARAK GÖNDERME MODALI
    // -----------------------------------------------------------
    const shareModalElement = document.getElementById('shareViaMessageModal');
    if (shareModalElement) {
        const shareModal = new bootstrap.Modal(shareModalElement);
        const userListContainer = document.getElementById('shareUserList');
        const searchInput = document.getElementById('shareUserSearch');
        let currentPostId = null;

        shareModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            currentPostId = button.dataset.postId;
            
            userListContainer.innerHTML = `<div class="text-center p-4"><div class="spinner-border"></div></div>`;
            // `userId` global değişkeninin var olduğunu varsayıyoruz
            fetch(`${BASE_URL}public/ajax/get_follow_list.php?user_id=${userId}&type=following`)
                .then(res => res.json())
                .then(data => {
                    renderUserList(data.success && data.users ? data.users : []);
                });
        });

        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            userListContainer.querySelectorAll('.share-user-item').forEach(user => {
                user.style.display = user.dataset.username.toLowerCase().includes(searchTerm) ? 'flex' : 'none';
            });
        });

        function renderUserList(users) {
            userListContainer.innerHTML = '';
            if (users.length === 0) {
                 userListContainer.innerHTML = '<p class="text-muted text-center p-4">Mesaj gönderebileceğin kimseyi takip etmiyorsun.</p>';
                 return;
            }
            users.forEach(user => {
                const profilePic = user.profile_picture_url ? `${BASE_URL}uploads/profile_pictures/${user.profile_picture_url}` : `https://ui-avatars.com/api/?name=${user.username}&background=random&color=fff`;
                userListContainer.insertAdjacentHTML('beforeend', `
                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded share-user-item" data-username="${user.username}">
                        <div class="d-flex align-items-center"><img src="${profilePic}" class="rounded-circle me-3" width="40" height="40" alt="${user.username}"><span class="fw-bold">${user.username}</span></div>
                        <button class="btn btn-sm btn-primary send-message-btn" data-receiver-id="${user.id}">Gönder</button>
                    </div>`);
            });
        }

        userListContainer.addEventListener('click', function(event) {
            const sendButton = event.target.closest('.send-message-btn');
            if (!sendButton) return;

            const receiverId = sendButton.dataset.receiverId;
            const originalButtonText = sendButton.innerHTML;
            sendButton.disabled = true;
            sendButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

            sendAjaxRequest(`${BASE_URL}public/ajax/send_post_as_message.php`, { receiver_id: receiverId, post_id: currentPostId },
                (data) => {
                    if (data.success) {
                        sendButton.innerHTML = '<i class="fas fa-check"></i> Gönderildi';
                        sendButton.classList.replace('btn-primary', 'btn-success');
                    } else {
                        sendButton.innerHTML = originalButtonText;
                    }
                },
                (error) => { sendButton.innerHTML = originalButtonText; }
            );
        });
    }

});
