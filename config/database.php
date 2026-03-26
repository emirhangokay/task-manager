<?php
/**
 * config/database.php
 * ============================================================
 * Veritabanı bağlantı yapılandırması
 * PDO kullanarak MySQL'e bağlanır.
 * Tüm sorgular prepared statement ile güvenli hale getirilir.
 * ============================================================
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'task_manager');
define('DB_USER', 'root');       // Kendi kullanıcı adınla değiştir
define('DB_PASS', '');           // Kendi şifrenle değiştir
define('DB_CHARSET', 'utf8mb4');

// ── BASE_URL: projenin alt klasör desteği ──────────────────
// Örn: htdocs/task-manager/ → BASE_URL = '/task-manager'
//       htdocs/              → BASE_URL = ''
if (!defined('BASE_URL')) {
    // Projenin kök dizinini DOCUMENT_ROOT'a göre hesapla
    $docRoot  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $projRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
    $base     = str_replace($docRoot, '', $projRoot);
    define('BASE_URL', $base === $projRoot ? '' : rtrim($base, '/'));
}

/**
 * Veritabanı bağlantısını döndürür (Singleton PDO instance).
 *
 * @return PDO
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // PHP ve MySQL saat dilimini eşitle (Türkiye: UTC+3)
            $pdo->exec("SET time_zone = '+03:00'");
            date_default_timezone_set('Europe/Istanbul');
        } catch (PDOException $e) {
            // Üretim ortamında hata detayını gösterme
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı.']));
        }
    }

    return $pdo;
}
