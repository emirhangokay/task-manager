<?php
/**
 * includes/functions.php
 * ============================================================
 * Genel yardımcı fonksiyonlar.
 * XSS koruması, JSON yanıtları, doğrulama vb.
 * ============================================================
 */

/**
 * Kullanıcı girdisini XSS'e karşı temizler.
 *
 * @param string $value
 * @return string
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * JSON formatında başarılı yanıt gönderir ve scripti sonlandırır.
 *
 * @param mixed  $data
 * @param string $message
 * @return void
 */
function jsonSuccess(mixed $data = null, string $message = 'İşlem başarılı.'): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * JSON formatında hata yanıtı gönderir ve scripti sonlandırır.
 *
 * @param string $message
 * @param int    $code HTTP durum kodu
 * @return void
 */
function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Kullanıcının tüm kategorilerini çeker.
 *
 * @param int $userId
 * @return array
 */
function getUserCategories(int $userId): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id, name, color FROM categories WHERE user_id = ? ORDER BY name ASC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Görevleri filtreler ve döndürür.
 *
 * @param int    $userId
 * @param array  $filters  ['status', 'category_id', 'priority', 'search', 'sort']
 * @return array
 */
function getTasks(int $userId, array $filters = []): array
{
    $db     = getDB();
    $where  = ['t.user_id = :uid'];
    $params = [':uid' => $userId];

    if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'in_progress', 'completed'], true)) {
        $where[]           = 't.status = :status';
        $params[':status'] = $filters['status'];
    }

    if (!empty($filters['category_id'])) {
        if ($filters['category_id'] === 'none') {
            $where[] = 't.category_id IS NULL';
        } else {
            $where[]             = 't.category_id = :cat_id';
            $params[':cat_id']   = (int)$filters['category_id'];
        }
    }

    if (!empty($filters['priority']) && in_array($filters['priority'], ['low', 'medium', 'high'], true)) {
        $where[]              = 't.priority = :priority';
        $params[':priority']  = $filters['priority'];
    }

    if (!empty($filters['search'])) {
        $where[]              = '(t.title LIKE :search OR t.description LIKE :search)';
        $params[':search']    = '%' . $filters['search'] . '%';
    }

    $orderMap = [
        'date_desc'     => 't.created_at DESC',
        'date_asc'      => 't.created_at ASC',
        'priority_desc' => "FIELD(t.priority,'high','medium','low')",
        'alpha_asc'     => 't.title ASC',
        'due_asc'       => 'ISNULL(t.due_date), t.due_date ASC',
    ];
    $sort  = $filters['sort'] ?? 'date_desc';
    $order = $orderMap[$sort] ?? $orderMap['date_desc'];

    $sql = "
        SELECT
            t.id, t.title, t.description, t.priority, t.status,
            t.due_date, t.created_at, t.updated_at,
            t.category_id,
            c.name  AS category_name,
            c.color AS category_color
        FROM tasks t
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $order
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Görev istatistiklerini döndürür.
 *
 * @param int $userId
 * @return array{total: int, pending: int, in_progress: int, completed: int, overdue: int}
 */
function getTaskStats(int $userId): array
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'pending')     AS pending,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'completed')   AS completed,
            SUM(status != 'completed' AND due_date < CURDATE()) AS overdue
        FROM tasks
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return [
        'total'       => (int)($row['total']       ?? 0),
        'pending'     => (int)($row['pending']      ?? 0),
        'in_progress' => (int)($row['in_progress']  ?? 0),
        'completed'   => (int)($row['completed']    ?? 0),
        'overdue'     => (int)($row['overdue']       ?? 0),
    ];
}

/**
 * Bugün bitiş tarihi olan görevleri döndürür.
 *
 * @param int $userId
 * @return array
 */
function getTodayTasks(int $userId): array
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id, title, status, priority
        FROM tasks
        WHERE user_id = ? AND due_date = CURDATE() AND status != 'completed'
        ORDER BY FIELD(priority,'high','medium','low')
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Türkçe öncelik etiketi döndürür.
 *
 * @param string $priority
 * @return string
 */
function priorityLabel(string $priority): string
{
    return match($priority) {
        'low'    => 'Düşük',
        'medium' => 'Orta',
        'high'   => 'Yüksek',
        default  => $priority,
    };
}

/**
 * Türkçe durum etiketi döndürür.
 *
 * @param string $status
 * @return string
 */
function statusLabel(string $status): string
{
    return match($status) {
        'pending'     => 'Bekliyor',
        'in_progress' => 'Devam Ediyor',
        'completed'   => 'Tamamlandı',
        default       => $status,
    };
}
