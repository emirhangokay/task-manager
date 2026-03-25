/**
 * modal.js — Modal ve Detail Panel yönetimi
 */

import { State }   from '../state.js';
import { escHtml } from '../utils/helpers.js';

const COLOR_PRESETS = [
  '#6B7280','#EF4444','#F59E0B','#10B981',
  '#3B82F6','#8B5CF6','#EC4899','#14B8A6',
  '#F97316','#06B6D4','#84CC16','#6366F1',
];

/* ── Task Modal ──────────────────────────────────────────── */

/** Yeni görev modalını açar */
export function openTaskModal(task = null) {
  const overlay = document.getElementById('taskModalOverlay');
  const title   = document.getElementById('taskModalTitle');
  const form    = document.getElementById('taskForm');
  const statusGroup = document.getElementById('taskStatusGroup');

  State.editingTaskId = task ? task.id : null;

  title.textContent = task ? 'Görevi Düzenle' : 'Yeni Görev Ekle';
  document.getElementById('taskId').value = task?.id ?? '';

  // Alanları doldur / temizle
  document.getElementById('taskTitle').value       = task?.title        ?? '';
  document.getElementById('taskDescription').value = task?.description  ?? '';
  document.getElementById('taskDueDate').value      = task?.due_date     ?? '';

  // Durum grubu sadece düzenleme modunda görünür
  statusGroup.classList.toggle('hidden', !task);
  if (task) {
    document.getElementById('taskStatus').value = task.status ?? 'pending';
  }

  // Öncelik butonlarını güncelle
  const priority = task?.priority ?? 'medium';
  document.querySelectorAll('.priority-btn').forEach(btn => {
    btn.classList.toggle('is-active', btn.dataset.value === priority);
  });

  // Kategori selectini doldur
  fillCategorySelect('taskCategoryId', task?.category_id ?? '');

  showModal('taskModal');
  setTimeout(() => document.getElementById('taskTitle').focus(), 50);
}

/** Görev formunu saklar (data döndürür, submit event yönetimi dışarda) */
export function getTaskFormData() {
  const priority = document.querySelector('.priority-btn.is-active')?.dataset.value ?? 'medium';
  return {
    id:          document.getElementById('taskId').value || null,
    title:       document.getElementById('taskTitle').value.trim(),
    description: document.getElementById('taskDescription').value.trim(),
    priority,
    status:      document.getElementById('taskStatus')?.value ?? 'pending',
    category_id: document.getElementById('taskCategoryId').value || null,
    due_date:    document.getElementById('taskDueDate').value || null,
  };
}

/* ── Category Modal ──────────────────────────────────────── */

export function openCatModal(cat = null) {
  document.getElementById('catModalTitle').textContent = cat ? 'Kategori Düzenle' : 'Yeni Kategori';
  document.getElementById('catId').value   = cat?.id    ?? '';
  document.getElementById('catName').value = cat?.name  ?? '';

  const color = cat?.color ?? '#6366F1';
  document.getElementById('catColorInput').value = color;
  renderColorPresets(color);

  showModal('catModal');
  setTimeout(() => document.getElementById('catName').focus(), 50);
}

export function getCatFormData() {
  return {
    id:    document.getElementById('catId').value || null,
    name:  document.getElementById('catName').value.trim(),
    color: document.getElementById('catColorInput').value,
  };
}

/* ── Genel Modal Yönetimi ────────────────────────────────── */

export function showModal(id) {
  const overlay = document.getElementById(`${id}Overlay`);
  if (!overlay) return;
  overlay.classList.add('is-visible');
  document.body.style.overflow = 'hidden';
  // Focus trap: ESC ile kapanma dışarıda başlatılır
}

export function closeModal(id) {
  const overlay = document.getElementById(`${id}Overlay`);
  if (!overlay) return;
  const modal = overlay.querySelector('.modal');
  if (modal) {
    modal.classList.add('is-closing');
    modal.addEventListener('animationend', () => {
      modal.classList.remove('is-closing');
      overlay.classList.remove('is-visible');
      document.body.style.overflow = '';
    }, { once: true });
  } else {
    overlay.classList.remove('is-visible');
    document.body.style.overflow = '';
  }
}

export function closeAllModals() {
  document.querySelectorAll('.modal-overlay.is-visible').forEach(o => {
    const id = o.id.replace('Overlay', '');
    closeModal(id);
  });
  closeDetailPanel();
}

/* ── Detail Panel ────────────────────────────────────────── */

export function openDetailPanel(task) {
  State.detailTaskId = task.id;
  const panel = document.getElementById('detailPanel');
  if (!panel) return;

  panel.querySelector('.detail-panel__task-title').textContent = task.title;
  panel.querySelector('[data-detail="desc"]').textContent      = task.description || '—';
  panel.querySelector('[data-detail="status"]').innerHTML      = `<span class="badge badge--status-${task.status}">${escHtml(task.status_label ?? task.status)}</span>`;
  panel.querySelector('[data-detail="priority"]').innerHTML    = `<span class="badge badge--priority-${task.priority}">${escHtml(task.priority_label ?? task.priority)}</span>`;
  panel.querySelector('[data-detail="category"]').textContent  = task.category_name || '—';
  panel.querySelector('[data-detail="due"]').textContent       = task.due_date || '—';
  panel.querySelector('[data-detail="created"]').textContent   = task.created_at || '—';
  panel.querySelector('[data-detail="updated"]').textContent   = task.updated_at || '—';

  panel.classList.add('is-open');
}

export function closeDetailPanel() {
  const panel = document.getElementById('detailPanel');
  panel?.classList.remove('is-open');
  State.detailTaskId = null;
}

/* ── Renk Picker ─────────────────────────────────────────── */

function renderColorPresets(selected) {
  const container = document.getElementById('colorPresets');
  if (!container) return;
  container.innerHTML = COLOR_PRESETS.map(c => `
    <button type="button"
      class="color-preset${c === selected ? ' is-selected' : ''}"
      style="background:${c}"
      data-color="${c}"
      aria-label="${c}"
    ></button>
  `).join('');

  container.querySelectorAll('.color-preset').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('catColorInput').value = btn.dataset.color;
      container.querySelectorAll('.color-preset').forEach(b => b.classList.remove('is-selected'));
      btn.classList.add('is-selected');
    });
  });
}

/* ── Yardımcı ────────────────────────────────────────────── */

function fillCategorySelect(selectId, selectedId) {
  const select = document.getElementById(selectId);
  if (!select) return;
  const current = select.innerHTML.split('<option value="">')[0]; // Başlık koruma (gereksiz)
  select.innerHTML = '<option value="">Kategori Yok</option>'
    + (window._categories ?? []).map(c =>
        `<option value="${c.id}"${String(c.id) === String(selectedId) ? ' selected' : ''}>${escHtml(c.name)}</option>`
      ).join('');
}

/** Overlay dışına tıklanınca kapat */
export function initModalOverlayClose() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) {
        const id = overlay.id.replace('Overlay', '');
        closeModal(id);
      }
    });
  });
}
