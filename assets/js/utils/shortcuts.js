/**
 * shortcuts.js — Klavye kısayolları
 * N → Yeni görev  |  Esc → Kapat  |  Ctrl+K → Arama
 * 1/2/3 → Kanban sütun geçişi  |  ? → Kısayol listesi
 */

import { openTaskModal }     from '../ui/modal.js';
import { closeAllModals }    from '../ui/modal.js';

let _initialized = false;

/** Kısayolları başlatır — yalnızca bir kez çağrılır */
export function initShortcuts() {
  if (_initialized) return;
  _initialized = true;

  document.addEventListener('keydown', handleKey);
  document.getElementById('shortcutsToggle')?.addEventListener('click', toggleShortcutsList);
}

function handleKey(e) {
  // Input veya textarea odaklanmışsa hiçbir şey yapma
  const tag = document.activeElement?.tagName;
  const inInput = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT'
    || document.activeElement?.isContentEditable;

  // ESC her zaman çalışır
  if (e.key === 'Escape') {
    closeAllModals();
    closeShortcutsList();
    return;
  }

  if (inInput) return;

  switch (e.key) {
    case 'n':
    case 'N':
      e.preventDefault();
      openTaskModal();
      break;

    case '?':
      e.preventDefault();
      toggleShortcutsList();
      break;

    // Ctrl+K → arama
    default:
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
      }
      break;
  }
}

function toggleShortcutsList() {
  const list = document.getElementById('shortcutsList');
  list?.classList.toggle('is-visible');
}

function closeShortcutsList() {
  document.getElementById('shortcutsList')?.classList.remove('is-visible');
}
