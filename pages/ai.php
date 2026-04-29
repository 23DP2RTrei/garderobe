<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isPremium()) {
    setFlash('error', 'Šī funkcija ir pieejama tikai Premium lietotājiem.');
    redirect(SITE_URL . '/pages/wardrobe.php');
}
$user = getCurrentUser();
$db   = getDB();
$uid  = $user['id'];

// ── Laika apstākļi (Open-Meteo, Rīga) ──────────────────────────────────────
$temp = $rainProb = $weatherCode = null;
$weatherDesc = ''; $weatherIcon = 'bi-cloud';

$ctx   = stream_context_create(['http' => ['timeout' => 4]]);
$wJson = @file_get_contents(
    'https://api.open-meteo.com/v1/forecast?latitude=56.9496&longitude=24.1052' .
    '&current=temperature_2m,precipitation,weathercode' .
    '&daily=precipitation_probability_max&forecast_days=1&timezone=Europe%2FRiga',
    false, $ctx
);
if ($wJson) {
    $w = json_decode($wJson, true);
    $temp        = $w['current']['temperature_2m'] ?? null;
    $weatherCode = $w['current']['weathercode']    ?? null;
    $rainProb    = $w['daily']['precipitation_probability_max'][0] ?? null;
    [$weatherDesc, $weatherIcon] = match(true) {
        $weatherCode === 0  => ['Skaidrs',           'bi-sun-fill'],
        $weatherCode <= 3   => ['Mākoņains',          'bi-cloud-sun-fill'],
        $weatherCode <= 48  => ['Migla',              'bi-cloud-fog2-fill'],
        $weatherCode <= 55  => ['Sīka lietusgāze',    'bi-cloud-drizzle-fill'],
        $weatherCode <= 65  => ['Lietus',             'bi-cloud-rain-fill'],
        $weatherCode <= 75  => ['Sniega nokrišņi',    'bi-snow'],
        $weatherCode <= 82  => ['Lietus gāzes',       'bi-cloud-rain-heavy-fill'],
        $weatherCode <= 99  => ['Pērkona negaiss',    'bi-cloud-lightning-rain-fill'],
        default             => ['Nenoteikts',         'bi-cloud-fill'],
    };
}

// ── Ģenerēt ieteikumu ───────────────────────────────────────────────────────
if (isset($_POST['generate'])) {
    verifyCSRF(SITE_URL . '/pages/ai.php');

    $month    = (int)date('n');
    $season   = match(true) { $month<=2||$month==12=>'winter', $month<=5=>'spring', $month<=8=>'summer', default=>'autumn' };
    $seasonLV = ['spring'=>'pavasarī','summer'=>'vasarā','autumn'=>'rudenī','winter'=>'ziemā'][$season];

    // Izvēlēties vienu apģērbu no katras kategorijas grupas
    $slotCats = [
        ['Cepure'],
        ['Jaka', 'Mētelis'],
        ['Krekls', 'T-krekls', 'Džemperis'],
        ['Bikses', 'Šorti', 'Kleita', 'Svārki'],
        ['Apavi'],
        ['Aksesuāri'],
    ];

    $selectedItems = [];
    foreach ($slotCats as $cats) {
        $ph   = implode(',', array_fill(0, count($cats), '?'));
        $args = array_merge([$uid], $cats, [$season, 'all']);
        $s    = $db->prepare("SELECT * FROM clothing WHERE user_id=? AND category IN ($ph) AND season IN (?,?) ORDER BY RAND() LIMIT 1");
        $s->execute($args);
        $item = $s->fetch();
        if ($item) $selectedItems[] = $item;
    }

    if (count($selectedItems) >= 2) {
        $names       = array_column($selectedItems, 'name');
        $clothingIds = json_encode(array_column($selectedItems, 'id'));

        $text = implode(' + ', $names) . ".";
        if ($temp !== null) {
            $text .= " Ārā ir {$temp}°C ($weatherDesc).";
            $text .= match(true) {
                $temp < 0   => " Ļoti salts — ģērbies pēc iespējas siltāk!",
                $temp < 8   => " Auksts laiks — mētelis vai biezs džemperis ir obligāts.",
                $temp < 15  => " Vēss — ieteicama jaka vai silts džemperis.",
                $temp < 22  => " Mērena temperatūra — viegls krekls vai plāns džemperis.",
                $temp < 28  => " Silts laiks — ērti un viegli!",
                default     => " Karsts — izvēlies visieglāko apģērbu!",
            };
        }
        if ($rainProb !== null && $rainProb >= 40) {
            $text .= " Sagaidāms lietus ({$rainProb}%) — neaizmirsti lietussargu!";
        } elseif ($rainProb !== null && $rainProb >= 20) {
            $text .= " Neliela lietus varbūtība ({$rainProb}%).";
        }

        // Dzēst vecās, saglabāt tikai jauno
        $db->prepare("DELETE FROM ai_suggestions WHERE user_id=?")->execute([$uid]);
        $db->prepare("INSERT INTO ai_suggestions (user_id, suggestion_text, season, clothing_ids) VALUES (?,?,?,?)")
           ->execute([$uid, $text, $season, $clothingIds]);
    } else {
        setFlash('error', 'Nepietiek apģērbu šai sezonai. Pievienojiet vairāk garderobes!');
    }
    redirect(SITE_URL . '/pages/ai.php');
}

