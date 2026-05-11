<?php
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'garderobe');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Garderobe');
define('SITE_URL',  getenv('RAILWAY_PUBLIC_DOMAIN')
    ? 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN')
    : 'http://localhost/garderobe');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('user','premium','admin') DEFAULT 'user',
                style_preferences VARCHAR(100) NULL,
                sizes VARCHAR(100) NULL,
                last_login TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS clothing (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(50) NOT NULL,
                color VARCHAR(50) NOT NULL,
                season ENUM('spring','summer','autumn','winter','all') DEFAULT 'all',
                size VARCHAR(20) NULL,
                brand VARCHAR(100) NULL,
                image_url VARCHAR(500) NULL,
                is_favorite TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS outfits (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                times_worn INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS outfit_clothing (
                outfit_id BIGINT UNSIGNED NOT NULL,
                clothing_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (outfit_id, clothing_id),
                FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE,
                FOREIGN KEY (clothing_id) REFERENCES clothing(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS ai_suggestions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                suggestion_text TEXT NOT NULL,
                season VARCHAR(20) NULL,
                clothing_ids TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // Admin lietotājs (ja neeksistē)
            $pdo->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES
                ('Administrators', 'admin@garderobe.lv', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
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
