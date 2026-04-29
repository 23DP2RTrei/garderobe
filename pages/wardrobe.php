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
$fav      = $_GET['fav']           ?? '';
$perPage  = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));

if ($search)   { $where[] = "c.name LIKE ?";   $params[] = "%$search%"; }
if ($category) { $where[] = "c.category = ?";  $params[] = $category; }
if ($season)   { $where[] = "c.season = ?";    $params[] = $season; }
if ($color)    { $where[] = "c.color LIKE ?";  $params[] = "%$color%"; }
if ($fav === '1') { $where[] = "c.is_favorite = 1"; }

$orderBy = match($sort) {
    'name'   => 'c.name ASC',
    'oldest' => 'c.created_at ASC',
    default  => 'c.created_at DESC',
};

$whereStr = implode(' AND ', $where);

// Count filtered
$countStmt = $db->prepare("SELECT COUNT(*) FROM clothing c WHERE $whereStr");
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT c.* FROM clothing c WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
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

// Build shared query string for pagination links
function buildQ(array $override = []): string {
    global $search, $category, $season, $sort, $fav, $page;
    $base = array_filter([
        'search'   => $search,
        'category' => $category,
        'season'   => $season,
        'sort'     => $sort !== 'newest' ? $sort : '',
        'fav'      => $fav,
        'page'     => $page > 1 ? (string)$page : '',
    ]);
    $merged = array_filter(array_merge($base, $override));
    return $merged ? '?' . http_build_query($merged) : '';
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
  <?php if ($fav):      ?><input type="hidden" name="fav"      value="1"><?php endif; ?>
  <div class="search-wrap">
    <i class="bi bi-search search-icon"></i>
    <input type="text" name="search" class="search-input" placeholder="Meklēt apģērbu..." value="<?= sanitize($search) ?>">
    <?php if ($search || $category || $season || $color || $fav): ?>
    <a href="wardrobe.php" class="search-clear"><i class="bi bi-x-lg"></i></a>
    <?php endif; ?>
  </div>
</form>

<!-- CATEGORY & SEASON PILLS -->
<?php
// Atsevišķi parametri — ērtākai URL celšanai
$qSearch = $search   ? '&search='.urlencode($search)     : '';
$qCat    = $category ? '&category='.urlencode($category) : '';
$qSeason = $season   ? '&season='.urlencode($season)     : '';
$qFav    = $fav      ? '&fav=1'                          : '';

// Palīgfunkcija: savieno parametrus vienā URL
function wUrl() {
    $q = ltrim(implode('', func_get_args()), '&');
    return 'wardrobe.php' . ($q ? '?' . $q : '');
}
?>

<!-- 1. rinda: Kategorijas -->
<div class="filter-pills-wrap mb-2">
  <a href="<?= wUrl($qSearch, $qSeason, $qFav) ?>" class="fpill <?= !$category ? 'active' : '' ?>">Visi</a>
  <?php foreach ($allCategories as $cat): ?>
  <a href="<?= wUrl('&category='.urlencode($cat), $qSearch, $qSeason, $qFav) ?>" class="fpill <?= $category===$cat ? 'active' : '' ?>"><?= sanitize($cat) ?></a>
  <?php endforeach; ?>
</div>

<!-- 2. rinda: Sezonas + Mīļākie + Šķirošana -->
<div class="filter-pills-wrap mb-4">
  <!-- "Visas" atceļ sezonu filtru -->
  <a href="<?= wUrl($qCat, $qSearch, $qFav) ?>" class="fpill fpill-sm <?= !$season ? 'active' : '' ?>">Visas</a>

  <?php foreach ($seasonLabels as $k => $v):
    $isSeasonActive = ($season === $k);
    // Aktīvs → klikšķis atceļ; neaktīvs → iestata šo sezonu
    $seasonHref = $isSeasonActive ? wUrl($qCat, $qSearch, $qFav) : wUrl('&season='.$k, $qCat, $qSearch, $qFav);
  ?>
  <a href="<?= $seasonHref ?>" class="fpill fpill-sm <?= $isSeasonActive ? 'active' : '' ?>"><?= $v ?></a>
  <?php endforeach; ?>

  <!-- Mīļākie toggle: aktīvs → noņem fav; neaktīvs → pievieno -->
  <?php $favHref = ($fav === '1') ? wUrl($qCat, $qSearch, $qSeason) : wUrl('&fav=1', $qCat, $qSearch, $qSeason); ?>
  <a href="<?= $favHref ?>" class="fpill fpill-sm fpill-fav <?= $fav==='1' ? 'active' : '' ?>">
    <i class="bi bi-heart-fill me-1"></i>Mīļākie
  </a>

  <select name="sort" form="filterForm" class="sort-select ms-auto flex-shrink-0" onchange="document.getElementById('filterForm').submit()">
    <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Jaunākie</option>
    <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Vecākie</option>
    <option value="name"   <?= $sort==='name'  ?'selected':'' ?>>Nosaukums</option>
  </select>
</div>

<!-- COUNT INFO -->
<?php if ($totalFiltered > 0): ?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <small class="text-muted"><?= $totalFiltered ?> apģērbs(-i) atrasts(-i)</small>
  <?php if ($totalPages > 1): ?>
  <small class="text-muted">Lapa <?= $page ?> no <?= $totalPages ?></small>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- PIECES GRID -->
<?php if (empty($clothes)): ?>
<div class="empty-state">
  <i class="bi bi-bag-plus"></i>
  <h4><?= $fav==='1' ? 'Nav mīļāko apģērbu' : 'Garderobe ir tukša' ?></h4>
  <p><?= $fav==='1' ? 'Nospiediet ♥ uz kāda apģērba, lai to pievienotu mīļākajiem!' : 'Pievienojiet pirmo apģērbu, lai sāktu!' ?></p>
  <?php if (!$fav): ?><a href="#" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addModal">Pievienot apģērbu</a><?php endif; ?>
</div>
<?php else: ?>
<div class="pieces-grid">
  <?php foreach ($clothes as $item): ?>
  <div class="piece-card">
    <?php if ($item['image_url']): ?>
    <div class="piece-img-wrap">
      <img src="<?= UPLOAD_URL . sanitize($item['image_url']) ?>" alt="<?= sanitize($item['name']) ?>" class="piece-img" loading="lazy">
      <div class="piece-actions">
        <!-- Favorite toggle -->
        <form method="POST" action="toggle_favorite.php" class="d-inline">
          <input type="hidden" name="csrf_token"     value="<?= generateCSRF() ?>">
          <input type="hidden" name="id"             value="<?= $item['id'] ?>">
          <input type="hidden" name="back_category"  value="<?= sanitize($category) ?>">
          <input type="hidden" name="back_season"    value="<?= sanitize($season) ?>">
          <input type="hidden" name="back_search"    value="<?= sanitize($search) ?>">
          <input type="hidden" name="back_sort"      value="<?= sanitize($sort) ?>">
          <input type="hidden" name="back_fav"       value="<?= sanitize($fav) ?>">
          <input type="hidden" name="back_page"      value="<?= $page ?>">
          <button type="submit" class="piece-btn piece-btn-fav <?= $item['is_favorite'] ? 'active' : '' ?>" title="<?= $item['is_favorite'] ? 'Noņemt no mīļākajiem' : 'Pievienot mīļākajiem' ?>">
            <i class="bi bi-heart<?= $item['is_favorite'] ? '-fill' : '' ?>"></i>
          </button>
        </form>
        <a href="?edit=<?= $item['id'] ?>" class="piece-btn" title="Rediģēt"><i class="bi bi-pencil"></i></a>
        <a href="?delete=<?= $item['id'] ?>" class="piece-btn piece-btn-del" onclick="return confirmDelete('Dzēst šo apģērbu?')" title="Dzēst"><i class="bi bi-trash"></i></a>
      </div>
    </div>
    <?php else: ?>
    <div class="piece-no-img">
      <i class="bi bi-image"></i>
      <div class="piece-actions">
        <form method="POST" action="toggle_favorite.php" class="d-inline">
          <input type="hidden" name="csrf_token"    value="<?= generateCSRF() ?>">
          <input type="hidden" name="id"            value="<?= $item['id'] ?>">
          <input type="hidden" name="back_category" value="<?= sanitize($category) ?>">
          <input type="hidden" name="back_season"   value="<?= sanitize($season) ?>">
          <input type="hidden" name="back_search"   value="<?= sanitize($search) ?>">
          <input type="hidden" name="back_sort"     value="<?= sanitize($sort) ?>">
          <input type="hidden" name="back_fav"      value="<?= sanitize($fav) ?>">
          <input type="hidden" name="back_page"     value="<?= $page ?>">
          <button type="submit" class="piece-btn piece-btn-fav <?= $item['is_favorite'] ? 'active' : '' ?>" title="<?= $item['is_favorite'] ? 'Noņemt' : 'Mīļākie' ?>">
            <i class="bi bi-heart<?= $item['is_favorite'] ? '-fill' : '' ?>"></i>
          </button>
        </form>
        <a href="?edit=<?= $item['id'] ?>" class="piece-btn"><i class="bi bi-pencil"></i></a>
        <a href="?delete=<?= $item['id'] ?>" class="piece-btn piece-btn-del" onclick="return confirmDelete('Dzēst šo apģērbu?')"><i class="bi bi-trash"></i></a>
      </div>
    </div>
    <?php endif; ?>
    <div class="piece-info">
      <div class="piece-name">
        <?= sanitize($item['name']) ?>
        <?php if ($item['is_favorite']): ?><i class="bi bi-heart-fill ms-1" style="color:#e63946;font-size:.7rem;"></i><?php endif; ?>
      </div>
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

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center" aria-label="Lapas navigācija">
  <ul class="pagination gap-1">
    <?php if ($page > 1): ?>
    <li class="page-item">
      <a class="page-link" href="?page=<?= $page-1 ?><?= $catQ ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $season ? '&season='.urlencode($season) : '' ?><?= $sort!=='newest' ? '&sort='.urlencode($sort) : '' ?><?= $fav ? '&fav=1' : '' ?>">
        <i class="bi bi-chevron-left"></i>
      </a>
    </li>
    <?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
    <li class="page-item <?= $i===$page ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $i ?><?= $catQ ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $season ? '&season='.urlencode($season) : '' ?><?= $sort!=='newest' ? '&sort='.urlencode($sort) : '' ?><?= $fav ? '&fav=1' : '' ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <li class="page-item">
      <a class="page-link" href="?page=<?= $page+1 ?><?= $catQ ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $season ? '&season='.urlencode($season) : '' ?><?= $sort!=='newest' ? '&sort='.urlencode($sort) : '' ?><?= $fav ? '&fav=1' : '' ?>">
        <i class="bi bi-chevron-right"></i>
      </a>
    </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

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
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nosaukums *</label>
              <input type="text" name="name" class="form-control" placeholder="piem. Baltais krekls" required maxlength="100">
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
              <input type="text" name="color" class="form-control" placeholder="piem. Balta" required maxlength="50">
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
              <input type="text" name="brand" class="form-control" placeholder="piem. Zara, H&M..." maxlength="100">
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
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nosaukums *</label>
              <input type="text" name="name" class="form-control" value="<?= sanitize($editItem['name']) ?>" required maxlength="100">
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
              <input type="text" name="color" class="form-control" value="<?= sanitize($editItem['color']) ?>" required maxlength="50">
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
              <input type="text" name="brand" class="form-control" value="<?= sanitize($editItem['brand'] ?? '') ?>" maxlength="100">
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
