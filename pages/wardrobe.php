<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();
$db = getDB();

$where  = ["c.user_id = ?"];
$params = [$user['id']];

$search   = trim($_GET['search']   ?? '');
$category = $_GET['category']      ?? '';
$season   = $_GET['season']        ?? '';
$color    = trim($_GET['color']    ?? '');
$sort     = $_GET['sort']          ?? 'newest';

if ($search)   { $where[] = "c.name LIKE ?";   $params[] = "%$search%"; }
if ($category) { $where[] = "c.category = ?";  $params[] = $category; }
if ($season)   { $where[] = "c.season = ?";    $params[] = $season; }
if ($color)    { $where[] = "c.color LIKE ?";  $params[] = "%$color%"; }

$orderBy = match($sort) {
    'name'   => 'c.name ASC',
    'oldest' => 'c.created_at ASC',
    default  => 'c.created_at DESC',
};

$sql = "SELECT c.* FROM clothing c WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$clothes = $stmt->fetchAll();

$totalClothes = $db->prepare("SELECT COUNT(*) FROM clothing WHERE user_id=?");
$totalClothes->execute([$user['id']]);
$totalClothes = (int)$totalClothes->fetchColumn();

$cats = $db->prepare("SELECT DISTINCT category FROM clothing WHERE user_id=? ORDER BY category");
$cats->execute([$user['id']]);
$allCategories = $cats->fetchAll(PDO::FETCH_COLUMN);

$seasonLabels = ['spring'=>'Pavasaris','summer'=>'Vasara','autumn'=>'Rudens','winter'=>'Ziema','all'=>'Universāls'];
$pageTitle = 'Mana garderobe';
require_once __DIR__ . '/../includes/header.php';

// DELETE
if (isset($_GET['delete'])) {
    $del = $db->prepare("SELECT image_url FROM clothing WHERE id=? AND user_id=?");
    $del->execute([(int)$_GET['delete'], $user['id']]);
    $toDelete = $del->fetch();
    if ($toDelete && $toDelete['image_url'] && file_exists(UPLOAD_DIR . $toDelete['image_url'])) {
        unlink(UPLOAD_DIR . $toDelete['image_url']);
    }
    $db->prepare("DELETE FROM clothing WHERE id=? AND user_id=?")->execute([(int)$_GET['delete'], $user['id']]);
    setFlash('success', 'Apģērbs dzēsts.');
    redirect(SITE_URL . '/pages/wardrobe.php');
}
?>

<!-- APP BAR -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="app-title">Garderobe</h1>
    <div class="app-subtitle"><?= $totalClothes ?> apģērbs(-i)</div>
  </div>
  <a href="#" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-plus-lg me-1"></i>Pievienot
  </a>
</div>

<!-- FITTED TABS -->
<div class="fitted-tabs-wrap mb-4">
  <div class="fitted-tabs">
    <a href="wardrobe.php" class="ftab active">Apģērbi</a>
    <a href="outfits.php"  class="ftab">Kombinācijas</a>
    <a href="stats.php"    class="ftab">Statistika</a>
  </div>
</div>

<!-- SEARCH -->
<form method="GET" id="filterForm" class="mb-3">
  <?php if ($category): ?><input type="hidden" name="category" value="<?= sanitize($category) ?>"><?php endif; ?>
  <?php if ($season):   ?><input type="hidden" name="season"   value="<?= sanitize($season) ?>"><?php endif; ?>
  <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= sanitize($sort) ?>"><?php endif; ?>
  <div class="search-wrap">
    <i class="bi bi-search search-icon"></i>
    <input type="text" name="search" class="search-input" placeholder="Meklēt apģērbu..." value="<?= sanitize($search) ?>">
    <?php if ($search || $category || $season || $color): ?>
    <a href="wardrobe.php" class="search-clear"><i class="bi bi-x-lg"></i></a>
    <?php endif; ?>
  </div>
</form>

<!-- CATEGORY PILLS -->
<div class="filter-pills-wrap mb-2">
  <?php
  $baseQ = ($search ? '&search='.urlencode($search) : '') . ($season ? '&season='.urlencode($season) : '');
  ?>
  <a href="wardrobe.php<?= $baseQ ? '?'.ltrim($baseQ,'&') : '' ?>" class="fpill <?= !$category ? 'active' : '' ?>">Visi</a>
  <?php foreach ($allCategories as $cat): ?>
  <a href="?category=<?= urlencode($cat) ?><?= $baseQ ?>" class="fpill <?= $category===$cat ? 'active' : '' ?>"><?= sanitize($cat) ?></a>
  <?php endforeach; ?>
</div>

<!-- SEASON PILLS -->
<div class="filter-pills-wrap mb-4">
  <?php $catQ = $category ? '&category='.urlencode($category) : ''; ?>
  <a href="wardrobe.php<?= ($catQ||$search) ? '?'.ltrim($catQ.$baseQ,'&') : '' ?>" class="fpill fpill-sm <?= !$season ? 'active' : '' ?>">Visas sezonas</a>
  <?php foreach ($seasonLabels as $k=>$v): ?>
  <a href="?season=<?= $k ?><?= $catQ ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="fpill fpill-sm <?= $season===$k ? 'active' : '' ?>"><?= $v ?></a>
  <?php endforeach; ?>
  <select name="sort" form="filterForm" class="sort-select ms-auto flex-shrink-0" onchange="document.getElementById('filterForm').submit()">
    <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Jaunākie</option>
    <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Vecākie</option>
    <option value="name"   <?= $sort==='name'  ?'selected':'' ?>>Nosaukums</option>
  </select>
