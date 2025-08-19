/**
 * feed.js
 * Ana akış ve gönderi etkileşimlerini yönetir (beğeni, yorum, kaydetme vb.).
 * Bu dosya, ana sayfa, gönderi detay sayfası ve keşfet gibi sayfalarda yüklenmelidir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // -----------------------------------------------------------
    // BEĞENİ SİSTEMİ
    // -----------------------------------------------------------
   // -----------------------------------------------------------
// BEĞENİ SİSTEMİ (YENİ VE GÜVENİLİR VERSİYON)
// -----------------------------------------------------------
document.body.addEventListener("click", function(event) {
    const button = event.target.closest(".like-button");
    // Eğer tıklanan bir like butonu değilse, işlemi durdur.
    if (!button) return;

    // Butonun tekrar tekrar tıklanmasını engelle
    button.disabled = true; 

    const postId = button.dataset.postId;
    const heartIcon = button.querySelector(".heart-icon");

    // 1. ADIM: Butondan yola çıkarak tüm aksiyonları içeren ana kapsayıcıyı bul.
    // (post.php'de .post-actions class'ını eklediğimizi varsayıyoruz)
    const actionsContainer = button.closest('.post-actions');
    if (!actionsContainer) {
        console.error('Post actions container (.post-actions) bulunamadı!');
        button.disabled = false;
        return;
    }

    // 2. ADIM: Şimdi ana kapsayıcının içindeki beğeni sayısını bul.
    const likeCountSpan = actionsContainer.querySelector('.like-count');

    // Sunucuya isteği gönder
    sendAjaxRequest(
        `${BASE_URL}public/ajax/post_handler.php`, 
        { 
            action: 'like',
            post_id: postId 
        },
        (data) => { // İstek Başarılı Olduğunda
            if (data.success) {
                // 3. ADIM: Sunucudan gelen GÜNCEL ve DOĞRU veriyi ekrana yazdır.
                
                // Sayıyı güncelle
                if (likeCountSpan) {
                    likeCountSpan.textContent = data.new_likes;
                }

                // Kalp ikonunu ve butonun durumunu güncelle
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
                // Sunucu bir hata mesajı dönerse kullanıcıya göster
                Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'İşlem gerçekleştirilemedi.' });
            }
        },
        (error) => { // Ağ Hatası Olduğunda
            console.error("Beğenme işlemi sırasında ağ hatası oluştu:", error);
            Swal.fire({ icon: 'error', title: 'Bağlantı Hatası!', text: 'Lütfen internet bağlantınızı kontrol edin.' });
        },
        () => { // Her Koşulda Çalışacak Kısım
            // İşlem bittiğinde butonu tekrar tıklanabilir yap.
            button.disabled = false; 
        }
    );
});

    // -----------------------------------------------------------
    // YORUM SİSTEMİ
    // -----------------------------------------------------------
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
    
                sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, 
                    { 
                        action: 'add_comment', // <-- YENİ
                        post_id: postId, 
                        comment_text: commentText 
                    },
                        (data) => {
                        if (data.success && data.comment_html) {
                            const commentList = document.querySelector(`#comments-${postId} .comment-list`);
                            if (commentList) {
                                const noCommentsMessage = commentList.querySelector('.no-comments');
                                if (noCommentsMessage) noCommentsMessage.remove();
                                commentList.insertAdjacentHTML('afterbegin', data.comment_html);
                            }
                            commentInput.value = '';
    
                            // --- DÜZELTİLEN BÖLÜM BURASI ---
                            // jQuery ($) yerine saf JavaScript (document.querySelector) kullanıldı.
                            const commentCountSpan = document.querySelector('#comment-counter-' + postId + ' .comment-count');
                            
                            if (commentCountSpan) {
                                // Mevcut sayıyı alıp 1 artırıyoruz.
                                let currentCount = parseInt(commentCountSpan.textContent, 10) || 0;
                                commentCountSpan.textContent = currentCount + 1;
                            }
                            // --- DÜZELTME SONU ---
    
                            attachDeleteCommentListeners();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Yorum eklenemedi.'});
                        }
                    },
                    null, // Hata durumu için sendAjaxRequest'in kendi handler'ı varsa bu null kalabilir
                    () => {
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
                this.disabled = true;
                if (commentItem) commentItem.style.opacity = '0.5';

                sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, 
                    { 
                        action: 'delete_comment', // <-- YENİ
                        comment_id: commentId 
                    },                    (data) => {
                        if (data.success) {
                            if (commentItem) commentItem.remove();
                            const commentCountSpan = document.querySelector(`#comment-counter-${postId} .comment-count`);
if (commentCountSpan) {
    commentCountSpan.textContent = Math.max(0, parseInt(commentCountSpan.textContent) - 1);
}
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Yorum silindi', showConfirmButton: false, timer: 1500 });
                        } else {
                            if (commentItem) commentItem.style.opacity = '1';
                            this.disabled = false;
                        }
                    },
                    (error) => {
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

    // Yorumları aç/kapa
    document.body.addEventListener('click', function(event) {
        const toggleButton = event.target.closest('.comment-toggle-button');
        if (!toggleButton) return;

        const postId = toggleButton.dataset.postId;
        const commentsContainer = document.getElementById(`comments-${postId}`);
        if (!commentsContainer) return;

        const isVisible = commentsContainer.style.display === 'block';
        if (isVisible) {
            commentsContainer.style.display = 'none';
        } else {
            commentsContainer.style.display = 'block';
            // Eğer yorumlar daha önce yüklenmediyse yükle
            if (!commentsContainer.dataset.loaded) {
                commentsContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
                // csrfToken değişkeninin sayfanızda var olduğunu varsayıyoruz (genellikle footer.php'de tanımlanır).
fetch(`${BASE_URL}public/ajax/post_handler.php?action=get_comments&post_id=${postId}&csrf_token=${csrfToken}`) // <-- YENİ
                .then(res => res.text())
                    .then(html => {
                        commentsContainer.innerHTML = html;
                        commentsContainer.dataset.loaded = "true";
                        attachCommentFormHandlers();
                        attachDeleteCommentListeners();
                    }).catch(err => {
                        commentsContainer.innerHTML = '<p class="text-center text-danger p-3">Yorumlar yüklenemedi.</p>';
                    });
            }
        }
    });


    // -----------------------------------------------------------
    // GÖNDERİ KAYDETME (BOOKMARK)
    // -----------------------------------------------------------
    document.body.addEventListener('click', function(event) {
        const saveButton = event.target.closest('.save-post-button');
        if (!saveButton) return;

        saveButton.disabled = true;
        const postId = saveButton.dataset.postId;
        const bookmarkIcon = saveButton.querySelector('i.fa-bookmark');

        sendAjaxRequest(`${BASE_URL}public/ajax/post_handler.php`, 
            { 
                action: 'save', // <-- YENİ
                post_id: postId 
            },            (data) => {
                if (data.success) {
                    if (data.action === 'saved') {
                        saveButton.dataset.saved = 'true';
                        bookmarkIcon.classList.replace('far', 'fas');
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Gönderi kaydedildi!', showConfirmButton: false, timer: 1500 });
                    } else if (data.action === 'unsaved') {
                        saveButton.dataset.saved = 'false';
                        bookmarkIcon.classList.replace('fas', 'far');
                    }
                }
            },
            null,
            () => {
                saveButton.disabled = false;
            }
        );
    });

 // -----------------------------------------------------------
    // TAKİP / TAKİPTEN ÇIKMA
    // -----------------------------------------------------------
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

        sendAjaxRequest(
            `${BASE_URL}public/ajax/user_handler.php`, 
            { 
                action: 'toggle_follow', // <-- Tek ve akıllı eylem
                following_id: followingId 
            },
            (data) => {
                if (data.success) {
                    // YENİ: Sunucudan gelen yeni takipçi/takip sayılarını alıp güncelle
                    const followerCountEl = document.getElementById("followerCount");
                    if (followerCountEl) followerCountEl.textContent = data.newFollowerCount;
        
                    if (data.action === "followed") {
                        btn.classList.replace("btn-primary", "btn-outline-secondary");
                        btn.innerHTML = 'Takip Ediliyor'; // Metni basitleştirebiliriz
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
            (error) => {
                btn.innerHTML = originalContent;
            },
            () => {
                btn.disabled = false;
                btn.dataset.loading = "false";
            }
        );
    }
    document.querySelectorAll(".follow-button").forEach(btn => {
        btn.addEventListener("click", handleFollowClick);
    });

    // -----------------------------------------------------------
    // ŞİKAYET ETME
    // -----------------------------------------------------------
    document.body.addEventListener('click', function(event) {
        const reportButton = event.target.closest('.report-button');
        if (!reportButton) return;
        event.preventDefault();

        const contentType = reportButton.dataset.type;
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
    });

    // -----------------------------------------------------------
    // LİNK KOPYALAMA & PAYLAŞMA
    // -----------------------------------------------------------
    document.body.addEventListener('click', function(event) {
        const copyLinkButton = event.target.closest('.copy-link-button');
        if (copyLinkButton) {
            event.preventDefault();
            const postId = copyLinkButton.dataset.postId;
            const postUrl = `${BASE_URL}public/pages/post.php?id=${postId}`;
            navigator.clipboard.writeText(postUrl).then(() => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link panoya kopyalandı!', showConfirmButton: false, timer: 2000 });
            }).catch(err => {
                console.error('Link kopyalanamadı: ', err);
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Hata! Link kopyalanamadı.', showConfirmButton: false, timer: 2000 });
            });
        }

        const whatsappButton = event.target.closest('.whatsapp-share-button');
        if (whatsappButton) {
            event.preventDefault();
            const postId = whatsappButton.dataset.postId;
            const postCaption = whatsappButton.dataset.postCaption || "bu gönderiye bir bak";
            const postUrl = `${BASE_URL}public/pages/post.php?id=${postId}`;
            const shareText = `Solaris'teki şu gönderiye bir bak: "${postCaption}"\n\n${postUrl}`;
            const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
            window.open(whatsappUrl, '_blank');
        }
    });

    attachCommentFormHandlers();
    attachDeleteCommentListeners();

});


