/**
 * app.js — TaskFlow Ana Giriş Noktası (ES Module)
 * Tüm modülleri import eder, event'leri bağlar, uygulamayı başlatır.
 */

import { State, setFilter, resetFilters, clearSelection } from './state.js';
import { fetchTasks, createTask, updateTask, deleteCategory as apiDeleteCat,
         createCategory, updateCategory, fetchCategories } from './api.js';
import { showToast } from './ui/toast.js';
import { renderSidebarCategories, initSidebarToggle, restoreSidebarState,
         updateSidebarStats }          from './ui/sidebar.js';
import { loadDashboard, renderStats }  from './ui/dashboard.js';
import { renderTaskList, renderSkeletons, buildTaskCard, computeLocalStats }
                                       from './ui/taskList.js';
import { renderKanban }                from './ui/kanban.js';
import { renderCalendar, prevMonth, nextMonth, goToToday }
                                       from './ui/calendar.js';
import { openTaskModal, openCatModal, closeModal, closeAllModals,
         getTaskFormData, getCatFormData, initModalOverlayClose }
                                       from './ui/modal.js';
import { initBulkBar }                 from './ui/bulkBar.js';
import { initShortcuts }               from './utils/shortcuts.js';
import { debounce }                    from './utils/helpers.js';

const _BASE = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';

/* ── Başlangıç ────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  // CSRF token
  State.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // Sidebar state yükle
  restoreSidebarState();
  initSidebarToggle();

  // Tema
  initTheme();

  // Modal overlay kapatma
  initModalOverlayClose();

  // Bulk bar
  initBulkBar();

  // Keyboard shortcuts
  initShortcuts();

  // Kategorileri yükle, sonra görevleri
  await loadCategories();
  await loadTasks();

  // Dashboard panel (ilk açılış)
  await loadDashboard();

  // Bildirimler (API + Browser)
  scheduleNotifications();
  initNotifDropdown();

  // E-posta doğrulama banner
  initVerifyBanner();

  // Event listener'lar bağla
  bindFilters();
  bindTaskForm();
  bindCatForm();
  bindViewToggle();
  bindCalendarNav();
  bindGlobalEvents();
});

/* ── Kategori Yükleme ────────────────────────────────────── */
async function loadCategories() {
  const res = await fetchCategories();
  if (res.success) {
    State.categories = res.data;
    window._categories = res.data; // modal.js için
    renderSidebarCategories();
  }
}

/* ── Görev Yükleme ───────────────────────────────────────── */
async function loadTasks() {
  renderSkeletons();
  const res = await fetchTasks(State.filters);
  if (!res.success) { showToast(res.message, 'error'); return; }

  State.tasks = res.data;
  renderCurrentView();
  renderStats(computeLocalStats());
  updateSidebarStats(computeLocalStats());
}

/* ── Görünüm Render ──────────────────────────────────────── */
function renderCurrentView() {
  const view = State.currentView;

  document.getElementById('listView')?.classList.toggle('hidden', view !== 'list');
  document.getElementById('kanbanView')?.classList.toggle('hidden', view !== 'kanban');
  document.getElementById('calendarView')?.classList.toggle('hidden', view !== 'calendar');

  if (view === 'list')     renderTaskList(State.tasks);
  else if (view === 'kanban') renderKanban(State.tasks);
  else if (view === 'calendar') renderCalendar();

  // Toolbar filtrelerini göster/gizle
  const toolbarFilters = document.getElementById('toolbarFilters');
  if (toolbarFilters) toolbarFilters.classList.toggle('hidden', view === 'calendar');
}

/* ── Filtreler ────────────────────────────────────────────── */
function bindFilters() {
  // Status dropdown
  document.getElementById('filterStatus')?.addEventListener('change', e => {
    setFilter('status', e.target.value);
    loadTasks();
  });

  // Category dropdown
  document.getElementById('filterCategory')?.addEventListener('change', e => {
    setFilter('category_id', e.target.value);
    loadTasks();
  });

  // Priority dropdown
  document.getElementById('filterPriority')?.addEventListener('change', e => {
    setFilter('priority', e.target.value);
    loadTasks();
  });

  // Sort dropdown
  document.getElementById('filterSort')?.addEventListener('change', e => {
    setFilter('sort', e.target.value);
    loadTasks();
  });

  // Arama (debounce)
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(e => {
      setFilter('search', e.target.value.trim());
      loadTasks();
    }, 300));
  }

  // Sidebar durum filtreleri
  document.querySelectorAll('[data-filter-status]').forEach(btn => {
    btn.addEventListener('click', () => {
      const status = btn.dataset.filterStatus;
      setFilter('status', status);
      document.getElementById('filterStatus').value = status;
      loadTasks();
      setActiveSidebarBtn(btn);
    });
  });

  // "Tüm Görevler" butonu
  document.getElementById('linkAllTasks')?.addEventListener('click', () => {
    resetFilters();
    document.getElementById('filterStatus').value   = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterPriority').value = '';
    document.getElementById('filterSort').value     = 'date_desc';
    document.getElementById('searchInput').value    = '';
    loadTasks();
    setActiveSidebarBtn(document.getElementById('linkAllTasks'));
  });

  // Sidebar kategori filtresi
  window.addEventListener('filterCategory', e => {
    const id = e.detail;
    const isActive = String(State.filters.category_id) === String(id);
    setFilter('category_id', isActive ? '' : id);
    document.getElementById('filterCategory').value = isActive ? '' : id;
    loadTasks();
  });

  // Görev yeniden yükle
  window.addEventListener('reloadTasks', loadTasks);
}

