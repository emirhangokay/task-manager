/**
 * kanban.js — Kanban board render ve Sortable.js drag&drop
 */

import { State, updateTaskInState } from '../state.js';
import { escHtml, relativeDueDate, isOverdue } from '../utils/helpers.js';
import { reorderTask } from '../api.js';
import { showToast }   from './toast.js';
import { openTaskModal, openDetailPanel } from './modal.js';

const COLS = [
  { key: 'pending',     label: 'Bekliyor',      icon: '⏳' },
  { key: 'in_progress', label: 'Devam Ediyor',  icon: '🔄' },
  { key: 'completed',   label: 'Tamamlandı',    icon: '✅' },
];

/** Kanban board'u render eder */
export function renderKanban(tasks) {
  const board = document.getElementById('kanbanBoard');
  if (!board) return;

  board.innerHTML = '';

  COLS.forEach(col => {
    const colTasks = tasks.filter(t => t.status === col.key);
    board.appendChild(buildColumn(col, colTasks));
  });

  initDragDrop();
}

function buildColumn(col, tasks) {
  const wrap = document.createElement('div');
  wrap.className = `kanban-col kanban-col--${col.key}`;
  wrap.innerHTML = `
    <div class="kanban-col__header">
      <span style="font-size:1rem">${col.icon}</span>
      <span class="kanban-col__title">${col.label}</span>
      <span class="kanban-col__badge">${tasks.length}</span>
    </div>
    <div class="kanban-col__body" data-status="${col.key}" id="kanban-${col.key}"></div>
  `;

  const body = wrap.querySelector('.kanban-col__body');
  tasks.forEach(t => body.appendChild(buildKanbanCard(t)));
  return wrap;
}

function buildKanbanCard(task) {
  const overdue = isOverdue(task);
  const card = document.createElement('div');
  card.className = [
    'task-card kanban-card',
    `task-card--priority-${task.priority}`,
    task.status === 'completed' ? 'is-completed' : '',
    overdue ? 'is-overdue' : '',
  ].filter(Boolean).join(' ');
  card.dataset.taskId = task.id;

  const dueBadge = task.due_date
    ? `<span class="badge badge--due ${overdue ? 'is-overdue' : ''}" style="font-size:.625rem">📅 ${relativeDueDate(task.due_date)}</span>`
    : '';

  const catBadge = task.category_name
    ? `<span class="badge badge--cat" style="font-size:.625rem">
        <span style="width:5px;height:5px;border-radius:50%;background:${escHtml(task.category_color)};display:inline-block"></span>
        ${escHtml(task.category_name)}
       </span>`
    : '';

  card.innerHTML = `
    <div class="task-card__body" style="cursor:pointer">
      <div class="task-card__title" style="font-size:.875rem">${escHtml(task.title)}</div>
      ${task.description ? `<div class="task-card__desc" style="font-size:.75rem">${escHtml(task.description)}</div>` : ''}
      <div class="task-card__meta" style="margin-top:6px">
        <span class="badge badge--priority-${task.priority}" style="font-size:.625rem">${escHtml(task.priority_label)}</span>
        ${catBadge}
        ${dueBadge}
      </div>
    </div>
    <div class="task-card__actions">
      <button class="btn btn--icon" data-action="edit" title="Düzenle" style="font-size:.75rem">✏️</button>
    </div>
  `;

  card.querySelector('.task-card__body').addEventListener('click', () => openDetailPanel(task));
  card.querySelector('[data-action="edit"]').addEventListener('click', e => {
    e.stopPropagation();
    openTaskModal(task);
  });

  return card;
}

/** SortableJS ile drag & drop başlatır */
function initDragDrop() {
  if (typeof Sortable === 'undefined') return;

  document.querySelectorAll('.kanban-col__body').forEach(col => {
    Sortable.create(col, {
      group:     'kanban',
      animation: 150,
      ghostClass:  'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass:   'sortable-drag',

      async onEnd(evt) {
        const taskId   = parseInt(evt.item.dataset.taskId, 10);
        const newStatus = evt.to.dataset.status;
        const position  = evt.newIndex;

        if (!taskId || !newStatus) return;

        const res = await reorderTask(taskId, newStatus, position);
        if (res.success) {
          updateTaskInState({ id: taskId, status: newStatus, position });
          // Sütun badge'lerini güncelle
          updateColumnBadges();
        } else {
          showToast(res.message, 'error');
          // Geri al: DOM'u yeniden render et
          window.dispatchEvent(new Event('reloadTasks'));
        }
      },
    });
  });
}

/** Sütun başlığındaki görev sayısı badge'lerini günceller */
function updateColumnBadges() {
  COLS.forEach(col => {
    const body  = document.getElementById(`kanban-${col.key}`);
    const badge = body?.closest('.kanban-col')?.querySelector('.kanban-col__badge');
    if (badge && body) badge.textContent = body.querySelectorAll('.task-card').length;
  });
}
