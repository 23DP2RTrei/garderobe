<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?>Garderobe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
    <script>
      /* Apply saved theme BEFORE CSS renders — prevents flash */
      (function () {
        var t = localStorage.getItem('garderobe-theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', t);
      }());
    </script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
      <i class="bi bi-bag-heart-fill me-2" style="color:#818cf8;"></i>Garderobe
    </a>

    <!-- Mobile: theme toggle + hamburger -->
    <div class="d-flex align-items-center gap-2 d-lg-none">
      <button class="theme-toggle" id="themeToggleMobile" aria-label="Mainīt izskatu">
        <i class="bi bi-moon-stars-fill" id="themeIconMobile"></i>
      </button>
      <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto gap-1 mt-2 mt-lg-0">
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL ?>/pages/wardrobe.php"><i class="bi bi-grid me-1"></i>Garderobe</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL ?>/pages/outfits.php"><i class="bi bi-layers me-1"></i>Kombinācijas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL ?>/pages/stats.php"><i class="bi bi-bar-chart me-1"></i>Statistika</a>
        </li>
        <?php if (isPremium()): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL ?>/pages/ai.php" style="color:#fbbf24 !important;"><i class="bi bi-stars me-1"></i>AI Ieteikumi</a>
        </li>
        <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav align-items-lg-center gap-1 mt-2 mt-lg-0">
        <?php if (isLoggedIn()):
          $navUser = getCurrentUser(); ?>

        <?php if ($navUser['role'] === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL ?>/pages/admin.php" style="color:#f87171 !important;"><i class="bi bi-shield-lock me-1"></i>Admin</a>
        </li>
        <?php endif; ?>

        <!-- Desktop theme toggle -->
        <li class="nav-item d-none d-lg-flex align-items-center">
          <button class="theme-toggle" id="themeToggle" aria-label="Mainīt izskatu">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
          </button>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i><?= sanitize($navUser['name']) ?>
            <?php if ($navUser['role'] === 'premium'): ?>
            <span class="badge ms-1 fw-semibold" style="background:#fbbf24;color:#78350f;font-size:.62rem;vertical-align:middle;">Premium</span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/pages/profile.php"><i class="bi bi-person me-2"></i>Profils</a></li>
            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.08);margin:.25rem 0;"></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/pages/logout.php" style="color:#f87171;"><i class="bi bi-box-arrow-right me-2"></i>Iziet</a></li>
          </ul>
        </li>

        <?php else: ?>

        <!-- Desktop theme toggle (guest) -->
        <li class="nav-item d-none d-lg-flex align-items-center">
          <button class="theme-toggle" id="themeToggle" aria-label="Mainīt izskatu">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
          </button>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/pages/login.php">Pieteikties</a></li>
        <li class="nav-item"><a class="btn btn-primary btn-sm px-3 ms-1" href="<?= SITE_URL ?>/pages/register.php">Reģistrēties</a></li>

        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='error'?'danger':$flash['type'] ?> alert-dismissible fade show" role="alert">
  <i class="bi bi-<?= ($flash['type']==='error'||$flash['type']==='danger')?'exclamation-circle':'check-circle' ?> me-2"></i><?= sanitize($flash['message']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
