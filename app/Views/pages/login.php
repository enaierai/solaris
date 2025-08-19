<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm p-4">
                <div class="card-body text-center">
                    <h2 class="mb-3 fw-bold">Solaris'e Hoş Geldiniz</h2>
                    <p class="text-muted mb-4">İlham almak ve keşfetmek için oturum açın.</p>
                    
                    <?php echo $message; // Controller'dan gelen hata veya başarı mesajları?>

                    <form action="<?php echo BASE_URL; ?>auth/login" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="mb-3">
                            <input type="text" class="form-control" name="username_or_email" placeholder="E-posta veya Kullanıcı Adı" required>
                        </div>
                        
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Şifre" required>
                        </div>
                        
                        <div class="d-flex justify-content-end mb-3">
                            <a href="#" class="text-secondary small">Şifreni mi unuttun?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Giriş Yap</button>
                    </form>
                    
                    <p class="small text-muted mb-0">Henüz hesabın yok mu? <a href="<?php echo BASE_URL; ?>auth/register">Şimdi Kaydol</a></p>
                </div>
            </div>
        </div>
    </div>
</div>