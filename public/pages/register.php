<?php
// public/pages/register.php (YENİ HALİ)
// SİLİNDİ: Bu dosya artık bir logic dosyası çağırmıyor.
// SİLİNDİ: <!DOCTYPE html>, <head>, <body> gibi tüm etiketler.
?>

<div class="login-card" style="margin-top: -50px;"> <h2 class="mb-3"><?php echo htmlspecialchars($brand_name); ?>'e Kayıt Olun</h2>
    <p class="subtitle">İlham almak ve keşfetmek için yeni bir hesap oluşturun.</p>
    
    <?php echo $message; // Hata veya başarı mesajlarını gösterir?>

    <form action="<?php echo BASE_URL; ?>register" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="mb-3">
            <input type="text" class="form-control" name="username" placeholder="Kullanıcı Adı" value="<?php echo htmlspecialchars($old_username); ?>" required>
        </div>
        
        <div class="mb-3">
            <input type="email" class="form-control" name="email" placeholder="E-posta Adresi" value="<?php echo htmlspecialchars($old_email); ?>" required>
        </div>
        
        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="Şifre" required>
        </div>
        
        <div class="mb-3">
            <input type="password" class="form-control" name="password_confirm" placeholder="Şifre (Tekrar)" required>
        </div>
        
        <button type="submit" class="btn btn-brand w-100 mb-3">Kayıt Ol</button>
    </form>
    
    <hr class="my-4">

    <p class="small-text mb-0">Zaten hesabınız var mı? <a href="<?php echo BASE_URL; ?>login">Şimdi Giriş Yapın</a></p>
</div>