function setActiveSidebarBtn(activeBtn) {
  document.querySelectorAll('.sidebar__nav-link').forEach(b => b.classList.remove('is-active'));
  activeBtn?.classList.add('is-active');
}

/* ── Görev Formu ─────────────────────────────────────────── */
function bindTaskForm() {
  // Yeni görev butonu
  document.getElementById('btnAddTask')?.addEventListener('click', () => openTaskModal());

  // Form submit
  document.getElementById('taskForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const data = getTaskFormData();

    if (!data.title) {
      const input = document.getElementById('taskTitle');
      input.classList.add('is-shaking');
      input.addEventListener('animationend', () => input.classList.remove('is-shaking'), { once: true });
      showToast('Görev başlığı zorunludur.', 'error');
      return;
    }

    const isEdit = !!data.id;
    const res    = isEdit ? await updateTask(data) : await createTask(data);

    if (res.success) {
      closeModal('taskModal');
      showToast(res.message, 'success');
      await loadTasks();
      await loadCategories();
    } else {
      showToast(res.message, 'error');
    }
  });

  // Öncelik butonları
  document.querySelectorAll('.priority-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.priority-btn').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
    });
  });
}

/* ── Kategori Formu ──────────────────────────────────────── */
function bindCatForm() {
  document.getElementById('btnAddCat')?.addEventListener('click', () => openCatModal());

  document.getElementById('catForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const data = getCatFormData();

    if (!data.name) { showToast('Kategori adı zorunludur.', 'error'); return; }

    const res = data.id
      ? await updateCategory(data.id, data.name, data.color)
      : await createCategory(data.name, data.color);

    if (res.success) {
      closeModal('catModal');
      showToast(res.message, 'success');
      await loadCategories();
      await loadTasks();
    } else {
      showToast(res.message, 'error');
    }
  });

  // Kategori sil
  window.addEventListener('deleteCategory', async e => {
    const id = e.detail;
    if (!confirm('Bu kategori silinsin mi? Görevler kategorisiz kalır.')) return;
    const res = await apiDeleteCat(id);
    if (res.success) {
      showToast('Kategori silindi.', 'success');
      await loadCategories();
      await loadTasks();
    } else {
      showToast(res.message, 'error');
    }
  });
}

/* ── Görünüm Toggle ──────────────────────────────────────── */
function bindViewToggle() {
  document.querySelectorAll('[data-view]').forEach(btn => {
    btn.addEventListener('click', () => {
      State.currentView = btn.dataset.view;
      document.querySelectorAll('[data-view]').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      renderCurrentView();
    });
  });
}

/* ── Takvim Navigasyonu ──────────────────────────────────── */
function bindCalendarNav() {
  document.getElementById('calPrev')?.addEventListener('click', prevMonth);
  document.getElementById('calNext')?.addEventListener('click', nextMonth);
  document.getElementById('calToday')?.addEventListener('click', goToToday);
}

/* ── Genel Event'ler ─────────────────────────────────────── */
function bindGlobalEvents() {
  // Tema toggle (topbar)
  document.getElementById('themeToggle')?.addEventListener('click', toggleTheme);

  // Çıkış
  document.getElementById('logoutBtn')?.addEventListener('click', e => {
    e.preventDefault();
    window.location.href = _BASE + '/logout.php';
  });

  // Detail panel kapat
  document.getElementById('detailPanelClose')?.addEventListener('click', () => {
    document.getElementById('detailPanel')?.classList.remove('is-open');
  });

  // Detail panel düzenle
  document.getElementById('detailEditBtn')?.addEventListener('click', () => {
    const task = State.tasks.find(t => t.id === State.detailTaskId);
    if (task) openTaskModal(task);
  });

  // Day modal kapat
  document.getElementById('dayModalClose')?.addEventListener('click', () => {
    document.getElementById('dayModalOverlay')?.classList.remove('is-visible');
  });
  document.getElementById('dayModalOverlay')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) {
      e.currentTarget.classList.remove('is-visible');
    }
  });
}

