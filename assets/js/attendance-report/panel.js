import { h, icon } from './dom.js';
import { DAY_NAMES_SHORT, MONTH_NAMES, dayArray, dow, isWeekend } from './calendar.js';
import { deriveCode, STATUS, statusBg, statusColor, summarize, attendanceRate } from './status.js';
import { initials, avatarColor } from './avatar.js';

let activePanel = null;
let escHandler = null;

function close() {
  if (activePanel) { activePanel.remove(); activePanel = null; }
  if (escHandler) { document.removeEventListener('keydown', escHandler); escHandler = null; }
}

function timeOnly(ts) {
  if (!ts) return null;
  return ts.length >= 16 ? ts.slice(11, 16) : ts;
}

function statCard(label, value, color) {
  return h('div', { class: 'ar-stat-card' },
    h('div', { class: 'ar-stat-card__num', style: color ? { color } : null }, String(value)),
    h('div', { class: 'ar-stat-card__lbl' }, label),
  );
}

function dayRow(year, monthIdx, day, row) {
  const w = dow(year, monthIdx, day);
  const we = isWeekend(w);
  const code = we ? 'WE' : deriveCode(row);
  const status = STATUS[code] || STATUS.A;

  const times = [];
  if (row && row.first_in) times.push(h('span', null, '↓ ', timeOnly(row.first_in)));
  if (row && row.last_out) times.push(h('span', null, '↑ ', timeOnly(row.last_out)));
  if (row && row.holiday_name) times.push(h('span', { class: 'note' }, row.holiday_name));
  else if (row && Array.isArray(row.notes) && row.notes.length) times.push(h('span', { class: 'note' }, row.notes[0]));

  return h('div', { class: 'ar-day-row' + (we ? ' ar-day-row--weekend' : '') },
    h('div', { class: 'ar-day-row__date' },
      h('span', { class: 'dow' }, DAY_NAMES_SHORT[w] + ' '),
      h('span', { class: 'dom' }, String(day).padStart(2, '0')),
    ),
    h('span', {
      class: 'ar-status-badge',
      style: { background: statusBg(code), color: statusColor(code) }
    }, status.abbr),
    h('div', { class: 'ar-day-row__times' }, ...times),
  );
}

export function openPanel({ user, year, monthIdx, daily, schedule, isSpecial }) {
  close();
  const days = dayArray(year, monthIdx);
  const dailyByDay = {};
  for (const r of (daily || [])) {
    dailyByDay[parseInt(r.date.slice(8, 10), 10)] = r;
  }
  const sum = summarize(daily || []);
  const workdays = days.filter(d => !isWeekend(dow(year, monthIdx, d))).length;
  const rate = attendanceRate(sum, workdays);

  const sheet = h('div', { class: 'ar-panel__sheet', onClick: e => e.stopPropagation() },
    h('div', { class: 'ar-panel__header' },
      h('div', { class: 'ar-panel__avatar', style: { background: avatarColor(user.id) } }, initials(user.full_name)),
      h('div', { style: { flex: 1, minWidth: 0 } },
        h('div', { class: 'ar-panel__name' }, user.full_name),
        h('div', { class: 'ar-panel__meta' },
          (user.role_name || user.email || '—'),
          ' · ', schedule || '—',
          isSpecial ? ' (Khusus)' : ''
        ),
      ),
      h('button', { class: 'ar-panel__close', title: 'Tutup', onClick: close }, icon('x', 14)),
    ),
    h('div', { class: 'ar-panel__section-title' }, `${MONTH_NAMES[monthIdx]} ${year} — Ringkasan`),
    h('div', { class: 'ar-panel__stats' },
      statCard('Hadir', sum.P, 'var(--ar-green)'),
      statCard('Terlambat', sum.L, 'var(--ar-amber)'),
      statCard('Tidak Hadir', sum.A, 'var(--ar-red)'),
      statCard('Cuti', sum.Le, 'var(--ar-blue)'),
      statCard('Pulang Cepat', sum.EC, 'var(--ar-orange)'),
      statCard('Kehadiran', `${rate}%`, 'var(--ar-accent)'),
    ),
    h('div', { class: 'ar-panel__section-title' }, 'Catatan Harian'),
    h('div', { class: 'ar-panel__list' },
      ...days.map(d => dayRow(year, monthIdx, d, dailyByDay[d])),
    ),
  );

  activePanel = h('div', { class: 'ar-panel', onClick: close }, sheet);
  document.body.appendChild(activePanel);

  escHandler = e => { if (e.key === 'Escape') close(); };
  document.addEventListener('keydown', escHandler);
}

export function closePanel() { close(); }
