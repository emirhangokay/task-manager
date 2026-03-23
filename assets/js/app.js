/**
 * assets/js/app.js
 * ============================================================
 * Görev Yönetim Uygulaması — Ana JavaScript Dosyası
 * Tüm AJAX çağrıları, DOM manipülasyonu ve UI mantığı burada.
 * Fetch API kullanılır; sayfa yenilemesi yoktur.
 * ============================================================
 */

/* ============================================================
   GLOBAL STATE
   ============================================================ */
const State = {
  csrfToken:  document.querySelector('meta[name="csrf-token"]')?.content ?? '',
  filters: {
    status:      '',
    category_id: '',
    priority:    '',
    search:      '',
    sort:        'date_desc',
  },
  editingTaskId: null,
  categories: [],
};

/* ============================================================
   TEMA YÖNETİMİ
   ============================================================ */

/**
 * Mevcut temayı localStorage'dan yükler ve uygular.
 */
function initTheme() {
  const saved = localStorage.getItem('theme') ?? 'light';
  applyTheme(saved);
}

/**
 * Belirtilen temayı uygular ve localStorage'a kaydeder.
 * @param {string} theme - 'light' | 'dark'
 */
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
  const btn = document.getElementById('themeToggle');
  if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
}

/**
 * Temayı toggle eder.
 */
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') ?? 'light';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

/* ============================================================
   API YARDIMCI FONKSİYONLARI
   ============================================================ */

/**
 * JSON döndüren bir API isteği atar.
 * @param {string} url
 * @param {object} options - fetch seçenekleri
 * @returns {Promise<object>}
 */
async function apiFetch(url, options = {}) {
  showLoading(true);
  try {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', ...(options.headers ?? {}) },
      ...options,
    });
    const data = await res.json();
    return data;
  } catch (err) {
    return { success: false, message: 'Sunucu bağlantı hatası.' };
  } finally {
    showLoading(false);
  }
}

/* ============================================================
   YÜKLENİYOR GÖSTERGESİ
   ============================================================ */

/**
 * Global loading overlay'i göster / gizle.
 * @param {boolean} visible
 */
function showLoading(visible) {
  const el = document.getElementById('loadingOverlay');
  if (el) el.classList.toggle('is-visible', visible);
}

/* ============================================================
   TOAST BİLDİRİMLERİ
   ============================================================ */

/**
 * Ekranda toast bildirimi gösterir.
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 * @param {number} duration - ms
 */
function showToast(message, type = 'success', duration = 3500) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast toast--${type}`;
  toast.innerHTML = `<span>${icons[type]}</span><span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('is-hiding');
    setTimeout(() => toast.remove(), 350);
  }, duration);
}

/* ============================================================
   KATEGORİLER
   ============================================================ */

/**
 * Kategorileri API'den çeker ve global State'e kaydeder.
 */
async function loadCategories() {
  const res = await apiFetch('/api/categories.php');
  if (res.success) {
    State.categories = res.data ?? [];
    renderSidebarCategories();
    renderCategorySelects();
  }
}

/**
 * Sidebar kategori listesini yeniden çizer.
 */
function renderSidebarCategories() {
  const list = document.getElementById('categoryList');
  if (!list) return;

  list.innerHTML = '';

  State.categories.forEach(cat => {
    const li = document.createElement('li');
    li.className = 'sidebar__nav-item';
    li.innerHTML = `
      <button class="sidebar__nav-link" data-cat-id="${cat.id}">
        <span class="sidebar__cat-dot" style="background:${escHtml(cat.color)}"></span>
        <span>${escHtml(cat.name)}</span>
        <span class="sidebar__cat-actions">
          <button class="btn btn--icon" style="font-size:.75rem" onclick="openEditCatModal(${cat.id})" title="Düzenle">✏️</button>
          <button class="btn btn--icon" style="font-size:.75rem" onclick="deleteCategory(${cat.id})" title="Sil">🗑️</button>
        </span>
      </button>
    `;
    li.querySelector('[data-cat-id]').addEventListener('click', (e) => {
      if (e.target.closest('.sidebar__cat-actions')) return;
      filterByCategory(cat.id);
    });
    list.appendChild(li);
  });
}

