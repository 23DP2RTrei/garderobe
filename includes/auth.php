<?php
require_once __DIR__ . '/config.php';

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/pages/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    $user = getCurrentUser();
    if ($user['role'] !== 'admin') {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

function isPremium() {
    $user = getCurrentUser();
    return $user && in_array($user['role'], ['premium', 'admin']);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($redirectTo = null) {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        setFlash('error', 'Drošības kļūda. Lūdzu mēģiniet vēlreiz.');
        header('Location: ' . ($redirectTo ?? (SITE_URL . '/index.php')));
        exit;
    }
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function uploadImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $filename = uniqid('img_', true) . '.png';
    $destPath = UPLOAD_DIR . $filename;

    if (processClothingImage($file['tmp_name'], $file['type'], $destPath)) {
        return $filename;
    }
    // Fallback: save original if GD fails
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fallback = uniqid('img_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $fallback)) {
        return $fallback;
    }
    return null;
}

function processClothingImage($srcPath, $mime, $destPath) {
    if (!function_exists('imagecreatetruecolor')) return false;
    $src = match($mime) {
        'image/jpeg' => imagecreatefromjpeg($srcPath),
        'image/png'  => imagecreatefrompng($srcPath),
        'image/webp' => imagecreatefromwebp($srcPath),
        'image/gif'  => imagecreatefromgif($srcPath),
        default      => false,
    };
    if (!$src) return false;

    $ow = imagesx($src);
    $oh = imagesy($src);
    $scale = min(900 / $ow, 900 / $oh, 1);
    $nw = max(1, (int)($ow * $scale));
    $nh = max(1, (int)($oh * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefill($out, 0, 0, $transparent);
    imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
    imagedestroy($src);

    // Sample edge pixels to detect background color (any color)
    $samples = [];
    $step = max(1, (int)min($nw, $nh) / 16);
    for ($i = 0; $i < $nw; $i += $step) {
        $samples[] = imagecolorat($out, $i, 0);
        $samples[] = imagecolorat($out, $i, $nh - 1);
    }
    for ($i = 0; $i < $nh; $i += $step) {
        $samples[] = imagecolorat($out, 0, $i);
        $samples[] = imagecolorat($out, $nw - 1, $i);
    }
    $br = $bg = $bb = 0;
    foreach ($samples as $c) {
        $br += ($c >> 16) & 0xFF;
        $bg += ($c >> 8)  & 0xFF;
        $bb +=  $c        & 0xFF;
    }
    $n  = count($samples);
    $br = (int)($br / $n);
    $bg = (int)($bg / $n);
    $bb = (int)($bb / $n);

    // Flood-fill background removal from all 4 edges
    $tolerance = 50;
    $visited   = [];
    $queue     = [];
    for ($x = 0; $x < $nw; $x++) { $queue[] = [$x, 0]; $queue[] = [$x, $nh - 1]; }
    for ($y = 1; $y < $nh - 1; $y++) { $queue[] = [0, $y]; $queue[] = [$nw - 1, $y]; }

    while (!empty($queue)) {
        [$x, $y] = array_pop($queue);
        $key = $x . ',' . $y;
        if (isset($visited[$key])) continue;
        $visited[$key] = true;
        $c    = imagecolorat($out, $x, $y);
        $r    = ($c >> 16) & 0xFF;
        $g    = ($c >> 8)  & 0xFF;
        $b    =  $c        & 0xFF;
        $dist = abs($r - $br) + abs($g - $bg) + abs($b - $bb);
        if ($dist > $tolerance) continue;
        $alpha = min(127, (int)(127 * $dist / $tolerance) + 80);
        imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, $alpha));
        foreach ([[$x-1,$y],[$x+1,$y],[$x,$y-1],[$x,$y+1]] as [$nx,$ny]) {
            if ($nx >= 0 && $nx < $nw && $ny >= 0 && $ny < $nh && !isset($visited["$nx,$ny"])) {
                $queue[] = [$nx, $ny];
            }
        }
    }

    imagepng($out, $destPath, 9);
    imagedestroy($out);
    return true;
}
