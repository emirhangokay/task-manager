<?php
/**
 * index.php
 * ============================================================
 * Ana sayfa — Dashboard ve görev listesi.
 * Oturum açık değilse login.php'ye yönlendirir.
 * Tüm görev ve kategori etkileşimleri AJAX ile yapılır.
 * ============================================================
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId   = currentUserId();
$username = currentUsername();
$stats    = getTaskStats($userId);
$csrf     = csrfToken();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- CSRF Meta -->
<meta name="csrf-token" content="<?= e($csrf) ?>" />

<!-- ========================================================
     UYGULAMA LAYOUT
     ======================================================== -->
<div class="app-layout">

  <!-- ======================================================
       SIDEBAR
       ====================================================== -->
  <aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar__logo">
      <div class="sidebar__logo-icon">✅</div>
      <div class="sidebar__logo-text">Görev Yönetim</div>
    </div>

    <!-- Hızlı filtreler -->
    <div class="sidebar__section">
      <div class="sidebar__section-title">Görünüm</div>
      <ul class="sidebar__nav">
        <li class="sidebar__nav-item">
          <button class="sidebar__nav-link is-active" id="linkAllTasks">
            📋 Tüm Görevler
          </button>
        </li>
        <li class="sidebar__nav-item">
          <button class="sidebar__nav-link" data-filter-status="pending">
            ⏳ Bekliyor
            <span class="sidebar__nav-badge"><?= $stats['pending'] ?></span>
          </button>
        </li>
        <li class="sidebar__nav-item">
          <button class="sidebar__nav-link" data-filter-status="in_progress">
            🔄 Devam Ediyor
            <span class="sidebar__nav-badge"><?= $stats['in_progress'] ?></span>
          </button>
        </li>
        <li class="sidebar__nav-item">
          <button class="sidebar__nav-link" data-filter-status="completed">
            ✅ Tamamlandı
            <span class="sidebar__nav-badge"><?= $stats['completed'] ?></span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Kategoriler -->
    <div class="sidebar__section" style="flex:1">
      <div class="sidebar__section-title">Kategoriler</div>
      <ul class="sidebar__nav" id="categoryList">
        <!-- JS tarafından doldurulur -->
      </ul>
      <button class="sidebar__add-cat" id="btnAddCat">
        ➕ Kategori Ekle
      </button>
    </div>

  </aside>

  <!-- Mobil overlay -->
  <div class="sidebar__overlay" id="sidebarOverlay"></div>

  <!-- ======================================================
       TOPBAR
       ====================================================== -->
  <header class="topbar">
    <!-- Hamburger (mobil) -->
    <button class="topbar__hamburger" id="hamburger" aria-label="Menüyü aç">☰</button>

    <!-- Arama -->
    <div class="topbar__search">
      <span class="topbar__search-icon">🔍</span>
      <input
        type="search"
        id="searchInput"
        placeholder="Görev ara…"
        autocomplete="off"
        aria-label="Görev ara"
      />
    </div>

    <!-- Sağ aksiyonlar -->
    <div class="topbar__actions">
      <button class="topbar__theme-btn" id="themeToggle" aria-label="Tema değiştir">🌙</button>

      <div class="topbar__user">
        <div class="topbar__avatar"><?= e(strtoupper(mb_substr($username, 0, 1))) ?></div>
        <span class="topbar__username"><?= e($username) ?></span>
      </div>

      <a href="/logout.php" class="topbar__logout" id="logoutBtn">Çıkış</a>
    </div>
  </header>

  <!-- ======================================================
       ANA İÇERİK
       ====================================================== -->
  <main class="main">

    <!-- Dashboard İstatistik Kartları -->
    <div class="stats-grid">
      <div class="stat-card stat-card--total">
        <div class="stat-card__icon">📋</div>
        <div>
          <div class="stat-card__label">Toplam</div>
          <div class="stat-card__value" id="statTotal"><?= $stats['total'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--pending">
        <div class="stat-card__icon">⏳</div>
        <div>
          <div class="stat-card__label">Bekliyor</div>
          <div class="stat-card__value" id="statPending"><?= $stats['pending'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--progress">
        <div class="stat-card__icon">🔄</div>
        <div>
          <div class="stat-card__label">Devam Ediyor</div>
          <div class="stat-card__value" id="statInProgress"><?= $stats['in_progress'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--done">
        <div class="stat-card__icon">✅</div>
        <div>
          <div class="stat-card__label">Tamamlandı</div>
          <div class="stat-card__value" id="statCompleted"><?= $stats['completed'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--overdue">
        <div class="stat-card__icon">⚠️</div>
        <div>
          <div class="stat-card__label">Gecikmiş</div>
          <div class="stat-card__value" id="statOverdue"><?= $stats['overdue'] ?></div>
        </div>
      </div>
    </div>

    <!-- Araç Çubuğu -->
    <div class="toolbar">
      <span class="toolbar__title">Görevlerim</span>

      <!-- Filtreler -->
      <select class="toolbar__select" id="filterStatus" aria-label="Duruma göre filtrele">
        <option value="">Tüm Durumlar</option>
        <option value="pending">Bekliyor</option>
        <option value="in_progress">Devam Ediyor</option>
        <option value="completed">Tamamlandı</option>
      </select>

      <select class="toolbar__select" id="filterCategory" aria-label="Kategoriye göre filtrele">
        <option value="">Tüm Kategoriler</option>
        <!-- JS tarafından doldurulur -->
      </select>

      <select class="toolbar__select" id="filterPriority" aria-label="Önceliğe göre filtrele">
        <option value="">Tüm Öncelikler</option>
        <option value="high">Yüksek</option>
        <option value="medium">Orta</option>
        <option value="low">Düşük</option>
      </select>

      <select class="toolbar__select" id="filterSort" aria-label="Sırala">
        <option value="date_desc">En Yeni</option>
        <option value="date_asc">En Eski</option>
        <option value="priority_desc">Önceliğe Göre</option>
        <option value="due_asc">Son Tarihe Göre</option>
        <option value="alpha_asc">Alfabetik</option>
      </select>

      <button class="btn btn--add-task" id="btnAddTask">+ Görev Ekle</button>
    </div>

    <!-- Görev Listesi -->
    <div class="tasks-list" id="tasksList">
      <!-- İçerik JS tarafından doldurulur -->
      <div class="tasks-empty">
        <div class="tasks-empty__icon">⏳</div>
        <div class="tasks-empty__title">Yükleniyor…</div>
      </div>
    </div>

  </main>
</div>

<!-- ========================================================
     MODAL: Görev Ekle / Düzenle
     ======================================================== -->
<div class="modal-overlay" id="taskModalOverlay" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
  <div class="modal">
    <div class="modal__header">
      <h2 class="modal__title" id="taskModalTitle">Yeni Görev Ekle</h2>
      <button class="modal__close btn btn--icon" onclick="closeModal('taskModal')" aria-label="Kapat">✕</button>
    </div>

    <form id="taskForm" novalidate>
      <input type="hidden" id="taskId" name="id" />

      <div class="form-group">
        <label class="form-label" for="taskTitle">Başlık <span style="color:var(--color-danger)">*</span></label>
        <input type="text" id="taskTitle" name="title" class="form-control" placeholder="Görev başlığı" maxlength="200" required />
      </div>

      <div class="form-group">
        <label class="form-label" for="taskDescription">Açıklama</label>
        <textarea id="taskDescription" name="description" class="form-control" rows="3" placeholder="İsteğe bağlı açıklama…" style="resize:vertical"></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label class="form-label" for="taskPriority">Öncelik</label>
          <select id="taskPriority" name="priority" class="form-control">
            <option value="low">Düşük</option>
            <option value="medium" selected>Orta</option>
            <option value="high">Yüksek</option>
          </select>
        </div>

        <div class="form-group hidden" id="taskStatusGroup">
          <label class="form-label" for="taskStatus">Durum</label>
          <select id="taskStatus" name="status" class="form-control">
            <option value="pending">Bekliyor</option>
            <option value="in_progress">Devam Ediyor</option>
            <option value="completed">Tamamlandı</option>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label class="form-label" for="taskCategoryId">Kategori</label>
          <select id="taskCategoryId" name="category_id" class="form-control js-category-select">
            <option value="">Kategori Yok</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="taskDueDate">Son Tarih</label>
          <input type="date" id="taskDueDate" name="due_date" class="form-control" />
        </div>
      </div>

      <div class="modal__footer">
        <button type="button" class="btn btn--secondary" onclick="closeModal('taskModal')">İptal</button>
        <button type="submit" class="btn btn--primary" style="width:auto;margin:0">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================
     MODAL: Kategori Ekle / Düzenle
     ======================================================== -->
<div class="modal-overlay" id="catModalOverlay" role="dialog" aria-modal="true" aria-labelledby="catModalTitle">
  <div class="modal" style="max-width:400px">
    <div class="modal__header">
      <h2 class="modal__title" id="catModalTitle">Yeni Kategori</h2>
      <button class="modal__close btn btn--icon" onclick="closeModal('catModal')" aria-label="Kapat">✕</button>
    </div>

    <form id="catForm" novalidate>
      <input type="hidden" id="catId" />

      <div class="form-group">
        <label class="form-label" for="catName">Kategori Adı <span style="color:var(--color-danger)">*</span></label>
        <input type="text" id="catName" class="form-control" placeholder="ör. İş, Kişisel…" maxlength="50" required />
      </div>

      <div class="form-group">
        <label class="form-label">Renk</label>
        <input type="hidden" id="catColorInput" value="#6B7280" />
        <div class="color-presets" id="colorPresets"></div>
      </div>

      <div class="modal__footer">
        <button type="button" class="btn btn--secondary" onclick="closeModal('catModal')">İptal</button>
        <button type="submit" class="btn btn--primary" style="width:auto;margin:0">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================
     GLOBAL UI ELEMENTLERİ
     ======================================================== -->

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
  <div class="spinner spinner--dark"></div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<!-- Ana JavaScript -->
<script src="/assets/js/app.js"></script>

</body>
</html>
