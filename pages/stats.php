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

// Kategoriju sadalījums
$cats = $db->prepare("SELECT category, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY category ORDER BY cnt DESC");
$cats->execute([$uid]); $cats = $cats->fetchAll();

// Sezonu sadalījums
$seasons = $db->prepare("SELECT season, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY season ORDER BY cnt DESC");
$seasons->execute([$uid]); $seasons = $seasons->fetchAll();

// Top kombinācijas
$topOutfits = $db->prepare("SELECT name, times_worn FROM outfits WHERE user_id=? ORDER BY times_worn DESC LIMIT 5");
$topOutfits->execute([$uid]); $topOutfits = $topOutfits->fetchAll();

// Krāsu sadalījums
$colors = $db->prepare("SELECT color, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY color ORDER BY cnt DESC LIMIT 8");
$colors->execute([$uid]); $colors = $colors->fetchAll();

// Pēdējie pievienotie apģērbi
$recent = $db->prepare("SELECT name, category, created_at FROM clothing WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recent->execute([$uid]); $recent = $recent->fetchAll();

$seasonLabels = ['spring'=>'Pavasaris','summer'=>'Vasara','autumn'=>'Rudens','winter'=>'Ziema','all'=>'Universāls'];

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
  <!-- KATEGORIJAS GRAFIKS -->
  <div class="col-md-6">
    <div class="card p-4">
      <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Kategoriju sadalījums</h5>
      <?php if ($cats): ?>
      <canvas id="catChart" height="220"></canvas>
      <?php else: ?>
      <p class="text-muted">Nav datu.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- SEZONAS GRAFIKS -->
  <div class="col-md-6">
    <div class="card p-4">
      <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-warning"></i>Sezonu sadalījums</h5>
      <?php if ($seasons): ?>
      <canvas id="seasonChart" height="220"></canvas>
      <?php else: ?>
      <p class="text-muted">Nav datu.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- TOP KOMBINĀCIJAS -->
  <div class="col-md-6">
    <div class="card p-4">
      <h5 class="fw-bold mb-3"><i class="bi bi-trophy me-2 text-warning"></i>Populārākās kombinācijas</h5>
      <?php if ($topOutfits): ?>
      <table class="table table-sm table-hover">
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
  <div class="col-md-6">
    <div class="card p-4">
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

  <!-- PĒDĒJIE -->
  <div class="col-12">
    <div class="card p-4">
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

<?php if (isPremium()): ?>
<div class="mt-4 text-end">
  <a href="export_pdf.php" class="btn btn-warning fw-semibold">
    <i class="bi bi-file-pdf me-2"></i>Eksportēt statistiku PDF
  </a>
</div>
<?php else: ?>
<div class="premium-banner mt-4 d-flex align-items-center gap-3">
  <i class="bi bi-stars flex-shrink-0" style="font-size:2rem;"></i>
  <div>
    <h5 class="fw-bold mb-1">Premium — PDF eksports</h5>
    <p class="mb-0">Lejupielādējiet garderobes statistiku un pārskatu PDF formātā. Jautājiet administratoram par Premium aktivizāciju.</p>
  </div>
</div>
<?php endif; ?>

<?php if ($cats || $seasons): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  var textColor = isDark ? '#94a3b8' : '#6c757d';
  var gridColor = isDark ? 'rgba(148,163,184,.15)' : 'rgba(0,0,0,.07)';
  Chart.defaults.color = textColor;

  <?php if ($cats): ?>
  new Chart(document.getElementById('catChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($cats, 'category')) ?>,
      datasets: [{
        label: 'Apģērbi',
        data:  <?= json_encode(array_column($cats, 'cnt')) ?>,
        backgroundColor: 'rgba(108,99,255,.75)',
        borderColor: '#6c63ff',
        borderWidth: 1.5,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: gridColor }, ticks: { color: textColor } },
        y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
      }
    }
  });
  <?php endif; ?>

  <?php if ($seasons):
    $seasonColors = ['spring'=>'#28a745','summer'=>'#fd7e14','autumn'=>'#e8446a','winter'=>'#4e9af1','all'=>'#9f7aea'];
    $sLabels = []; $sCounts = []; $sColors = [];
    foreach ($seasons as $r) {
      $sLabels[] = $seasonLabels[$r['season']] ?? $r['season'];
      $sCounts[] = $r['cnt'];
      $sColors[] = $seasonColors[$r['season']] ?? '#6c63ff';
    }
  ?>
  new Chart(document.getElementById('seasonChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($sLabels) ?>,
      datasets: [{
        data: <?= json_encode($sCounts) ?>,
        backgroundColor: <?= json_encode($sColors) ?>,
        borderWidth: 2,
        borderColor: isDark ? '#1e293b' : '#fff',
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      cutout: '62%',
      plugins: {
        legend: { position: 'bottom', labels: { color: textColor, padding: 14, font: { size: 12 } } }
      }
    }
  });
  <?php endif; ?>
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
