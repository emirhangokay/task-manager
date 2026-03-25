/**
 * bulkBar.js — Toplu işlem çubuğu
 */

import { State, clearSelection } from '../state.js';
import { bulkUpdate, bulkDelete } from '../api.js';
import { showToast } from './toast.js';
import { renderTaskList } from './taskList.js';

/** Bulk bar görünümünü günceller */
export function updateBulkBar() {
  const bar   = document.getElementById('bulkBar');
  const count = document.getElementById('bulkCount');
  if (!bar) return;

  const n = State.selectedIds.size;
  bar.classList.toggle('is-visible', n > 0);
  if (count) count.textContent = `${n} görev seçildi`;
}

/** Bulk bar event listener'larını başlatır */
export function initBulkBar() {
  document.getElementById('bulkComplete')?.addEventListener('click', () => doBulkUpdate('status', 'completed'));
  document.getElementById('bulkPending')?.addEventListener('click',  () => doBulkUpdate('status', 'pending'));
  document.getElementById('bulkDelete')?.addEventListener('click',   () => doBulkDeleteHandler());
  document.getElementById('bulkClose')?.addEventListener('click',    () => cancelBulk());
}

async function doBulkUpdate(field, value) {
  const ids = [...State.selectedIds];
  const res = await bulkUpdate(ids, field, value);
  if (res.success) {
    showToast(res.message, 'success');
    cancelBulk();
    window.dispatchEvent(new Event('reloadTasks'));
  } else {
    showToast(res.message, 'error');
  }
}

async function doBulkDeleteHandler() {
  const ids = [...State.selectedIds];
  if (!confirm(`${ids.length} görev silinsin mi?`)) return;
  const res = await bulkDelete(ids);
  if (res.success) {
    showToast(res.message, 'success');
    cancelBulk();
    window.dispatchEvent(new Event('reloadTasks'));
  } else {
    showToast(res.message, 'error');
  }
}

function cancelBulk() {
  clearSelection();
  updateBulkBar();
  document.querySelectorAll('.task-card.is-selected').forEach(c => c.classList.remove('is-selected'));
}
