/**
 * api.js — Tüm backend AJAX çağrıları
 * fetch() sarmalayıcılar; spinner ve hata yönetimi burada.
 */

import { State } from './state.js';
import { showLoading } from './ui/toast.js';

const _BASE = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';

/** Temel fetch sarmalayıcısı */
async function req(url, options = {}) {
  showLoading(true);
  try {
    const res  = await fetch(_BASE + url, {
      headers: { 'Content-Type': 'application/json', ...(options.headers ?? {}) },
      ...options,
    });
    return await res.json();
  } catch {
    return { success: false, message: 'Sunucu bağlantı hatası.' };
  } finally {
    showLoading(false);
  }
}

/** CSRF token'lı POST gövdesi */
function body(data) {
  return JSON.stringify({ ...data, csrf_token: State.csrfToken });
}

/* ── Görevler ──────────────────────────────────────────────── */

export function fetchTasks(filters = {}) {
  const q = new URLSearchParams(filters).toString();
  return req(`/api/tasks.php?${q}`);
}

export function createTask(data) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'create', ...data }) });
}

export function updateTask(data) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'update', ...data }) });
}

export function deleteTask(id) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'delete', id }) });
}

export function toggleStatus(id, newStatus) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'toggle_status', id, new_status: newStatus }) });
}

export function reorderTask(id, status, position) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'reorder', id, status, position }) });
}

export function bulkUpdate(ids, field, value) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'bulk_update', ids: [...ids], field, value }) });
}

export function bulkDelete(ids) {
  return req('/api/tasks.php', { method: 'POST', body: body({ action: 'bulk_delete', ids: [...ids] }) });
}

/* ── Kategoriler ───────────────────────────────────────────── */

export function fetchCategories() {
  return req('/api/categories.php');
}

export function createCategory(name, color) {
  return req('/api/categories.php', { method: 'POST', body: body({ action: 'create', name, color }) });
}

export function updateCategory(id, name, color) {
  return req('/api/categories.php', { method: 'POST', body: body({ action: 'update', id, name, color }) });
}

export function deleteCategory(id) {
  return req('/api/categories.php', { method: 'POST', body: body({ action: 'delete', id }) });
}

/* ── Dashboard ─────────────────────────────────────────────── */

export function fetchStats() {
  return req('/api/dashboard.php?action=stats');
}

export function fetchRecentActivity(limit = 10) {
  return req(`/api/dashboard.php?action=recent&limit=${limit}`);
}

export function fetchTodayTasks() {
  return req('/api/dashboard.php?action=today');
}

/* ── Auth ──────────────────────────────────────────────────── */

export function logout() {
  return req('/api/auth.php', { method: 'POST', body: body({ action: 'logout' }) });
}
