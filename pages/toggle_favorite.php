<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(SITE_URL . '/pages/wardrobe.php');
verifyCSRF(SITE_URL . '/pages/wardrobe.php');

$user = getCurrentUser();
$db   = getDB();
$id   = (int)($_POST['id'] ?? 0);

$stmt = $db->prepare("SELECT id, is_favorite FROM clothing WHERE id=? AND user_id=?");
$stmt->execute([$id, $user['id']]);
$item = $stmt->fetch();

if ($item) {
    $db->prepare("UPDATE clothing SET is_favorite=? WHERE id=? AND user_id=?")
       ->execute([$item['is_favorite'] ? 0 : 1, $id, $user['id']]);
}

$back = http_build_query(array_filter([
    'category' => $_POST['back_category'] ?? '',
    'season'   => $_POST['back_season']   ?? '',
    'search'   => $_POST['back_search']   ?? '',
    'sort'     => $_POST['back_sort']     ?? '',
    'fav'      => $_POST['back_fav']      ?? '',
    'page'     => $_POST['back_page']     ?? '',
]));
redirect(SITE_URL . '/pages/wardrobe.php' . ($back ? '?' . $back : ''));
