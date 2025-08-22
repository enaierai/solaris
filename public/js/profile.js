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

});
