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
        } catch (PDOException $e) {
            // Üretim ortamında hata detayını gösterme
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı.']));
        }
    }

    return $pdo;
}
