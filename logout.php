<?php
/**
 * logout.php
 * ============================================================
 * Kullanıcı oturumunu kapatır ve giriş sayfasına yönlendirir.
 * ============================================================
 */

require_once __DIR__ . '/includes/auth.php';

logoutUser();

header('Location: /login.php');
exit;
