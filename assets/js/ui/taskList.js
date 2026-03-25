/**
 * taskList.js — Liste görünümü render ve etkileşimler
 */

import { State, updateTaskInState, removeTaskFromState } from '../state.js';
import { escHtml, relativeDueDate, isToday, isOverdue } from '../utils/helpers.js';
import { showToast }     from './toast.js';
import { openTaskModal, openDetailPanel } from './modal.js';
import { updateBulkBar } from './bulkBar.js';
import { deleteTask, toggleStatus, fetchTasks } from '../api.js';
import { renderStats }   from './dashboard.js';

/** Tüm görev listesini render eder */
export function renderTaskList(tasks) {
  const container = document.getElementById('tasksList');
  if (!container) return;

  container.innerHTML = '';

  // Quick-add satırı
  const quickAdd = document.createElement('div');
  quickAdd.className = 'task-quick-add';
  quickAdd.innerHTML = `<span style="font-size:1.1rem">+</span> Yeni görev ekle…`;
  quickAdd.addEventListener('click', () => openTaskModal());
  container.appendChild(quickAdd);

  if (!tasks.length) {
    container.insertAdjacentHTML('beforeend', `
      <div class="tasks-empty">
        <div class="tasks-empty__icon">📋</div>
        <div class="tasks-empty__title">Görev bulunamadı</div>
        <div class="tasks-empty__subtitle">Filtreleri değiştirin veya yeni bir görev ekleyin.</div>
        <button class="btn btn--primary" onclick="document.getElementById('btnAddTask').click()">+ Görev Ekle</button>
      </div>
    `);
    return;
  }

  tasks.forEach(task => container.appendChild(buildTaskCard(task)));
}

/** Tek görev kartı oluşturur */
export function buildTaskCard(task) {
  const isComplete = task.status === 'completed';
  const overdue    = isOverdue(task);
  const todayDue   = task.due_date && isToday(task.due_date);

  const card = document.createElement('div');
  card.className = [
    'task-card',
    `task-card--priority-${task.priority}`,
    isComplete ? 'is-completed' : '',
    overdue    ? 'is-overdue'   : '',
    State.selectedIds.has(task.id) ? 'is-selected' : '',
  ].filter(Boolean).join(' ');
  card.dataset.taskId = task.id;

  // Süresi geçmiş badge
  const dueBadge = task.due_date ? `
    <span class="badge badge--due ${overdue ? 'is-overdue' : ''} ${todayDue ? 'is-today' : ''}">
      📅 ${relativeDueDate(task.due_date)}
    </span>` : '';

  // Kategori badge
  const catBadge = task.category_name ? `
    <span class="badge badge--cat">
      <span style="width:6px;height:6px;border-radius:50%;background:${escHtml(task.category_color)};display:inline-block"></span>
      ${escHtml(task.category_name)}
    </span>` : '';

  card.innerHTML = `
    <div class="task-card__checkbox${isComplete ? ' is-checked' : ''}" role="checkbox" aria-checked="${isComplete}" tabindex="0"></div>
    <div class="task-card__body">
      <div class="task-card__title">${escHtml(task.title)}</div>
      ${task.description ? `<div class="task-card__desc">${escHtml(task.description)}</div>` : ''}
      <div class="task-card__meta">
        <span class="badge badge--priority-${task.priority}">${escHtml(task.priority_label)}</span>
        ${catBadge}
        ${dueBadge}
      </div>
    </div>
    <div class="task-card__actions">
      <button class="btn btn--icon" data-action="edit"   title="Düzenle">✏️</button>
      <button class="btn btn--icon" data-action="delete" title="Sil" style="color:var(--danger)">🗑️</button>
    </div>
  `;

  // Checkbox (tamamla/geri al)
  card.querySelector('.task-card__checkbox').addEventListener('click', e => {
    e.stopPropagation();
    handleToggle(task, card);
  });

  // Karta tıkla → detay paneli
  card.querySelector('.task-card__body').addEventListener('click', () => {
    openDetailPanel(task);
  });

  // Düzenle
  card.querySelector('[data-action="edit"]').addEventListener('click', e => {
    e.stopPropagation();
    openTaskModal(task);
  });

  // Sil
  card.querySelector('[data-action="delete"]').addEventListener('click', e => {
    e.stopPropagation();
    handleDelete(task, card);
  });

  // Toplu seçim
  card.addEventListener('click', e => {
    if (e.ctrlKey || e.metaKey || e.shiftKey) {
      e.preventDefault();
      toggleSelection(task.id, card);
    }
  });

  return card;
}

