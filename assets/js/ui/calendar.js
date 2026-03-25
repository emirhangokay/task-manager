/**
 * calendar.js — Aylık takvim görünümü (CSS grid, kütüphanesiz)
 */

import { State }   from '../state.js';
import { escHtml } from '../utils/helpers.js';
import { openDetailPanel } from './modal.js';

let _year  = new Date().getFullYear();
let _month = new Date().getMonth(); // 0-11

const TR_MONTHS = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
const PRIORITY_COLORS = { high: '#EF4444', medium: '#F59E0B', low: '#10B981' };

/** Takvimi render eder */
export function renderCalendar() {
  const wrap = document.getElementById('calendarWrap');
  if (!wrap) return;

  // Header
  wrap.querySelector('#calTitle').textContent = `${TR_MONTHS[_month]} ${_year}`;

  const grid = wrap.querySelector('.calendar-grid');
  if (!grid) return;

  grid.innerHTML = '';

  const today        = new Date();
  const firstDay     = new Date(_year, _month, 1);
  const lastDay      = new Date(_year, _month + 1, 0);
  const startWeekday = (firstDay.getDay() + 6) % 7; // Pazartesi = 0

  // Önceki aydan dolgu günleri
  for (let i = 0; i < startWeekday; i++) {
    const d = new Date(_year, _month, 1 - (startWeekday - i));
    grid.appendChild(buildDayCell(d, true));
  }

  // Bu ayın günleri
  for (let d = 1; d <= lastDay.getDate(); d++) {
    grid.appendChild(buildDayCell(new Date(_year, _month, d), false, today));
  }

  // Sonraki aydan dolgu (6 satır tamamla)
  const total = startWeekday + lastDay.getDate();
  const remaining = 42 - total;
  for (let i = 1; i <= remaining; i++) {
    grid.appendChild(buildDayCell(new Date(_year, _month + 1, i), true));
  }
}

function buildDayCell(date, otherMonth, today) {
  const isoDate = toISO(date);
  const isToday  = today && isoDate === toISO(today);

  const cell = document.createElement('div');
  cell.className = [
    'calendar-day',
    otherMonth ? 'calendar-day--other-month' : '',
    isToday    ? 'calendar-day--today' : '',
  ].filter(Boolean).join(' ');

  // O güne ait görevler
  const dayTasks = State.tasks.filter(t => t.due_date === isoDate);

  cell.innerHTML = `
    <div class="calendar-day__num">${date.getDate()}</div>
    <div class="calendar-day__tasks">
      ${dayTasks.slice(0, 3).map(t => `
        <span class="calendar-day__task-dot"
          style="background:${PRIORITY_COLORS[t.priority] ?? '#94A3B8'}"
          data-task-id="${t.id}"
          title="${escHtml(t.title)}"
        >${escHtml(t.title)}</span>
      `).join('')}
      ${dayTasks.length > 3 ? `<span class="calendar-day__more">+${dayTasks.length - 3} daha</span>` : ''}
    </div>
  `;

  // Görev dot'una tıklayınca detay paneli aç
  cell.querySelectorAll('[data-task-id]').forEach(dot => {
    dot.addEventListener('click', e => {
      e.stopPropagation();
      const task = State.tasks.find(t => t.id == dot.dataset.taskId);
      if (task) openDetailPanel(task);
    });
  });

  // Güne tıklayınca modal (o gün görevleri)
  if (!otherMonth && dayTasks.length > 0) {
    cell.addEventListener('click', () => openDayModal(date, dayTasks));
  }

  return cell;
}

function openDayModal(date, tasks) {
  // Basit inline modal — ayrı bir modal overlay kullanır
  const overlay = document.getElementById('dayModalOverlay');
  if (!overlay) return;

  const dateStr = `${TR_MONTHS[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
  overlay.querySelector('#dayModalTitle').textContent = `📅 ${dateStr}`;
  overlay.querySelector('#dayModalList').innerHTML = tasks.map(t => `
    <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--border)">
      <span style="width:8px;height:8px;border-radius:50%;background:${PRIORITY_COLORS[t.priority] ?? '#94A3B8'};flex-shrink:0"></span>
      <span style="flex:1;font-size:.875rem;color:var(--text-primary)">${escHtml(t.title)}</span>
      <span class="badge badge--status-${t.status}" style="font-size:.625rem">${escHtml(t.status_label ?? t.status)}</span>
    </div>
  `).join('');

  overlay.classList.add('is-visible');
}

/** Önceki aya git */
export function prevMonth() {
  if (_month === 0) { _month = 11; _year--; }
  else _month--;
  renderCalendar();
}

/** Sonraki aya git */
export function nextMonth() {
  if (_month === 11) { _month = 0; _year++; }
  else _month++;
  renderCalendar();
}

/** Bugüne dön */
export function goToToday() {
  _year  = new Date().getFullYear();
  _month = new Date().getMonth();
  renderCalendar();
}

function toISO(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
