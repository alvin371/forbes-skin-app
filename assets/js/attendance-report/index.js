import { h, mount, clear } from './dom.js';
import { createStore } from './store.js';
import { renderToolbar } from './toolbar.js';
import { renderGrid } from './grid.js';
import { renderPagination } from './pagination.js';
import { openCellDetail, closeCellDetail } from './modal.js';
import { openPanel, closePanel } from './panel.js';
import { exportExcel, exportPdf } from './export.js';
import { fetchMonth } from './loader.js';
import { parseMonth } from './calendar.js';

(function bootstrap() {
  const root = document.getElementById('attendance-app');
  if (!root) return;
  const dataEl = document.getElementById('attendance-bootstrap');
  if (!dataEl) {
    root.appendChild(h('div', { class: 'ar-empty' }, 'Data tidak tersedia.'));
    return;
  }
  let bootData;
  try { bootData = JSON.parse(dataEl.textContent || '{}'); }
  catch (e) {
    root.appendChild(h('div', { class: 'ar-empty' }, 'Data tidak valid.'));
    return;
  }

  // Normalize: when single-user view, fold singleReport into matrix + users so
  // the grid+panel can treat it uniformly.
  if (bootData.targetUser && bootData.singleReport && Array.isArray(bootData.singleReport.daily)) {
    const tid = Number(bootData.targetUser.id);
    bootData.matrix = bootData.matrix || {};
    if (!bootData.matrix[tid]) bootData.matrix[tid] = bootData.singleReport.daily;
    if (!bootData.summaries[tid] && bootData.singleReport.summary) {
      bootData.summaries[tid] = bootData.singleReport.summary;
    }
    bootData.users = bootData.users || [];
    if (!bootData.users.find(u => Number(u.id) === tid)) {
      bootData.users = [...bootData.users, bootData.targetUser];
    }
  }

  const store = createStore(bootData);

  // Skeleton: toolbar / grid-wrap (with loader overlay) / pagination
  const toolbarEl   = h('div');
  const gridEl      = h('div');
  const scrollHintEl = h('div', { class: 'ar-scroll-hint' },
    h('span', { class: 'ar-scroll-hint__icon' }, '↔'),
    h('span', null, 'Geser horizontal untuk lihat semua tanggal')
  );
  const gridWrap    = h('div', { class: 'ar-grid-wrap', style: { position: 'relative' } }, gridEl);
  const paginationEl = h('div');

  root.appendChild(toolbarEl);
  root.appendChild(scrollHintEl);
  root.appendChild(gridWrap);
  root.appendChild(paginationEl);

  function setLoading(on) {
    let loader = gridWrap.querySelector('.ar-loader');
    if (on && !loader) {
      gridWrap.appendChild(h('div', { class: 'ar-loader' }, h('div', { class: 'ar-spinner' })));
    } else if (!on && loader) {
      loader.remove();
    }
  }

  // ── Helpers to find user by id (server returns id as int; bootstrap selectedUserId may be int) ──
  function findUser(id) {
    const state = store.get();
    return state.users.find(u => Number(u.id) === Number(id))
        || (state.targetUser && Number(state.targetUser.id) === Number(id) ? state.targetUser : null);
  }

  function resolveSchedule(userId) {
    const state = store.get();
    const sum = state.summaries[userId];
    if (sum && sum.start_time && sum.end_time) return `${sum.start_time} – ${sum.end_time}`;
    return null;
  }

  // ── Render ─────────────────────────────────────────
  function render() {
    renderToolbar(toolbarEl, store, {
      onMonthChange: m => loadMonth(m),
      onFilterChange: v => { store.set({ selUser: v, page: 1 }); render(); },
      onPerPageChange: n => { store.set({ perPage: n, page: 1 }); render(); },
      onExportExcel: handleExportExcel,
      onExportPdf: handleExportPdf,
    });

    const meta = renderGrid(gridEl, store, {
      onCellClick: handleCellClick,
      onUserOpen: handleUserOpen,
    });

    renderPagination(paginationEl, {
      page: meta.page || store.get().page,
      perPage: store.get().perPage,
      total: meta.total,
      totalPages: meta.totalPages,
    }, p => { store.set({ page: p }); render(); });
  }

  // ── Actions ────────────────────────────────────────
  async function loadMonth(month) {
    const state = store.get();
    setLoading(true);
    try {
      const data = await fetchMonth(state.endpoints.data, month, state.selectedUserId);
      const { year, month: monthIdx } = parseMonth(data.month);
      store.set({
        month: data.month,
        year,
        monthIdx,
        users: data.users || state.users,
        matrix: data.matrix || {},
        summaries: data.summaries || {},
        singleReport: data.singleReport || null,
        page: 1,
      });
      // Sync URL without reload
      const url = new URL(window.location.href);
      url.searchParams.set('month', data.month);
      window.history.replaceState({}, '', url.toString());
      render();
    } catch (e) {
      console.error('[attendance-report] loadMonth failed:', e);
      alert('Gagal memuat data bulan: ' + e.message);
    } finally {
      setLoading(false);
    }
  }

  function handleCellClick(userId, day) {
    const state = store.get();
    const user = findUser(userId);
    if (!user) return;
    const userDaily = state.matrix[userId] || (state.singleReport && state.singleReport.daily) || [];
    const row = userDaily.find(r => parseInt(r.date.slice(8, 10), 10) === day);
    openCellDetail({
      user,
      day,
      year: state.year,
      monthIdx: state.monthIdx,
      row,
      schedule: resolveSchedule(userId),
    });
  }

  function handleUserOpen(userId) {
    const state = store.get();
    const user = findUser(userId);
    if (!user) return;
    const daily = state.matrix[userId]
      || (state.singleReport && Number(state.targetUser?.id) === Number(userId) ? state.singleReport.daily : [])
      || [];
    const sum = state.summaries[userId];
    const schedule = sum ? `${sum.start_time} – ${sum.end_time}` : null;
    openPanel({
      user,
      year: state.year,
      monthIdx: state.monthIdx,
      daily,
      schedule,
      isSpecial: !!(sum && sum.special_schedule),
    });
  }

  async function handleExportExcel() {
    const state = store.get();
    const users = state.selUser === 'all' ? state.users : state.users.filter(u => String(u.id) === String(state.selUser));
    try {
      await exportExcel({
        users,
        matrix: state.matrix,
        year: state.year,
        monthIdx: state.monthIdx,
      });
    } catch (e) {
      console.error(e);
      alert('Ekspor Excel gagal: ' + e.message);
    }
  }

  function handleExportPdf() {
    const state = store.get();
    const userId = state.selUser !== 'all' ? Number(state.selUser) : 0;
    exportPdf({
      endpoint: state.endpoints.exportPdf,
      month: state.month,
      userId,
    });
  }

  // ── Global ESC / single-user auto-open ────────────
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeCellDetail();
      closePanel();
    }
  });

  render();

  // Auto-open user panel for ?user_id=X URLs OR for non-admin (own report)
  const initState = store.get();
  const autoOpenId = initState.selectedUserId
    || (!initState.isAdminHr && initState.targetUser ? Number(initState.targetUser.id) : 0);
  if (autoOpenId) handleUserOpen(autoOpenId);
})();
