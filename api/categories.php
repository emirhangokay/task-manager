<?php
/**
 * api/categories.php
 * ============================================================
 * Kategori CRUD API uç noktası (AJAX).
 * GET              → Kategorileri listele
 * POST action=create → Yeni kategori
 * POST action=update → Kategori güncelle
 * POST action=delete → Kategori sil (görevler kategorisiz kalır)
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

// Session dosya kilidini erken serbest bırak (eş zamanlı AJAX desteği)
session_write_close();

$db = getDB();

// -------------------------------------------------------
// GET: kategorileri listele
// -------------------------------------------------------
if ($method === 'GET') {
    jsonSuccess(getUserCategories($userId));
}

// -------------------------------------------------------
// POST: yazma işlemleri
// -------------------------------------------------------
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if (!verifyCsrf($body['csrf_token'] ?? '')) {
        jsonError('Geçersiz CSRF token.', 403);
    }

    switch ($action) {
        // ------------------------------------------------
        // Kategori oluştur
        // ------------------------------------------------
        case 'create':
            $name  = trim($body['name']  ?? '');
            $color = trim($body['color'] ?? '#6B7280');

            if (!$name) {
                jsonError('Kategori adı zorunludur.');
            }
            if (strlen($name) > 50) {
                jsonError('Kategori adı en fazla 50 karakter olabilir.');
            }
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                jsonError('Geçersiz renk kodu.');
            }

            $stmt = $db->prepare('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $name, $color]);
            $newId = (int)$db->lastInsertId();

            jsonSuccess(['id' => $newId, 'name' => $name, 'color' => $color], 'Kategori oluşturuldu.');

        // ------------------------------------------------
        // Kategori güncelle
        // ------------------------------------------------
        case 'update':
            $id    = (int)($body['id']   ?? 0);
            $name  = trim($body['name']  ?? '');
            $color = trim($body['color'] ?? '');

            if (!$id || !$name) {
                jsonError('ID ve isim zorunludur.');
            }
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                jsonError('Geçersiz renk kodu.');
            }

            $stmt = $db->prepare('UPDATE categories SET name=?, color=? WHERE id=? AND user_id=?');
            $stmt->execute([$name, $color, $id, $userId]);

            if ($stmt->rowCount() === 0) {
                jsonError('Kategori bulunamadı.', 404);
            }

            jsonSuccess(['id' => $id, 'name' => $name, 'color' => $color], 'Kategori güncellendi.');

        // ------------------------------------------------
        // Kategori sil
        // ------------------------------------------------
        case 'delete':
            $id = (int)($body['id'] ?? 0);
            if (!$id) {
                jsonError('Kategori ID eksik.');
            }

            // Silinince görevler NULL kategoriye düşer (ON DELETE SET NULL)
            $stmt = $db->prepare('DELETE FROM categories WHERE id=? AND user_id=?');
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() === 0) {
                jsonError('Kategori bulunamadı.', 404);
            }

            jsonSuccess(null, 'Kategori silindi.');

        default:
            jsonError('Geçersiz action.');
    }
}

jsonError('Desteklenmeyen HTTP metodu.', 405);
