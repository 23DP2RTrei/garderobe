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
$weather     = null;
$temp        = null;
$rainProb    = null;
$weatherCode = null;
$weatherDesc = '';
$weatherIcon = '🌤️';

$ctx = stream_context_create(['http' => ['timeout' => 4]]);
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
        $weatherCode === 0             => ['Skaidrs',               '☀️'],
        $weatherCode <= 3              => ['Mainīgi mākoņains',     '⛅'],
        $weatherCode <= 48             => ['Migla',                 '🌫️'],
        $weatherCode <= 55             => ['Sīka lietusgāze',       '🌦️'],
        $weatherCode <= 65             => ['Lietus',                '🌧️'],
        $weatherCode <= 75             => ['Sniega nokrišņi',       '❄️'],
        $weatherCode <= 82             => ['Lietus gāzes',          '🌧️'],
        $weatherCode <= 99             => ['Pērkona negaiss',       '⛈️'],
        default                        => ['Nenoteikts',            '🌤️'],
    };
}

// ── Ģenerēt ieteikumu ───────────────────────────────────────────────────────
if (isset($_POST['generate'])) {
    $month    = (int)date('n');
    $season   = match(true) { $month<=2||$month==12=>'winter', $month<=5=>'spring', $month<=8=>'summer', default=>'autumn' };
    $seasonLV = ['spring'=>'pavasarī','summer'=>'vasarā','autumn'=>'rudenī','winter'=>'ziemā'][$season];

    $stmt = $db->prepare("SELECT * FROM clothing WHERE user_id=? AND season IN (?,?) ORDER BY RAND() LIMIT 8");
    $stmt->execute([$uid, $season, 'all']);
    $items = $stmt->fetchAll();

    if (count($items) >= 2) {
        $names = array_column($items, 'name');
        $top3  = implode(', ', array_slice($names, 0, 3));

        $text = "Ieteikums $seasonLV: Šodien lieliski der — $top3.";

        // Laika apstākļu pielāgojums
        if ($temp !== null) {
            $text .= " Šobrīd ārā ir {$temp}°C ($weatherDesc).";
            $advice = match(true) {
                $temp < 0   => " Salts laiks — noteikti ģērbies ļoti silti: biezs mētelis, cepure un šalle ir obligāti!",
                $temp < 8   => " Auksts laiks — ieteicams mētelis vai biezs džemperis un jaka.",
                $temp < 15  => " Vēss laiks — jaka vai džemperis noderētu.",
                $temp < 22  => " Mērena temperatūra — viegls krekls vai plāns džemperis ir ideāls.",
                $temp < 28  => " Silts laiks — viegla apģērba dienā, var ģērbties brīvi!",
                default     => " Karsts laiks — izvēlies vieglāko apģērbu, ko vien vari!",
            };
            $text .= $advice;
        }
        if ($rainProb !== null && $rainProb >= 40) {
            $text .= " ☔ Šodien gaidāms lietus ({$rainProb}% varbūtība) — neaizmirsti lietussargu!";
        } elseif ($rainProb !== null && $rainProb >= 20) {
            $text .= " Neliela lietus varbūtība ({$rainProb}%) — lietussargs var noderēt.";
        }

        $db->prepare("INSERT INTO ai_suggestions (user_id, suggestion_text, season) VALUES (?,?,?)")
           ->execute([$uid, $text, $season]);
        setFlash('success', 'Jauns AI ieteikums ģenerēts!');
    } else {
        setFlash('error', 'Nepietiek apģērbu šai sezonai. Pievienojiet vairāk!');
    }
    redirect(SITE_URL . '/pages/ai.php');
}

// ── Ielādēt iepriekšējos ieteikumus ────────────────────────────────────────
$suggestions = $db->prepare("SELECT * FROM ai_suggestions WHERE user_id=? ORDER BY created_at DESC");
$suggestions->execute([$uid]);
$suggestions = $suggestions->fetchAll();

$month = (int)date('n');
$currentSeason = match(true) {
    $month<=2||$month==12 => 'Ziema ❄️',
    $month<=5             => 'Pavasaris 🌸',
    $month<=8             => 'Vasara ☀️',
    default               => 'Rudens 🍂'
};

$pageTitle = 'AI Ieteikumi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="background:linear-gradient(135deg,#f6d365 0%,#fda085 100%);color:#333;">
  <h1><i class="bi bi-stars me-2"></i>AI Tērpu ieteikumi</h1>
  <p class="mb-0">Sezona: <strong><?= $currentSeason ?></strong></p>
