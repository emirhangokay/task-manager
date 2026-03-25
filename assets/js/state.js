/**
 * state.js — Merkezi uygulama durumu
 * Tüm modüller bu nesneyi import eder.
 */

export const State = {
  tasks:        [],
  categories:   [],
  currentView:  'list',     // 'list' | 'kanban' | 'calendar'
  selectedIds:  new Set(),
  filters: {
    status:      '',
    category_id: '',
    priority:    '',
    search:      '',
    sort:        'date_desc',
  },
  editingTaskId:    null,
  detailTaskId:     null,
  csrfToken:        '',
  notifBadgeCount:  0,
};

/** Filtre günceller */
export function setFilter(key, value) {
  State.filters[key] = value;
}

/** Filtreler sıfırlar */
export function resetFilters() {
  State.filters = { status: '', category_id: '', priority: '', search: '', sort: 'date_desc' };
}

/** Görev seçim setini sıfırlar */
export function clearSelection() {
  State.selectedIds.clear();
}

/** Görev güncelleme (state içinde) */
export function updateTaskInState(updated) {
  const idx = State.tasks.findIndex(t => t.id === updated.id);
  if (idx !== -1) State.tasks[idx] = { ...State.tasks[idx], ...updated };
}

/** Görevi state'ten kaldır */
export function removeTaskFromState(id) {
  State.tasks = State.tasks.filter(t => t.id !== id);
}
