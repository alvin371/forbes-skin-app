/**
 * Maps a daily row from the controller to a status code used by UI.
 * Controller emits status string ∈ {Present, Absent, Weekend, Holiday, Leave}
 * with `late` and `early_checkout` boolean flags. We derive UI codes:
 *   P=Present, L=Late, EC=Early Checkout, A=Absent, Le=Leave, H=Holiday, WE=Weekend
 *   OT only if explicitly flagged (none today server-side; reserved for future).
 */
export function deriveCode(row) {
  if (!row) return 'A';
  switch (row.status) {
    case 'Weekend': return 'WE';
    case 'Holiday': return 'H';
    case 'Leave':   return 'Le';
    case 'Absent':  return 'A';
    case 'Present':
      if (row.late) return 'L';
      if (row.early_checkout) return 'EC';
      return 'P';
    default: return 'A';
  }
}

export const STATUS = {
  P:  { label: 'Hadir',         cssVar: '--ar-green',  bgVar: '--ar-green-bg',  abbr: 'H'  },
  L:  { label: 'Terlambat',     cssVar: '--ar-amber',  bgVar: '--ar-amber-bg',  abbr: 'TL' },
  EC: { label: 'Pulang Cepat',  cssVar: '--ar-orange', bgVar: '--ar-orange-bg', abbr: 'PC' },
  A:  { label: 'Tidak Hadir',   cssVar: '--ar-red',    bgVar: '--ar-red-bg',    abbr: 'TH' },
  Le: { label: 'Cuti',          cssVar: '--ar-blue',   bgVar: '--ar-blue-bg',   abbr: 'C'  },
  OT: { label: 'Lembur',        cssVar: '--ar-purple', bgVar: '--ar-purple-bg', abbr: 'L'  },
  H:  { label: 'Libur',         cssVar: '--ar-blue',   bgVar: '--ar-blue-bg',   abbr: 'Lb' },
  WE: { label: 'Akhir Pekan',   cssVar: null,          bgVar: null,             abbr: '—'  },
};

export function statusColor(code) {
  const s = STATUS[code];
  return s && s.cssVar ? `var(${s.cssVar})` : '#a1a1aa';
}

export function statusBg(code) {
  const s = STATUS[code];
  return s && s.bgVar ? `var(${s.bgVar})` : '#f4f4f5';
}

export function summarize(daily) {
  const acc = { P: 0, L: 0, EC: 0, A: 0, Le: 0, OT: 0, H: 0, WE: 0 };
  if (!Array.isArray(daily)) return acc;
  for (const row of daily) {
    const c = deriveCode(row);
    if (acc[c] != null) acc[c]++;
  }
  return acc;
}

export function attendanceRate(summary, workdays) {
  if (!workdays) return 0;
  return Math.round(((summary.P + summary.L + summary.EC + summary.OT) / workdays) * 100);
}
