<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();
$db   = getDB();

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM outfits WHERE id = ? AND user_id = ?")->execute([(int)$_GET['delete'], $user['id']]);
    setFlash('success', 'Kombinācija dzēsta.');
    redirect(SITE_URL . '/pages/outfits.php');
}
if (isset($_GET['worn'])) {
    $db->prepare("UPDATE outfits SET times_worn = times_worn + 1 WHERE id = ? AND user_id = ?")->execute([(int)$_GET['worn'], $user['id']]);
    setFlash('success', 'Atzīmēts kā valkāts!');
    redirect(SITE_URL . '/pages/outfits.php');
}

$savedStmt = $db->prepare("SELECT o.*, (SELECT COUNT(*) FROM outfit_clothing oc WHERE oc.outfit_id = o.id) as item_count FROM outfits o WHERE o.user_id = ? ORDER BY o.created_at DESC");
$savedStmt->execute([$user['id']]);
$savedOutfits = $savedStmt->fetchAll();

$clothesStmt = $db->prepare("SELECT * FROM clothing WHERE user_id = ? ORDER BY name");
$clothesStmt->execute([$user['id']]);
$allClothes = $clothesStmt->fetchAll();

$slots = [
    'hat'    => ['label' => 'Cepure',    'icon' => 'bi-circle',         'cats' => ['Cepure']],
    'outer'  => ['label' => 'Virskārta', 'icon' => 'bi-wind',           'cats' => ['Jaka', 'Mētelis']],
    'top'    => ['label' => 'Augša',     'icon' => 'bi-square',         'cats' => ['Krekls', 'T-krekls', 'Džemperis']],
    'bottom' => ['label' => 'Apakša',    'icon' => 'bi-layout-split',   'cats' => ['Bikses', 'Šorti', 'Kleita', 'Svārki']],
    'shoes'  => ['label' => 'Apavi',     'icon' => 'bi-geo-alt',        'cats' => ['Apavi']],
];

$catOrder = ['Cepure'=>0,'Jaka'=>1,'Mētelis'=>1,'Krekls'=>2,'T-krekls'=>2,'Džemperis'=>2,
             'Kleita'=>3,'Svārki'=>3,'Bikses'=>3,'Šorti'=>3,'Apavi'=>4,'Aksesuāri'=>5,'Cits'=>6];

$slotItems = [];
foreach ($slots as $key => $slot) {
    $slotItems[$key] = array_values(array_filter($allClothes, fn($c) => in_array($c['category'], $slot['cats'])));
}

$pageTitle = 'Tērpu kombinācijas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
  <div>
    <h1><i class="bi bi-layers me-2"></i>Tērpu kombinācijas</h1>
    <p class="mb-0 opacity-75"><?= count($savedOutfits) ?> saglabātā kombinācija(-as)</p>
  </div>
</div>

