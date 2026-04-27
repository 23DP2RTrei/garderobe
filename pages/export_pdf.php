<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isPremium()) { setFlash('error','Tikai Premium.'); redirect(SITE_URL.'/pages/stats.php'); }
$user = getCurrentUser();
$db   = getDB();
$uid  = $user['id'];

$total   = $db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=?"); $total->execute([$uid]); $total=(int)$total->fetchColumn();
$outfits = $db->prepare("SELECT COUNT(*) FROM outfits WHERE user_id=?"); $outfits->execute([$uid]); $outfits=(int)$outfits->fetchColumn();
$worn    = $db->prepare("SELECT COALESCE(SUM(times_worn),0) FROM outfits WHERE user_id=?"); $worn->execute([$uid]); $worn=(int)$worn->fetchColumn();

$cats = $db->prepare("SELECT category, COUNT(*) as cnt FROM clothing WHERE user_id=? GROUP BY category ORDER BY cnt DESC");
$cats->execute([$uid]); $cats=$cats->fetchAll();

$top = $db->prepare("SELECT name, times_worn FROM outfits WHERE user_id=? ORDER BY times_worn DESC LIMIT 5");
$top->execute([$uid]); $top=$top->fetchAll();

$seasonLabels = ['spring'=>'Pavasaris','summer'=>'Vasara','autumn'=>'Rudens','winter'=>'Ziema','all'=>'Universāls'];

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<title>Garderobes pārskats</title>
<style>
  body { font-family: 'Times New Roman', serif; margin: 2cm; color: #333; }
  h1   { color: #6c63ff; border-bottom: 3px solid #6c63ff; padding-bottom: 8px; }
  h2   { color: #5a52d5; margin-top: 2em; }
  table { width: 100%; border-collapse: collapse; margin-top: 1em; }
  th    { background: #6c63ff; color: white; padding: 8px 12px; text-align: left; }
  td    { padding: 7px 12px; border-bottom: 1px solid #eee; }
  tr:nth-child(even) td { background: #f8f8ff; }
  .stat { display: inline-block; margin: 0 2em 1em 0; }
  .stat-num { font-size: 2.5em; font-weight: bold; color: #6c63ff; }
  .stat-label { color: #888; font-size: .9em; }
  .footer { margin-top: 3em; border-top: 1px solid #ddd; padding-top: 1em; font-size: .8em; color: #aaa; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:1.5em;">
  <button onclick="window.print()" style="padding:10px 20px;background:#6c63ff;color:white;border:none;border-radius:8px;cursor:pointer;font-size:1em;">
    🖨️ Drukāt / Saglabāt PDF
  </button>
  <a href="<?= SITE_URL ?>/pages/stats.php" style="margin-left:1em;color:#6c63ff;">← Atpakaļ uz statistiku</a>
</div>

<h1>🧥 Garderobes pārskats</h1>
<p><strong>Lietotājs:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)<br>
<strong>Datums:</strong> <?= date('d.m.Y H:i') ?></p>

<h2>Kopsavilkums</h2>
<div>
  <div class="stat"><div class="stat-num"><?= $total ?></div><div class="stat-label">Apģērbi</div></div>
  <div class="stat"><div class="stat-num"><?= $outfits ?></div><div class="stat-label">Kombinācijas</div></div>
  <div class="stat"><div class="stat-num"><?= $worn ?></div><div class="stat-label">Kopā valkāts</div></div>
</div>

<h2>Kategoriju sadalījums</h2>
<table>
  <tr><th>Kategorija</th><th>Skaits</th><th>%</th></tr>
  <?php foreach ($cats as $c): $pct = $total ? round($c['cnt']/$total*100) : 0; ?>
  <tr><td><?= htmlspecialchars($c['category']) ?></td><td><?= $c['cnt'] ?></td><td><?= $pct ?>%</td></tr>
  <?php endforeach; ?>
</table>

<h2>Top kombinācijas</h2>
<table>
  <tr><th>#</th><th>Nosaukums</th><th>Valkāts (reizes)</th></tr>
  <?php foreach ($top as $i=>$t): ?>
  <tr><td><?= $i+1 ?></td><td><?= htmlspecialchars($t['name']) ?></td><td><?= $t['times_worn'] ?></td></tr>
  <?php endforeach; ?>
</table>

<div class="footer">
  Ģenerēts: <?= date('d.m.Y H:i') ?> · Garderobe — Digitālās garderobes pārvaldības sistēma · Roberts Treijs © 2025
</div>
</body></html>
