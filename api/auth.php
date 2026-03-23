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

    default:
        jsonError('Geçersiz action.', 400);
}
