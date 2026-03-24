<?php
/**
 * api/tasks.php
 * ============================================================
 * Görev CRUD API uç noktası (AJAX).
 * GET              → Görevleri listele (filtrelerle)
 * POST action=create  → Yeni görev ekle
 * POST action=update  → Görev güncelle
 * POST action=delete  → Görev sil
 * POST action=toggle  → Durum değiştir (hızlı toggle)
 * Tüm yanıtlar JSON formatındadır.
 * ============================================================
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Oturum kontrolü
if (!currentUserId()) {
    jsonError('Yetkisiz erişim.', 401);
}

$userId = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];

// Session okunduktan sonra dosya kilidini serbest bırak.
// Aksi hâlde eş zamanlı AJAX istekleri birbirini bekler,
// bu da MySQL lock timeout hatasına yol açar.
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

    // Çıktıyı XSS'e karşı temizle
    foreach ($tasks as &$task) {
        $task['title_safe']       = e($task['title']);
        $task['description_safe'] = $task['description'] ? e($task['description']) : '';
        $task['priority_label']   = priorityLabel($task['priority']);
        $task['status_label']     = statusLabel($task['status']);
        $task['is_overdue']       = ($task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed');
    }

    jsonSuccess($tasks);
}

// -------------------------------------------------------
// POST: veri yazma işlemleri
// -------------------------------------------------------
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    // CSRF doğrulaması
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
            $dueDate     = !empty($body['due_date'])  ? $body['due_date']  : null;

            if (!$title) {
                jsonError('Görev başlığı zorunludur.');
            }
            if (strlen($title) > 200) {
                jsonError('Başlık en fazla 200 karakter olabilir.');
            }
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                jsonError('Geçersiz öncelik değeri.');
            }
            if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                jsonError('Geçersiz tarih formatı.');
            }

            $stmt = $db->prepare("
                INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([$userId, $categoryId, $title, $description ?: null, $priority, $dueDate]);
            $newId = (int)$db->lastInsertId();

            // Yeni görevi döndür
            $row = $db->prepare("
                SELECT t.*, c.name AS category_name, c.color AS category_color
                FROM tasks t LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.id = ?
            ");
            $row->execute([$newId]);
            $task = $row->fetch();
            $task['priority_label'] = priorityLabel($task['priority']);
            $task['status_label']   = statusLabel($task['status']);

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

            if (!$id) {
                jsonError('Görev ID eksik.');
            }
            if (!$title) {
                jsonError('Görev başlığı zorunludur.');
            }
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                jsonError('Geçersiz öncelik.');
            }
            if (!in_array($status, ['pending', 'in_progress', 'completed'], true)) {
                jsonError('Geçersiz durum.');
            }

            // Görevin bu kullanıcıya ait olduğunu doğrula
            $check = $db->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
            $check->execute([$id, $userId]);
            if (!$check->fetch()) {
                jsonError('Görev bulunamadı.', 404);
            }

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

            jsonSuccess($task, 'Görev güncellendi.');

        // ------------------------------------------------
        // Görev sil
        // ------------------------------------------------
        case 'delete':
            $id = (int)($body['id'] ?? 0);
            if (!$id) {
                jsonError('Görev ID eksik.');
            }

            $stmt = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() === 0) {
                jsonError('Görev bulunamadı.', 404);
            }

            jsonSuccess(null, 'Görev silindi.');

        // ------------------------------------------------
        // Hızlı durum değiştirme (toggle)
        // ------------------------------------------------
        case 'toggle_status':
            $id        = (int)($body['id']     ?? 0);
            $newStatus = $body['new_status']   ?? '';

            if (!$id) {
                jsonError('Görev ID eksik.');
            }
            if (!in_array($newStatus, ['pending', 'in_progress', 'completed'], true)) {
                jsonError('Geçersiz durum.');
            }

            $stmt = $db->prepare("UPDATE tasks SET status=?, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$newStatus, $id, $userId]);

            if ($stmt->rowCount() === 0) {
                jsonError('Görev bulunamadı.', 404);
            }

            jsonSuccess(['status' => $newStatus, 'status_label' => statusLabel($newStatus)], 'Durum güncellendi.');

        default:
            jsonError('Geçersiz action.');
    }
}

jsonError('Desteklenmeyen HTTP metodu.', 405);
