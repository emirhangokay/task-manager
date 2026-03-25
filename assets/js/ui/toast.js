/**
 * toast.js — Toast bildirimleri ve loading overlay
 */

/** Toast göster
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration ms
 */
export function showToast(message, type = 'success', duration = 3500) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

  const toast = document.createElement('div');
  toast.className = `toast toast--${type}`;
  toast.innerHTML = `
    <span class="toast__icon">${icons[type] ?? '•'}</span>
    <span class="toast__text">${message}</span>
    <button class="toast__close" aria-label="Kapat">✕</button>
  `;

  toast.querySelector('.toast__close').addEventListener('click', () => removeToast(toast));
  container.appendChild(toast);

  const timer = setTimeout(() => removeToast(toast), duration);
  toast.dataset.timer = timer;
}

function removeToast(toast) {
  clearTimeout(toast.dataset.timer);
  toast.classList.add('is-leaving');
  toast.addEventListener('animationend', () => toast.remove(), { once: true });
}

/** Global loading overlay */
export function showLoading(visible) {
  const el = document.getElementById('loadingOverlay');
  el?.classList.toggle('is-visible', visible);
}
