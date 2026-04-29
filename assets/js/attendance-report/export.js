import { dayArray, dow, isWeekend, DAY_NAMES_SHORT, MONTH_NAMES } from './calendar.js';
import { deriveCode, STATUS } from './status.js';

let xlsxPromise = null;
function loadXlsx() {
  if (!xlsxPromise) {
    xlsxPromise = import('https://cdn.sheetjs.com/xlsx-0.20.2/package/xlsx.mjs');
  }
  return xlsxPromise;
}

function timeOnly(ts) {
  if (!ts) return '';
  return ts.length >= 16 ? ts.slice(11, 16) : ts;
}

function buildRows({ users, matrix, year, monthIdx }) {
  const days = dayArray(year, monthIdx);
  const header = [
    'Karyawan', 'Peran', 'Jadwal',
    ...days.map(d => `${d} ${DAY_NAMES_SHORT[dow(year, monthIdx, d)]}`),
    'Hadir', 'Terlambat', 'Tidak Hadir', 'Cuti', 'Pulang Cepat',
  ];

  const idx = {};
  for (const id in matrix) {
    const m = {};
    for (const r of matrix[id]) m[parseInt(r.date.slice(8, 10), 10)] = r;
    idx[id] = m;
  }

  const rows = users.map(u => {
    const days_ = idx[u.id] || {};
    let P = 0, L = 0, A = 0, Le = 0, EC = 0;
    const cells = days.map(d => {
      const w = dow(year, monthIdx, d);
      if (isWeekend(w)) return 'WE';
      const r = days_[d];
      const code = deriveCode(r);
      if (code === 'P') P++;
      else if (code === 'L') L++;
      else if (code === 'A') A++;
      else if (code === 'Le') Le++;
      else if (code === 'EC') EC++;
      const ci = timeOnly(r && r.first_in);
      const co = timeOnly(r && r.last_out);
      return ci ? `${STATUS[code].abbr} ${ci}→${co || '?'}` : STATUS[code].abbr;
    });
    return [u.full_name, u.role_name || '', '', ...cells, P, L, A, Le, EC];
  });

  return { header, rows };
}

export async function exportExcel({ users, matrix, year, monthIdx }) {
  const XLSX = await loadXlsx();
  const { header, rows } = buildRows({ users, matrix, year, monthIdx });
  const sheet = XLSX.utils.aoa_to_sheet([
    [`Laporan Kehadiran — ${MONTH_NAMES[monthIdx]} ${year}`],
    [],
    header,
    ...rows,
  ]);
  const days = dayArray(year, monthIdx);
  sheet['!cols'] = [{ wch: 28 }, { wch: 14 }, { wch: 14 }, ...days.map(() => ({ wch: 14 })), ...Array(5).fill({ wch: 8 })];
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, sheet, 'Kehadiran');
  XLSX.writeFile(wb, `Laporan_Kehadiran_${MONTH_NAMES[monthIdx]}_${year}.xlsx`);
}

export function exportPdf({ endpoint, month, userId }) {
  const url = new URL(endpoint, window.location.origin);
  url.searchParams.set('month', month);
  if (userId) url.searchParams.set('user_id', String(userId));
  window.open(url.toString(), '_blank', 'noopener');
}
