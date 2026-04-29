<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) redirect(SITE_URL . '/pages/wardrobe.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF(SITE_URL . '/pages/register.php');
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($name) < 2)  $errors[] = 'Vārds ir pārāk īss (min. 2 rakstzīmes).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-pasta adrese nav derīga.';
    if (strlen($pass) < 8)  $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm.';
    if (!preg_match('/[A-Z]/', $pass)) $errors[] = 'Parolei jāietver vismaz viens lielais burts.';
    if (!preg_match('/[0-9]/', $pass)) $errors[] = 'Parolei jāietver vismaz viens cipars.';
    if ($pass !== $pass2) $errors[] = 'Paroles nesakrīt.';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Šāds e-pasts jau ir reģistrēts.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
            $_SESSION['user_id'] = $db->lastInsertId();
            setFlash('success', 'Laipni lūdzam, ' . $name . '! Jūsu konts ir izveidots.');
            redirect(SITE_URL . '/pages/wardrobe.php');
        }
    }
}
$pageTitle = 'Reģistrācija';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reģistrācija — Garderobe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
    <script>
      (function() {
        var t = localStorage.getItem('garderobe-theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', t);
      })();
    </script>
</head>
<body style="background: linear-gradient(135deg,#1a1a2e 0%,#16213e 100%); min-height:100vh; display:flex; align-items:center;">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="text-center mb-4">
        <a href="<?= SITE_URL ?>" class="text-white text-decoration-none">
          <i class="bi bi-bag-heart-fill fs-2 me-2"></i>
          <span class="fs-3 fw-bold">Garderobe</span>
        </a>
      </div>
      <div class="card p-4">
        <h4 class="fw-bold mb-4 text-center">Izveidot kontu</h4>
        <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Vārds Uzvārds</label>
            <input type="text" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" placeholder="Jānis Bērziņš" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">E-pasta adrese</label>
            <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="janis@example.lv" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Parole</label>
            <input type="password" name="password" class="form-control" placeholder="Min. 8 rakstzīmes, 1 lielais burts, 1 cipars" required>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Apstiprināt paroli</label>
            <input type="password" name="password2" class="form-control" placeholder="Atkārtojiet paroli" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-person-plus me-2"></i>Reģistrēties
          </button>
        </form>
        <p class="text-center mt-3 mb-0 text-muted">
          Jau ir konts? <a href="<?= SITE_URL ?>/pages/login.php" class="text-primary fw-semibold">Pieteikties</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
