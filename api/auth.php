<?php
/**
 * api/auth.php
 * ============================================================
 * Kimlik doğrulama API uç noktası.
 * POST action=login  → Kullanıcı girişi
 * POST action=register → Kullanıcı kaydı
 * Tüm yanıtlar JSON formatındadır.
 * ============================================================
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Global hata yakalayıcı — PHP hatalarını JSON'a çevir
set_exception_handler(function(Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    exit;
});

// Yalnızca POST kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Yalnızca POST destekleniyor.', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // --------------------------------------------------------
    // Giriş
    // --------------------------------------------------------
    case 'login':
        $login    = trim($body['login']    ?? '');
        $password = trim($body['password'] ?? '');

        if (!$login || !$password) {
            jsonError('Kullanıcı adı/e-posta ve şifre zorunludur.');
        }

        $result = loginUser($login, $password);
        if (!$result['success']) {
            jsonError($result['message'], 401);
        }
        jsonSuccess(['username' => currentUsername()], $result['message']);
        break;

    // --------------------------------------------------------
    // Kayıt
    // --------------------------------------------------------
    case 'register':
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email']    ?? '');
        $password = $body['password']      ?? '';

        // Temel doğrulama
        if (!$username || !$email || !$password) {
            jsonError('Tüm alanlar zorunludur.');
        }
        if (strlen($username) < 3 || strlen($username) > 50) {
            jsonError('Kullanıcı adı 3-50 karakter arasında olmalıdır.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Geçerli bir e-posta adresi girin.');
        }
        if (strlen($password) < 6) {
            jsonError('Şifre en az 6 karakter olmalıdır.');
        }

        $result = registerUser($username, $email, $password);
        if (!$result['success']) {
            jsonError($result['message']);
        }

        // Kayıt başarılıysa otomatik giriş yap
        loginUser($username, $password);
        jsonSuccess(['username' => currentUsername()], $result['message']);
        break;

    // --------------------------------------------------------
    // E-posta doğrulama
    // --------------------------------------------------------
    case 'verify_email':
        $userId = currentUserId();
        if (!$userId) { jsonError('Oturum açmanız gerekiyor.', 401); }
        $code = trim($body['code'] ?? '');
        if (!$code) { jsonError('Doğrulama kodu zorunludur.'); }
        $result = verifyEmailCode($userId, $code);
        if (!$result['success']) { jsonError($result['message']); }
        jsonSuccess([], $result['message']);
        break;

    // --------------------------------------------------------
    // Doğrulama kodunu yeniden gönder
    // --------------------------------------------------------
    case 'resend_code':
        $userId = currentUserId();
        if (!$userId) { jsonError('Oturum açmanız gerekiyor.', 401); }
        $result = resendVerificationCode($userId);
        if (!$result['success']) { jsonError($result['message']); }
        jsonSuccess([], $result['message']);
        break;

    // --------------------------------------------------------
    // Şifremi unuttum
    // --------------------------------------------------------
    case 'forgot_password':
        $email = trim($body['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Geçerli bir e-posta adresi girin.');
        }
        $result = sendPasswordReset($email);
        jsonSuccess([], $result['message']);
        break;

    // --------------------------------------------------------
    // Şifre sıfırla
    // --------------------------------------------------------
    case 'reset_password':
        $userId      = (int)($body['user_id']     ?? 0);
        $token       = trim($body['token']         ?? '');
        $newPassword = $body['password']           ?? '';
        if (!$userId || !$token || !$newPassword) {
            jsonError('Eksik parametreler.');
        }
        $result = resetPasswordWithToken($userId, $token, $newPassword);
        if (!$result['success']) { jsonError($result['message']); }
        jsonSuccess([], $result['message']);
        break;

    default:
        jsonError('Geçersiz action.', 400);
}