</div>

<!-- PIECES GRID -->
<?php if (empty($clothes)): ?>
<div class="empty-state">
  <i class="bi bi-bag-plus"></i>
  <h4>Garderobe ir tukša</h4>
  <p>Pievienojiet pirmo apģērbu, lai sāktu!</p>
  <a href="#" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addModal">Pievienot apģērbu</a>
</div>
<?php else: ?>
<div class="pieces-grid">
  <?php foreach ($clothes as $item): ?>
  <div class="piece-card">
    <?php if ($item['image_url']): ?>
    <div class="piece-img-wrap">
      <img src="<?= UPLOAD_URL . sanitize($item['image_url']) ?>" alt="<?= sanitize($item['name']) ?>" class="piece-img" loading="lazy">
      <div class="piece-actions">
        <a href="?edit=<?= $item['id'] ?>" class="piece-btn" title="Rediģēt"><i class="bi bi-pencil"></i></a>
        <a href="?delete=<?= $item['id'] ?>" class="piece-btn piece-btn-del" onclick="return confirmDelete('Dzēst šo apģērbu?')" title="Dzēst"><i class="bi bi-trash"></i></a>
      </div>
    </div>
    <?php else: ?>
    <div class="piece-no-img">
      <i class="bi bi-image"></i>
      <div class="piece-actions">
        <a href="?edit=<?= $item['id'] ?>" class="piece-btn"><i class="bi bi-pencil"></i></a>
        <a href="?delete=<?= $item['id'] ?>" class="piece-btn piece-btn-del" onclick="return confirmDelete('Dzēst šo apģērbu?')"><i class="bi bi-trash"></i></a>
      </div>
    </div>
    <?php endif; ?>
    <div class="piece-info">
      <div class="piece-name"><?= sanitize($item['name']) ?></div>
      <div class="piece-meta">
        <span class="badge-season season-<?= $item['season'] ?>"><?= $seasonLabels[$item['season']] ?></span>
        <?php if ($item['color']): ?>
        <span class="piece-color"><?= sanitize($item['color']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Pievienot apģērbu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="clothing_save.php" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nosaukums *</label>
              <input type="text" name="name" class="form-control" placeholder="piem. Baltais krekls" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kategorija *</label>
              <select name="category" class="form-select" required>
                <option value="">-- izvēlēties --</option>
                <?php foreach (['Cepure','Krekls','T-krekls','Džemperis','Jaka','Mētelis','Kleita','Svārki','Bikses','Šorti','Apavi','Aksesuāri','Cits'] as $c): ?>
                <option><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Krāsa *</label>
              <input type="text" name="color" class="form-control" placeholder="piem. Balta" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Sezona</label>
              <select name="season" class="form-select">
                <?php foreach ($seasonLabels as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Izmērs</label>
              <select name="size" class="form-select">
                <option value="">--</option>
                <?php foreach (['XS','S','M','L','XL','XXL','36','37','38','39','40','41','42','43','44','45'] as $s): ?><option><?= $s ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Zīmols</label>
              <input type="text" name="brand" class="form-control" placeholder="piem. Zara, H&M...">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Fotoattēls</label>
              <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
              <img id="imagePreview" src="" style="display:none;max-height:90px;margin-top:8px;border-radius:8px;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Atcelt</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check-lg me-1"></i>Saglabāt</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if (isset($_GET['edit'])):
  $editStmt = $db->prepare("SELECT * FROM clothing WHERE id=? AND user_id=?");
  $editStmt->execute([(int)$_GET['edit'], $user['id']]);
  $editItem = $editStmt->fetch();
  if ($editItem): ?>
<div class="modal fade show d-block" id="editModal" tabindex="-1" style="background:rgba(0,0,0,.55);">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Rediģēt apģērbu</h5>
        <a href="wardrobe.php" class="btn-close"></a>
      </div>
      <form method="POST" action="clothing_save.php" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nosaukums *</label>
              <input type="text" name="name" class="form-control" value="<?= sanitize($editItem['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kategorija *</label>
              <select name="category" class="form-select" required>
                <?php foreach (['Cepure','Krekls','T-krekls','Džemperis','Jaka','Mētelis','Kleita','Svārki','Bikses','Šorti','Apavi','Aksesuāri','Cits'] as $c): ?>
                <option <?= $editItem['category']===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Krāsa *</label>
              <input type="text" name="color" class="form-control" value="<?= sanitize($editItem['color']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Sezona</label>
              <select name="season" class="form-select">
                <?php foreach ($seasonLabels as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $editItem['season']===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Izmērs</label>
              <select name="size" class="form-select">
                <option value="">--</option>
                <?php foreach (['XS','S','M','L','XL','XXL','36','37','38','39','40','41','42','43','44','45'] as $s): ?>
                <option <?= $editItem['size']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Zīmols</label>
              <input type="text" name="brand" class="form-control" value="<?= sanitize($editItem['brand'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Jauns fotoattēls</label>
              <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
              <?php if ($editItem['image_url']): ?>
              <img src="<?= UPLOAD_URL . sanitize($editItem['image_url']) ?>" style="max-height:70px;margin-top:8px;border-radius:8px;background:#fff;mix-blend-mode:multiply;">
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="wardrobe.php" class="btn btn-outline-secondary">Atcelt</a>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check-lg me-1"></i>Saglabāt</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
