<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isPremium()) { setFlash('error','Tikai Premium.'); redirect(SITE_URL.'/pages/stats.php'); }
$user = getCurrentUser();
$db   = getDB();
$uid  = $user['id'];

$total  = (int)$db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=?")->execute([$uid]) ? $db->query("SELECT COUNT(*) FROM clothing WHERE user_id=$uid")->fetchColumn() : 0;
// Use proper prepared statements
$s = $db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=?"); $s->execute([$uid]); $total = (int)$s->fetchColumn();
$s = $db->prepare("SELECT COUNT(*) FROM outfits WHERE user_id=?");  $s->execute([$uid]); $outfits = (int)$s->fetchColumn();
$s = $db->prepare("SELECT COALESCE(SUM(times_worn),0) FROM outfits WHERE user_id=?"); $s->execute([$uid]); $worn = (int)$s->fetchColumn();
$s = $db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=? AND is_favorite=1"); $s->execute([$uid]); $favCount = (int)$s->fetchColumn();

$cats = $db->prepare("SELECT category, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY category ORDER BY cnt DESC");
$cats->execute([$uid]); $cats = $cats->fetchAll();

$seasons = $db->prepare("SELECT season, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY season ORDER BY cnt DESC");
$seasons->execute([$uid]); $seasons = $seasons->fetchAll();

$top = $db->prepare("SELECT name, times_worn FROM outfits WHERE user_id=? ORDER BY times_worn DESC LIMIT 5");
$top->execute([$uid]); $top = $top->fetchAll();

$colors = $db->prepare("SELECT color, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY color ORDER BY cnt DESC LIMIT 8");
$colors->execute([$uid]); $colors = $colors->fetchAll();

