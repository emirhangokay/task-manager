<?php
/**
 * includes/auth.php
 * ============================================================
 * Kullanıcı oturum yönetimi ve kimlik doğrulama fonksiyonları.
 * Session güvenliği, giriş/kayıt ve yetkilendirme işlemleri.
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

// Oturum henüz başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // HTTPS kullanıyorsan true yap
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Kullanıcı giriş yapmış mı kontrol eder.
 * Yapmamışsa login sayfasına yönlendirir.
 *
 * @return void
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Mevcut oturum kullanıcısının ID'sini döndürür.
 *
 * @return int|null
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Mevcut oturum kullanıcısının adını döndürür.
 *
 * @return string|null
 */
function currentUsername(): ?string
{
    return $_SESSION['username'] ?? null;
}

/**
 * CSRF token oluşturur veya mevcut token'ı döndürür.
 *
 * @return string
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gönderilen CSRF token'ı doğrular.
 *
 * @param string $token
 * @return bool
 */
function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Kullanıcı kaydı oluşturur.
 * Varsayılan kategorileri de ekler.
 *
 * @param string $username
 * @param string $email
 * @param string $password
 * @return array{success: bool, message: string}
 */
function registerUser(string $username, string $email, string $password): array
{
    $db = getDB();

    // Kullanıcı adı veya e-posta daha önce alınmış mı?
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);
    $userId = (int)$db->lastInsertId();

    // Varsayılan kategorileri oluştur
    $defaultCategories = [
        ['Genel',    '#6B7280'],
        ['İş',       '#3B82F6'],
        ['Kişisel',  '#22C55E'],
        ['Acil',     '#EF4444'],
    ];
    $catStmt = $db->prepare('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)');
    foreach ($defaultCategories as [$name, $color]) {
        $catStmt->execute([$userId, $name, $color]);
    }

    return ['success' => true, 'message' => 'Hesap oluşturuldu.'];
}

/**
 * Kullanıcı girişi yapar ve session başlatır.
 *
 * @param string $login   Kullanıcı adı veya e-posta
 * @param string $password
 * @return array{success: bool, message: string}
 */
function loginUser(string $login, string $password): array
{
    $db = getDB();

    $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Kullanıcı adı/e-posta veya şifre hatalı.'];
    }

    // Session fixation koruması
    session_regenerate_id(true);

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];

    return ['success' => true, 'message' => 'Giriş başarılı.'];
}

/**
 * Oturumu kapatır.
 *
 * @return void
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
