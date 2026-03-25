<?php
/**
 * index.php
 * ============================================================
 * Ana sayfa — TaskFlow Dashboard + Görev Yönetimi
 * Liste / Kanban / Takvim görünümleri, detay paneli, toplu işlem
 * ============================================================
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId   = currentUserId();
$username = currentUsername();
$stats    = getTaskStats($userId);
$csrf     = csrfToken();
$initial  = mb_strtoupper(mb_substr($username, 0, 1));

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- CSRF Meta -->
<meta name="csrf-token" content="<?= e($csrf) ?>" />

<!-- ================================================================
     UYGULAMA LAYOUT
     ================================================================ -->
<div class="app-layout" id="appLayout">

  <!-- ============================================================
       SIDEBAR
       ============================================================ -->
  <aside class="sidebar" id="sidebar">

    <!-- Header: Logo + Toggle -->
    <div class="sidebar__header">
      <div class="sidebar__logo">
        <div class="sidebar__logo-icon">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <span class="sidebar__logo-text">TaskFlow</span>
      </div>
      <button class="sidebar__toggle" id="sidebarToggle" title="Menüyü daralt">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <!-- Kullanıcı Bilgisi -->
    <div class="sidebar__user">
      <div class="sidebar__user-avatar"><?= e($initial) ?></div>
      <div class="sidebar__user-info">
        <div class="sidebar__user-name"><?= e($username) ?></div>
        <div class="sidebar__user-email">TaskFlow Kullanıcısı</div>
      </div>
    </div>

    <!-- Body: Nav grupları -->
    <div class="sidebar__body">

      <!-- Görünüm -->
      <div class="sidebar__section">
        <div class="sidebar__section-title">Görünüm</div>
        <ul class="sidebar__nav">
          <li class="sidebar__nav-item">
            <button class="sidebar__nav-link is-active" id="linkAllTasks">
              <span class="sidebar__nav-icon">📋</span>
              <span class="sidebar__nav-text">Tüm Görevler</span>
            </button>
          </li>
          <li class="sidebar__nav-item">
            <button class="sidebar__nav-link" data-filter-status="pending">
              <span class="sidebar__nav-icon">⏳</span>
              <span class="sidebar__nav-text">Bekliyor</span>
              <span class="sidebar__nav-badge"><?= $stats['pending'] ?></span>
            </button>
          </li>
          <li class="sidebar__nav-item">
            <button class="sidebar__nav-link" data-filter-status="in_progress">
              <span class="sidebar__nav-icon">🔄</span>
              <span class="sidebar__nav-text">Devam Ediyor</span>
              <span class="sidebar__nav-badge"><?= $stats['in_progress'] ?></span>
            </button>
          </li>
          <li class="sidebar__nav-item">
            <button class="sidebar__nav-link" data-filter-status="completed">
              <span class="sidebar__nav-icon">✅</span>
              <span class="sidebar__nav-text">Tamamlandı</span>
              <span class="sidebar__nav-badge"><?= $stats['completed'] ?></span>
            </button>
          </li>
        </ul>
      </div>

      <!-- Kategoriler -->
      <div class="sidebar__section" style="flex:1">
        <div class="sidebar__section-title">Kategoriler</div>
        <ul class="sidebar__nav" id="categoryList">
          <!-- JS tarafından render edilir -->
        </ul>
        <button class="sidebar__add-cat" id="btnAddCat">
          <span>+</span>
          <span class="sidebar__nav-text">Kategori Ekle</span>
        </button>
      </div>

    </div>

    <!-- Footer: Tema + Çıkış -->
    <div class="sidebar__footer">
      <button class="sidebar__nav-link" id="themeToggle">
        <span class="sidebar__nav-icon">🌙</span>
        <span class="sidebar__nav-text">Tema Değiştir</span>
      </button>
      <a href="<?= BASE_URL ?>/logout.php" class="sidebar__nav-link" id="logoutBtn">
        <span class="sidebar__nav-icon">🚪</span>
        <span class="sidebar__nav-text">Çıkış Yap</span>
      </a>
    </div>

  </aside>

  <!-- Mobil overlay -->
  <div class="sidebar__overlay" id="sidebarOverlay"></div>

  <!-- ============================================================
       TOPBAR
       ============================================================ -->
  <header class="topbar">
    <button class="topbar__hamburger" id="hamburger" aria-label="Menüyü aç">☰</button>

    <span class="topbar__title">TaskFlow</span>

    <!-- Arama -->
    <div class="topbar__search">
      <span class="topbar__search-icon">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <circle cx="6" cy="6" r="4.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M9.5 9.5L12.5 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </span>
      <input
        type="search"
        id="searchInput"
        class="topbar__search-input"
        placeholder="Görev ara…"
        autocomplete="off"
        aria-label="Görev ara"
      />
      <span class="topbar__search-hint">⌘K</span>
    </div>

    <!-- Sağ aksiyonlar -->
    <div class="topbar__actions">
      <!-- Bildirim -->
      <button class="topbar__icon-btn" title="Bildirimler">
        <span>🔔</span>
        <span class="topbar__badge hidden" id="notifBadge">0</span>
      </button>

      <!-- Avatar -->
      <div class="topbar__avatar" title="<?= e($username) ?>"><?= e($initial) ?></div>
    </div>
  </header>

  <!-- ============================================================
       ANA İÇERİK
       ============================================================ -->
  <main class="main">

    <!-- İstatistik Kartları -->
    <div class="stats-grid">
      <div class="stat-card stat-card--total">
        <div class="stat-card__icon-wrap">📋</div>
        <div class="stat-card__body">
          <div class="stat-card__label">Toplam</div>
          <div class="stat-card__value" id="statTotal"><?= $stats['total'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--pending">
        <div class="stat-card__icon-wrap">⏳</div>
        <div class="stat-card__body">
          <div class="stat-card__label">Bekliyor</div>
          <div class="stat-card__value" id="statPending"><?= $stats['pending'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--progress">
        <div class="stat-card__icon-wrap">🔄</div>
        <div class="stat-card__body">
          <div class="stat-card__label">Devam Ediyor</div>
          <div class="stat-card__value" id="statInProgress"><?= $stats['in_progress'] ?></div>
        </div>
      </div>
      <div class="stat-card stat-card--done">
        <div class="stat-card__icon-wrap">✅</div>
        <div class="stat-card__body">
          <div class="stat-card__label">Tamamlandı</div>
          <div class="stat-card__value" id="statCompleted"><?= $stats['completed'] ?></div>
          <div class="stat-card__pct" id="statCompletedPct"></div>
        </div>
      </div>
    </div>

    <!-- Dashboard: Bugünün görevleri + Öncelik dağılımı + Aktivite -->
    <div class="dashboard-grid" id="dashboardGrid">

      <!-- Sol: Bugün -->
      <div class="dashboard-panel">
        <div class="dashboard-panel__header">
          <span class="dashboard-panel__title">📅 Bugünün Görevleri</span>
        </div>
        <div class="dashboard-panel__body" id="todayTaskList">
          <div class="spinner spinner--sm" style="margin:0 auto"></div>
        </div>
      </div>

      <!-- Sağ: Öncelik + Aktivite -->
      <div style="display:flex;flex-direction:column;gap:16px">

        <div class="dashboard-panel">
          <div class="dashboard-panel__header">
            <span class="dashboard-panel__title">📊 Öncelik Dağılımı</span>
          </div>
          <div class="dashboard-panel__body" id="priorityBars">
            <div class="spinner spinner--sm" style="margin:0 auto"></div>
          </div>
        </div>

        <div class="dashboard-panel">
          <div class="dashboard-panel__header">
            <span class="dashboard-panel__title">🕐 Son Aktivite</span>
          </div>
          <div class="dashboard-panel__body" id="activityList">
            <div class="spinner spinner--sm" style="margin:0 auto"></div>
          </div>
        </div>

      </div>
    </div>

    <!-- Toolbar: filtreler + görünüm toggle -->
    <div class="toolbar">
      <span class="toolbar__title">Görevlerim</span>

      <div id="toolbarFilters" style="display:contents">
        <select class="toolbar__select" id="filterStatus">
          <option value="">Tüm Durumlar</option>
          <option value="pending">Bekliyor</option>
          <option value="in_progress">Devam Ediyor</option>
          <option value="completed">Tamamlandı</option>
        </select>

        <select class="toolbar__select" id="filterCategory">
          <option value="">Tüm Kategoriler</option>
        </select>

        <select class="toolbar__select" id="filterPriority">
          <option value="">Tüm Öncelikler</option>
          <option value="high">Yüksek</option>
          <option value="medium">Orta</option>
          <option value="low">Düşük</option>
        </select>

        <select class="toolbar__select" id="filterSort">
          <option value="date_desc">En Yeni</option>
          <option value="date_asc">En Eski</option>
          <option value="priority_desc">Önceliğe Göre</option>
          <option value="due_asc">Son Tarihe Göre</option>
          <option value="alpha_asc">Alfabetik</option>
        </select>
      </div>

      <div class="toolbar__spacer"></div>

      <!-- Görünüm toggle -->
      <div class="view-toggle">
        <button class="view-toggle__btn is-active" data-view="list" title="Liste">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M2 3.5h10M2 7h10M2 10.5h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span>Liste</span>
        </button>
        <button class="view-toggle__btn" data-view="kanban" title="Kanban">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <rect x="1" y="2" width="3.5" height="10" rx="1" stroke="currentColor" stroke-width="1.5"/>
            <rect x="5.25" y="2" width="3.5" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
            <rect x="9.5" y="2" width="3.5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <span>Kanban</span>
        </button>
        <button class="view-toggle__btn" data-view="calendar" title="Takvim">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <rect x="1" y="2.5" width="12" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M1 6h12M4.5 1v3M9.5 1v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span>Takvim</span>
        </button>
      </div>

      <button class="btn btn--primary btn--sm" id="btnAddTask">+ Görev Ekle</button>
    </div>

    <!-- Liste Görünümü -->
    <div id="listView">
      <div class="tasks-list" id="tasksList">
        <!-- JS doldurur -->
      </div>
    </div>

    <!-- Kanban Görünümü -->
    <div id="kanbanView" class="hidden">
      <div class="kanban-board" id="kanbanBoard">
        <!-- JS doldurur -->
      </div>
    </div>

    <!-- Takvim Görünümü -->
    <div id="calendarView" class="hidden">
      <div class="calendar-wrap" id="calendarWrap">
        <div class="calendar-header">
          <button class="btn btn--ghost btn--sm" id="calPrev">‹ Önceki</button>
          <span class="calendar-header__title" id="calTitle"></span>
          <button class="btn btn--ghost btn--sm" id="calToday">Bugün</button>
          <button class="btn btn--ghost btn--sm" id="calNext">Sonraki ›</button>
        </div>
        <div class="calendar-weekdays">
          <?php foreach (['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $d): ?>
            <div class="calendar-weekday"><?= $d ?></div>
          <?php endforeach; ?>
        </div>
        <div class="calendar-grid"></div>
      </div>
    </div>

  </main>
</div>

<!-- ================================================================
     GÖREV DETAY PANELİ
     ================================================================ -->
<div class="detail-panel" id="detailPanel">
  <div class="detail-panel__header">
    <span class="detail-panel__title">Görev Detayı</span>
    <div style="display:flex;gap:6px">
      <button class="btn btn--icon btn--sm" id="detailEditBtn" title="Düzenle">✏️</button>
      <button class="btn btn--icon btn--sm" id="detailPanelClose" title="Kapat">✕</button>
    </div>
  </div>
  <div class="detail-panel__body">
    <div class="detail-panel__task-title">—</div>
    <div class="detail-panel__desc" data-detail="desc">—</div>
    <div class="detail-panel__divider"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="detail-panel__field">
        <div class="detail-panel__label">Durum</div>
        <div class="detail-panel__value" data-detail="status">—</div>
      </div>
      <div class="detail-panel__field">
        <div class="detail-panel__label">Öncelik</div>
        <div class="detail-panel__value" data-detail="priority">—</div>
      </div>
      <div class="detail-panel__field">
        <div class="detail-panel__label">Kategori</div>
        <div class="detail-panel__value" data-detail="category">—</div>
      </div>
      <div class="detail-panel__field">
        <div class="detail-panel__label">Son Tarih</div>
        <div class="detail-panel__value" data-detail="due">—</div>
      </div>
      <div class="detail-panel__field">
        <div class="detail-panel__label">Oluşturulma</div>
        <div class="detail-panel__value" data-detail="created">—</div>
      </div>
      <div class="detail-panel__field">
        <div class="detail-panel__label">Son Güncelleme</div>
        <div class="detail-panel__value" data-detail="updated">—</div>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Görev Ekle / Düzenle
     ================================================================ -->
<div class="modal-overlay" id="taskModalOverlay" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal__header">
      <h2 class="modal__title" id="taskModalTitle">Yeni Görev Ekle</h2>
      <button class="modal__close" onclick="closeModalGlobal('taskModal')" aria-label="Kapat">✕</button>
    </div>
    <div class="modal__body">
      <form id="taskForm" novalidate>
        <input type="hidden" id="taskId" />

        <div class="form-group">
          <label class="form-label" for="taskTitle">Başlık <span style="color:var(--danger)">*</span></label>
          <input type="text" id="taskTitle" class="form-control" placeholder="Görev başlığı…" maxlength="200" required autocomplete="off"/>
        </div>

        <div class="form-group">
          <label class="form-label" for="taskDescription">Açıklama</label>
          <textarea id="taskDescription" class="form-control" rows="3" placeholder="İsteğe bağlı açıklama…"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Öncelik</label>
          <div class="priority-group">
            <button type="button" class="priority-btn" data-value="low">Düşük</button>
            <button type="button" class="priority-btn is-active" data-value="medium">Orta</button>
            <button type="button" class="priority-btn" data-value="high">Yüksek</button>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label" for="taskCategoryId">Kategori</label>
            <select id="taskCategoryId" class="form-control">
              <option value="">Kategori Yok</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="taskDueDate">Son Tarih</label>
            <input type="date" id="taskDueDate" class="form-control" />
          </div>
        </div>

        <div class="form-group hidden" id="taskStatusGroup">
          <label class="form-label" for="taskStatus">Durum</label>
          <select id="taskStatus" class="form-control">
            <option value="pending">Bekliyor</option>
            <option value="in_progress">Devam Ediyor</option>
            <option value="completed">Tamamlandı</option>
          </select>
        </div>

    </div>
    <div class="modal__footer">
      <button type="button" class="btn btn--secondary" onclick="closeModalGlobal('taskModal')">İptal</button>
      <button type="submit" class="btn btn--primary" form="taskForm">Kaydet</button>
    </div>
      </form>
  </div>
</div>

<!-- ================================================================
     MODAL: Kategori Ekle / Düzenle
     ================================================================ -->
<div class="modal-overlay" id="catModalOverlay" role="dialog" aria-modal="true">
  <div class="modal modal--sm">
    <div class="modal__header">
      <h2 class="modal__title" id="catModalTitle">Yeni Kategori</h2>
      <button class="modal__close" onclick="closeModalGlobal('catModal')" aria-label="Kapat">✕</button>
    </div>
    <div class="modal__body">
      <form id="catForm" novalidate>
        <input type="hidden" id="catId" />
        <div class="form-group">
          <label class="form-label" for="catName">Kategori Adı <span style="color:var(--danger)">*</span></label>
          <input type="text" id="catName" class="form-control" placeholder="ör. İş, Kişisel…" maxlength="50" required />
        </div>
        <div class="form-group">
          <label class="form-label">Renk</label>
          <input type="hidden" id="catColorInput" value="#6366F1" />
          <div class="color-presets" id="colorPresets"></div>
        </div>
    </div>
    <div class="modal__footer">
      <button type="button" class="btn btn--secondary" onclick="closeModalGlobal('catModal')">İptal</button>
      <button type="submit" class="btn btn--primary" form="catForm">Kaydet</button>
    </div>
      </form>
  </div>
</div>

<!-- ================================================================
     MODAL: Gün Detayı (Takvim)
     ================================================================ -->
<div class="modal-overlay" id="dayModalOverlay" role="dialog" aria-modal="true">
  <div class="modal modal--sm">
    <div class="modal__header">
      <h2 class="modal__title" id="dayModalTitle">Gün Görevi</h2>
      <button class="modal__close" id="dayModalClose" aria-label="Kapat">✕</button>
    </div>
    <div class="modal__body" id="dayModalList"></div>
  </div>
</div>

<!-- ================================================================
     TOPLU İŞLEM ÇUBUĞU
     ================================================================ -->
<div class="bulk-bar" id="bulkBar">
  <span class="bulk-bar__count" id="bulkCount">0 görev seçildi</span>
  <div class="bulk-bar__divider"></div>
  <button class="bulk-bar__btn" id="bulkComplete">✅ Tamamlandı</button>
  <button class="bulk-bar__btn" id="bulkPending">⏳ Bekliyor</button>
  <button class="bulk-bar__btn bulk-bar__btn--danger" id="bulkDelete">🗑️ Sil</button>
  <div class="bulk-bar__divider"></div>
  <button class="bulk-bar__close" id="bulkClose" title="İptal">✕</button>
</div>

<!-- ================================================================
     KLAVYE KISAYOLLARI PANELİ
     ================================================================ -->
<div class="shortcuts-panel">
  <button class="shortcuts-toggle" id="shortcutsToggle" title="Klavye kısayolları">?</button>
  <div class="shortcuts-list" id="shortcutsList">
    <div class="shortcuts-list__title">Kısayollar</div>
    <div class="shortcut-row"><span>Yeni görev</span><kbd>N</kbd></div>
    <div class="shortcut-row"><span>Arama</span><kbd>⌘K</kbd></div>
    <div class="shortcut-row"><span>Kapat</span><kbd>Esc</kbd></div>
    <div class="shortcut-row"><span>Çoklu seç</span><kbd>Ctrl+Tık</kbd></div>
    <div class="shortcut-row"><span>Bu panel</span><kbd>?</kbd></div>
  </div>
</div>

<!-- ================================================================
     GLOBAL UI
     ================================================================ -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner spinner--lg"></div>
</div>

<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<!-- BASE_URL: JS modülleri için -->
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<!-- Modal global close (inline onclick için) -->
<script>function closeModalGlobal(id){ document.getElementById(id+'Overlay')?.classList.remove('is-visible'); document.body.style.overflow=''; }</script>
<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- Ana JS (ES Module) -->
<script type="module" src="<?= BASE_URL ?>/assets/js/app.js"></script>

</body>
</html>
