<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();
$db   = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(SITE_URL . '/pages/outfits.php');

$name     = trim($_POST['name'] ?? '');
$clothing = $_POST['clothing'] ?? [];

if (!$name) { setFlash('error','Nosaukums ir obligāts.'); redirect(SITE_URL . '/pages/outfits.php'); }
if (count($clothing) < 2) { setFlash('error','Izvēlieties vismaz 2 apģērbus.'); redirect(SITE_URL . '/pages/outfits.php'); }

// Pārbaud ka apģērbi pieder šim lietotājam
$placeholders = implode(',', array_fill(0, count($clothing), '?'));
$check = $db->prepare("SELECT id FROM clothing WHERE id IN ($placeholders) AND user_id = ?");
$check->execute(array_merge($clothing, [$user['id']]));
$valid = $check->fetchAll(PDO::FETCH_COLUMN);

if (count($valid) < 2) { setFlash('error','Nederīgi apģērbi.'); redirect(SITE_URL . '/pages/outfits.php'); }

$db->prepare("INSERT INTO outfits (user_id, name) VALUES (?,?)")->execute([$user['id'], $name]);
$outfitId = $db->lastInsertId();

$ins = $db->prepare("INSERT INTO outfit_clothing (outfit_id, clothing_id) VALUES (?,?)");
foreach ($valid as $cid) $ins->execute([$outfitId, $cid]);

setFlash('success', 'Kombinācija "' . $name . '" izveidota!');
redirect(SITE_URL . '/pages/outfits.php');
