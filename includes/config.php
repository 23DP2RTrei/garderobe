<?php
// Datu bāzes savienojuma konfigurācija
// Mainiet šīs vērtības atbilstoši jūsu serverim

define('DB_HOST', 'localhost');
define('DB_NAME', 'garderobe');
define('DB_USER', 'root');       // Mainiet uz savu lietotāju
define('DB_PASS', '');           // Mainiet uz savu paroli
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Garderobe');
define('SITE_URL', 'http://localhost/garderobe');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Auto-migrācija: pievieno jaunās kolonnas, ja tās vēl neeksistē
            try { $pdo->exec("ALTER TABLE users         ADD COLUMN last_login    TIMESTAMP NULL DEFAULT NULL"); } catch (PDOException $e) {}
            try { $pdo->exec("ALTER TABLE clothing      ADD COLUMN is_favorite   TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
            try { $pdo->exec("ALTER TABLE ai_suggestions ADD COLUMN clothing_ids TEXT NULL DEFAULT NULL"); } catch (PDOException $e) {}
        } catch (PDOException $e) {
            die('<div style="padding:20px;background:#fee;border:1px solid #f00;margin:20px;font-family:sans-serif;">
                <h2>Datu bāzes kļūda</h2>
                <p>Nevar izveidot savienojumu. Pārbaudiet config.php iestatījumus.</p>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}
