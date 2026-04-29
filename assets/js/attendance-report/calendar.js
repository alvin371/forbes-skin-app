export const MONTH_NAMES = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export const DAY_NAMES_SHORT = ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'];
export const DAY_NAMES_FULL = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export function parseMonth(yyyymm) {
  const [y, m] = yyyymm.split('-').map(Number);
  return { year: y, month: m - 1 };
}

export function formatMonth(year, monthIdx) {
  return `${year}-${String(monthIdx + 1).padStart(2, '0')}`;
}

export function shiftMonth(yyyymm, delta) {
  const { year, month } = parseMonth(yyyymm);
  const d = new Date(year, month + delta, 1);
  return formatMonth(d.getFullYear(), d.getMonth());
}

export function daysIn(year, monthIdx) {
  return new Date(year, monthIdx + 1, 0).getDate();
}

export function dow(year, monthIdx, day) {
  return new Date(year, monthIdx, day).getDay();
}

export function isWeekend(d) {
  return d === 0 || d === 6;
}

export function dayArray(year, monthIdx) {
  const n = daysIn(year, monthIdx);
  return Array.from({ length: n }, (_, i) => i + 1);
}
