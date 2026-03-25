<?php
/**
 * api/settings.php
 * ============================================================
 * Kullanıcı ayarları API uç noktası.
 * GET               → Ayarları getir
 * POST action=update          → Profil + ayarları güncelle
 * POST action=change_password → Şifreyi değiştir
 * POST action=delete_account  → Hesabı sil
 * ============================================================
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
session_write_close();

$userId = currentUserId();
if (!$userId) { jsonError('Yetkisiz erişim.', 401); }

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uStmt = $db->prepare(
        'SELECT username, display_name, email, email_verified, created_at FROM users WHERE id = ? LIMIT 1'
    );
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();

    $sStmt = $db->prepare(
        'SELECT theme, default_view, notifications_enabled, avatar_color, language
         FROM user_settings WHERE user_id = ? LIMIT 1'
    );
    $sStmt->execute([$userId]);
    $settings = $sStmt->fetch();

    if (!$settings) {
        // İlk kez — varsayılanları oluştur
        $db->prepare(
            'INSERT INTO user_settings (user_id, theme, default_view, notifications_enabled, avatar_color, language)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$userId, 'light', 'list', 1, '#6366f1', 'tr']);
        $settings = [
            'theme' => 'light', 'default_view' => 'list',
            'notifications_enabled' => 1, 'avatar_color' => '#6366f1', 'language' => 'tr',
        ];
    }

    jsonSuccess(['user' => $user, 'settings' => $settings]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Geçersiz istek.', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {

    case 'update':
        $displayName           = trim($body['display_name']           ?? '');
        $theme                 = $body['theme']                        ?? 'light';
        $defaultView           = $body['default_view']                 ?? 'list';
        $notificationsEnabled  = isset($body['notifications_enabled']) ? (int)(bool)$body['notifications_enabled'] : 1;
        $avatarColor           = $body['avatar_color']                 ?? '#6366f1';
        $language              = $body['language']                     ?? 'tr';

        // Basit doğrulama
        if (!in_array($theme, ['light', 'dark'], true))                 { $theme = 'light'; }
        if (!in_array($defaultView, ['list','kanban','calendar'], true)) { $defaultView = 'list'; }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $avatarColor))           { $avatarColor = '#6366f1'; }
        if (!in_array($language, ['tr','en'], true))                    { $language = 'tr'; }

        $db->prepare(
            'UPDATE users SET display_name = ? WHERE id = ?'
        )->execute([$displayName ?: null, $userId]);

        $db->prepare(
            'INSERT INTO user_settings (user_id, theme, default_view, notifications_enabled, avatar_color, language)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               theme = VALUES(theme),
               default_view = VALUES(default_view),
               notifications_enabled = VALUES(notifications_enabled),
               avatar_color = VALUES(avatar_color),
               language = VALUES(language)'
        )->execute([$userId, $theme, $defaultView, $notificationsEnabled, $avatarColor, $language]);

        jsonSuccess([], 'Ayarlar kaydedildi.');
        break;

    case 'change_password':
        $current     = $body['current_password'] ?? '';
        $newPassword = $body['new_password']     ?? '';
        $confirm     = $body['confirm_password'] ?? '';

        if (!$current || !$newPassword || !$confirm) {
            jsonError('Tüm alanlar zorunludur.');
        }
        if ($newPassword !== $confirm) {
            jsonError('Yeni şifreler eşleşmiyor.');
        }
        if (strlen($newPassword) < 6) {
            jsonError('Yeni şifre en az 6 karakter olmalıdır.');
        }

        $stmt = $db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current, $user['password'])) {
            jsonError('Mevcut şifre hatalı.', 401);
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);

        jsonSuccess([], 'Şifreniz başarıyla değiştirildi.');
        break;

    case 'delete_account':
        $password = $body['password'] ?? '';
        if (!$password) { jsonError('Şifrenizi girerek onaylayın.'); }

        $stmt = $db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonError('Şifre hatalı.', 401);
        }

        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
        logoutUser();
        jsonSuccess([], 'Hesabınız silindi.');
        break;

    default:
        jsonError('Geçersiz action.', 400);
}
