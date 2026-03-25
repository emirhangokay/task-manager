<?php
/**
 * includes/header.php
 * ============================================================
 * Tüm sayfalarda ortak HTML <head> bölümü.
 * Sayfa başlığı $pageTitle değişkeniyle özelleştirilebilir.
 * ============================================================
 */
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Görev Yönetim';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?> — TaskFlow</title>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicon.svg" />

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700&display=swap" rel="stylesheet" />

  <!-- Stil dosyaları -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css" />

  <!-- Tema: localStorage'dan erken uygula (flash önleme) -->
  <script>
    (function(){
      const t = localStorage.getItem('theme');
      if (t) document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>