/**
 * Kategori select elementlerini günceller.
 */
function renderCategorySelects() {
  const selects = document.querySelectorAll('.js-category-select');
  selects.forEach(sel => {
    const current = sel.value;
    const firstOption = sel.querySelector('option:first-child');
    sel.innerHTML = '';
    if (firstOption) sel.appendChild(firstOption);

    State.categories.forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = cat.name;
      if (String(cat.id) === String(current)) opt.selected = true;
      sel.appendChild(opt);
    });
  });

  // Sidebar filtre için de güncelle
  const filterSel = document.getElementById('filterCategory');
  if (filterSel) {
    const cur = filterSel.value;
    filterSel.innerHTML = '<option value="">Tüm Kategoriler</option>';
    State.categories.forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = cat.name;
      if (String(cat.id) === String(cur)) opt.selected = true;
      filterSel.appendChild(opt);
    });
  }
}

/**
 * Sidebar'da kategori filtresi uygular.
 * @param {number} catId
 */
function filterByCategory(catId) {
  State.filters.category_id = String(catId);
  const filterSel = document.getElementById('filterCategory');
  if (filterSel) filterSel.value = catId;
  updateActiveNavLink(null, catId);
  loadTasks();
}

/**
 * Kategori silme.
 * @param {number} id
 */
async function deleteCategory(id) {
  if (!confirm('Bu kategoriyi silmek istiyor musunuz? Görevler kategorisiz kalacak.')) return;

  const res = await apiFetch('/api/categories.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', id, csrf_token: State.csrfToken }),
  });

  if (res.success) {
    showToast(res.message, 'success');
    await loadCategories();
    if (State.filters.category_id === String(id)) {
      State.filters.category_id = '';
    }
    loadTasks();
  } else {
    showToast(res.message, 'error');
  }
}

/* ============================================================
   KATEGORİ MODAL
   ============================================================ */

/** Yeni kategori ekle modalını açar */
function openAddCatModal() {
  document.getElementById('catModalTitle').textContent = 'Yeni Kategori';
  document.getElementById('catId').value = '';
  document.getElementById('catName').value = '';
  selectColorPreset('#6B7280');
  openModal('catModal');
}

/**
 * Düzenle modalını açar.
 * @param {number} id
 */
function openEditCatModal(id) {
  const cat = State.categories.find(c => c.id === id);
  if (!cat) return;
  document.getElementById('catModalTitle').textContent = 'Kategori Düzenle';
  document.getElementById('catId').value = cat.id;
  document.getElementById('catName').value = cat.name;
  selectColorPreset(cat.color);
  openModal('catModal');
}

/** Kategori kaydet (yeni veya güncelle) */
async function saveCategoryForm(e) {
  e.preventDefault();
  const id    = document.getElementById('catId').value;
  const name  = document.getElementById('catName').value.trim();
  const color = document.getElementById('catColorInput').value;

  if (!name) { showToast('Kategori adı zorunludur.', 'error'); return; }

  const action = id ? 'update' : 'create';
  const body   = { action, name, color, csrf_token: State.csrfToken };
  if (id) body.id = parseInt(id);

  const res = await apiFetch('/api/categories.php', {
    method: 'POST',
    body: JSON.stringify(body),
  });

  if (res.success) {
    showToast(res.message, 'success');
    closeModal('catModal');
    await loadCategories();
    loadTasks();
  } else {
    showToast(res.message, 'error');
  }
}

/* ============================================================
   GÖREVLER
   ============================================================ */

/**
 * Filtrelere göre görevleri yükler ve render eder.
 */
async function loadTasks() {
  const params = new URLSearchParams();
  Object.entries(State.filters).forEach(([k, v]) => { if (v) params.append(k, v); });

  const res = await apiFetch(`/api/tasks.php?${params}`);
  if (res.success) {
    renderTasks(res.data ?? []);
  } else {
    showToast(res.message, 'error');
  }
}

/**
 * Görev listesini DOM'a yazar.
 * @param {Array} tasks
 */
