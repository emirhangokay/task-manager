/**
 * sidebar.js — Sidebar render ve etkileşimler
 */

import { State }       from '../state.js';
import { escHtml }     from '../utils/helpers.js';
import { openCatModal } from './modal.js';

/** Kategori listesini sidebar'a render eder */
export function renderSidebarCategories() {
  const list = document.getElementById('categoryList');
  if (!list) return;

  // Kategori filtre selectini de güncelle
  updateCategoryFilterSelect();

  if (!State.categories.length) {
    list.innerHTML = '<li style="padding:6px 18px;font-size:.8125rem;color:var(--text-tertiary)">Kategori yok</li>';
    return;
  }

  list.innerHTML = '';

  State.categories.forEach(cat => {
    const li  = document.createElement('li');
    li.className = 'sidebar__nav-item';

    // Görev sayısı badge'i
    const count = State.tasks.filter(t => t.category_id == cat.id).length;

    li.innerHTML = `
      <div class="sidebar__nav-link" data-cat-id="${cat.id}" role="button" tabindex="0">
        <span class="sidebar__cat-dot" style="background:${escHtml(cat.color)}"></span>
        <span class="sidebar__nav-text">${escHtml(cat.name)}</span>
        ${count > 0 ? `<span class="sidebar__nav-badge">${count}</span>` : ''}
        <span class="sidebar__cat-actions">
          <button class="sidebar__cat-btn" data-edit="${cat.id}" title="Düzenle" aria-label="Düzenle">✏️</button>
          <button class="sidebar__cat-btn" data-del="${cat.id}"  title="Sil"     aria-label="Sil">🗑️</button>
        </span>
      </div>
    `;

    // Aktif kategori vurgula
    if (String(State.filters.category_id) === String(cat.id)) {
      li.querySelector('.sidebar__nav-link').classList.add('is-active');
    }

    // Kategori filtresi
    li.querySelector('[data-cat-id]').addEventListener('click', e => {
      if (e.target.closest('.sidebar__cat-actions')) return;
      window.dispatchEvent(new CustomEvent('filterCategory', { detail: cat.id }));
    });

    // Düzenle
    li.querySelector('[data-edit]')?.addEventListener('click', e => {
      e.stopPropagation();
      openCatModal(cat);
    });

    // Sil
    li.querySelector('[data-del]')?.addEventListener('click', async e => {
      e.stopPropagation();
      window.dispatchEvent(new CustomEvent('deleteCategory', { detail: cat.id }));
    });

    list.appendChild(li);
  });
}

/** Sidebar'daki aktif nav-link'i günceller */
export function setActiveSidebarItem(selector) {
  document.querySelectorAll('.sidebar__nav-link').forEach(el => el.classList.remove('is-active'));
  const target = document.querySelector(selector);
  target?.classList.add('is-active');
}

/** Kategori filter select'ini güncelle (toolbar'da da kullanılır) */
export function updateCategoryFilterSelect() {
  const select = document.getElementById('filterCategory');
  if (!select) return;
  const val = select.value;
  select.innerHTML = '<option value="">Tüm Kategoriler</option>'
    + State.categories.map(c =>
        `<option value="${c.id}"${String(c.id) === val ? ' selected' : ''}>${escHtml(c.name)}</option>`
      ).join('');
}

/** Sidebar mobil aç/kapa */
export function initSidebarToggle() {
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('sidebarToggle');

  hamburger?.addEventListener('click', () => {
    sidebar.classList.toggle('is-open');
    overlay.classList.toggle('is-visible');
  });

  overlay?.addEventListener('click', () => {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-visible');
  });

  // Desktop mini/full toggle
  toggleBtn?.addEventListener('click', () => {
    document.querySelector('.app-layout').classList.toggle('sidebar-mini');
    localStorage.setItem('sidebarMini', document.querySelector('.app-layout').classList.contains('sidebar-mini'));
  });
}

/** localStorage'daki sidebar durumunu uygula */
export function restoreSidebarState() {
  if (localStorage.getItem('sidebarMini') === 'true') {
    document.querySelector('.app-layout')?.classList.add('sidebar-mini');
  }
}

/** Sidebar istatistik badge'lerini güncelle */
export function updateSidebarStats(stats) {
  const map = {
    '[data-filter-status="pending"]':     stats.pending,
    '[data-filter-status="in_progress"]': stats.in_progress,
    '[data-filter-status="completed"]':   stats.completed,
  };
  for (const [sel, count] of Object.entries(map)) {
    const badge = document.querySelector(`${sel} .sidebar__nav-badge`);
    if (badge) badge.textContent = count;
  }
}