$recent = $db->prepare("SELECT name, category, season, created_at FROM clothing WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$recent->execute([$uid]); $recent = $recent->fetchAll();

$seasonLabels = ['spring'=>'Pavasaris','summer'=>'Vasara','autumn'=>'Rudens','winter'=>'Ziema','all'=>'Universāls'];

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<title>Garderobes pārskats — <?= htmlspecialchars($user['name']) ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; color: #1e293b; background: #fff; font-size: 14px; line-height: 1.5; }
  .page { max-width: 900px; margin: 0 auto; padding: 2.5cm 2cm; }

  /* Header */
  .pdf-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #6366f1; padding-bottom: 1.2rem; margin-bottom: 2rem; }
  .pdf-title { font-size: 1.8rem; font-weight: 800; color: #6366f1; letter-spacing: -.02em; }
  .pdf-subtitle { color: #64748b; font-size: .85rem; margin-top: .25rem; }
  .pdf-meta { text-align: right; font-size: .8rem; color: #64748b; }
  .pdf-meta strong { color: #1e293b; }

  /* Section titles */
  h2 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 2rem 0 .85rem; padding-bottom: .4rem; border-bottom: 1px solid #e2e8f0; }

  /* Summary cards */
  .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: .5rem; }
  .stat-box { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; border-radius: 12px; padding: 1rem 1.25rem; }
  .stat-box.teal  { background: linear-gradient(135deg,#43d8c9,#2cbfb1); }
  .stat-box.pink  { background: linear-gradient(135deg,#ff6584,#e8446a); }
  .stat-box.amber { background: linear-gradient(135deg,#f59e0b,#d97706); }
  .stat-num   { font-size: 2rem; font-weight: 800; line-height: 1; }
  .stat-label { font-size: .72rem; font-weight: 600; opacity: .9; margin-top: .2rem; }

  /* Tables */
  table { width: 100%; border-collapse: collapse; font-size: .875rem; }
  thead th { background: #6366f1; color: #fff; padding: 8px 12px; text-align: left; font-weight: 600; }
  tbody td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:nth-child(even) td { background: #f8fafc; }
  .rank-badge { display: inline-block; background: #6366f1; color: #fff; width: 22px; height: 22px; border-radius: 50%; text-align: center; line-height: 22px; font-size: .72rem; font-weight: 700; }

  /* Progress bar (categories) */
  .bar-wrap { background: #f1f5f9; border-radius: 4px; height: 8px; margin-top: 4px; overflow: hidden; }
  .bar-fill  { height: 100%; background: #6366f1; border-radius: 4px; }

  /* Color badges */
  .color-list { display: flex; flex-wrap: wrap; gap: .5rem; }
  .color-tag { background: rgba(99,102,241,.1); color: #4338ca; border: 1.5px solid rgba(99,102,241,.2); border-radius: 20px; padding: 3px 12px; font-size: .8rem; font-weight: 600; }

  /* Season dots */
  .season-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }

  /* Print button */
  .no-print { padding: 1rem 2rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem; }
  .btn-print { padding: 8px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; }
  .btn-back  { color: #6366f1; text-decoration: none; font-size: .9rem; }

  /* Footer */
  .pdf-footer { margin-top: 2.5rem; border-top: 1px solid #e2e8f0; padding-top: 1rem; font-size: .75rem; color: #94a3b8; text-align: center; }

  @media print {
    .no-print { display: none !important; }
    .page { padding: 1cm; }
    body { font-size: 12px; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">Drukāt / Saglabāt PDF</button>
  <a class="btn-back" href="<?= SITE_URL ?>/pages/stats.php">← Atpakaļ uz statistiku</a>
</div>

<div class="page">

  <!-- Header -->
  <div class="pdf-header">
    <div>
      <div class="pdf-title">Garderobe</div>
      <div class="pdf-subtitle">Digitālās garderobes pārskats</div>
    </div>
    <div class="pdf-meta">
      <strong><?= htmlspecialchars($user['name']) ?></strong><br>
      <?= htmlspecialchars($user['email']) ?><br>
      <?= date('d.m.Y H:i') ?>
    </div>
  </div>

  <!-- Summary -->
  <h2>Kopsavilkums</h2>
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Apģērbi</div>
    </div>
    <div class="stat-box teal">
      <div class="stat-num"><?= $outfits ?></div>
      <div class="stat-label">Kombinācijas</div>
    </div>
    <div class="stat-box pink">
      <div class="stat-num"><?= $worn ?></div>
      <div class="stat-label">Kopā valkāts</div>
    </div>
    <div class="stat-box amber">
      <div class="stat-num"><?= $favCount ?></div>
      <div class="stat-label">Mīļākie</div>
    </div>
  </div>

  <!-- Categories -->
  <h2>Kategoriju sadalījums</h2>
  <table>
    <thead><tr><th>Kategorija</th><th>Skaits</th><th>Īpatsvars</th><th style="width:200px;">Grafiks</th></tr></thead>
    <tbody>
    <?php foreach ($cats as $c):
      $pct = $total ? round($c['cnt'] / $total * 100) : 0; ?>
    <tr>
      <td><?= htmlspecialchars($c['category']) ?></td>
      <td><?= $c['cnt'] ?></td>
      <td><?= $pct ?>%</td>
      <td><div class="bar-wrap"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Seasons -->
  <h2>Sezonu sadalījums</h2>
  <?php $sColors = ['spring'=>'#28a745','summer'=>'#fd7e14','autumn'=>'#dc3545','winter'=>'#007bff','all'=>'#6f42c1']; ?>
  <table>
    <thead><tr><th>Sezona</th><th>Skaits</th><th>Īpatsvars</th></tr></thead>
    <tbody>
    <?php foreach ($seasons as $r):
      $pct = $total ? round($r['cnt'] / $total * 100) : 0;
      $col = $sColors[$r['season']] ?? '#6366f1'; ?>
    <tr>
      <td><span class="season-dot" style="background:<?= $col ?>;"></span><?= $seasonLabels[$r['season']] ?? $r['season'] ?></td>
      <td><?= $r['cnt'] ?></td>
      <td><?= $pct ?>%</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Top outfits -->
  <?php if ($top): ?>
  <h2>Populārākās kombinācijas</h2>
  <table>
    <thead><tr><th>#</th><th>Nosaukums</th><th>Valkāts (reizes)</th></tr></thead>
    <tbody>
    <?php foreach ($top as $i => $t): ?>
    <tr>
      <td><span class="rank-badge"><?= $i+1 ?></span></td>
      <td><?= htmlspecialchars($t['name']) ?></td>
      <td><?= $t['times_worn'] ?>x</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Colors -->
  <?php if ($colors): ?>
  <h2>Biežākās krāsas</h2>
  <div class="color-list">
    <?php foreach ($colors as $c): ?>
    <span class="color-tag"><?= htmlspecialchars($c['color']) ?> (<?= $c['cnt'] ?>)</span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Recent -->
  <?php if ($recent): ?>
  <h2>Pēdējie pievienotie apģērbi</h2>
  <table>
    <thead><tr><th>Nosaukums</th><th>Kategorija</th><th>Sezona</th><th>Pievienots</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['category']) ?></td>
      <td><?= $seasonLabels[$r['season']] ?? $r['season'] ?></td>
      <td><?= date('d.m.Y', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="pdf-footer">
    Ģenerēts: <?= date('d.m.Y H:i') ?> &middot; Garderobe — Digitālās garderobes pārvaldības sistēma &middot; Roberts Treijs &copy; 2026 &middot; Rīgas Valsts Tehnikums
  </div>

</div>
</body></html>
