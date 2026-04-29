import { parseMonth } from './calendar.js';

export function createStore(bootstrap) {
  const { year, month } = parseMonth(bootstrap.month);
  let state = {
    month: bootstrap.month,
    year,
    monthIdx: month,
    isAdminHr: !!bootstrap.isAdminHr,
    selectedUserId: bootstrap.selectedUserId | 0,
    users: bootstrap.users || [],
    targetUser: bootstrap.targetUser || null,
    matrix: bootstrap.matrix || {},
    summaries: bootstrap.summaries || {},
    singleReport: bootstrap.singleReport || null,
    endpoints: bootstrap.endpoints || {},
    selUser: 'all',
    page: 1,
    perPage: 8,
    loading: false,
    detail: null,   // { userId, day }
    panelUserId: null,
    exportOpen: false,
  };

  const subs = new Set();

  function get() { return state; }

  function set(partial) {
    state = { ...state, ...partial };
    subs.forEach(fn => fn(state));
  }

  function subscribe(fn) {
    subs.add(fn);
    return () => subs.delete(fn);
  }

  return { get, set, subscribe };
}

export function buildBuildersIndex(state) {
  // index daily array by day-of-month for O(1) cell lookup
  const idx = {};
  for (const userId in state.matrix) {
    const arr = state.matrix[userId];
    const map = {};
    for (const row of arr) {
      const d = parseInt(row.date.slice(8, 10), 10);
      map[d] = row;
    }
    idx[userId] = map;
  }
  return idx;
}