function renderTasks(tasks) {
  const container = document.getElementById('tasksList');
  if (!container) return;

  if (tasks.length === 0) {
    container.innerHTML = `
      <div class="tasks-empty">
        <div class="tasks-empty__icon">📋</div>
        <div class="tasks-empty__title">Görev bulunamadı</div>
        <p>Yeni bir görev eklemek için "Görev Ekle" butonuna tıklayın.</p>
      </div>`;
    return;
  }

  container.innerHTML = tasks.map(task => buildTaskCard(task)).join('');

  // Checkbox event listener'ları
  container.querySelectorAll('.task-card__check').forEach(chk => {
    chk.addEventListener('change', handleCheckboxChange);
  });

  // Durum select listener'ları
  container.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', handleStatusSelectChange);
  });
}

/**
 * Tek bir görev kartının HTML'ini üretir.
 * @param {object} task
 * @returns {string}
 */
function buildTaskCard(task) {
  const isOverdue   = task.is_overdue;
  const isCompleted = task.status === 'completed';
  const isToday     = task.due_date === getTodayDate();

  const cardClass = [
    'task-card',
    isOverdue   ? 'task-card--overdue'   : '',
    isCompleted ? 'task-card--completed' : '',
  ].filter(Boolean).join(' ');

  const catBadge = task.category_name
    ? `<span class="badge badge--cat" style="background:${escHtml(task.category_color)}">${escHtml(task.category_name)}</span>`
    : '';

  const dueDateHtml = task.due_date
    ? `<span class="task-card__due ${isOverdue ? 'task-card__due--overdue' : isToday ? 'task-card__due--today' : ''}">
         📅 ${isOverdue ? 'Gecikti: ' : isToday ? 'Bugün: ' : ''}${formatDate(task.due_date)}
       </span>`
    : '';

  const nextStatus = {
    pending:     'in_progress',
    in_progress: 'completed',
    completed:   'pending',
  }[task.status] ?? 'pending';

  return `
    <div class="task-card ${cardClass}" data-id="${task.id}">
      <div class="task-card__checkbox">
        <input type="checkbox" class="task-card__check"
               data-id="${task.id}"
               ${isCompleted ? 'checked' : ''}
               title="${isCompleted ? 'Tamamlanmadı işaretle' : 'Tamamlandı işaretle'}" />
      </div>
      <div class="task-card__body">
        <div class="task-card__title">${escHtml(task.title)}</div>
        ${task.description ? `<div class="task-card__desc">${escHtml(task.description)}</div>` : ''}
        <div class="task-card__meta">
          ${catBadge}
          <span class="badge badge--priority-${task.priority}">${task.priority_label}</span>
          <select class="status-select" data-id="${task.id}" title="Durum değiştir">
            <option value="pending"     ${task.status === 'pending'     ? 'selected' : ''}>Bekliyor</option>
            <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>Devam Ediyor</option>
            <option value="completed"   ${task.status === 'completed'   ? 'selected' : ''}>Tamamlandı</option>
          </select>
          ${dueDateHtml}
        </div>
      </div>
      <div class="task-card__actions">
        <button class="btn btn--icon" onclick="openEditTaskModal(${task.id})" title="Düzenle">✏️</button>
        <button class="btn btn--icon" onclick="deleteTask(${task.id})" title="Sil">🗑️</button>
      </div>
    </div>`;
}

/**
 * Checkbox değiştiğinde tamamlama durumunu toggle eder.
 * @param {Event} e
 */
async function handleCheckboxChange(e) {
  const chk    = e.target;
  const taskId = parseInt(chk.dataset.id);
  const newStatus = chk.checked ? 'completed' : 'pending';

  const res = await apiFetch('/api/tasks.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'toggle_status', id: taskId, new_status: newStatus, csrf_token: State.csrfToken }),
  });

  if (res.success) {
    // Kartı güncelle (yeniden yükleme yerine sadece sınıf değiştir)
    const card = chk.closest('.task-card');
    if (card) {
      card.classList.toggle('task-card--completed', newStatus === 'completed');
      const sel = card.querySelector('.status-select');
      if (sel) sel.value = newStatus;
    }
    showToast(res.message, 'success');
  } else {
    chk.checked = !chk.checked; // Geri al
    showToast(res.message, 'error');
  }
}

/**
 * Durum select'i değiştiğinde durumu günceller.
 * @param {Event} e
 */