<div class="row g-3">

  <!-- ── OUTFIT BUILDER ── -->
  <div class="col-lg-4 col-md-5">
    <div class="builder-panel">
      <div class="builder-title">
        <i class="bi bi-magic"></i> Outfit Builder
      </div>

      <div class="outfit-flow">
        <?php foreach ($slots as $key => $slot):
          $items = $slotItems[$key];
          $hasItems = !empty($items);
        ?>
        <div class="flow-slot <?= $hasItems ? 'has-items' : 'no-items' ?>" id="slot-<?= $key ?>">
          <div class="flow-arrows">
            <button class="flow-arrow" onclick="prevSlot('<?= $key ?>')" <?= count($items)<=1?'disabled':'' ?>>
              <i class="bi bi-chevron-left"></i>
            </button>

            <div class="flow-img-area" id="display-<?= $key ?>">
              <?php if (!$hasItems): ?>
              <div class="flow-no-item">
                <i class="bi <?= $slot['icon'] ?>"></i>
                <a href="wardrobe.php" style="font-size:.67rem;">Pievienot</a>
              </div>
              <?php else: $f = $items[0]; ?>
              <?php if ($f['image_url']): ?>
              <img src="<?= UPLOAD_URL.sanitize($f['image_url']) ?>" class="flow-photo" id="photo-<?= $key ?>" alt="">
              <?php else: ?>
              <div class="flow-no-item" id="photo-<?= $key ?>">
                <i class="bi <?= $slot['icon'] ?>" style="font-size:1.8rem;color:#cbd5e1;"></i>
              </div>
              <?php endif; ?>
              <?php endif; ?>
            </div>

            <button class="flow-arrow" onclick="nextSlot('<?= $key ?>')" <?= count($items)<=1?'disabled':'' ?>>
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>

          <div class="flow-meta">
            <span class="flow-label"><?= $slot['label'] ?></span>
            <?php if ($hasItems): ?>
            <div class="flow-right">
              <?php if (count($items)>1): ?>
              <span class="flow-count" id="count-<?= $key ?>">1/<?= count($items) ?></span>
              <?php endif; ?>
              <label class="flow-include">
                <input type="checkbox" id="inc-<?= $key ?>" checked onchange="toggleInc('<?= $key ?>')"> Iekļaut
              </label>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($allClothes) >= 2): ?>
      <form method="POST" action="outfit_save.php" id="builderForm" onsubmit="return buildSubmit()">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="builder-save">
          <input type="text" name="name" class="form-control form-control-sm" placeholder="Nosaukums..." required maxlength="100">
          <button type="submit" class="btn btn-primary btn-sm fw-semibold">
            <i class="bi bi-bookmark-plus"></i> Saglabāt
          </button>
        </div>
      </form>
      <?php else: ?>
      <div class="text-center text-muted small mt-3 p-2" style="background:#f8f9fb;border-radius:10px;">
        Pievienojiet vismaz 2 apģērbus <a href="wardrobe.php">garderobē</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── SAVED OUTFITS ── -->
  <div class="col-lg-8 col-md-7">
    <h6 class="fw-bold mb-3 text-muted text-uppercase" style="letter-spacing:.5px; font-size:.75rem;">
      <i class="bi bi-bookmark-fill me-1 text-primary"></i>Saglabātās kombinācijas
    </h6>

    <?php if (empty($savedOutfits)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-collection" style="font-size:3rem;opacity:.35;"></i>
      <p class="mt-3">Vēl nav nevienas kombinācijas.<br>Izmantojiet builder kreisajā pusē!</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($savedOutfits as $outfit):
        $itemsStmt = $db->prepare("SELECT c.* FROM clothing c JOIN outfit_clothing oc ON c.id=oc.clothing_id WHERE oc.outfit_id=?");
        $itemsStmt->execute([$outfit['id']]);
        $outfitItems = $itemsStmt->fetchAll();
        usort($outfitItems, fn($a,$b) => ($catOrder[$a['category']]??99) - ($catOrder[$b['category']]??99));
        $total   = count($outfitItems);
        $preview = array_slice($outfitItems, 0, 4);
        $extra   = max(0, $total - 4);
        $cnt     = min($total, 4);
      ?>
      <div class="col-6 col-xl-4">
        <div class="saved-card">
          <div class="outfit-mosaic" data-count="<?= $cnt ?>">
            <?php foreach ($preview as $idx => $item): ?>
            <div class="mosaic-cell">
              <?php if ($item['image_url']): ?>
              <img src="<?= UPLOAD_URL.sanitize($item['image_url']) ?>" class="mosaic-img" alt="" loading="lazy">
              <?php else: ?>
              <div class="mosaic-noimg"><i class="bi bi-image"></i></div>
              <?php endif; ?>
              <span class="mosaic-tag"><?= sanitize($item['category']) ?></span>
              <?php if ($idx === 3 && $extra > 0): ?>
              <div class="mosaic-extra">+<?= $extra ?></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="saved-footer">
            <div>
              <div class="fw-semibold" style="font-size:.85rem;"><?= sanitize($outfit['name']) ?></div>
              <small class="text-muted"><i class="bi bi-repeat me-1"></i><?= $outfit['times_worn'] ?>x valkāts</small>
            </div>
            <div class="d-flex gap-1">
              <a href="?worn=<?= $outfit['id'] ?>" class="btn btn-sm btn-outline-success" title="Atzīmēt kā valkātu"><i class="bi bi-check-lg"></i></a>
              <a href="?delete=<?= $outfit['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Dzēst?')" title="Dzēst"><i class="bi bi-trash"></i></a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<style>
/* ── Builder panel ── */
.builder-panel {
  background: #fff;
  border-radius: 20px;
  padding: 1.25rem;
  box-shadow: 0 4px 24px rgba(0,0,0,.09);
  position: sticky; top: 80px;
}
.builder-title {
  font-weight: 700; font-size: 1rem; color: #6c63ff;
  text-align: center; margin-bottom: 1rem;
  letter-spacing: .3px;
}

/* ── Outfit flow ── */
.outfit-flow {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: #f8f9fc;
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: .75rem;
}

.flow-slot {
  padding: .4rem .6rem;
  border-bottom: 1px solid #f0f0f0;
  transition: background .2s;
}
.flow-slot:last-child { border-bottom: none; }
.flow-slot.has-items { background: #fff; }
.flow-slot.no-items  { background: #f8f9fc; opacity: .5; }
.flow-slot.excluded  { opacity: .35; }

.flow-arrows {
  display: flex; align-items: center; gap: .4rem;
}
.flow-arrow {
  width: 32px; height: 32px; border-radius: 50%;
  border: 1.5px solid #ddd; background: #f8f9fb;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0; transition: all .15s;
  font-size: .85rem; color: #555;
}
.flow-arrow:hover:not(:disabled) { border-color: #6c63ff; color: #6c63ff; background: #f0eeff; }
.flow-arrow:disabled { opacity: .2; cursor: default; }

.flow-img-area {
  flex: 1; display: flex; justify-content: center; align-items: center;
  background: #fff; border-radius: 10px; overflow: hidden;
  min-height: 100px;
}
.flow-photo {
  width: 100%; max-height: 130px;
  object-fit: contain;
  mix-blend-mode: multiply;
  display: block;
}
.flow-emoji { font-size: 3.5rem; line-height: 1; }
.flow-empty { text-align: center; color: #ccc; font-size: 1.8rem; line-height: 1.6; }

.flow-meta {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: .25rem; padding: 0 .1rem;
}
.flow-label  { font-size: .72rem; font-weight: 600; color: #666; }
.flow-right  { display: flex; align-items: center; gap: .4rem; }
.flow-count  { font-size: .68rem; background: #e2e0f9; color: #5a52d5; border-radius: 20px; padding: 1px 7px; font-weight: 700; }
.flow-include { font-size: .68rem; color: #888; cursor: pointer; display: flex; align-items: center; gap: .2rem; user-select: none; }
.flow-include input { accent-color: #6c63ff; }

.builder-save {
  display: flex; gap: .4rem;
  padding-top: .75rem; border-top: 1px solid #eee;
}

/* ── Saved outfit cards ── */
.saved-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.saved-card:hover { transform: translateY(-3px); box-shadow: 0 6px 24px rgba(0,0,0,.12); }

.saved-stack {
  display: flex; flex-direction: column;
  background: #fff;
}
.saved-layer {
  position: relative;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
}
.saved-layer:last-child { border-bottom: none; }

.saved-photo {
  width: 100%;
  height: 100px;
  object-fit: contain;
  mix-blend-mode: multiply;
  display: block;
  background: #fff;
}
.saved-no-img {
  height: 100px; display: flex; align-items: center; justify-content: center;
  font-size: 2.5rem; background: #f8f9fb;
}
.saved-cat-tag {
  position: absolute; top: 4px; left: 4px;
  background: rgba(0,0,0,.45); color: #fff;
  font-size: .6rem; border-radius: 4px; padding: 1px 5px;
}
.saved-footer {
  padding: .6rem .75rem;
  display: flex; justify-content: space-between; align-items: center;
  border-top: 1px solid #f0f0f0;
}
/* ── Dark mode overrides ── */
[data-bs-theme="dark"] .builder-panel {
  background: #1e293b;
  box-shadow: 0 4px 24px rgba(0,0,0,.35);
}
[data-bs-theme="dark"] .outfit-flow { background: #0f172a; }
[data-bs-theme="dark"] .flow-slot.has-items { background: #1e293b; }
[data-bs-theme="dark"] .flow-slot.no-items  { background: #0f172a; }
[data-bs-theme="dark"] .flow-slot           { border-color: #334155; }
[data-bs-theme="dark"] .flow-arrow          { border-color: #475569; background: #1e293b; color: #94a3b8; }
[data-bs-theme="dark"] .flow-arrow:hover:not(:disabled) { border-color: #818cf8; color: #818cf8; background: #1e2a4a; }
[data-bs-theme="dark"] .flow-img-area       { background: #1e2a3a; }
[data-bs-theme="dark"] .flow-photo          { mix-blend-mode: normal; }
[data-bs-theme="dark"] .flow-label          { color: #94a3b8; }
[data-bs-theme="dark"] .flow-count          { background: #2d3748; color: #a5b4fc; }
[data-bs-theme="dark"] .flow-include        { color: #64748b; }
[data-bs-theme="dark"] .builder-save        { border-color: #334155; }
[data-bs-theme="dark"] .saved-card          { background: #1e293b; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
[data-bs-theme="dark"] .saved-footer        { border-color: #334155; }
[data-bs-theme="dark"] .saved-layer         { background: #1e293b; border-color: #334155; }
[data-bs-theme="dark"] .saved-photo         { mix-blend-mode: normal; background: #1e2a3a; }
[data-bs-theme="dark"] .saved-no-img        { background: #1e2a3a; }
</style>

<script>
const UPLOAD_URL = '<?= UPLOAD_URL ?>';
const slotData   = <?= json_encode($slotItems, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
const slotIcons  = { hat:'bi-circle', outer:'bi-wind', top:'bi-square', bottom:'bi-layout-split', shoes:'bi-geo-alt' };
const curIdx     = { hat:0, outer:0, top:0, bottom:0, shoes:0 };
const included   = { hat:true, outer:true, top:true, bottom:true, shoes:true };

function prevSlot(key) {
    if (!slotData[key]?.length) return;
    curIdx[key] = (curIdx[key] - 1 + slotData[key].length) % slotData[key].length;
    refreshSlot(key);
}
function nextSlot(key) {
    if (!slotData[key]?.length) return;
    curIdx[key] = (curIdx[key] + 1) % slotData[key].length;
    refreshSlot(key);
}
function refreshSlot(key) {
    const item    = slotData[key][curIdx[key]];
    const dispEl  = document.getElementById('display-' + key);
    const countEl = document.getElementById('count-'   + key);
    const imgHtml = item.image_url
        ? `<img src="${UPLOAD_URL}${item.image_url}" class="flow-photo" alt="">`
        : `<div class="flow-no-item"><i class="bi ${slotIcons[key]}" style="font-size:1.8rem;color:#cbd5e1;"></i></div>`;
    dispEl.innerHTML = imgHtml;
    if (countEl) countEl.textContent = `${curIdx[key]+1}/${slotData[key].length}`;
}
function toggleInc(key) {
    included[key] = document.getElementById('inc-' + key).checked;
    document.getElementById('slot-' + key).classList.toggle('excluded', !included[key]);
}
function buildSubmit() {
    const form = document.getElementById('builderForm');
    form.querySelectorAll('input[name="clothing[]"]').forEach(e => e.remove());
    let n = 0;
    Object.keys(slotData).forEach(key => {
        if (included[key] && slotData[key].length > 0) {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'clothing[]';
            inp.value = slotData[key][curIdx[key]].id;
            form.appendChild(inp); n++;
        }
    });
    if (n < 2) { alert('Iekļaujiet vismaz 2 apģērbus!'); return false; }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
