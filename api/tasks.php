<?php
/**
 * api/tasks.php
 * ============================================================
 * Görev CRUD API uç noktası (AJAX).
 * GET                    → Görevleri listele (filtrelerle)
 * POST action=create     → Yeni görev ekle
 * POST action=update     → Görev güncelle
 * POST action=delete     → Görev sil
 * POST action=toggle_status → Hızlı durum değiştir
 * POST action=reorder    → Kanban sıralama (position + status)
 * POST action=bulk_update   → Toplu durum/kategori güncelle
 * POST action=bulk_delete   → Toplu sil
 * Tüm yanıtlar JSON formatındadır.
 * ============================================================
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!currentUserId()) {
    jsonError('Yetkisiz erişim.', 401);
}

$userId = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];

session_write_close();

// -------------------------------------------------------
// GET: görevleri filtreli getir
// -------------------------------------------------------
if ($method === 'GET') {
    $filters = [
        'status'      => $_GET['status']      ?? '',
        'category_id' => $_GET['category_id'] ?? '',
        'priority'    => $_GET['priority']    ?? '',
        'search'      => $_GET['search']      ?? '',
        'sort'        => $_GET['sort']        ?? 'date_desc',
    ];

    $tasks = getTasks($userId, $filters);

    foreach ($tasks as &$task) {
        $task['title_safe']       = e($task['title']);
        $task['description_safe'] = $task['description'] ? e($task['description']) : '';
        $task['priority_label']   = priorityLabel($task['priority']);
        $task['status_label']     = statusLabel($task['status']);
        $task['is_overdue']       = $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed';
    }

    jsonSuccess($tasks);
}

// -------------------------------------------------------
// POST: veri yazma işlemleri
// -------------------------------------------------------
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if (!verifyCsrf($body['csrf_token'] ?? '')) {
        jsonError('Geçersiz CSRF token.', 403);
    }

    $db = getDB();

    switch ($action) {
        // ------------------------------------------------
        // Yeni görev oluştur
        // ------------------------------------------------
        case 'create':
            $title       = trim($body['title']       ?? '');
            $description = trim($body['description'] ?? '');
            $priority    = $body['priority']          ?? 'medium';
            $categoryId  = !empty($body['category_id']) ? (int)$body['category_id'] : null;
            $dueDate     = !empty($body['due_date'])  ? $body['due_date'] : null;

            if (!$title)                                            jsonError('Görev başlığı zorunludur.');
            if (strlen($title) > 200)                              jsonError('Başlık en fazla 200 karakter olabilir.');
            if (!in_array($priority, ['low','medium','high'], true)) jsonError('Geçersiz öncelik değeri.');
            if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) jsonError('Geçersiz tarih formatı.');

            // Aynı statustaki en büyük position değerini al
            $posStmt = $db->prepare("SELECT COALESCE(MAX(position),0)+1 FROM tasks WHERE user_id=? AND status='pending'");
            $posStmt->execute([$userId]);
            $position = (int)$posStmt->fetchColumn();

            $stmt = $db->prepare("
                INSERT INTO tasks (user_id, category_id, title, description, priority, status, position, due_date)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([$userId, $categoryId, $title, $description ?: null, $priority, $position, $dueDate]);
            $newId = (int)$db->lastInsertId();

            $row = $db->prepare("
                SELECT t.*, c.name AS category_name, c.color AS category_color
                FROM tasks t LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.id = ?
            ");
            $row->execute([$newId]);
            $task = $row->fetch();
            $task['priority_label'] = priorityLabel($task['priority']);
            $task['status_label']   = statusLabel($task['status']);

            logActivity($userId, $newId, 'created', "'{$task['title']}' görevi oluşturuldu.");

            jsonSuccess($task, 'Görev eklendi.');

        // ------------------------------------------------
        // Görev güncelle
        // ------------------------------------------------
        case 'update':
            $id          = (int)($body['id']          ?? 0);
            $title       = trim($body['title']        ?? '');
            $description = trim($body['description']  ?? '');
            $priority    = $body['priority']           ?? 'medium';
            $status      = $body['status']             ?? 'pending';
            $categoryId  = !empty($body['category_id']) ? (int)$body['category_id'] : null;
            $dueDate     = !empty($body['due_date'])   ? $body['due_date'] : null;

            if (!$id)    jsonError('Görev ID eksik.');
            if (!$title) jsonError('Görev başlığı zorunludur.');
            if (!in_array($priority, ['low','medium','high'], true))              jsonError('Geçersiz öncelik.');
            if (!in_array($status, ['pending','in_progress','completed'], true))  jsonError('Geçersiz durum.');

            $check = $db->prepare('SELECT id, status FROM tasks WHERE id = ? AND user_id = ?');
            $check->execute([$id, $userId]);
            $old = $check->fetch();
            if (!$old) jsonError('Görev bulunamadı.', 404);

            $stmt = $db->prepare("
                UPDATE tasks
                SET title=?, description=?, priority=?, status=?, category_id=?, due_date=?, updated_at=NOW()
                WHERE id=? AND user_id=?
            ");
            $stmt->execute([$title, $description ?: null, $priority, $status, $categoryId, $dueDate, $id, $userId]);

            $row = $db->prepare("
                SELECT t.*, c.name AS category_name, c.color AS category_color
                FROM tasks t LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.id = ?
            ");
            $row->execute([$id]);
            $task = $row->fetch();
            $task['priority_label'] = priorityLabel($task['priority']);
            $task['status_label']   = statusLabel($task['status']);

            $logAction = ($old['status'] !== $status) ? 'status_changed' : 'updated';
            logActivity($userId, $id, $logAction, "'{$task['title']}' güncellendi.");

            jsonSuccess($task, 'Görev güncellendi.');

        // ------------------------------------------------
        // Görev sil
        // ------------------------------------------------
        case 'delete':
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonError('Görev ID eksik.');

            $titleStmt = $db->prepare('SELECT title FROM tasks WHERE id=? AND user_id=?');
            $titleStmt->execute([$id, $userId]);
            $titleRow = $titleStmt->fetch();
            if (!$titleRow) jsonError('Görev bulunamadı.', 404);

            $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?')->execute([$id, $userId]);

            logActivity($userId, null, 'deleted', "'{$titleRow['title']}' silindi.");

            jsonSuccess(null, 'Görev silindi.');

        // ------------------------------------------------
        // Hızlı durum değiştirme (checkbox / toggle)
        // ------------------------------------------------
        case 'toggle_status':
            $id        = (int)($body['id']         ?? 0);
            $newStatus = $body['new_status']        ?? '';

            if (!$id) jsonError('Görev ID eksik.');
            if (!in_array($newStatus, ['pending','in_progress','completed'], true)) jsonError('Geçersiz durum.');

            $stmt = $db->prepare("UPDATE tasks SET status=?, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$newStatus, $id, $userId]);
            if ($stmt->rowCount() === 0) jsonError('Görev bulunamadı.', 404);

            $logDetail = $newStatus === 'completed' ? 'Tamamlandı olarak işaretlendi.' : statusLabel($newStatus) . ' olarak güncellendi.';
            logActivity($userId, $id, 'status_changed', $logDetail);

            jsonSuccess(['status' => $newStatus, 'status_label' => statusLabel($newStatus)], 'Durum güncellendi.');

        // ------------------------------------------------
        // Kanban yeniden sıralama (drag & drop)
        // ------------------------------------------------
        case 'reorder':
            $id       = (int)($body['id']       ?? 0);
            $status   = $body['status']          ?? '';
            $position = (int)($body['position']  ?? 0);

            if (!$id) jsonError('Görev ID eksik.');
            if (!in_array($status, ['pending','in_progress','completed'], true)) jsonError('Geçersiz durum.');

            $stmt = $db->prepare("UPDATE tasks SET status=?, position=?, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$status, $position, $id, $userId]);
            if ($stmt->rowCount() === 0) jsonError('Görev bulunamadı.', 404);

            logActivity($userId, $id, 'status_changed', statusLabel($status) . ' sütununa taşındı.');

            jsonSuccess(['status' => $status, 'position' => $position], 'Sıralama güncellendi.');

        // ------------------------------------------------
        // Toplu durum / kategori değiştirme
        // ------------------------------------------------
        case 'bulk_update':
            $ids      = array_filter(array_map('intval', $body['ids'] ?? []));
            $field    = $body['field']  ?? '';
            $value    = $body['value']  ?? '';

            if (empty($ids))                                           jsonError('Görev seçilmedi.');
            if (!in_array($field, ['status','category_id'], true))     jsonError('Geçersiz alan.');
            if ($field === 'status' && !in_array($value, ['pending','in_progress','completed'], true)) jsonError('Geçersiz durum.');

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = [$value, ...$ids, $userId];
            $db->prepare("UPDATE tasks SET {$field}=?, updated_at=NOW() WHERE id IN ({$placeholders}) AND user_id=?")
               ->execute($params);

            logActivity($userId, null, 'updated', count($ids) . ' görev toplu güncellendi.');

            jsonSuccess(null, count($ids) . ' görev güncellendi.');

        // ------------------------------------------------
        // Toplu silme
        // ------------------------------------------------
        case 'bulk_delete':
            $ids = array_filter(array_map('intval', $body['ids'] ?? []));
            if (empty($ids)) jsonError('Görev seçilmedi.');

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = [...$ids, $userId];
            $db->prepare("DELETE FROM tasks WHERE id IN ({$placeholders}) AND user_id=?")->execute($params);

            logActivity($userId, null, 'deleted', count($ids) . ' görev toplu silindi.');

            jsonSuccess(null, count($ids) . ' görev silindi.');

        default:
            jsonError('Geçersiz action.');
    }
}

jsonError('Desteklenmeyen HTTP metodu.', 405);