async function handleStatusSelectChange(e) {
  const sel      = e.target;
  const taskId   = parseInt(sel.dataset.id);
  const newStatus = sel.value;

  const res = await apiFetch('/api/tasks.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'toggle_status', id: taskId, new_status: newStatus, csrf_token: State.csrfToken }),
  });

  if (res.success) {
    const card = sel.closest('.task-card');
    if (card) {
      card.classList.toggle('task-card--completed', newStatus === 'completed');
      const chk = card.querySelector('.task-card__check');
      if (chk) chk.checked = newStatus === 'completed';
    }
    showToast(res.message, 'success');
  } else {
    showToast(res.message, 'error');
    loadTasks(); // Başarısızsa yeniden yükle
  }
}

/**
 * Görev siler.
 * @param {number} id
 */
async function deleteTask(id) {
  if (!confirm('Bu görevi silmek istediğinizden emin misiniz?')) return;

  const res = await apiFetch('/api/tasks.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', id, csrf_token: State.csrfToken }),
  });

  if (res.success) {
    const card = document.querySelector(`.task-card[data-id="${id}"]`);
    if (card) {
      card.style.opacity = '0';
      card.style.transform = 'scale(.95)';
      card.style.transition = 'opacity .2s, transform .2s';
      setTimeout(() => { card.remove(); checkEmptyState(); }, 200);
    }
    showToast(res.message, 'success');
    loadStats();
  } else {
    showToast(res.message, 'error');
  }
}

/** Görev listesi boş mu kontrol eder */
function checkEmptyState() {
  const container = document.getElementById('tasksList');
  if (!container) return;
  if (container.querySelectorAll('.task-card').length === 0) {
    container.innerHTML = `
      <div class="tasks-empty">
        <div class="tasks-empty__icon">📋</div>
        <div class="tasks-empty__title">Görev bulunamadı</div>
        <p>Yeni bir görev eklemek için "Görev Ekle" butonuna tıklayın.</p>
      </div>`;
  }
}

/* ============================================================
   GÖREV MODAL
   ============================================================ */

/** Yeni görev ekleme modalını açar */
function openAddTaskModal() {
  State.editingTaskId = null;
  document.getElementById('taskModalTitle').textContent = 'Yeni Görev Ekle';
  document.getElementById('taskForm').reset();
  document.getElementById('taskId').value = '';
  document.getElementById('taskStatus').closest('.form-group').classList.add('hidden');
  openModal('taskModal');
}

/**
 * Görev düzenleme modalını açar.
 * @param {number} id
 */
async function openEditTaskModal(id) {
  // Görev verilerini çek
  const res = await apiFetch(`/api/tasks.php?id=${id}`);
  let task;
  if (res.success && Array.isArray(res.data)) {
    task = res.data.find(t => t.id === id);
  }

  // Eğer bulunamadıysa tüm listeyi çekmeyi dene
  if (!task) {
    const all = await apiFetch('/api/tasks.php');
    if (all.success) task = (all.data ?? []).find(t => t.id === id);
  }

  if (!task) { showToast('Görev yüklenemedi.', 'error'); return; }

  State.editingTaskId = id;
  document.getElementById('taskModalTitle').textContent = 'Görevi Düzenle';
  document.getElementById('taskId').value            = task.id;
  document.getElementById('taskTitle').value         = task.title;
  document.getElementById('taskDescription').value   = task.description ?? '';
  document.getElementById('taskPriority').value      = task.priority;
  document.getElementById('taskStatus').value        = task.status;
  document.getElementById('taskDueDate').value       = task.due_date ?? '';
  document.getElementById('taskCategoryId').value    = task.category_id ?? '';
  document.getElementById('taskStatus').closest('.form-group').classList.remove('hidden');

  openModal('taskModal');
}

