import { h, icon, mount } from './dom.js';
import { MONTH_NAMES, parseMonth, shiftMonth } from './calendar.js';
import { STATUS } from './status.js';

export function renderToolbar(root, store, { onMonthChange, onFilterChange, onPerPageChange, onExportExcel, onExportPdf }) {
  const state = store.get();
  const { year, monthIdx } = state;

  // Month navigator
  const monthLabel = h('div', { class: 'ar-month-nav__label' }, `${MONTH_NAMES[monthIdx]} ${year}`);
  const monthNav = h('div', { class: 'ar-month-nav' },
    h('button', { class: 'ar-month-nav__btn', title: 'Bulan Sebelumnya',
      onClick: () => onMonthChange(shiftMonth(state.month, -1))
    }, icon('chevL', 14)),
    monthLabel,
    h('button', { class: 'ar-month-nav__btn', title: 'Bulan Berikutnya',
      onClick: () => onMonthChange(shiftMonth(state.month, +1))
    }, icon('chevR', 14)),
  );

  // User filter
  const userSelect = h('select', { onChange: e => onFilterChange(e.target.value) },
    h('option', { value: 'all' }, 'Semua Pengguna'),
    ...state.users.map(u => h('option', { value: String(u.id), selected: String(u.id) === String(state.selUser) }, u.full_name))
  );
  userSelect.value = state.selUser;
  const userFilter = state.isAdminHr ? h('div', { class: 'ar-control' },
    icon('filter', 13), userSelect
  ) : null;

  // Rows per page
  const perPageSelect = h('select', { onChange: e => onPerPageChange(parseInt(e.target.value, 10)) },
    ...[5, 8, 10, 15, 20].map(n => h('option', { value: String(n), selected: n === state.perPage }, String(n)))
  );
  perPageSelect.value = String(state.perPage);
  const perPageCtl = state.isAdminHr ? h('div', { class: 'ar-control' },
    h('span', { class: 'ar-control__label' }, 'Baris'),
    perPageSelect
  ) : null;

  // Legend
  const legend = h('div', { class: 'ar-legend' },
    ...['P', 'L', 'EC', 'A', 'Le', 'OT'].map(code => h('div', { class: 'ar-legend__item' },
      h('span', { class: 'ar-legend__dot', style: { background: `var(${STATUS[code].cssVar})` } }),
      h('span', { class: 'ar-legend__label' }, STATUS[code].label),
    ))
  );

  // Export menu
  const exportBtn = h('button', { class: 'ar-btn', title: 'Ekspor' },
    icon('download', 14), 'Ekspor', icon('chevDown', 13)
  );
  const exportMenu = h('div', { class: 'ar-export__menu', style: { display: 'none' } },
    h('button', {
      class: 'ar-export__item',
      onClick: () => { exportMenu.style.display = 'none'; onExportExcel(); }
    },
      h('span', { class: 'ar-export__icon ar-export__icon--xl' }, 'XL'),
      'Ekspor ke Excel'
    ),
    h('button', {
      class: 'ar-export__item',
      onClick: () => { exportMenu.style.display = 'none'; onExportPdf(); }
    },
      h('span', { class: 'ar-export__icon ar-export__icon--pdf' }, 'PDF'),
      'Ekspor ke PDF'
    ),
  );
  const exportWrap = h('div', { class: 'ar-export' }, exportBtn, exportMenu);
  exportBtn.addEventListener('click', e => {
    e.stopPropagation();
    exportMenu.style.display = exportMenu.style.display === 'none' ? 'block' : 'none';
  });
  document.addEventListener('mousedown', e => {
    if (!exportWrap.contains(e.target)) exportMenu.style.display = 'none';
  });

  const bar = h('div', { class: 'ar-toolbar' },
    monthNav,
    userFilter,
    perPageCtl,
    h('div', { class: 'ar-toolbar__spacer' }),
    legend,
    exportWrap,
  );

  mount(root, bar);
}
