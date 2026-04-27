<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();
$db   = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/pages/wardrobe.php');
}

$id       = (int)($_POST['id'] ?? 0);
$name     = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$color    = trim($_POST['color'] ?? '');
$season   = $_POST['season'] ?? 'all';
$size     = trim($_POST['size'] ?? '');
$brand    = trim($_POST['brand'] ?? '');

if (!$name || !$category || !$color) {
    setFlash('error', 'Lūdzu aizpildiet visus obligātos laukus.');
    redirect(SITE_URL . '/pages/wardrobe.php');
}

$validSeasons = ['spring','summer','autumn','winter','all'];
if (!in_array($season, $validSeasons)) $season = 'all';

// Attēla augšupielāde
$imageUrl = null;
if (!empty($_FILES['image']['name'])) {
    $imageUrl = uploadImage($_FILES['image']);
    if (!$imageUrl) {
        setFlash('error', 'Attēla augšupielāde neizdevās. Atļautie formāti: JPG, PNG, GIF, WEBP. Maks. 5MB.');
        redirect(SITE_URL . '/pages/wardrobe.php');
    }
}

if ($id) {
    // UPDATE — pārbaud piederību
    $check = $db->prepare("SELECT id, image_url FROM clothing WHERE id = ? AND user_id = ?");
    $check->execute([$id, $user['id']]);
    $existing = $check->fetch();
    if (!$existing) {
        setFlash('error', 'Apģērbs nav atrasts.');
        redirect(SITE_URL . '/pages/wardrobe.php');
    }
    if (!$imageUrl) $imageUrl = $existing['image_url'];

    $stmt = $db->prepare("UPDATE clothing SET name=?,category=?,color=?,season=?,size=?,brand=?,image_url=? WHERE id=? AND user_id=?");
    $stmt->execute([$name,$category,$color,$season,$size,$brand,$imageUrl,$id,$user['id']]);
    setFlash('success', 'Apģērbs atjaunināts!');
} else {
    // INSERT
    $stmt = $db->prepare("INSERT INTO clothing (user_id,name,category,color,season,size,brand,image_url) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$user['id'],$name,$category,$color,$season,$size,$brand,$imageUrl]);
    setFlash('success', 'Apģērbs pievienots garderobes!');
}

redirect(SITE_URL . '/pages/wardrobe.php');
