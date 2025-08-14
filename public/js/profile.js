/**
 * profile.js
 * Profil sayfasıyla ilgili tüm işlevleri yönetir.
 * Sadece profil sayfalarında (profile.php) yüklenmelidir.
 */
document.addEventListener("DOMContentLoaded", function() {

    // Sadece profil sayfasındaysak devam et
    if (!document.querySelector('.profile-header')) {
        return;
    }

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

                // sendAjaxRequest burada kullanılamaz çünkü success/error callback'leri özel.
                // Doğrudan fetch kullanmak daha mantıklı.
                formData.append('csrf_token', csrfToken); // csrfToken'in global olduğunu varsayıyoruz
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
                    });
            }
        });
    }

    setupImageUpload('changeProfilePictureBtn', 'profilePictureInput', `${BASE_URL}public/ajax/upload_profile_picture.php`, 'avatar');
    setupImageUpload('changeCoverPictureBtn', 'coverPictureInput', `${BASE_URL}public/ajax/upload_cover_picture.php`, 'cover');

    // -----------------------------------------------------------
    // PROFİL SEKMELERİ
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
            const newBio = bioInput.value; // trim() kaldırılarak boşluklara izin verilebilir
            const originalSaveBioBtnText = saveBioBtn.innerHTML;
            saveBioBtn.disabled = true;
            saveBioBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...';

            sendAjaxRequest(`${BASE_URL}public/ajax/update_bio.php`, { bio: newBio },
                (data) => {
                    if (data.success) {
                        bioDisplay.innerHTML = data.new_bio.replace(/\n/g, "<br>");
                        bioEditForm.style.display = "none";
                        bioDisplay.style.display = "block";
                        editBioBtn.style.display = "inline-block";
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

        sendAjaxRequest(url, { following_id: followingId },
            (data) => {
                if (data.success) {
                    if (data.action === "followed") {
                        btn.classList.replace("btn-primary", "btn-outline-secondary");
                        btn.innerHTML = '<i class="fas fa-user-check"></i> Takip Ediliyor';
                        btn.dataset.isFollowing = "true";
                        updateFollowerCount(1);
                    } else if (data.action === "unfollowed") {
                        btn.classList.replace("btn-outline-secondary", "btn-primary");
                        btn.innerHTML = '<i class="fas fa-user-plus"></i> Takip Et';
                        btn.dataset.isFollowing = "false";
                        updateFollowerCount(-1);
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
    // KULLANICI ENGELLEME / ENGELİ KALDIRMA
    // -----------------------------------------------------------
    document.body.addEventListener('click', function(event) {
        const blockButton = event.target.closest('.block-user-button');
        if (!blockButton) return;

        const blockedId = blockButton.dataset.blockedId;
        const isBlocked = blockButton.dataset.isBlocked === 'true';
        const username = blockButton.dataset.username || 'bu kullanıcıyı';

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
                blockButton.disabled = true;
                sendAjaxRequest(`${BASE_URL}public/ajax/toggle_block_user.php`, { blocked_id: blockedId },
                    (data) => {
                        if (data.success) {
                            Swal.fire('Başarılı!', data.message, 'success')
                                .then(() => window.location.reload());
                        } else {
                            blockButton.disabled = false;
                        }
                    },
                    (error) => {
                        blockButton.disabled = false;
                    }
                );
            }
        });
    });

});
