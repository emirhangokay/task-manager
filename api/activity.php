<?php
/**
 * api/activity.php
 * ============================================================
 * Aktivite logu endpoint'i.
 * GET ?limit=N → Son N aktiviteyi döndürür (max 50)
 * ============================================================
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!currentUserId()) {
    jsonError('Yetkisiz erişim.', 401);
}

$userId = currentUserId();
session_write_close();

$limit = min((int)($_GET['limit'] ?? 10), 50);
$db    = getDB();

$stmt = $db->prepare("
    SELECT al.id, al.action, al.details, al.created_at,
           t.title AS task_title, t.id AS task_id
    FROM activity_logs al
    LEFT JOIN tasks t ON t.id = al.task_id
    WHERE al.user_id = ?
    ORDER BY al.created_at DESC
    LIMIT ?
");
$stmt->execute([$userId, $limit]);
jsonSuccess($stmt->fetchAll());
