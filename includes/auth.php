<?php
/**
 * includes/auth.php
 * ============================================================
 * Kullanıcı oturum yönetimi ve kimlik doğrulama fonksiyonları.
 * Session güvenliği, giriş/kayıt ve yetkilendirme işlemleri.
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mail.php';

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
        header('Location: ' . BASE_URL . '/login.php');
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

    // E-posta doğrulama kodu
    $code    = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $stmt = $db->prepare(
        'INSERT INTO users (username, email, password, email_verified, verification_code, verification_expires)
         VALUES (?, ?, ?, 0, ?, ?)'
    );
    $stmt->execute([$username, $email, $hash, $code, $expires]);
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

    // Doğrulama e-postası gönder (hata olsa da kaydı engelleme)
    sendVerificationEmail($email, $username, $code);

    return ['success' => true, 'message' => 'Hesap oluşturuldu. Lütfen e-postanızı doğrulayın.'];
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

/**
 * E-posta doğrulama kodu doğrular.
 */
function verifyEmailCode(int $userId, string $code): array
{
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT id FROM users
         WHERE id = ? AND verification_code = ? AND verification_expires > NOW() AND email_verified = 0
         LIMIT 1'
    );
    $stmt->execute([$userId, $code]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'message' => 'Kod hatalı veya süresi dolmuş.'];
    }

    $db->prepare(
        'UPDATE users SET email_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = ?'
    )->execute([$userId]);

    return ['success' => true, 'message' => 'E-posta adresiniz doğrulandı.'];
}

/**
 * Doğrulama kodunu yeniden gönderir.
 */
function resendVerificationCode(int $userId): array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT email, username, email_verified FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
    }
    if ($user['email_verified']) {
        return ['success' => false, 'message' => 'E-posta zaten doğrulanmış.'];
    }

    $code    = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $db->prepare(
        'UPDATE users SET verification_code = ?, verification_expires = ? WHERE id = ?'
    )->execute([$code, $expires, $userId]);

    sendVerificationEmail($user['email'], $user['username'], $code);

    return ['success' => true, 'message' => 'Doğrulama kodu tekrar gönderildi.'];
}

/**
 * Şifre sıfırlama bağlantısı gönderir.
 */
function sendPasswordReset(string $email): array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, username FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Güvenlik: kullanıcı bulunamasa da aynı mesajı ver
    if (!$user) {
        return ['success' => true, 'message' => 'Eğer bu e-posta kayıtlıysa sıfırlama bağlantısı gönderildi.'];
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // verification_code / expires alanlarını token deposu olarak kullan
    $db->prepare(
        'UPDATE users SET verification_code = ?, verification_expires = ? WHERE id = ?'
    )->execute([$token, $expires, $user['id']]);

    $resetUrl = BASE_URL . '/reset-password.php?token=' . $token . '&uid=' . $user['id'];
    sendPasswordResetEmail($email, $user['username'], $resetUrl);

    return ['success' => true, 'message' => 'Eğer bu e-posta kayıtlıysa sıfırlama bağlantısı gönderildi.'];
}

/**
 * Şifre sıfırlama token'ı doğrular ve yeni şifre ayarlar.
 */
function resetPasswordWithToken(int $userId, string $token, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.'];
    }

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT id FROM users WHERE id = ? AND verification_code = ? AND verification_expires > NOW() LIMIT 1'
    );
    $stmt->execute([$userId, $token]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'message' => 'Geçersiz veya süresi dolmuş bağlantı.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $db->prepare(
        'UPDATE users SET password = ?, verification_code = NULL, verification_expires = NULL WHERE id = ?'
    )->execute([$hash, $userId]);

    return ['success' => true, 'message' => 'Şifreniz başarıyla güncellendi.'];
}
