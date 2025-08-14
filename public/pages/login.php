<?php
// Sayfanın tüm mantığını (form kontrolü, session işlemleri vb.) çalıştırır.
// Bu dosya, $message ve $csrf_token gibi değişkenleri hazırlar.
include_once __DIR__.'/../../includes/logic/login.logic.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - <?php echo htmlspecialchars($brand_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --brand-color: #5a189a;
            --brand-hover-color: #7b43a9;
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
        }
        body {
            font-family: var(--font-family);
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 16px;
        }
        .login-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
        }
        .login-card h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--brand-color);
        }
        .login-card p.subtitle { color: #6c757d; margin-bottom: 32px; }
        .form-control:focus { border-color: var(--brand-color); box-shadow: 0 0 0 0.25rem rgba(90, 24, 154, 0.25); }
        .btn-brand { background-color: var(--brand-color); color: white; font-weight: 600; border-radius: 25px; padding: 12px 24px; transition: background-color 0.2s ease; }
        .btn-brand:hover { background-color: var(--brand-hover-color); color: white; }
        .divider { display: flex; align-items: center; text-align: center; color: #aaa; margin: 24px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #ddd; }
        .divider:not(:empty)::before { margin-right: .25em; }
        .divider:not(:empty)::after { margin-left: .25em; }
        .small-text { font-size: 14px; color: #6c757d; }
        .small-text a { color: var(--brand-color); font-weight: 500; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="mb-3"><?php echo htmlspecialchars($brand_name); ?>'e Hoş Geldiniz</h2>
        <p class="subtitle">İlham almak ve keşfetmek için oturum açın.</p>
        
        <?php echo $message; // Hata veya başarı mesajlarını gösterir?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
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
            <!-- Sosyal medya butonları burada -->
        </div>
        
        <div class="divider"></div>

        <p class="small-text mb-0">Henüz hesabınız yok mu? <a href="<?php echo BASE_URL; ?>public/pages/register.php">Şimdi Kaydolun</a></p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>