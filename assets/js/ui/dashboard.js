/**
 * dashboard.js — Dashboard istatistik kartları, bugünün görevleri,
 *                öncelik dağılımı ve aktivite akışı
 */

import { escHtml, timeAgo, activityIcon } from '../utils/helpers.js';
import { fetchStats, fetchRecentActivity, fetchTodayTasks } from '../api.js';
import { State } from '../state.js';

/** Dashboard verilerini yükle ve render et */
export async function loadDashboard() {
  const [statsRes, activityRes, todayRes] = await Promise.all([
    fetchStats(),
    fetchRecentActivity(8),
    fetchTodayTasks(),
  ]);

  if (statsRes.success) {
    renderStats(statsRes.data);
    renderPriorityBars(statsRes.data);
  }
  if (activityRes.success) renderActivity(activityRes.data);
  if (todayRes.success)    renderTodayTasks(todayRes.data);
}

/** Özet istatistik kartlarını günceller */
export function renderStats(stats) {
  const ids = {
    statTotal:      stats.total,
    statPending:    stats.pending,
    statInProgress: stats.in_progress,
    statCompleted:  stats.completed,
    statOverdue:    stats.overdue,
  };
  for (const [id, val] of Object.entries(ids)) {
    const el = document.getElementById(id);
    if (el) el.textContent = val ?? 0;
  }

  // Tamamlanma yüzdesi
  const pctEl = document.getElementById('statCompletedPct');
  if (pctEl && stats.total > 0) {
    pctEl.textContent = `%${Math.round((stats.completed / stats.total) * 100)} tamamlandı`;
  }
}

/** Bugünün görevlerini render eder */
function renderTodayTasks(tasks) {
  const container = document.getElementById('todayTaskList');
  if (!container) return;

  if (!tasks.length) {
    container.innerHTML = `
      <div class="tasks-empty" style="padding:20px 0">
        <div class="tasks-empty__icon">🎉</div>
        <div class="tasks-empty__title" style="font-size:.9375rem">Bugün için görev yok!</div>
        <div class="tasks-empty__subtitle">Harika iş çıkarıyorsun.</div>
      </div>`;
    return;
  }

  const colors = { high: 'var(--danger)', medium: 'var(--warning)', low: 'var(--success)' };

  container.innerHTML = tasks.map(t => `
    <div class="today-task-item">
      <span class="today-task-item__dot" style="background:${colors[t.priority] ?? '#94A3B8'}"></span>
      <span class="today-task-item__title">${escHtml(t.title)}</span>
      <span class="badge badge--status-${t.status}" style="font-size:.625rem">${escHtml(t.status)}</span>
    </div>
  `).join('');
}

/** Öncelik dağılım barlarını render eder */
function renderPriorityBars(stats) {
  const container = document.getElementById('priorityBars');
  if (!container) return;

  const total = stats.total || 1;

  // Görevleri önceliğe göre say
  const counts = { high: 0, medium: 0, low: 0 };
  State.tasks.forEach(t => { if (t.priority in counts) counts[t.priority]++; });

  const items = [
    { label: 'Yüksek', key: 'high',   cls: '--high' },
    { label: 'Orta',   key: 'medium', cls: '--medium' },
    { label: 'Düşük',  key: 'low',    cls: '--low' },
  ];

  container.innerHTML = items.map(({ label, key, cls }) => {
    const pct = Math.round((counts[key] / total) * 100);
    return `
      <div class="priority-bar-item">
        <div class="priority-bar-info">
          <span class="priority-bar-label">${label}</span>
          <span class="priority-bar-count">${counts[key]}</span>
        </div>
        <div class="priority-bar-track">
          <div class="priority-bar-fill priority-bar-fill${cls}" style="width:${pct}%"></div>
        </div>
      </div>`;
  }).join('');
}

/** Aktivite akışını render eder */
function renderActivity(activities) {
  const container = document.getElementById('activityList');
  if (!container) return;

  if (!activities.length) {
    container.innerHTML = '<p style="font-size:.875rem;color:var(--text-tertiary);text-align:center;padding:12px 0">Henüz aktivite yok.</p>';
    return;
  }

  container.innerHTML = activities.map(a => `
    <div class="activity-item">
      <div class="activity-item__dot activity-item__dot--${a.action}">${activityIcon(a.action)}</div>
      <div class="activity-item__body">
        <div class="activity-item__text">${escHtml(a.details || a.task_title || '—')}</div>
        <div class="activity-item__time">${timeAgo(a.created_at)}</div>
      </div>
    </div>
  `).join('');
}
