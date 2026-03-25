<?php
/**
 * landing.php
 * ============================================================
 * TaskFlow — Açılış (landing) sayfası
 * Ziyaretçilere gösterilen SaaS tanıtım sayfası.
 * Giriş yapmış kullanıcıları app'e yönlendirir.
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (currentUserId()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TaskFlow — Akıllı Görev Yönetimi</title>
  <meta name="description" content="TaskFlow ile görevlerinizi listeleyin, kanban tahtasında sürükleyin ve takvimde takip edin. Ücretsiz başlayın." />
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicon.svg" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css" />
</head>
<body>

<!-- NAVBAR -->
<nav class="lp-nav">
  <a href="<?= BASE_URL ?>/landing.php" class="lp-nav__brand">
    <div class="lp-nav__brand-icon">
      <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
        <path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    TaskFlow
  </a>
  <div class="lp-nav__links">
    <a href="#features" class="lp-nav__link">Özellikler</a>
    <a href="#how" class="lp-nav__link">Nasıl Çalışır</a>
    <a href="<?= BASE_URL ?>/login.php" class="lp-nav__link">Giriş Yap</a>
    <a href="<?= BASE_URL ?>/register.php" class="lp-nav__cta">Ücretsiz Başla</a>
  </div>
</nav>

<!-- HERO -->
<section class="lp-hero">
  <div class="lp-hero__badge">
    <svg width="12" height="12" viewBox="0 0 20 20" fill="none"><path d="M3 10l5 5L17 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Tamamen Ücretsiz
  </div>
  <h1 class="lp-hero__title">
    Görevlerinizi<br>
    <span>Akıllıca Yönetin</span>
  </h1>
  <p class="lp-hero__sub">
    Liste, Kanban ve Takvim görünümleriyle tüm işlerinizi tek yerden takip edin. Kategori, öncelik ve tarih filtresiyle hiç bir görev gözden kaçmasın.
  </p>
  <div class="lp-hero__actions">
    <a href="<?= BASE_URL ?>/register.php" class="btn-hero-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      Ücretsiz Başla
    </a>
    <a href="#features" class="btn-hero-secondary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="10 15 15 12 10 9"/></svg>
      Özellikleri Keşfet
    </a>
  </div>

  <!-- App Mock Screenshot -->
  <div class="lp-hero__screenshot" style="margin-top:56px">
    <div class="lp-hero__screenshot-inner">
      <div class="lp-hero__screenshot-bar">
        <span></span><span></span><span></span>
      </div>
      <div class="lp-hero__screenshot-content">
        <div class="lp-mock">
          <div class="lp-mock__sidebar">
            <div class="lp-mock__sidebar-logo">
              <div class="lp-mock__sidebar-logo-icon"></div>
              <div class="lp-mock__sidebar-logo-text"></div>
            </div>
            <div class="lp-mock__nav-item"></div>
            <div class="lp-mock__nav-item"></div>
            <div class="lp-mock__nav-item"></div>
            <div class="lp-mock__nav-item"></div>
          </div>
          <div class="lp-mock__main">
            <div class="lp-mock__stats">
              <div class="lp-mock__stat"></div>
              <div class="lp-mock__stat"></div>
              <div class="lp-mock__stat"></div>
              <div class="lp-mock__stat"></div>
            </div>
            <div class="lp-mock__tasks">
              <div class="lp-mock__task"></div>
              <div class="lp-mock__task"></div>
              <div class="lp-mock__task"></div>
              <div class="lp-mock__task"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="lp-section" id="features">
  <p class="lp-section__label">Özellikler</p>
  <h2 class="lp-section__title">İhtiyacınız olan her şey</h2>
  <p class="lp-section__sub">Sade arayüzü, güçlü özellikleri ve sezgisel tasarımıyla TaskFlow iş akışınızı hızlandırır.</p>

  <div class="lp-features">
    <div class="lp-feature reveal">
      <div class="lp-feature__icon" style="background:rgba(99,102,241,.12)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      </div>
      <h3 class="lp-feature__title">3 Görünüm Modu</h3>
      <p class="lp-feature__desc">Liste, Kanban ve Takvim görünümleri arasında tek tıkla geçiş yapın. Her görünüm anında güncellenir.</p>
    </div>

    <div class="lp-feature reveal">
      <div class="lp-feature__icon" style="background:rgba(34,197,94,.12)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </div>
      <h3 class="lp-feature__title">Akıllı Filtreler</h3>
      <p class="lp-feature__desc">Kategori, öncelik, durum ve son tarih gibi güçlü filtrelerle binlerce görev arasında anında bulun.</p>
    </div>

    <div class="lp-feature reveal">
      <div class="lp-feature__icon" style="background:rgba(234,179,8,.12)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <h3 class="lp-feature__title">Kategori & Etiket</h3>
      <p class="lp-feature__desc">Renkli kategorilerle görevlerinizi gruplandırın. İş, kişisel, acil — dilediğiniz kadar kategori oluşturun.</p>
    </div>

    <div class="lp-feature reveal">
      <div class="lp-feature__icon" style="background:rgba(239,68,68,.12)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
      </div>
      <h3 class="lp-feature__title">Kanban Drag & Drop</h3>
      <p class="lp-feature__desc">Görevleri sütunlar arasında sürükleyip bırakın. Durum güncellemeleri anında kaydedilir.</p>
    </div>

    <div class="lp-feature reveal">
      <div class="lp-feature__icon" style="background:rgba(6,182,212,.12)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>
      </div>
      <h3 class="lp-feature__title">Takvim Görünümü</h3>
      <p class="lp-feature__desc">Son tarihleri takvim üzerinde görün. Hangi gün ne bitiyor? Tek bakışta anlayın.</p>
    </div>

    <div class="lp-feature reveal">
      <div class="lp-feature__icon" style="background:rgba(139,92,246,.12)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <h3 class="lp-feature__title">Çoklu Kullanıcı</h3>
      <p class="lp-feature__desc">Her kullanıcının kendi özel alanı. Güvenli giriş, e-posta doğrulama ve şifreli hesap koruması.</p>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="lp-section lp-section--alt" id="how">
  <p class="lp-section__label">Nasıl Çalışır</p>
  <h2 class="lp-section__title">3 adımda başlayın</h2>
  <p class="lp-section__sub">Kredi kartı gerekmez. Dakikalar içinde kurun ve kullanmaya başlayın.</p>

  <div class="lp-steps">
    <div class="lp-step reveal">
      <div class="lp-step__num">1</div>
      <h3 class="lp-step__title">Hesap Oluşturun</h3>
      <p class="lp-step__desc">E-posta adresinizle ücretsiz kayıt olun. Kurulum yok, ödeme yok.</p>
    </div>
    <div class="lp-step reveal">
      <div class="lp-step__num">2</div>
      <h3 class="lp-step__title">Görev Ekleyin</h3>
      <p class="lp-step__desc">Kategorilere ayırın, öncelik belirleyin, son tarih ekleyin. İstediğiniz kadar görev.</p>
    </div>
    <div class="lp-step reveal">
      <div class="lp-step__num">3</div>
      <h3 class="lp-step__title">Takip Edin</h3>
      <p class="lp-step__desc">Liste, Kanban veya Takvim görünümünde ilerlemenizi izleyin.</p>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="lp-cta">
  <h2 class="lp-cta__title reveal">Üretkenliğinizi artırmaya<br>bugün başlayın</h2>
  <p class="lp-cta__sub reveal">Ücretsiz hesap oluşturun ve görev yönetiminin ne kadar kolay olabileceğini görün.</p>
  <a href="<?= BASE_URL ?>/register.php" class="btn-cta reveal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    Hemen Başla — Ücretsiz
  </a>
</section>

<!-- FOOTER -->
<footer class="lp-footer">
  <a href="<?= BASE_URL ?>/landing.php" class="lp-footer__brand">
    <div class="lp-nav__brand-icon" style="width:26px;height:26px;border-radius:6px">
      <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    TaskFlow
  </a>
  <p class="lp-footer__copy">© <?= date('Y') ?> TaskFlow. Tüm hakları saklıdır.</p>
  <div style="display:flex;gap:16px">
    <a href="<?= BASE_URL ?>/login.php" style="font-size:.875rem;color:var(--text-muted);text-decoration:none">Giriş Yap</a>
    <a href="<?= BASE_URL ?>/register.php" style="font-size:.875rem;color:var(--accent);text-decoration:none;font-weight:500">Kayıt Ol</a>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/landing.js"></script>
</body>
</html>
