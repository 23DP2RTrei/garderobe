<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();
$db   = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $style = trim($_POST['style_preferences'] ?? '');
    $sizes = trim($_POST['sizes'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $curPass = $_POST['current_password'] ?? '';

    if (strlen($name) < 2) $errors[] = 'Vārds ir pārāk īss.';

    if ($newPass) {
        if (!password_verify($curPass, $user['password'])) $errors[] = 'Pašreizējā parole ir nepareiza.';
        if (strlen($newPass) < 8) $errors[] = 'Jaunai parolei jābūt vismaz 8 rakstzīmēm.';
        if (!preg_match('/[A-Z]/', $newPass)) $errors[] = 'Parolei jāietver lielais burts.';
        if (!preg_match('/[0-9]/', $newPass)) $errors[] = 'Parolei jāietver cipars.';
    }

    if (empty($errors)) {
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $db->prepare("UPDATE users SET name=?,style_preferences=?,sizes=?,password=? WHERE id=?")
               ->execute([$name,$style,$sizes,$hash,$user['id']]);
        } else {
            $db->prepare("UPDATE users SET name=?,style_preferences=?,sizes=? WHERE id=?")
               ->execute([$name,$style,$sizes,$user['id']]);
        }
        setFlash('success', 'Profils atjaunināts!');
        redirect(SITE_URL . '/pages/profile.php');
    }
    $user = array_merge($user, ['name'=>$name,'style_preferences'=>$style,'sizes'=>$sizes]);
}

$pageTitle = 'Profils';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-person-circle me-2"></i>Mans profils</h1>
</div>

<div class="row g-4">
  <div class="col-md-8">
    <div class="card p-4">
      <h5 class="fw-bold mb-4">Profila dati</h5>
      <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Vārds Uzvārds *</label>
            <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">E-pasts</label>
            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
            <small class="text-muted">E-pastu nevar mainīt.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Stila preferences</label>
            <select name="style_preferences" class="form-select">
              <option value="">-- Nav norādīts --</option>
              <?php foreach (['Ikdienas','Formāls','Sporta','Romantisks','Elegants','Minimālistisks'] as $s): ?>
              <option <?= ($user['style_preferences'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Pamatoizmērs</label>
            <select name="sizes" class="form-select">
              <option value="">-- Nav norādīts --</option>
              <?php foreach (['XS','S','M','L','XL','XXL'] as $sz): ?>
              <option <?= ($user['sizes'] ?? '') === $sz ? 'selected' : '' ?>><?= $sz ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12"><hr><h6 class="fw-bold text-muted">Mainīt paroli (neobligāts)</h6></div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Pašreizējā parole</label>
            <input type="password" name="current_password" class="form-control" placeholder="Ievadiet tikai, ja mainīsiet paroli">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Jaunā parole</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min. 8 zīmes, lielais burts, cipars">
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary fw-semibold">
            <i class="bi bi-check-lg me-1"></i>Saglabāt izmaiņas
          </button>
        </div>
      </form>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4 text-center">
      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:72px;height:72px;background:var(--brand-subtle);border-radius:50%;">
        <i class="bi bi-person-fill" style="font-size:2.2rem;color:var(--brand);"></i>
      </div>
      <h5 class="fw-bold mt-1 mb-1"><?= sanitize($user['name']) ?></h5>
      <p class="text-muted mb-2" style="font-size:.875rem;"><?= sanitize($user['email']) ?></p>
      <?php
      $roleLV    = ['user'=>'Reģistrēts', 'premium'=>'Premium', 'admin'=>'Administrators'];
      $roleClass = ['user'=>'secondary',  'premium'=>'warning',  'admin'=>'danger'];
      ?>
      <span class="badge bg-<?= $roleClass[$user['role']] ?>">
        <?php if ($user['role'] === 'premium'): ?><i class="bi bi-stars me-1"></i><?php endif; ?>
        <?php if ($user['role'] === 'admin'): ?><i class="bi bi-shield-lock me-1"></i><?php endif; ?>
        <?= $roleLV[$user['role']] ?>
      </span>
      <?php if ($user['role'] === 'user'): ?>
      <div class="premium-banner mt-3 p-3 text-start" style="font-size:.85rem;">
        <strong><i class="bi bi-stars me-1"></i>Premium</strong><br>
        <small>AI ieteikumi, PDF eksports un detalizēta statistika. Jautājiet administratoram!</small>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