/** Görev formunu kaydeder (yeni veya güncelleme) */
async function saveTaskForm(e) {
  e.preventDefault();

  const id          = document.getElementById('taskId').value;
  const title       = document.getElementById('taskTitle').value.trim();
  const description = document.getElementById('taskDescription').value.trim();
  const priority    = document.getElementById('taskPriority').value;
  const status      = document.getElementById('taskStatus').value;
  const dueDate     = document.getElementById('taskDueDate').value;
  const categoryId  = document.getElementById('taskCategoryId').value;

  if (!title) {
    showToast('Görev başlığı zorunludur.', 'error');
    document.getElementById('taskTitle').focus();
    return;
  }

  const action = id ? 'update' : 'create';
  const body   = { action, title, description, priority, due_date: dueDate, category_id: categoryId || null, csrf_token: State.csrfToken };
  if (id) { body.id = parseInt(id); body.status = status; }

  const res = await apiFetch('/api/tasks.php', {
    method: 'POST',
    body: JSON.stringify(body),
  });

  if (res.success) {
    showToast(res.message, 'success');
    closeModal('taskModal');
    loadTasks();
    loadStats();
  } else {
    showToast(res.message, 'error');
  }
}

/* ============================================================
   İSTATİSTİKLER
   ============================================================ */

/**
 * Dashboard istatistik kartlarını günceller.
 */
async function loadStats() {
  const res = await apiFetch('/api/tasks.php?stats=1');
  if (!res.success) return;

  // PHP tarafından stats endpoint'i yoksa görevleri sayalım
  const tasks = res.data ?? [];
  const total      = tasks.length;
  const pending    = tasks.filter(t => t.status === 'pending').length;
  const inProgress = tasks.filter(t => t.status === 'in_progress').length;
  const completed  = tasks.filter(t => t.status === 'completed').length;
  const overdue    = tasks.filter(t => t.is_overdue).length;

  setStatCard('statTotal',      total);
  setStatCard('statPending',    pending);
  setStatCard('statInProgress', inProgress);
  setStatCard('statCompleted',  completed);
  setStatCard('statOverdue',    overdue);
}

/**
 * Stat kartındaki sayıyı günceller.
 * @param {string} id
 * @param {number} value
 */
function setStatCard(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

/* ============================================================
   FİLTRELEME & SIRALAMA
   ============================================================ */

/** Tüm filtreleri ve sidebar aktif linkini günceller */
function initFilters() {
  const selMap = {
    filterStatus:   'status',
    filterCategory: 'category_id',
    filterPriority: 'priority',
    filterSort:     'sort',
  };

  Object.entries(selMap).forEach(([elId, key]) => {
    const el = document.getElementById(elId);
    if (!el) return;
    el.addEventListener('change', () => {
      State.filters[key] = el.value;
      loadTasks();
    });
  });

  // Arama (debounced)
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        State.filters.search = searchInput.value.trim();
        loadTasks();
      }, 350);
    });
  }
}

/**
 * Sidebar nav linkini aktif yapar.
 * @param {string|null} status
 * @param {number|null} catId
 */
function updateActiveNavLink(status, catId) {
  document.querySelectorAll('.sidebar__nav-link').forEach(el => el.classList.remove('is-active'));

  if (status !== null) {
    const el = document.querySelector(`[data-filter-status="${status}"]`);
    if (el) el.classList.add('is-active');
  }
  if (catId !== null) {
    const el = document.querySelector(`[data-cat-id="${catId}"]`);
    if (el) el.classList.add('is-active');
  }
}

/* ============================================================
   MODAL YARDIMCILARI
   ============================================================ */

/**
 * Modalı açar.
 * @param {string} id
 */
function openModal(id) {
  const overlay = document.getElementById(id + 'Overlay') ?? document.getElementById(id);
  if (overlay) {
    overlay.classList.add('is-open');
    // İlk input'a focus
    setTimeout(() => overlay.querySelector('input:not([type=hidden]),.form-control')?.focus(), 50);
  }
}

/**
 * Modalı kapatır.
 * @param {string} id
 */
function closeModal(id) {
  const overlay = document.getElementById(id + 'Overlay') ?? document.getElementById(id);
  if (overlay) overlay.classList.remove('is-open');
}

/* ============================================================
   RENK PRESET SEÇİCİ
   ============================================================ */
const PRESET_COLORS = [
  '#6B7280','#EF4444','#F59E0B','#22C55E',
  '#3B82F6','#8B5CF6','#EC4899','#14B8A6',
  '#F97316','#06B6D4','#84CC16','#6366F1',
];

