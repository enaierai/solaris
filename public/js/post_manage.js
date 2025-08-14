/**
 * post_manage.js
 * Gönderi düzenleme, silme ve genel modal (beğenenler, takipçiler) işlevlerini yönetir.
 * Bu dosya, gönderi detay sayfası (post.php) ve profil sayfası (profile.php) gibi sayfalarda yüklenebilir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // -----------------------------------------------------------
    // GÖNDERİ AÇIKLAMASI DÜZENLEME (post.php)
    // -----------------------------------------------------------
    const editCaptionBtn = document.getElementById('editCaptionBtn');
    if (editCaptionBtn) {
        const captionDisplay = document.getElementById('captionDisplay');
        const captionEditForm = document.getElementById('captionEditForm');
        const captionInput = document.getElementById('captionInput');
        const saveCaptionBtn = document.getElementById('saveCaptionBtn');
        const cancelCaptionBtn = document.getElementById('cancelCaptionBtn');
        // postId değişkeninin post.php'de global olarak tanımlandığını varsayıyoruz
        const postId = editCaptionBtn.dataset.postId;

        editCaptionBtn.addEventListener('click', () => {
            captionEditForm.style.display = 'block';
            captionDisplay.style.display = 'none';
            captionInput.value = captionDisplay.textContent.trim();
            captionInput.focus();
        });

        cancelCaptionBtn.addEventListener('click', () => {
            captionEditForm.style.display = 'none';
            captionDisplay.style.display = 'block';
        });

        saveCaptionBtn.addEventListener('click', () => {
            const newCaption = captionInput.value;
            saveCaptionBtn.disabled = true;

            sendAjaxRequest(`${BASE_URL}public/ajax/update_post_caption.php`, { post_id: postId, new_caption: newCaption },
                (data) => {
                    if (data.success) {
                        captionDisplay.innerHTML = data.updated_caption_html;
                        captionEditForm.style.display = 'none';
                        captionDisplay.style.display = 'block';
                    }
                },
                null,
                () => { saveCaptionBtn.disabled = false; }
            );
        });
    }

    // -----------------------------------------------------------
    // GÖNDERİ SİLME (post.php)
    // -----------------------------------------------------------
    const deletePostBtn = document.getElementById('deletePostBtn');
    if (deletePostBtn) {
        deletePostBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // postId ve postOwnerUsername'in post.php'de global olarak tanımlandığını varsayıyoruz
            const postId = this.dataset.postId;
            const postOwnerUsername = this.dataset.owner;

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
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';

                    sendAjaxRequest(`${BASE_URL}public/ajax/delete_post.php`, { post_id: postId },
                        (data) => {
                            if (data.success) {
                                Swal.fire('Silindi!', data.message, 'success').then(() => {
                                    window.location.href = `${BASE_URL}public/pages/profile.php?user=${postOwnerUsername}`;
                                });
                            } else {
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-trash-alt"></i> Sil';
                            }
                        },
                        (error) => {
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-trash-alt"></i> Sil';
                        }
                    );
                }
            });
        });
    }

    // -----------------------------------------------------------
    // GENEL MODAL SİSTEMİ (Beğenenler, Takipçiler, Takip Edilenler)
    // -----------------------------------------------------------
    const generalModalElement = document.getElementById('generalModal');
    if (generalModalElement) {
        const generalModal = new bootstrap.Modal(generalModalElement);
        const generalModalLabel = document.getElementById('generalModalLabel');
        const generalModalBody = document.getElementById('generalModalBody');

        document.body.addEventListener('click', function(event) {
            const likersTarget = event.target.closest('.view-likers');
            const followersTarget = event.target.closest('.view-followers');
            const followingTarget = event.target.closest('.view-following');
            let target = null, url = '', title = '', listType = '';

            if (likersTarget) {
                target = likersTarget;
                url = `${BASE_URL}public/ajax/get_likers.php?post_id=${target.dataset.postId}`;
                title = 'Beğenenler';
                listType = 'likers';
            } else if (followersTarget) {
                target = followersTarget;
                url = `${BASE_URL}public/ajax/get_follow_list.php?user_id=${target.dataset.userid}&type=followers`;
                title = 'Takipçiler';
                listType = 'followers';
            } else if (followingTarget) {
                target = followingTarget;
                url = `${BASE_URL}public/ajax/get_follow_list.php?user_id=${target.dataset.userid}&type=following`;
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
                        generalModalBody.innerHTML = `<p class="text-center text-muted p-4">Gösterilecek kimse bulunamadı.</p>`;
                    } else {
                        userList.forEach(user => {
                            const profilePic = user.profile_picture_url ? `${BASE_URL}uploads/profile_pictures/${user.profile_picture_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.username)}&background=random&color=fff`;
                            let buttonHtml = '';
                            // isProfileOwner değişkeninin profil sayfasında tanımlandığını varsayıyoruz
                            if (typeof isProfileOwner !== 'undefined' && isProfileOwner) {
                                if (listType === 'following') {
                                    buttonHtml = `<button class="btn btn-sm btn-outline-secondary ms-auto modal-unfollow-btn" data-user-id="${user.id}">Takipten Çık</button>`;
                                } else if (listType === 'followers') {
                                    buttonHtml = `<button class="btn btn-sm btn-outline-danger ms-auto modal-remove-follower-btn" data-user-id="${user.id}">Çıkar</button>`;
                                }
                            }
                            const userHtml = `<div class="d-flex align-items-center mb-3" id="modal-user-row-${user.id}"><a href="${BASE_URL}public/pages/profile.php?user=${user.username}" class="d-flex align-items-center text-decoration-none text-dark"><img src="${profilePic}" class="rounded-circle me-3" width="40" height="40" alt="${user.username}" style="object-fit: cover;"><span class="fw-bold">${user.username}</span></a>${buttonHtml}</div>`;
                            generalModalBody.insertAdjacentHTML('beforeend', userHtml);
                        });
                    }
                } else {
                    generalModalBody.innerHTML = `<p class="text-center text-danger p-4">${data.message || 'Liste yüklenemedi.'}</p>`;
                }
            });
        });

        generalModalBody.addEventListener('click', function(event) {
            const unfollowBtn = event.target.closest('.modal-unfollow-btn');
            if (unfollowBtn) {
                const userIdToUnfollow = unfollowBtn.dataset.userId;
                unfollowBtn.disabled = true;
                sendAjaxRequest(`${BASE_URL}public/ajax/unfollow.php`, { following_id: userIdToUnfollow }, (data) => {
                    if (data.success) {
                        document.getElementById(`modal-user-row-${userIdToUnfollow}`).remove();
                        // Takip edilen sayısı için ayrı bir sayaç güncelleme fonksiyonu eklenebilir.
                    } else {
                        unfollowBtn.disabled = false;
                    }
                });
            }

            const removeFollowerBtn = event.target.closest('.modal-remove-follower-btn');
            if (removeFollowerBtn) {
                Swal.fire({
                    title: 'Takipçiyi Çıkar',
                    text: "Bu takipçiyi çıkarmak istediğinizden emin misiniz?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Evet, Çıkar',
                    cancelButtonText: 'İptal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const userIdToRemove = removeFollowerBtn.dataset.userId;
                        removeFollowerBtn.disabled = true;
                        sendAjaxRequest(`${BASE_URL}public/ajax/remove_follower.php`, { follower_id: userIdToRemove }, (data) => {
                            if (data.success) {
                                document.getElementById(`modal-user-row-${userIdToRemove}`).remove();
                                updateFollowerCount(-1); // utils.js'den gelen fonksiyon
                            } else {
                                removeFollowerBtn.disabled = false;
                            }
                        });
                    }
                });
            }
        });
    }

});
