<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) redirect(SITE_URL . '/pages/wardrobe.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email && $pass) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            setFlash('success', 'Laipni lūdzam atpakaļ, ' . $user['name'] . '!');
            redirect(SITE_URL . '/pages/wardrobe.php');
        } else {
            $error = 'Nepareizs e-pasts vai parole.';
        }
    } else {
        $error = 'Lūdzu aizpildiet visus laukus.';
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieteikšanās — Garderobe</title>
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
<body style="background: linear-gradient(135deg,#0f172a 0%,#1e1b4b 100%); min-height:100vh; display:flex; align-items:center;">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="text-center mb-4">
        <a href="<?= SITE_URL ?>" class="text-white text-decoration-none">
          <i class="bi bi-bag-heart-fill fs-2 me-2"></i>
          <span class="fs-3 fw-bold">Garderobe</span>
        </a>
      </div>
      <div class="card p-4">
        <h4 class="fw-bold mb-4 text-center">Pieteikties</h4>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">E-pasta adrese</label>
            <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="janis@example.lv" required autofocus>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Parole</label>
            <input type="password" name="password" class="form-control" placeholder="Ievadiet paroli" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Pieteikties
          </button>
        </form>
        <p class="text-center mt-3 mb-0 text-muted">
          Nav konta? <a href="<?= SITE_URL ?>/pages/register.php" class="text-primary fw-semibold">Reģistrēties</a>
        </p>
        <hr class="my-3">
        <p class="text-center text-muted small mb-0">Demo: <code>admin@garderobe.lv</code> / <code>Admin123!</code></p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
