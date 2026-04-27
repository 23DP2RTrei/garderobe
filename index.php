<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/wardrobe.php');
}
$pageTitle = 'Sākumlapa';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garderobe — Digitālā garderobes pārvaldība</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
    <script>
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
    <div class="ms-auto d-flex align-items-center gap-2">
      <button class="theme-toggle" id="themeToggle" title="Mainīt izskatu">
        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
      </button>
      <a class="btn btn-sm px-3" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:8px;" href="<?= SITE_URL ?>/pages/login.php">Pieteikties</a>
      <a class="btn btn-primary btn-sm px-3" href="<?= SITE_URL ?>/pages/register.php">Reģistrēties</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero-icon-wrap">
      <i class="bi bi-bag-heart-fill"></i>
    </div>
    <div class="hero-badge">Digitālā garderobe</div>
    <h1>Tava garderobe,<br>organizēta un gudra</h1>
    <p>Katalogē apģērbus, veido tērpu kombinācijas un saņem personalizētus ieteikumus katrai dienai.</p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-primary btn-lg px-5">
        <i class="bi bi-rocket me-2"></i>Sākt bezmaksas
      </a>
      <a href="#features" class="btn btn-lg px-5" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);">
        Uzzināt vairāk
      </a>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features" class="py-5 my-2">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold fs-2" style="letter-spacing:-.02em;">Ko piedāvā Garderobe?</h2>
      <p class="text-muted mt-2">Viss, kas nepieciešams, lai pārvaldītu savu garderobi gudri.</p>
    </div>
    <div class="row g-4">

      <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
          <div class="feature-icon bg-primary bg-opacity-10">
            <i class="bi bi-grid-3x3-gap text-primary"></i>
          </div>
          <h5 class="fw-bold mb-2">Garderobes katalogs</h5>
          <p class="text-muted mb-0">Pievieno apģērbus ar fotoattēliem. Sistēma automātiski noņem fonu un organizē pēc kategorijas, krāsas un sezonas.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
          <div class="feature-icon bg-success bg-opacity-10">
            <i class="bi bi-layers text-success"></i>
          </div>
          <h5 class="fw-bold mb-2">Tērpu kombinācijas</h5>
          <p class="text-muted mb-0">Veido kombinācijas ar vizuālu builder — skaties, kuri apģērbi izskatās vislabāk kopā, un saglabā iecienītākos.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
          <div class="feature-icon bg-warning bg-opacity-10">
            <i class="bi bi-stars text-warning"></i>
          </div>
          <h5 class="fw-bold mb-2">
            AI Ieteikumi
            <span class="badge fw-semibold ms-1" style="background:#fef9c3;color:#854d0e;font-size:.6rem;border-radius:6px;">Premium</span>
          </h5>
          <p class="text-muted mb-0">Personalizēti tērpu ieteikumi, balstoties uz sezonu, stilu un reālajiem laika apstākļiem Rīgā.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
          <div class="feature-icon bg-info bg-opacity-10">
            <i class="bi bi-bar-chart text-info"></i>
          </div>
          <h5 class="fw-bold mb-2">Statistika</h5>
          <p class="text-muted mb-0">Analizē, kurus apģērbus velc visbiežāk, kādas krāsas dominē garderobē un kuras kombinācijas ir populārākās.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
          <div class="feature-icon bg-danger bg-opacity-10">
            <i class="bi bi-funnel text-danger"></i>
          </div>
          <h5 class="fw-bold mb-2">Meklēšana un filtri</h5>
          <p class="text-muted mb-0">Ātri atrod vajadzīgo apģērbu pēc krāsas, kategorijas, zīmola vai sezonas ar viedās meklēšanas palīdzību.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card feature-card h-100 p-4">
          <div class="feature-icon" style="background:#f3e8ff;">
            <i class="bi bi-file-earmark-pdf" style="color:#7c3aed;"></i>
          </div>
          <h5 class="fw-bold mb-2">
            PDF eksports
            <span class="badge fw-semibold ms-1" style="background:#fef9c3;color:#854d0e;font-size:.6rem;border-radius:6px;">Premium</span>
          </h5>
          <p class="text-muted mb-0">Lejupielādē garderobes pārskatu PDF formātā, lai to arhivētu vai dalītos ar citiem.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-5 mb-4">
  <div class="container">
    <div class="p-5 text-center text-white" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);border-radius:24px;">
      <h2 class="fw-bold mb-3" style="letter-spacing:-.02em;">Gatavs sākt?</h2>
      <p class="mb-4" style="opacity:.8;">Reģistrācija ir bezmaksas. Nav nepieciešama maksājuma karte.</p>
      <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-light btn-lg px-5 fw-bold" style="border-radius:12px;">
        <i class="bi bi-person-plus me-2"></i>Izveidot kontu
      </a>
    </div>
  </div>
</section>

<footer class="footer py-4">
  <div class="container text-center">
    <p class="mb-1">
      <i class="bi bi-bag-heart-fill me-1" style="color:#818cf8;"></i>
      <strong>Garderobe</strong>
    </p>
    <small>Roberts Treijs &copy; 2025 &middot; Rīgas Valsts Tehnikums</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