/** Checkbox toggle */
async function handleToggle(task, card) {
  const wasCompleted = task.status === 'completed';
  const newStatus    = wasCompleted ? 'pending' : 'completed';

  // Animasyon
  const checkbox = card.querySelector('.task-card__checkbox');
  checkbox.classList.add('is-completing');
  checkbox.addEventListener('animationend', () => checkbox.classList.remove('is-completing'), { once: true });

  const res = await toggleStatus(task.id, newStatus);
  if (res.success) {
    updateTaskInState({ id: task.id, status: newStatus, status_label: res.data.status_label });
    card.classList.toggle('is-completed', newStatus === 'completed');
    checkbox.classList.toggle('is-checked', newStatus === 'completed');
    checkbox.setAttribute('aria-checked', newStatus === 'completed');
    showToast(newStatus === 'completed' ? '✅ Görev tamamlandı!' : 'Görev geri alındı.', 'success', 2000);

    // Stats güncelle
    const stats = computeLocalStats();
    renderStats(stats);
  } else {
    showToast(res.message, 'error');
  }
}

/** Silme */
async function handleDelete(task, card) {
  if (!confirm(`"${task.title}" silinsin mi?`)) return;

  card.classList.add('is-removing');
  card.addEventListener('animationend', async () => {
    const res = await deleteTask(task.id);
    if (res.success) {
      removeTaskFromState(task.id);
      card.remove();
      showToast('Görev silindi.', 'success');
      renderStats(computeLocalStats());
    } else {
      card.classList.remove('is-removing');
      showToast(res.message, 'error');
    }
  }, { once: true });
}

/** Toplu seçim toggle */
function toggleSelection(id, card) {
  if (State.selectedIds.has(id)) {
    State.selectedIds.delete(id);
    card.classList.remove('is-selected');
  } else {
    State.selectedIds.add(id);
    card.classList.add('is-selected');
  }
  updateBulkBar();
}

/** Local state'ten istatistik hesapla */
export function computeLocalStats() {
  const today = new Date().toISOString().split('T')[0];
  return {
    total:       State.tasks.length,
    pending:     State.tasks.filter(t => t.status === 'pending').length,
    in_progress: State.tasks.filter(t => t.status === 'in_progress').length,
    completed:   State.tasks.filter(t => t.status === 'completed').length,
    overdue:     State.tasks.filter(t => t.due_date && t.due_date < today && t.status !== 'completed').length,
  };
}

/** Skeleton yükleme kartları göster */
export function renderSkeletons(count = 4) {
  const container = document.getElementById('tasksList');
  if (!container) return;
  container.innerHTML = Array.from({ length: count }, () => `
    <div class="skeleton-card">
      <div class="skeleton-line" style="width:20px;height:20px;border-radius:6px;flex-shrink:0"></div>
      <div style="flex:1;display:flex;flex-direction:column;gap:6px">
        <div class="skeleton-line" style="height:16px;width:60%"></div>
        <div class="skeleton-line" style="height:12px;width:80%"></div>
        <div style="display:flex;gap:6px">
          <div class="skeleton-line" style="height:20px;width:60px;border-radius:999px"></div>
          <div class="skeleton-line" style="height:20px;width:80px;border-radius:999px"></div>
        </div>
      </div>
    </div>
  `).join('');
}