// ── Ielādēt pēdējo ieteikumu ────────────────────────────────────────────────
$latest = $db->prepare("SELECT * FROM ai_suggestions WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$latest->execute([$uid]);
$latest = $latest->fetch();

$suggestedClothes = [];
if ($latest && !empty($latest['clothing_ids'])) {
    $ids = json_decode($latest['clothing_ids'], true);
    if ($ids) {
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $s    = $db->prepare("SELECT * FROM clothing WHERE id IN ($ph) AND user_id=?");
        $s->execute(array_merge($ids, [$uid]));
        $fetched = array_column($s->fetchAll(), null, 'id');
        foreach ($ids as $id) { if (isset($fetched[$id])) $suggestedClothes[] = $fetched[$id]; }
    }
}

$month = (int)date('n');
$currentSeason = match(true) {
    $month<=2||$month==12 => 'Ziema', $month<=5 => 'Pavasaris',
    $month<=8             => 'Vasara', default   => 'Rudens',
};
$seasonIcon = ['Ziema'=>'bi-snow','Pavasaris'=>'bi-flower1','Vasara'=>'bi-sun-fill','Rudens'=>'bi-leaf-fill'][$currentSeason];

$pageTitle = 'AI Ieteikumi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="ai-header mb-4">
  <div class="ai-header-inner">
    <div>
      <div class="ai-badge"><i class="bi bi-stars me-1"></i>Premium</div>
      <h1 class="ai-title">AI Tērpu ieteikumi</h1>
      <p class="ai-sub"><i class="bi <?= $seasonIcon ?> me-1"></i><?= $currentSeason ?></p>
    </div>
    <?php if ($temp !== null): ?>
    <div class="ai-weather-pill">
      <i class="bi <?= $weatherIcon ?> me-2" style="font-size:1.3rem;"></i>
      <div>
        <div class="fw-bold" style="font-size:1.2rem;"><?= number_format($temp,1) ?>°C</div>
        <div style="font-size:.75rem;opacity:.75;"><?= $weatherDesc ?><?php if ($rainProb!==null): ?> · <?= $rainProb ?>% lietus<?php endif; ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4">

  <!-- ── LEFT: Generate button ── -->
  <div class="col-lg-4 col-md-5">
    <div class="card p-4 h-100 d-flex flex-column">
      <h5 class="fw-bold mb-1">Ģenerēt tērpu</h5>
      <p class="text-muted mb-4" style="font-size:.875rem;">
        AI izvēlēsies apģērbus no jūsu garderobes, ņemot vērā sezonu
        <?= $temp!==null ? 'un <strong>reālo laiku Rīgā</strong>' : '' ?>.
      </p>
      <form method="POST" class="mt-auto">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <button type="submit" name="generate" class="btn btn-generate w-100">
          <i class="bi bi-magic me-2"></i>Ģenerēt ieteikumu
        </button>
      </form>
      <?php if ($latest): ?>
      <div class="mt-3 text-center">
        <small class="text-muted"><i class="bi bi-clock me-1"></i>Ģenerēts: <?= date('d.m.Y H:i', strtotime($latest['created_at'])) ?></small>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── RIGHT: Mini-fit preview ── -->
  <div class="col-lg-8 col-md-7">
    <?php if (empty($suggestedClothes)): ?>
    <div class="fit-empty">
      <i class="bi bi-magic"></i>
      <p>Nospied "Ģenerēt ieteikumu", lai redzētu savu tērpu!</p>
    </div>
    <?php else: ?>
    <div class="fit-preview">
      <div class="fit-stack">
        <?php foreach ($suggestedClothes as $item): ?>
        <div class="fit-item">
          <div class="fit-img-wrap">
            <?php if ($item['image_url']): ?>
            <img src="<?= UPLOAD_URL . sanitize($item['image_url']) ?>" class="fit-img" alt="<?= sanitize($item['name']) ?>">
            <?php else: ?>
            <div class="fit-no-img"><i class="bi bi-image"></i></div>
            <?php endif; ?>
          </div>
          <div class="fit-item-info">
            <div class="fit-item-name"><?= sanitize($item['name']) ?></div>
            <div class="fit-item-cat"><?= sanitize($item['category']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($latest): ?>
      <div class="fit-caption">
        <i class="bi bi-stars me-1 text-warning"></i>
        <?= sanitize($latest['suggestion_text']) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<style>
/* ── AI header ── */
.ai-header { background: linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0e2050 100%); border-radius: 20px; padding: 1.75rem 2rem; color: #fff; }
.ai-header-inner { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
.ai-badge { display: inline-block; background: rgba(251,191,36,.2); border: 1px solid rgba(251,191,36,.4); color: #fbbf24; border-radius: 20px; padding: 3px 12px; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; margin-bottom: .5rem; }
.ai-title { font-size: 1.75rem; font-weight: 800; margin: 0 0 .2rem; }
.ai-sub   { font-size: .875rem; opacity: .7; margin: 0; }
.ai-weather-pill { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 16px; padding: .85rem 1.25rem; display: flex; align-items: center; gap: .75rem; color: #fff; min-width: 160px; }

/* ── Generate button ── */
.btn-generate { background: linear-gradient(135deg,#f6d365,#fda085); border: none; color: #1a1a1a; font-weight: 700; font-size: 1rem; padding: .85rem 1.5rem; border-radius: 14px; transition: all .2s; box-shadow: 0 4px 16px rgba(253,160,133,.4); }
.btn-generate:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(253,160,133,.55); color: #1a1a1a; }

/* ── Empty state ── */
.fit-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 260px; color: var(--text-muted); text-align: center; gap: .75rem; border: 2px dashed var(--border); border-radius: 20px; padding: 2rem; }
.fit-empty i { font-size: 3rem; opacity: .25; }
.fit-empty p { margin: 0; font-size: .9rem; }

/* ── Fit preview ── */
.fit-preview { background: var(--surface); border-radius: 20px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-md); }

.fit-stack { display: flex; flex-direction: column; }

.fit-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: .6rem 1rem;
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
.fit-item:last-child { border-bottom: none; }
.fit-item:hover { background: var(--surface-2); }

.fit-img-wrap {
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--border);
}
[data-bs-theme="dark"] .fit-img-wrap { background: #1e2a3a; border-color: #334155; }

.fit-img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }
[data-bs-theme="dark"] .fit-img { mix-blend-mode: normal; }

.fit-no-img { font-size: 2rem; color: var(--text-muted); opacity: .4; }

.fit-item-info { flex: 1; min-width: 0; }
.fit-item-name { font-weight: 700; font-size: .9rem; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fit-item-cat  { font-size: .75rem; color: var(--text-muted); margin-top: .1rem; }

.fit-caption {
  padding: .85rem 1.1rem;
  font-size: .82rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border);
  background: var(--surface-2);
  line-height: 1.5;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
