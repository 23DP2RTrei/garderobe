<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$user = getCurrentUser();
$db   = getDB();

// Mainīt lomu
if (isset($_POST['change_role'])) {
    $uid  = (int)$_POST['uid'];
    $role = $_POST['role'] ?? 'user';
    if ($uid !== (int)$user['id'] && in_array($role, ['user','premium','admin'])) {
        $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $uid]);
        setFlash('success', 'Loma mainīta.');
    } else {
        setFlash('error', 'Nevar mainīt savu lomu vai nepareiza vērtība.');
    }
    redirect(SITE_URL . '/pages/admin.php');
}

// Bloķēt (dzēst) lietotāju
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    if ($uid !== (int)$user['id']) {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        setFlash('success', 'Lietotājs dzēsts.');
    } else {
        setFlash('error', 'Nevar dzēst sevi.');
    }
    redirect(SITE_URL . '/pages/admin.php');
}

// Statistika
$userCount    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$clothingCount= $db->query("SELECT COUNT(*) FROM clothing")->fetchColumn();
$outfitCount  = $db->query("SELECT COUNT(*) FROM outfits")->fetchColumn();

// Visi lietotāji
$users = $db->query("SELECT u.*, 
    (SELECT COUNT(*) FROM clothing WHERE user_id=u.id) as clothing_count,
    (SELECT COUNT(*) FROM outfits WHERE user_id=u.id) as outfit_count
    FROM users u ORDER BY u.created_at DESC")->fetchAll();

$pageTitle = 'Administrācija';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="background:linear-gradient(135deg,#1a1a2e,#e63946);">
  <h1><i class="bi bi-shield-lock me-2"></i>Administrācijas panelis</h1>
</div>

<!-- SISTĒMAS STATISTIKA -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="stat-card" style="background:linear-gradient(135deg,#e63946,#c1121f);">
      <div class="stat-num"><?= $userCount ?></div>
      <div class="fw-semibold"><i class="bi bi-people me-1"></i>Lietotāji</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card" style="background:linear-gradient(135deg,#6c63ff,#5a52d5);">
      <div class="stat-num"><?= $clothingCount ?></div>
      <div class="fw-semibold"><i class="bi bi-bag me-1"></i>Apģērbi sistēmā</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card" style="background:linear-gradient(135deg,#43d8c9,#2cbfb1);">
      <div class="stat-num"><?= $outfitCount ?></div>
      <div class="fw-semibold"><i class="bi bi-layers me-1"></i>Kombinācijas</div>
    </div>
  </div>
</div>

<!-- LIETOTĀJU PĀRVALDĪBA -->
<div class="card p-4">
  <h5 class="fw-bold mb-3"><i class="bi bi-people me-2 text-danger"></i>Lietotāju pārvaldība</h5>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>#</th><th>Vārds</th><th>E-pasts</th><th>Loma</th>
          <th>Apģērbi</th><th>Komb.</th><th>Reģistrēts</th><th>Darbības</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u):
        $roleClass = ['user'=>'secondary','premium'=>'warning','admin'=>'danger'];
        $roleLV    = ['user'=>'Lietotājs','premium'=>'Premium','admin'=>'Admin'];
      ?>
      <tr <?= $u['id']==$user['id'] ? 'class="table-active"' : '' ?>>
        <td><?= $u['id'] ?></td>
        <td class="fw-semibold"><?= sanitize($u['name']) ?>
          <?php if ($u['id']==$user['id']): ?><small class="text-muted">(jūs)</small><?php endif; ?>
        </td>
        <td><?= sanitize($u['email']) ?></td>
        <td><span class="badge bg-<?= $roleClass[$u['role']] ?>"><?= $roleLV[$u['role']] ?></span></td>
        <td><?= $u['clothing_count'] ?></td>
        <td><?= $u['outfit_count'] ?></td>
        <td><small><?= date('d.m.Y', strtotime($u['created_at'])) ?></small></td>
        <td>
          <?php if ($u['id'] != $user['id']): ?>
          <form method="POST" class="d-inline-flex gap-1 align-items-center">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <select name="role" class="form-select form-select-sm" style="width:120px;">
              <option value="user"    <?= $u['role']==='user'    ?'selected':'' ?>>Lietotājs</option>
              <option value="premium" <?= $u['role']==='premium' ?'selected':'' ?>>Premium</option>
              <option value="admin"   <?= $u['role']==='admin'   ?'selected':'' ?>>Admin</option>
            </select>
            <button type="submit" name="change_role" class="btn btn-sm btn-primary">
              <i class="bi bi-check"></i>
            </button>
          </form>
          <a href="?delete_user=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
             onclick="return confirmDelete('Dzēst lietotāju <?= sanitize($u['name']) ?>? Tiks dzēsti arī visi viņa dati.')">
            <i class="bi bi-trash"></i>
          </a>
          <?php else: ?>
          <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
