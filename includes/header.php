<?php
/**
 * includes/header.php
 * ============================================================
 * Tüm sayfalarda ortak kullanılan HTML <head> bölümü.
 * Sayfa başlığı $pageTitle değişkeniyle özelleştirilebilir.
 * ============================================================
 */
// e() ve diğer yardımcı fonksiyonlar bu dosyada tanımlı
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Görev Yönetim';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?> — Görev Yönetim</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Ana stil dosyası -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body>