/** Renk preset butonlarını oluşturur */
function initColorPresets() {
  const container = document.getElementById('colorPresets');
  if (!container) return;

  PRESET_COLORS.forEach(color => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'color-preset';
    btn.style.background = color;
    btn.dataset.color = color;
    btn.title = color;
    btn.addEventListener('click', () => selectColorPreset(color));
    container.appendChild(btn);
  });
}

/**
 * Renk presetini seçer ve input'u günceller.
 * @param {string} color
 */
function selectColorPreset(color) {
  document.querySelectorAll('.color-preset').forEach(btn => {
    btn.classList.toggle('is-selected', btn.dataset.color === color);
  });
  const input = document.getElementById('catColorInput');
  if (input) input.value = color;
}

/* ============================================================
   SIDEBAR MOBİL
   ============================================================ */

function initSidebar() {
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebarOverlay');
  if (!hamburger || !sidebar) return;

  hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('is-open');
    if (overlay) overlay.classList.toggle('is-visible');
  });

  overlay?.addEventListener('click', closeSidebar);
}

function closeSidebar() {
  document.getElementById('sidebar')?.classList.remove('is-open');
  document.getElementById('sidebarOverlay')?.classList.remove('is-visible');
}

/* ============================================================
   YARDIMCI FONKSİYONLAR
   ============================================================ */

/**
 * HTML özel karakterleri escape eder (XSS koruması).
 * @param {string} str
 * @returns {string}
 */
function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * ISO tarih stringini Türkçe formatla gösterir.
 * @param {string} dateStr - YYYY-MM-DD
 * @returns {string}
 */
function formatDate(dateStr) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-');
  return `${d}.${m}.${y}`;
}

/**
 * Bugünün tarihini YYYY-MM-DD formatında döndürür.
 * @returns {string}
 */
function getTodayDate() {
  return new Date().toISOString().slice(0, 10);
}

/* ============================================================
   GLOBAL OLAYLAR
   ============================================================ */

/** ESC tuşu ve modal dışına tıklama ile kapatma */
function initModalClose() {
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.is-open').forEach(el => {
        el.classList.remove('is-open');
      });
    }
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('is-open');
    });
  });
}

/* ============================================================
   BAŞLATMA
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  // Tema
  initTheme();
  document.getElementById('themeToggle')?.addEventListener('click', toggleTheme);

  // Modal kapat
  initModalClose();

  // Sidebar mobil
  initSidebar();

  // Filtre eventleri
  initFilters();

  // Renk presetleri
  initColorPresets();

  // Tema yükle (index sayfası için)
  if (document.getElementById('tasksList')) {
    await loadCategories();
    await loadTasks();
    await loadStats(); // İstatistikler görevlerden hesaplanır
  }

  // Sidebar hızlı filtre linkleri
  document.querySelectorAll('[data-filter-status]').forEach(btn => {
    btn.addEventListener('click', () => {
      const status = btn.dataset.filterStatus;
      State.filters.status = status;
      const filterSel = document.getElementById('filterStatus');
      if (filterSel) filterSel.value = status;
      updateActiveNavLink(status, null);
      loadTasks();
    });
  });

  // Tüm görevler linki
  document.getElementById('linkAllTasks')?.addEventListener('click', () => {
    State.filters.status      = '';
    State.filters.category_id = '';
    const fs = document.getElementById('filterStatus');
    const fc = document.getElementById('filterCategory');
    if (fs) fs.value = '';
    if (fc) fc.value = '';
    updateActiveNavLink('', null);
    loadTasks();
  });

  // Görev form submit
  document.getElementById('taskForm')?.addEventListener('submit', saveTaskForm);

  // Kategori form submit
  document.getElementById('catForm')?.addEventListener('submit', saveCategoryForm);

  // Görev ekle butonu
  document.getElementById('btnAddTask')?.addEventListener('click', openAddTaskModal);

  // Kategori ekle butonu
  document.getElementById('btnAddCat')?.addEventListener('click', openAddCatModal);

  // Logout onayı
  document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
    if (!confirm('Çıkış yapmak istiyor musunuz?')) e.preventDefault();
  });
});
