<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();
$db   = getDB();
$uid  = $user['id'];

// Kopsavilkuma statistika
$total   = $db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=?"); $total->execute([$uid]);
$total   = (int)$total->fetchColumn();

$outfitC = $db->prepare("SELECT COUNT(*) FROM outfits WHERE user_id=?"); $outfitC->execute([$uid]);
$outfitC = (int)$outfitC->fetchColumn();

$wornC   = $db->prepare("SELECT COALESCE(SUM(times_worn),0) FROM outfits WHERE user_id=?"); $wornC->execute([$uid]);
$wornC   = (int)$wornC->fetchColumn();

$favC    = $db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=? AND is_favorite=1"); $favC->execute([$uid]);
$favC    = (int)$favC->fetchColumn();

// Top kombinācijas
$topOutfits = $db->prepare("SELECT name, times_worn FROM outfits WHERE user_id=? ORDER BY times_worn DESC LIMIT 5");
$topOutfits->execute([$uid]); $topOutfits = $topOutfits->fetchAll();

// Krāsu sadalījums
$colors = $db->prepare("SELECT color, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY color ORDER BY cnt DESC LIMIT 8");
$colors->execute([$uid]); $colors = $colors->fetchAll();

// Pēdējie pievienotie apģērbi
$recent = $db->prepare("SELECT name, category, created_at FROM clothing WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recent->execute([$uid]); $recent = $recent->fetchAll();

$pageTitle = 'Statistika';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-bar-chart me-2"></i>Garderobes statistika</h1>
</div>

<!-- KOPSAVILKUMS -->
<div class="row g-4 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#6c63ff,#5a52d5);">
      <div class="stat-num"><?= $total ?></div>
      <div class="fw-semibold"><i class="bi bi-bag me-1"></i>Apģērbi</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#43d8c9,#2cbfb1);">
      <div class="stat-num"><?= $outfitC ?></div>
      <div class="fw-semibold"><i class="bi bi-layers me-1"></i>Kombinācijas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#ff6584,#e8446a);">
      <div class="stat-num"><?= $wornC ?></div>
      <div class="fw-semibold"><i class="bi bi-repeat me-1"></i>Kopā valkāts</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
      <div class="stat-num"><?= $favC ?></div>
      <div class="fw-semibold"><i class="bi bi-heart-fill me-1"></i>Mīļākie</div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- TOP KOMBINĀCIJAS -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5 class="fw-bold mb-3"><i class="bi bi-trophy me-2 text-warning"></i>Populārākās kombinācijas</h5>
      <?php if ($topOutfits): ?>
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>#</th><th>Nosaukums</th><th class="text-end">Valkāts</th></tr></thead>
        <tbody>
        <?php foreach ($topOutfits as $i => $o): ?>
        <tr>
          <td><span class="badge bg-primary"><?= $i+1 ?></span></td>
          <td><?= sanitize($o['name']) ?></td>
          <td class="text-end"><strong><?= $o['times_worn'] ?>x</strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="text-muted">Vēl nav nevienas kombinācijas.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- KRĀSAS -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5 class="fw-bold mb-3"><i class="bi bi-palette me-2 text-danger"></i>Biežākās krāsas</h5>
      <?php if ($colors): ?>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($colors as $c): ?>
        <span class="color-badge">
          <?= sanitize($c['color']) ?> <span class="color-cnt"><?= $c['cnt'] ?></span>
        </span>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-muted">Nav datu.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- PĒDĒJIE PIEVIENOTIE -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-info"></i>Pēdējie pievienotie</h5>
      <?php if ($recent): ?>
      <table class="table table-hover mb-0">
        <thead><tr><th>Nosaukums</th><th>Kategorija</th><th>Pievienots</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= sanitize($r['name']) ?></td>
          <td><?= sanitize($r['category']) ?></td>
          <td class="text-muted"><?= date('d.m.Y', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="text-muted">Nav datu.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<div class="mt-4 text-end">
  <a href="export_pdf.php" class="btn btn-warning fw-semibold">
    <i class="bi bi-file-pdf me-2"></i>Eksportēt statistiku PDF
  </a>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
