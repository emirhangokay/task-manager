/**
 * helpers.js — Genel yardımcı fonksiyonlar
 * Tarih formatlama, XSS escape, debounce, vs.
 */

/** HTML özel karakterlerini escape eder */
export function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Tarih stringini "Bugün", "Dün", "3 gün sonra" gibi gösterir
 * @param {string} dateStr  YYYY-MM-DD
 */
export function relativeDueDate(dateStr) {
  if (!dateStr) return '';
  const today = new Date(); today.setHours(0,0,0,0);
  const due   = new Date(dateStr + 'T00:00:00');
  const diff  = Math.round((due - today) / 86400000);

  if (diff === 0)  return 'Bugün';
  if (diff === -1) return 'Dün';
  if (diff === 1)  return 'Yarın';
  if (diff < 0)   return `${Math.abs(diff)} gün geçti`;
  if (diff <= 7)  return `${diff} gün sonra`;
  return due.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short' });
}

/**
 * "2025-03-25T10:00:00" → "25 Mar, 10:00"
 */
export function formatDateTime(isoStr) {
  if (!isoStr) return '';
  const d = new Date(isoStr);
  return d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short' })
       + ', '
       + d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Geçmiş zaman: "3 dakika önce", "2 saat önce", vb.
 */
export function timeAgo(isoStr) {
  if (!isoStr) return '';
  const diff = Math.floor((Date.now() - new Date(isoStr)) / 1000);
  if (diff < 60)   return 'Az önce';
  if (diff < 3600) return `${Math.floor(diff/60)} dakika önce`;
  if (diff < 86400) return `${Math.floor(diff/3600)} saat önce`;
  if (diff < 604800) return `${Math.floor(diff/86400)} gün önce`;
  return formatDateTime(isoStr);
}

/**
 * Bir tarih bugün mü?
 */
export function isToday(dateStr) {
  if (!dateStr) return false;
  return dateStr === new Date().toISOString().split('T')[0];
}

/**
 * Süresi geçmiş mi?
 */
export function isOverdue(task) {
  return task.due_date && task.due_date < new Date().toISOString().split('T')[0] && task.status !== 'completed';
}

/**
 * Öncelik rengi hex kodu döndürür
 */
export function priorityColor(priority) {
  const map = { high: '#EF4444', medium: '#F59E0B', low: '#10B981' };
  return map[priority] ?? '#94A3B8';
}

/**
 * Aksiyon → ikon eşleşmesi (aktivite log)
 */
export function activityIcon(action) {
  const map = { created: '➕', updated: '✏️', completed: '✅', deleted: '🗑️', status_changed: '🔄' };
  return map[action] ?? '•';
}

/**
 * Fonksiyonu belirli bir süre bekletir (debounce)
 */
export function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

/**
 * Renk kontrast için açık mı koyu mu belirler
 */
export function isDarkColor(hex) {
  const r = parseInt(hex.slice(1,3),16);
  const g = parseInt(hex.slice(3,5),16);
  const b = parseInt(hex.slice(5,7),16);
  return (r*299 + g*587 + b*114) / 1000 < 128;
}

/**
 * DOM element oluşturucu kısayol
 */
export function el(tag, attrs = {}, ...children) {
  const elem = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class')          elem.className = v;
    else if (k === 'html')      elem.innerHTML  = v;
    else if (k === 'text')      elem.textContent = v;
    else if (k.startsWith('on')) elem.addEventListener(k.slice(2), v);
    else                         elem.setAttribute(k, v);
  }
  children.flat().forEach(c => {
    if (typeof c === 'string') elem.insertAdjacentHTML('beforeend', c);
    else if (c) elem.appendChild(c);
  });
  return elem;
}