</div>

<div class="row g-4 mb-4">

  <!-- Laika apstākļi -->
  <?php if ($temp !== null): ?>
  <div class="col-12">
    <div class="weather-bar">
      <div class="weather-main">
        <span class="weather-icon-big"><?= $weatherIcon ?></span>
        <div>
          <div class="weather-temp"><?= number_format($temp, 1) ?>°C</div>
          <div class="weather-desc"><?= $weatherDesc ?>, Rīga</div>
        </div>
      </div>
      <div class="weather-details">
        <?php
        $tempTip = match(true) {
            $temp < 0   => ['Ļoti auksts', 'danger',  'bi-thermometer-low'],
            $temp < 8   => ['Auksts',      'primary', 'bi-thermometer-low'],
            $temp < 15  => ['Vēss',        'info',    'bi-thermometer-half'],
            $temp < 22  => ['Mērens',      'success', 'bi-thermometer-half'],
            $temp < 28  => ['Silts',       'warning', 'bi-thermometer-high'],
            default     => ['Karsts',      'danger',  'bi-thermometer-high'],
        };
        ?>
        <span class="weather-tag bg-<?= $tempTip[1] ?>-subtle text-<?= $tempTip[1] ?>">
          <i class="bi <?= $tempTip[2] ?> me-1"></i><?= $tempTip[0] ?>
        </span>
        <?php if ($rainProb !== null): ?>
        <span class="weather-tag <?= $rainProb>=40 ? 'bg-primary-subtle text-primary' : 'bg-light text-muted' ?>">
          🌧️ Lietus: <?= $rainProb ?>%
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Ģenerēt ieteikumu -->
  <div class="col-md-8">
    <div class="card p-4">
      <h5 class="fw-bold mb-2">Ģenerēt jaunu ieteikumu</h5>
      <p class="text-muted mb-3" style="font-size:.9rem;">
        AI analizēs jūsu garderobi, pašreizējo sezonu<?= $temp!==null ? ' un <strong>reālos laika apstākļus Rīgā</strong>' : '' ?>, un ieteiks piemērotāko tērpu.
      </p>
      <form method="POST">
        <button type="submit" name="generate" class="btn btn-warning fw-semibold btn-lg">
          <i class="bi bi-magic me-2"></i>Ģenerēt ieteikumu
        </button>
      </form>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4 text-center h-100" style="background:linear-gradient(135deg,#667eea22,#764ba222);">
      <div style="font-size:2.5rem;"><?= $weatherIcon ?></div>
      <h6 class="fw-bold mt-2">AI + Laika apstākļi</h6>
      <small class="text-muted">Analizē sezonu, laiku un garderobi</small>
    </div>
  </div>
</div>

<!-- Iepriekšējie ieteikumi -->
<?php if (empty($suggestions)): ?>
<div class="text-center py-5">
  <div style="font-size:4rem;">✨</div>
  <h4 class="text-muted mt-3">Vēl nav ieteikumu</h4>
  <p class="text-muted">Spied "Ģenerēt ieteikumu" augstāk!</p>
</div>
<?php else: ?>
<h5 class="fw-bold mb-3">Iepriekšējie ieteikumi</h5>
<div class="row g-3">
  <?php foreach ($suggestions as $s): ?>
  <div class="col-12">
    <div class="card p-3 d-flex flex-row gap-3 align-items-start">
      <div style="font-size:2rem;min-width:48px;text-align:center;">
        <?= match($s['season']) { 'spring'=>'🌸','summer'=>'☀️','autumn'=>'🍂','winter'=>'❄️',default=>'✨' } ?>
      </div>
      <div class="flex-fill">
        <p class="mb-1"><?= sanitize($s['suggestion_text']) ?></p>
        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d.m.Y H:i', strtotime($s['created_at'])) ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.weather-bar {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 16px; padding: 1.25rem 1.5rem; color: white;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 1rem;
}
.weather-main { display: flex; align-items: center; gap: 1rem; }
.weather-icon-big { font-size: 2.5rem; line-height: 1; }
.weather-temp { font-size: 2rem; font-weight: 800; line-height: 1; }
.weather-desc { font-size: .85rem; opacity: .75; margin-top: .1rem; }
.weather-details { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.weather-tag { border-radius: 20px; padding: 4px 12px; font-size: .8rem; font-weight: 600; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
