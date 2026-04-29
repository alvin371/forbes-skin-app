import { h, icon, mount } from './dom.js';
import { dayArray, dow, isWeekend, DAY_NAMES_SHORT } from './calendar.js';
import { deriveCode, STATUS, statusColor, summarize } from './status.js';
import { initials, avatarColor } from './avatar.js';
import { renderScheduleEditor } from './schedule-editor.js';

/**
 * Builds an indexed lookup `{userId: {day: row}}` from the matrix.
 */
function indexMatrix(matrix) {
  const out = {};
  for (const userId in matrix) {
    const map = {};
    for (const row of matrix[userId]) {
      const d = parseInt(row.date.slice(8, 10), 10);
      map[d] = row;
    }
    out[userId] = map;
  }
  return out;
}

export function renderGrid(root, store, { onCellClick, onUserOpen }) {
  const state = store.get();
  const { year, monthIdx, users, matrix, summaries, selUser, page, perPage, isAdminHr } = state;
  const days = dayArray(year, monthIdx);
  const idx = indexMatrix(matrix);

  // Filter
  const allUsers = isAdminHr ? users : (state.targetUser ? [state.targetUser] : []);
  const filtered = selUser === 'all' ? allUsers : allUsers.filter(u => String(u.id) === String(selUser));

  if (!filtered.length) {
    mount(root, h('div', { class: 'ar-empty' }, 'Tidak ada pengguna untuk ditampilkan.'));
    return { totalPages: 0, total: 0 };
  }

  const total = filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const frag = document.createDocumentFragment();

  // Header row
  const header = h('div', { class: 'ar-grid__header' },
    h('div', { class: 'ar-grid__user-col' }, 'Karyawan'),
    ...days.map(d => {
      const w = dow(year, monthIdx, d);
      const we = isWeekend(w);
      return h('div', { class: 'ar-grid__day-col' + (we ? ' ar-grid__day-col--weekend' : '') },
        h('div', { class: 'ar-dow' }, DAY_NAMES_SHORT[w]),
        h('div', { class: 'ar-dom' }, String(d)),
      );
    }),
    h('div', { class: 'ar-grid__sum' }, 'Ringkasan' + (isAdminHr ? ' / Jadwal' : '')),
  );
  frag.appendChild(header);

  // Body rows
  for (const user of paged) {
    const userId = user.id;
    const userDays = idx[userId] || {};
    const sum = summaries[userId] ? null : summarize(matrix[userId] || []);
    const summaryData = summaries[userId] || null;

    const row = h('div', { class: 'ar-grid__row', dataset: { userId: String(userId) } });

    // User col
    row.appendChild(h('div', { class: 'ar-grid__user-col' },
      h('div', { class: 'ar-avatar', style: { background: avatarColor(userId) } }, initials(user.full_name)),
      h('div', { style: { minWidth: 0, flex: 1 } },
        h('div', { class: 'ar-user-name', title: user.full_name }, user.full_name),
        h('div', { class: 'ar-user-meta' }, user.role_name || user.email || ''),
      ),
      h('button', { class: 'ar-eye-btn', title: 'Lihat Detail',
        onClick: e => { e.stopPropagation(); onUserOpen(userId); }
      }, icon('eye', 13)),
    ));

    // Day cells
    for (const d of days) {
      const w = dow(year, monthIdx, d);
      const we = isWeekend(w);
      const rec = userDays[d];
      const code = we ? 'WE' : deriveCode(rec);
      const dot = we
        ? h('span', { class: 'ar-status-dot ar-status-dot--small ar-status-dot--empty' })
        : h('span', { class: 'ar-status-dot ar-status-dot--small', style: { background: statusColor(code) } });
      const cell = h('div', {
        class: 'ar-grid__cell' + (we ? ' ar-grid__cell--weekend' : ''),
        title: we ? 'Akhir Pekan' : (STATUS[code] ? STATUS[code].label : ''),
        dataset: { userId: String(userId), day: String(d), code },
      }, dot);
      if (!we) {
        cell.addEventListener('click', () => onCellClick(userId, d));
      }
      row.appendChild(cell);
    }

    // Summary col — for admin show stat pills + schedule edit; otherwise stat pills.
    const sumCol = h('div', { class: 'ar-grid__sum' });
    const counts = summaryData
      ? { P: summaryData.present_days || 0, L: summaryData.late_count || 0, A: summaryData.absent_count || 0,
          Le: summaryData.leave_days || 0, EC: summaryData.early_checkout_count || 0, OT: 0 }
      : sum || { P: 0, L: 0, A: 0, Le: 0, OT: 0, EC: 0 };

    [['P', 'P'], ['L', 'L'], ['A', 'A'], ['Le', 'Le'], ['EC', 'EC'], ['OT', 'OT']].forEach(([k, lbl]) => {
      if ((counts[k] || 0) > 0) {
        sumCol.appendChild(h('span', { class: 'ar-sum-pill', style: { color: statusColor(k) } },
          h('span', { class: 'ar-sum-pill__num' }, String(counts[k])),
          h('span', { class: 'ar-sum-pill__lbl' }, lbl),
        ));
      }
    });

    if (isAdminHr) {
      sumCol.appendChild(renderScheduleEditor({
        userId,
        summary: summaryData,
        endpoint: state.endpoints.setSchedule,
      }));
    }

    row.appendChild(sumCol);
    frag.appendChild(row);
  }

  // Width = user col + day cols + summary col
  const minWidth = 220 + days.length * 32 + 220;
  const wrap = h('div', { class: 'ar-grid', style: { minWidth: minWidth + 'px' } });
  wrap.appendChild(frag);
  mount(root, wrap);

  return { totalPages, total, page: safePage };
}
