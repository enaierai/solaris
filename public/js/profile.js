/**
 * profile.js (NİHAİ VERSİYON - TÜM PROFİL İŞLEVLERİ VE MODAL DÜZELTMELERİ)
 * Profil sayfasıyla ilgili tüm işlevleri yönetir.
 * Sadece profil sayfalarında (profile.php) yüklenmelidir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // Sadece profil sayfasındaysak devam et
    if (!document.querySelector('.profile-header')) {
        return;
    }

    // isProfileOwner ve userId değişkenlerinin global olarak tanımlandığını varsayıyoruz.
    // (profile.php'nin en altında ve header.php/footer.php'de)

    // -----------------------------------------------------------
    // PROFİL VE KAPAK RESMİ YÜKLEME
    // -----------------------------------------------------------
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
                
                Swal.fire({
                    title: 'Yükleniyor...',
                    text: 'Lütfen bekleyin',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                if (typeof csrfToken !== 'undefined' && csrfToken) {
                    formData.append('csrf_token', csrfToken);
                }

                fetch(ajaxUrl, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Başarılı!', 'Fotoğrafınız güncellendi.', 'success').then(() => {
                                window.location.reload(); 
                            });
                        } else {
                            Swal.fire('Hata!', data.message || 'Yükleme başarısız.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Hata!', 'Bir sunucu hatası oluştu.', 'error');
                        console.error("Resim yükleme hatası:", err);
                    });
            }
        });
    }

    setupImageUpload('changeProfilePictureBtn', 'profilePictureInput', `${BASE_URL}profile/upload_profile_picture`, 'avatar');
    setupImageUpload('changeCoverPictureBtn', 'coverPictureInput', `${BASE_URL}profile/upload_cover_picture`, 'cover');

    // -----------------------------------------------------------
    // PROFİL SEKMELERİ (Gönderiler, Kaydedilenler)
    // -----------------------------------------------------------
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
            tabEl.addEventListener('show.bs.tab', function(event) {
                const newHash = event.target.getAttribute('data-bs-target');
                if (history.pushState) {
                    history.pushState(null, null, newHash);
                } else {
                    window.location.hash = newHash;
                }
            });
        });
    }

    // -----------------------------------------------------------
    // BİYOGRAFİ DÜZENLEME
    // -----------------------------------------------------------
    const editBioBtn = document.getElementById("editBioBtn");
    if (editBioBtn) {
        const bioDisplay = document.getElementById("bioDisplay");
        const bioEditForm = document.getElementById("bioEditForm");
        const bioInput = document.getElementById("bioInput");
        const saveBioBtn = document.getElementById("saveBioBtn");
        const cancelBioBtn = document.getElementById("cancelBioBtn");

        editBioBtn.addEventListener("click", function() {
            bioDisplay.style.display = "none";
            editBioBtn.style.display = "none";
            bioEditForm.style.display = "block";
            bioInput.focus();
            bioInput.value = bioDisplay.textContent.trim();
        });

        cancelBioBtn.addEventListener("click", function() {
            bioEditForm.style.display = "none";
            bioDisplay.style.display = "block";
            editBioBtn.style.display = "inline-block";
        });

        saveBioBtn.addEventListener("click", function() {
            const newBio = bioInput.value; 
            const originalSaveBioBtnText = saveBioBtn.innerHTML;
            saveBioBtn.disabled = true;
            saveBioBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...';

            sendAjaxRequest(`${BASE_URL}profile/update_bio`, 
                { bio: newBio },
                (data) => {
                    if (data.success) {
                        bioDisplay.innerHTML = data.new_bio.replace(/\n/g, "<br>");
                        bioEditForm.style.display = "none";
                        bioDisplay.style.display = "block";
                        editBioBtn.style.display = "inline-block";
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Biyografi güncellendi!', showConfirmButton: false, timer: 1500 });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Biyografi güncellenemedi.' });
                    }
                },
                null, 
                () => {
                    saveBioBtn.disabled = false;
                    saveBioBtn.innerHTML = originalSaveBioBtnText;
                }
            );
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
        // Bu iki değişken artık modal açıldığında bulunacak
        let modalSearchFooter = null; 
        let modalUserSearchInput = null; 

        let currentModalUserList = []; 
        let currentListType = ''; 
        let currentUserIdToFetch = null; 

        // Modal açılmadan hemen önce tetiklenir
        generalModalElement.addEventListener('show.bs.modal', function (event) {
            // Modal içindeki elementleri burada bul
            modalSearchFooter = document.getElementById('modalSearchFooter');
            modalUserSearchInput = document.getElementById('modalUserSearchInput');

            const relatedTarget = event.relatedTarget; 
            let url = '', title = '';

            // `modalSearchFooter` elementinin varlığını kontrol et
            if (!modalSearchFooter || !modalUserSearchInput) { // İkisi de bulunmalı
                console.error("Modal Search Footer veya Input bulunamadı!");
                // Hata varsa işlemi durdurmak yerine, footer'ı gizleyip devam edebiliriz
                if (modalSearchFooter) modalSearchFooter.classList.add('d-none');
                generalModalBody.innerHTML = `<p class="text-center text-danger p-4">Modal bileşenleri eksik.</p>`;
                return; 
            }

            if (relatedTarget.classList.contains('view-likers')) {
                const postId = relatedTarget.dataset.postId;
                if (!postId) return;
                url = `${BASE_URL}post/get_likers?post_id=${postId}`; 
                title = 'Beğenenler';
                currentListType = 'likers'; 
                modalSearchFooter.classList.add('d-none'); 
            } else if (relatedTarget.classList.contains('view-followers')) {
                const userIdToFetch = relatedTarget.dataset.userid;
                if (!userIdToFetch) return;
                url = `${BASE_URL}user/get_followers?user_id=${userIdToFetch}`; 
                title = 'Takipçiler';
                currentListType = 'followers'; 
                modalSearchFooter.classList.remove('d-none'); 
                currentUserIdToFetch = userIdToFetch; 
            } else if (relatedTarget.classList.contains('view-following')) {
                const userIdToFetch = relatedTarget.dataset.userid;
                if (!userIdToFetch) return;
                url = `${BASE_URL}user/get_following_list?user_id=${userIdToFetch}`; 
                title = 'Takip Edilenler';
                currentListType = 'following'; 
                modalSearchFooter.classList.remove('d-none'); 
                currentUserIdToFetch = userIdToFetch; 
            } else {
                return; 
            }

            generalModalLabel.textContent = title;
            generalModalBody.innerHTML = `<div class="d-flex justify-content-center p-4"><div class="spinner-border" role="status"></div></div>`;
            modalUserSearchInput.value = ''; 
            
            fetch(url) 
                .then(response => { 
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text); });
                    }
                    return response.json();
                })
                .then(data => {
                    const userList = data.likers || data.users; 
                    if (data.success && userList) {
                        currentModalUserList = userList; 
                        renderModalUserList(currentModalUserList, currentListType, currentUserIdToFetch); 
                    } else {
                        generalModalBody.innerHTML = `<p class="text-center text-danger p-4">${data.message || 'Liste yüklenemedi.'}</p>`;
                        currentModalUserList = [];
                    }
                })
                .catch(error => {
                    generalModalBody.innerHTML = `<p class="text-center text-danger p-4">Liste yüklenirken bir hata oluştu: ${error.message || 'Bilinmeyen Hata'}</p>`;
                    console.error("Modal listesi yüklenirken hata:", error);
                    currentModalUserList = [];
                });
        });

        generalModalElement.addEventListener('hidden.bs.modal', function () {
            generalModalBody.innerHTML = '';
            if (modalUserSearchInput) { // Kapanırken de varlığını kontrol et
                modalUserSearchInput.value = '';
            }
            currentModalUserList = [];
            currentListType = ''; 
            currentUserIdToFetch = null;
            if (modalSearchFooter) { 
                modalSearchFooter.classList.add('d-none'); 
            }
        });

        function renderModalUserList(users, listTypeToRender, currentUserIdToFetchForRender) { 
            generalModalBody.innerHTML = '';
            if (users.length === 0) {
                generalModalBody.innerHTML = `<p class="text-center text-muted p-4">Gösterilecek kimse bulunamadı.</p>`;
                return;
            }

            users.forEach(user => {
                const profilePic = user.profile_picture_url ? `${BASE_URL}serve.php?path=profile_pictures/${user.profile_picture_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.username)}&background=random&color=fff`;
                
                let buttonHtml = '';
                const loggedInUserId = typeof userId !== 'undefined' ? userId : null;
                const isProfileOwnerGlobal = typeof isProfileOwner !== 'undefined' ? isProfileOwner : false;

                // Kendi profilimizdeki takip ettiklerimiz veya takipçilerimiz listesiyse butonları göster
                if (isProfileOwnerGlobal && loggedInUserId === currentUserIdToFetchForRender) { 
                    if (listTypeToRender === 'following') { // Takip Edilenler listesi (kendi profilimizdeki)
                        buttonHtml = `<button class="btn btn-sm btn-outline-secondary ms-auto modal-unfollow-btn" data-user-id="${user.id}">Takipten Çık</button>`;
                    } else if (listTypeToRender === 'followers') { // Takipçiler listesi (kendi profilimizdeki)
                        if (loggedInUserId !== user.id) { 
                             buttonHtml = `<button class="btn btn-sm btn-outline-danger ms-auto modal-remove-follower-btn" data-user-id="${user.id}">Çıkar</button>`;
                        }
                    }
                }
                // Başka bir kullanıcının profilindeysek veya kendi profilimizde ama başka bir listeyi görüntülüyorsak
                else if (loggedInUserId && loggedInUserId !== user.id) { 
                    if (user.is_followed_by_current_user !== undefined) {
                        buttonHtml = `<button class="btn btn-sm ${user.is_followed_by_current_user ? 'btn-outline-secondary' : 'btn-primary'} ms-auto follow-button" data-following-id="${user.id}" data-is-following="${user.is_followed_by_current_user ? 'true' : 'false'}">
                                        ${user.is_followed_by_current_user ? 'Takip Ediliyor' : 'Takip Et'}
                                      </button>`;
                    } else {
                        buttonHtml = `<button class="btn btn-sm btn-primary ms-auto follow-button" data-following-id="${user.id}" data-is-following="false">Takip Et</button>`;
                    }
                }


                const userHtml = `
                    <div class="d-flex align-items-center mb-3 user-list-item" id="modal-user-row-${user.id}" data-username="${user.username}">
                        <a href="${BASE_URL}profile/${user.username}" class="d-flex align-items-center text-decoration-none text-dark">
                            <img src="${profilePic}" class="rounded-circle me-3" width="40" height="40" alt="${user.username}" style="object-fit: cover;">
                            <span class="fw-bold">${user.username}</span>
                        </a>
                        ${buttonHtml}
                    </div>`;
                generalModalBody.insertAdjacentHTML('beforeend', userHtml);
            });
        }

        if (modalUserSearchInput) { // Arama input'u var olduğundan emin ol
            modalUserSearchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const filteredUsers = currentModalUserList.filter(user => 
                    user.username.toLowerCase().includes(searchTerm)
                );
                renderModalUserList(filteredUsers, currentListType, currentUserIdToFetch); 
            });
        } else {
            console.warn("Modal User Search Input bulunamadı. Arama özelliği çalışmayacak.");
        }

        generalModalBody.addEventListener('click', function(event) {
            const unfollowBtn = event.target.closest('.modal-unfollow-btn');
            const removeFollowerBtn = event.target.closest('.modal-remove-follower-btn');
            const followBtnInModal = event.target.closest('.follow-button'); 

            if (unfollowBtn) {
                const userIdToUnfollow = unfollowBtn.dataset.userId;
                unfollowBtn.disabled = true;
                sendAjaxRequest(`${BASE_URL}user/toggle_follow`, { following_id: userIdToUnfollow }, (data) => {
                    if (data.success) {
                        document.getElementById(`modal-user-row-${userIdToUnfollow}`).remove();
                        const followingCountEl = document.getElementById("followingCount");
                        if(followingCountEl) {
                            followingCountEl.textContent = Math.max(0, parseInt(followingCountEl.textContent) - 1);
                        }
                    } else {
                        Swal.fire('Hata!', data.message || "Takipten çıkılamadı.", 'error');
                        unfollowBtn.disabled = false;
                    }
                });
            }

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
                        sendAjaxRequest(`${BASE_URL}user/remove_follower`, { follower_id: userIdToRemove }, (data) => {
                            if (data.success) {
                                document.getElementById(`modal-user-row-${userIdToRemove}`).remove();
                                const followerCountEl = document.getElementById("followerCount");
                                if(followerCountEl) {
                                    followerCountEl.textContent = Math.max(0, parseInt(followerCountEl.textContent) - 1);
                                }
                            } else {
                                Swal.fire('Hata!', data.message || "Takipçi kaldırılamadı.", 'error');
                                removeFollowerBtn.disabled = false;
                            }
                        });
                    }
                });
            }

            if (followBtnInModal) {
                // Bu butona tıklama olayı feed.js'deki merkezi dinleyici tarafından zaten ele alınıyor.
                // Burada tekrar ele almamıza gerek yok.
            }
        });
    }
});
