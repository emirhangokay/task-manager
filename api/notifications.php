<?php
/**
 * api/notifications.php
 * ============================================================
 * Bildirim API uç noktası.
 * GET               → Bildirimleri listele
 * POST action=mark_read      → Tek bildirimi okundu işaretle
 * POST action=mark_all_read  → Tümünü okundu işaretle
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
    $limit  = min((int)($_GET['limit'] ?? 20), 50);
    $unread = isset($_GET['unread']) ? (bool)$_GET['unread'] : false;

    $sql    = 'SELECT id, type, title, message, task_id, is_read, created_at
               FROM notifications WHERE user_id = ?';
    $params = [$userId];
    if ($unread) { $sql .= ' AND is_read = 0'; }
    $sql .= ' ORDER BY created_at DESC LIMIT ' . $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();

    // Okunmamış sayısı
    $cntStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $cntStmt->execute([$userId]);
    $unreadCount = (int)$cntStmt->fetchColumn();

    jsonSuccess([
        'notifications' => $notifications,
        'unread_count'  => $unreadCount,
    ]);
}

// POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Geçersiz istek.', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {
    case 'mark_read':
        $id = (int)($body['id'] ?? 0);
        if (!$id) { jsonError('Bildirim ID zorunludur.'); }
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
           ->execute([$id, $userId]);
        jsonSuccess([], 'Bildirim okundu olarak işaretlendi.');
        break;

    case 'mark_all_read':
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')
           ->execute([$userId]);
        jsonSuccess([], 'Tüm bildirimler okundu olarak işaretlendi.');
        break;

    default:
        jsonError('Geçersiz action.', 400);
}
