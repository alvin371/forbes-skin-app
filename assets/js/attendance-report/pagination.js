import { h, mount } from './dom.js';

function pageNumbers(current, total) {
  const arr = [];
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || Math.abs(i - current) <= 1) arr.push(i);
  }
  const out = [];
  for (let i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i - 1] > 1) out.push('…');
    out.push(arr[i]);
  }
  return out;
}

export function renderPagination(root, { page, perPage, total, totalPages }, onPage) {
  if (total === 0) { mount(root, null); return; }
  const from = (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);

  const btn = (label, opts = {}) => h('button', {
    class: 'ar-page-btn' + (opts.active ? ' ar-page-btn--active' : ''),
    disabled: !!opts.disabled,
    onClick: opts.onClick,
  }, label);

  const numbers = pageNumbers(page, totalPages).map(p =>
    p === '…'
      ? h('span', { class: 'ar-page-ellipsis' }, '…')
      : btn(String(p), { active: p === page, onClick: () => onPage(p) })
  );

  const bar = h('div', { class: 'ar-pagination' },
    h('span', { class: 'ar-pagination__count' },
      'Menampilkan ', h('strong', null, `${from}–${to}`), ' dari ', h('strong', null, String(total)), ' karyawan'
    ),
    btn('«', { disabled: page <= 1, onClick: () => onPage(1) }),
    btn('‹', { disabled: page <= 1, onClick: () => onPage(page - 1) }),
    ...numbers,
    btn('›', { disabled: page >= totalPages, onClick: () => onPage(page + 1) }),
    btn('»', { disabled: page >= totalPages, onClick: () => onPage(totalPages) }),
  );
  mount(root, bar);
}
