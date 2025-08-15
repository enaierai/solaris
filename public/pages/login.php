<?php
// public/pages/login.php (YENİ HALİ)
// SİLİNDİ: Bu dosya artık bir logic dosyası çağırmıyor.
// SİLİNDİ: <!DOCTYPE html>, <head>, <body> gibi tüm etiketler.
// Bu etiketler artık header.php ve footer.php'den geliyor.
?>
<div class="login-card" style="margin-top: -50px;"> <h2 class="mb-3"><?php echo htmlspecialchars($brand_name); ?>'e Hoş Geldiniz</h2>
    <p class="subtitle">İlham almak ve keşfetmek için oturum açın.</p>
    
    <?php echo $message; // Hata veya başarı mesajlarını gösterir?>

    <form action="<?php echo BASE_URL; ?>login" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="mb-3">
            <input type="text" class="form-control" name="username_or_email" placeholder="E-posta veya Kullanıcı Adı" required>
        </div>
        
        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="Şifre" required>
        </div>
        
        <div class="d-flex justify-content-end mb-3">
            <a href="#" class="text-secondary small">Şifrenizi mi unuttunuz?</a>
        </div>
        
        <button type="submit" class="btn btn-brand w-100 mb-3">Giriş Yap</button>
    </form>
    
    <div class="divider"><span class="px-2">veya</span></div>
    
    <div class="d-grid gap-2">
        </div>
    
    <div class="divider"></div>

    <p class="small-text mb-0">Henüz hesabınız yok mu? <a href="<?php echo BASE_URL; ?>register">Şimdi Kaydolun</a></p>
</div>
    
<?php
// SİLİNDİ: bootstrap.bundle.min.js script'i. Bu artık footer.php'de olmalı.
// SİLİNDİ: </body> ve </html> etiketleri.
// SİLİNDİ: $conn->close(); Veritabanı bağlantısı artık merkezi yönetiliyor.
?>