/* ── Tema ────────────────────────────────────────────────── */
function initTheme() {
  const saved = localStorage.getItem('theme') ?? 'light';
  applyTheme(saved);
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
  const btn = document.getElementById('themeToggle');
  if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') ?? 'light';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

/* ── Bildirim Dropdown (API) ─────────────────────────────── */
function initNotifDropdown() {
  const btn      = document.getElementById('notifBtn');
  const dropdown = document.getElementById('notifDropdown');
  const markAll  = document.getElementById('markAllReadBtn');
  if (!btn || !dropdown) return;

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isHidden = dropdown.classList.contains('hidden');
    dropdown.classList.toggle('hidden', !isHidden);
    if (isHidden) loadNotifications();
  });

  document.addEventListener('click', (e) => {
    if (!document.getElementById('notifWrap')?.contains(e.target)) {
      dropdown?.classList.add('hidden');
    }
  });

  markAll?.addEventListener('click', async () => {
    await fetch(_BASE + '/api/notifications.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'mark_all_read' }),
    });
    loadNotifications();
  });

  // 60s polling
  setInterval(loadNotifications, 60 * 1000);
  loadNotifications();
}

async function loadNotifications() {
  try {
    const res  = await fetch(_BASE + '/api/notifications.php?limit=15');
    const data = await res.json();
    if (!data.success) return;

    const { notifications, unread_count } = data.data;

    // Badge güncelle
    const badge = document.getElementById('notifBadge');
    if (badge) {
      badge.textContent = unread_count > 9 ? '9+' : unread_count;
      badge.classList.toggle('hidden', unread_count === 0);
    }

    // Liste render
    const list = document.getElementById('notifList');
    if (!list) return;

    if (!notifications.length) {
      list.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text-muted);font-size:.875rem">Bildirim yok</div>';
      return;
    }

    list.innerHTML = notifications.map(n => {
      const unread = n.is_read == 0;
      return `<div class="notif-item ${unread ? 'notif-item--unread' : 'notif-item--read'}"
                   data-id="${n.id}" data-unread="${unread ? 1 : 0}">
        <div class="notif-item__dot"></div>
        <div class="notif-item__body">
          <div class="notif-item__title">${escHtml(n.title)}</div>
          ${n.message ? `<div class="notif-item__msg">${escHtml(n.message)}</div>` : ''}
        </div>
        <div class="notif-item__time">${timeAgoShort(n.created_at)}</div>
      </div>`;
    }).join('');

    // Tıklanınca okundu işaretle
    list.querySelectorAll('.notif-item').forEach(item => {
      item.addEventListener('click', async () => {
        if (item.dataset.unread === '1') {
          await fetch(_BASE + '/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', id: parseInt(item.dataset.id) }),
          });
          item.classList.remove('notif-item--unread');
          item.classList.add('notif-item--read');
          item.dataset.unread = '0';
          loadNotifications();
        }
      });
    });
  } catch (_) { /* sessiz */ }
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function timeAgoShort(dt) {
  const diff = Date.now() - new Date(dt).getTime();
  const m = Math.floor(diff / 60000);
  if (m < 1) return 'şimdi';
  if (m < 60) return m + 'dk';
  const h = Math.floor(m / 60);
  if (h < 24) return h + 's';
  return Math.floor(h / 24) + 'g';
}

/* ── E-posta Doğrulama Banner ────────────────────────────── */
async function initVerifyBanner() {
  const banner = document.getElementById('verifyBanner');
  const closeBtn = document.getElementById('closeBanner');
  if (!banner) return;

  // Kapatma butonunu bağla
  closeBtn?.addEventListener('click', () => {
    banner.classList.add('hidden');
    sessionStorage.setItem('verifyBannerDismissed', '1');
  });

  // Dismiss edildiyse gösterme
  if (sessionStorage.getItem('verifyBannerDismissed')) return;

  try {
    const res  = await fetch(_BASE + '/api/settings.php');
    const data = await res.json();
    if (!data.success) return;
    const verified = data.data?.user?.email_verified;
    if (!verified || verified == 0) {
      banner.classList.remove('hidden');
    }
  } catch (_) { /* sessiz */ }
}

/* ── Browser Bildirimleri ────────────────────────────────── */
function scheduleNotifications() {
  if (!('Notification' in window)) return;

  // İzin iste
  if (Notification.permission === 'default') {
    Notification.requestPermission();
  }

  // 30 dakikada bir kontrol et
  setInterval(checkDueNotifications, 30 * 60 * 1000);
  checkDueNotifications();
}

function checkDueNotifications() {
  if (Notification.permission !== 'granted') return;

  const today = new Date().toISOString().split('T')[0];
  const dueTasks = State.tasks.filter(t =>
    t.due_date === today && t.status !== 'completed'
  );

  if (!dueTasks.length) return;

  const notifCount = dueTasks.length;

  // Tek bildirim
  if (State.notifBadgeCount !== notifCount) {
    State.notifBadgeCount = notifCount;
    new Notification('TaskFlow — Bugün bitiş tarihi olan görevler', {
      body: `${notifCount} görevinizin bugün teslim tarihi var.`,
      icon: '/favicon.svg',
    });
  }
}
