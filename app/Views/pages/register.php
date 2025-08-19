<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm p-4">
                <div class="card-body text-center">
                    <h2 class="mb-3 fw-bold">Solaris'e Kayıt Ol</h2>
                    <p class="text-muted mb-4">Yeni bir hesap oluşturarak ilhamını paylaş.</p>
                    
                    <?php
                    // Genel mesajları göster (örn: DB hatası, CSRF hatası)
                    echo $message;

                    // Eğer doğrulama hataları varsa, onları liste halinde göster
                    if (!empty($errors)) {
                        echo '<div class="alert alert-danger text-start small p-2"><ul>';
                        foreach ($errors as $error) {
                            echo '<li>'.$error.'</li>';
                        }
                        echo '</ul></div>';
                    }
                    ?>

                    <form action="<?php echo BASE_URL; ?>register" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="mb-3">
                            <input type="text" class="form-control" name="username" placeholder="Kullanıcı Adı" value="<?php echo $old_data['username']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="E-posta Adresi" value="<?php echo $old_data['email']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Şifre" required>
                        </div>

                        <div class="mb-3">
                            <input type="password" class="form-control" name="password_confirm" placeholder="Şifre (Tekrar)" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Kayıt Ol</button>
                    </form>
                    
                    <p class="small text-muted mb-0">Zaten bir hesabın var mı? <a href="<?php echo BASE_URL; ?>login">Giriş Yap</a></p>
                </div>
            </div>
        </div>
    </div>
</div>