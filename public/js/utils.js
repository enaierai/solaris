/**
 * utils.js (SADELEŞTİRİLDİ)
 * Proje genelinde kullanılan temel yardımcı fonksiyonları içerir.
 * Bu dosya, diğer tüm script dosyalarından önce yüklenmelidir.
 * AJAX istekleri ve DOM manipülasyonu feed.js'e taşındı.
 */

// BASE_URL, csrfToken ve userId gibi global değişkenlerin header.php/footer.php'den geldiğini varsayıyoruz.

/**
 * Sayfanın temasını (aydınlık/karanlık) uygular.
 * Bu fonksiyon artık feed.js'de de tanımlı, ancak burada da bırakılabilir
 * veya sadece tek bir yerde (tercihen app.js veya feed.js) tutulabilir.
 * Şimdilik, feed.js'deki versiyonu kullanacağımız için bu fonksiyonu burada tutmaya gerek yok.
 * Ancak, header.php'den çağrıldığı için burada bırakıyorum,
 * ama ileride app.js'e taşınabilir.
 */
function applyTheme(theme) {
    const docBody = document.body;
    const themeIconDark = document.querySelector('.theme-icon-dark');
    const themeIconLight = document.querySelector('.theme-icon-light');
    const themeText = document.getElementById('theme-text');

    docBody.setAttribute('data-theme', theme);

    if (theme === 'dark') {
        if (themeIconDark) themeIconDark.style.display = 'none';
        if (themeIconLight) themeIconLight.style.display = 'inline-block';
        if (themeText) themeText.textContent = 'Aydınlık Mod';
    } else {
        if (themeIconDark) themeIconDark.style.display = 'inline-block';
        if (themeIconLight) themeIconLight.style.display = 'none';
        if (themeText) themeText.textContent = 'Karanlık Mod';
    }
}

/**
 * Profil sayfasındaki takipçi sayısını günceller.
 * Bu fonksiyon profile.js'e taşınacak. Şimdilik burada bırakıyorum.
 */
function updateFollowerCount(change) {
    const el = document.getElementById("followerCount");
    if (el) {
        let count = parseInt(el.textContent) || 0;
        el.textContent = Math.max(0, count + change);
    }
}

/**
 * Profil sayfasındaki takip edilen sayısı günceller.
 * Bu fonksiyon profile.js'e taşınacak. Şimdilik burada bırakıyorum.
 */
function updateFollowingCount(change) {
    const el = document.getElementById("followingCount");
    if (el) {
        let count = parseInt(el.textContent) || 0;
        el.textContent = Math.max(0, count + change);
    }
}

/**
 * Sunucudan gelen post verisinden bir HTML gönderi kartı oluşturur.
 * Bu fonksiyon artık feed.js'de doğrudan oluşturulacak, burada tutmaya gerek yok.
 * Ancak, sonsuz kaydırma için hala dışarıda olması gerekebilir.
 * Şimdilik burada bırakıyorum, ancak ileride tamamen kaldırılabilir veya feed.js'e taşınabilir.
 */
function createPostCard(post) {
    // Bu fonksiyonun içeriği artık post_card_feed.php'nin kendisi tarafından üretildiği için
    // JavaScript tarafında bu kadar karmaşık bir HTML oluşturmaya gerek kalmayacak.
    // Ancak, sonsuz kaydırma (infinite scroll) AJAX ile yeni postlar çektiğinde
    // sunucudan doğrudan HTML alacağımız için bu fonksiyonun JS tarafında bir karşılığına ihtiyaç duymayabiliriz.
    // Şimdilik boş bırakıyorum, çünkü feed.js'deki loadMorePosts metodu artık doğrudan HTML bekleyecek.
    return ''; 
}

/**
 * KEŞFET SAYFASI için bir gönderi kartı HTML'i oluşturur.
 * Bu fonksiyon da artık feed.js'de doğrudan oluşturulacak, burada tutmaya gerek yok.
 */
function createExploreCard(post) {
    // Bu fonksiyonun içeriği de artık sunucudan gelecek HTML tarafından karşılanacak.
    return '';
}
