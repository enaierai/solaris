<?php
// --- GÜVENLİ VE NİHAİ HEADER ---

// Controller'dan gelen ana verileri ve meta verilerini alalım.
// '??' operatörü, değişken yoksa varsayılan bir değer atayarak hataları önler.
$page_name = $page_name ?? '';
$is_logged_in = $is_logged_in ?? false;
$current_username = $_SESSION['username'] ?? '';
$current_user_avatar = getUserAvatar($current_username, $_SESSION['profile_picture_url'] ?? null);

// Bildirim ve mesaj sayıları için varsayılan değerler
$unread_notifications_count = $unread_notifications_count ?? 0;
$unread_messages_count = $unread_messages_count ?? 0;

// Meta verilerini alalım
$meta = $meta ?? [];
$meta_title = $meta['meta_title'] ?? 'Solaris';
$meta_description = $meta['meta_description'] ?? 'Solaris\'e hoş geldiniz.';
$meta_keywords = $meta['meta_keywords'] ?? 'sosyal medya, paylaşım';
$meta_author = $meta['meta_author'] ?? 'Solaris';
$og_image = $meta['og_image'] ?? BASE_URL.'public/uploads/default_og.png';
$og_url = $meta['og_url'] ?? BASE_URL;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
<meta charset="UTF-8" />
<meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?php echo htmlspecialchars($meta_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>" />
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>" />
    <meta name="author" content="<?php echo htmlspecialchars($meta_author); ?>" />
    <meta name="robots" content="index, follow" />

    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>" />
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>" />
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>" />
    <meta property="og:site_name" content="Solaris" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="<?php echo htmlspecialchars($og_url); ?>" />
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>" />
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>" />
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>" />
    <meta name="twitter:site" content="@SolarisApp" />
    <meta name="twitter:creator" content="@SolarisApp" />
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>uploads/icon.ico" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />

    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet" />

    <link href="https://unpkg.com/tippy.js@6.3.7/dist/tippy.css" rel="stylesheet" />

    <link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css" rel="stylesheet" />

    <link href="<?php echo BASE_URL; ?>public/css/style.css" rel="stylesheet" />
</head>

<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg py-3 sticky-top">
        <div class="container-fluid custom-container-fluid">
            <a class="navbar-brand logo-font fw-bold me-3" href="<?php echo BASE_URL; ?>home">Solaris</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex my-2 my-lg-0 navbar-nav-centered w-lg-25" action="<?php echo BASE_URL; ?>search" method="GET" role="search">
                    <div class="search-bar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#767676" style="margin-right: 8px;"><path d="M17.33 18.74a10 10 0 1 1 1.41-1.41l4.47 4.47-1.41 1.41zM11 3a8 8 0 1 0 0 16 8 8 0 0 0 0-16"></path></svg>
                        <input type="text" placeholder="Ara..." aria-label="Search" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </div>
                </form>

                <ul class="navbar-nav mb-2 mb-lg-0 ms-lg-auto align-items-center">
                    <?php if ($is_logged_in) { ?>
                        <li class="nav-item d-lg-none"><a class="nav-link" href="<?php echo BASE_URL; ?>home"><i class="fas fa-home me-2"></i>Ana Sayfa</a></li>
                        <li class="nav-item d-lg-none"><a class="nav-link" href="<?php echo BASE_URL; ?>explore"><i class="fas fa-compass me-2"></i>Keşfet</a></li>
                        <li class="nav-item d-lg-none"><a class="nav-link" href="<?php echo BASE_URL; ?>upload"><i class="fas fa-cloud-upload-alt me-2"></i>Yükle</a></li>
                        
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>notifications">
                                <i class="fas fa-bell me-2"></i>Bildirimler
                                <span id="unreadNotificationsBadgeMobile" class="badge bg-danger ms-1 rounded-pill <?php echo ($unread_notifications_count == 0) ? 'd-none' : ''; ?>">
                                    <?php echo $unread_notifications_count; ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item d-lg-none">
                             <a class="nav-link" href="<?php echo BASE_URL; ?>messages">
                                <i class="fas fa-envelope me-2"></i>Mesajlar
                                <span id="unreadMessagesBadgeMobile" class="badge bg-danger rounded-pill <?php echo ($unread_messages_count == 0) ? 'd-none' : ''; ?>">
                                    <?php echo $unread_messages_count > 5 ? '5+' : $unread_messages_count; ?>
                                </span>
                            </a>
                        </li>

                        <li class="nav-item d-none d-lg-block me-3">
                            <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>notifications" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Bildirimler">
                                <i class="fas fa-bell fa-lg"></i>
                                <span id="unreadNotificationsBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?php echo ($unread_notifications_count == 0) ? 'd-none' : ''; ?>" style="font-size: 0.6em; border: 1px solid white;">
                                    <?php echo $unread_notifications_count; ?>
                                </span>
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle fa-lg me-2"></i>
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($current_username); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL.'profile/'.htmlspecialchars($current_username); ?>">Profil</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings">Ayarlar</a></li>
                                <li>
        <a class="dropdown-item d-flex align-items-center" href="#" id="theme-toggler">
            <i class="fas fa-moon me-2 opacity-75 theme-icon-dark"></i>
            <i class="fas fa-sun me-2 opacity-75 theme-icon-light" style="display: none;"></i>
            <span id="theme-text">Karanlık Mod</span>
        </a>
    </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>security">Güvenlik</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout">Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php } else { ?>
                        <li class="nav-item"><a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>login">Giriş Yap</a></li>
                        <li class="nav-item ms-2"><a class="btn btn-primary" href="<?php echo BASE_URL; ?>register">Kayıt Ol</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="main-content-wrapper flex-grow-1 d-flex">
        <aside class="sidebar p-3 pt-4 border-end d-none d-lg-flex flex-column">
            <ul class="nav flex-column sidebar-nav w-100">
                <?php if ($is_logged_in) { ?>
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Ana Sayfa"><a class="nav-link" href="<?php echo BASE_URL; ?>home"><i class="fas fa-home"></i></a></li>
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Keşfet"><a class="nav-link" href="<?php echo BASE_URL; ?>explore"><i class="fas fa-compass"></i></a></li>
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Yükle"><a class="nav-link" href="<?php echo BASE_URL; ?>upload"><i class="fas fa-cloud-upload-alt"></i></a></li>
                    
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Mesajlar">
                        <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>messages">
                            <i class="fas fa-envelope"></i>
                             <span id="unreadMessagesBadgeDesktop" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?php echo ($unread_messages_count == 0) ? 'd-none' : ''; ?>" style="font-size: 0.6em; border: 1px solid white;">
                                <?php echo $unread_messages_count > 5 ? '5+' : $unread_messages_count; ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Profil"><a class="nav-link" href="<?php echo BASE_URL.'profile/'.htmlspecialchars($current_username); ?>">Profil</a></li>
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Ayarlar"><a class="nav-link" href="<?php echo BASE_URL; ?>settings"><i class="fas fa-cog"></i></a></li>
                    <li class="nav-item mb-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Güvenlik"><a class="nav-link" href="<?php echo BASE_URL; ?>security"><i class="fas fa-shield-alt"></i></a></li>
                    <li class="nav-item mt-auto pt-3" data-bs-toggle="tooltip" data-bs-placement="right" title="Çıkış Yap"><a class="nav-link logout-link" href="<?php echo BASE_URL; ?>logout"><i class="fas fa-sign-out-alt"></i></a></li>
                <?php } ?>
            </ul>
        </aside>
        <main class="content-area flex-grow-1 p-4">
            <div class="container-fluid custom-container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">