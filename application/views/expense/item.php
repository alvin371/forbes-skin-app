<?php
if (!function_exists('escv')) {
    function escv($v) {
        if (is_array($v)) return implode(',', array_map('html_escape', $v));
        return html_escape((string)$v);
    }
}

$MODULE = $MODULE ?? 'expense';
$totals = $totals ?? null;

$rows = [];
$fmt = function($n){ return is_numeric($n) ? number_format((float)$n,0,'','.') : ($n ?? ''); };

foreach ($data as $v) {
    $rows[] = [
        'id'               => (int)($v['id'] ?? 0),
        'date'             => DATE('Y-m-d', strtotime($v['date'])) ?? '',
        'brand'            => $v['brand'] ?? '',
        'category'         => $v['category'] ?? '',
        'title'            => $v['title'] ?? '',
        'desc'             => $v['desc'] ?? '',
        'price'            => (float)($v['price'] ?? 0),
        'price_fmt'        => $fmt($v['price'] ?? 0),
        'customer_text'    => $v['customer_text'] ?? '',
        'customer'         => (int)($v['customer'] ?? 0),
        'type'             => $v['type'] ?? '',
        'type_sub'         => $v['type_sub'] ?? '',
        'price_total'    => (float)(abs($v['price_total']) ?? 0),
        'price_total_fmt'=> $fmt(abs($v['price_total']) ?? 0),
        'is_recurring'     => (int)($v['is_recurring'] ?? 0),
        'last_generated_at'=> DATE('Y-m-d', strtotime($v['last_generated_at'] ?? '')) ?? '',
        'percent'          => (float)($v['percent'] ?? 0),
        'percent_fmt'      => $fmt($v['percent'] ?? 0),
        'net_sales'        => (float)($v['net_sales'] ?? 0),
        'net_sales_fmt'    => $fmt($v['net_sales'] ?? 0),
        'recurring_type'   => $v['recurring_type'] ?? '',
    ];
}
?>

<!-- Toolbar -->
<div id="tableToolbar" class="mb-2 d-flex align-items-center gap-2">
  <button id="btnResetFilters" type="button" class="btn btn-sm btn-outline-danger" 
    style="padding: 2px 6px !important; font-size: 12px !important; line-height: 1.2 !important; height: 24px !important;">
    <i class="bi bi-x-circle me-1"></i>Reset Filter
  </button>
</div>

<!-- Ringkasan filter aktif (opsional) -->
<?php if (!empty($filters) && is_array($filters)): ?>
  <div class="mb-2 small text-muted">
    <strong>Filter aktif:</strong>
    <?php foreach ($filters as $f): ?>
      <span class="badge bg-light text-dark border">
        <?= escv($f['field']) ?> <?= escv($f['op'] ?? 'equals') ?> "<?= escv($f['value']) ?>"
      </span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div id="ui-portal"></div>

<div id="agGridWrapper" class="ag-theme-quartz">
  <div id="myGrid" style="width:100%; height:100%;"></div>
  <div id="filter-portal"></div>
</div>

<style>
  .dropdown-panel { max-width: 320px; z-index: 1050;}
  .btn-xs { font-size:.8rem; line-height:1; }
  .hdr-filter .btn-filter { background:none; border:none; cursor:pointer; }
  .hdr-dd-portal {
    position:absolute; z-index:1050; width:260px; background:#fff; border:1px solid #ddd;
    border-radius:.5rem; padding:.5rem; box-shadow:0 10px 30px rgba(0,0,0,.08);
  }
  .hdr-dd-portal .actions { display:flex; gap:.5rem; justify-content:flex-end; margin-top:.5rem; }
  .btn-2xs { font-size:.75rem; padding:.15rem .4rem; border:1px solid #ddd; border-radius:.35rem; background:#f8f9fa; }
  .bg-green.badge { background:#198754 !important; }
  .bg-red.badge { background:#dc3545 !important; }
  .bg-blue.badge { background:#0d6efd !important; }
  .bg-grey.badge { background:#6c757d !important; }
</style>

<script>
(function(){
// ===========================
// Konfigurasi modul
// ===========================
const MODULE = '<?= $MODULE ?>'; // 'expense'
const LIST_URL   = `<?= base_url() ?>${MODULE}/item`;
const DISTINCT_URL = `<?= base_url() ?>${MODULE}/filter_values`;

// ===========================
// Helpers
// ===========================
function debounce(fn, ms=120){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }
function fitCols() {
  if (!window.gridOptions?.api) return;
  gridOptions.api.sizeColumnsToFit({ defaultMinWidth: 60 });
}
function saveFilterStateToSession(){
  try{
    const obj = {};
    Object.entries(valueFilters).forEach(([f, set])=>{
      if (set instanceof Set && set.size>0) obj[f] = Array.from(set);
    });
    sessionStorage.setItem('agGridFilters', JSON.stringify(obj));
  }catch(e){ console.warn('save filters fail', e); }
}


function badge(cls, text) { if (!text) text='-'; return `<span class="${cls} badge">${text}</span>`; }
function escHtml(s){return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function delAll(params, base){params.delete(base); params.delete(base+'[]');}
function getAllEither(params, base){const a=params.getAll(base); return a&&a.length?a:params.getAll(base+'[]');}
function appendArr(params, base, value){params.append(base+'[]', value);}

function formatRupiah(n){ if(n==null) return '-'; try{return (+n).toLocaleString('id-ID')}catch{return n} }

function dateFormat(dateStr){
  if (!dateStr || dateStr === '-') return null;
  const d = new Date(dateStr); if (isNaN(d)) return null;
  const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0');
  return `${y}-${m}-${day}`;
}

// ===========================
// FOOTER TOTALS - dihitung dari rows yang sedang ditampilkan (current page)
// ===========================

const nf0 = new Intl.NumberFormat('id-ID');
const nf2 = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function toNum(v) {
  if (typeof v === 'number') return isFinite(v) ? v : 0;
  if (typeof v !== 'string') return 0;
  const s = v.replace(/[^0-9,.\-]/g,'');
  const norm = (s.indexOf(',') > s.indexOf('.'))
    ? s.replace(/\./g, '').replace(',', '.')
    : s;
  const n = Number(norm);
  return isFinite(n) ? n : 0;
}

function ymdToBulanKey(ymd) {
  if (!ymd) return '';
  return ymd.slice(0, 7) + '-01';
}

function computeDisplayedTotals() {
  if (!window.gridOptions?.api) return null;

  let price = 0, qty = 0, price_total = 0, net_price = 0, percent = 0;
  const seen = new Set();
  let sumNetSales = 0;

  gridOptions.api.forEachNodeAfterFilterAndSort(node => {
    const d = node.data || {};

    price       += toNum(d.price);
    qty         += toNum(d.qty);
    price_total += Math.abs(toNum(d.price_total));
    net_price   += toNum(d.net_price);
    percent     += toNum(d.percent);

    const brand = d.brand || '';
    const bulanKey = ymdToBulanKey(d.date || '');
    const key = brand + '|' + bulanKey;
  });

  return { price, qty, price_total, net_price, percent };
}


function buildFooterRowFromTotals(t) {
  const displayed = gridOptions.api.getColumns().filter(c => c.isVisible());
  const row = {};
  if (displayed.length) {
    const firstColId = displayed[0].getColId();
    row[firstColId] = 'TOTAL';
  }

  const numericCols = new Set(['price', 'qty', 'price_total', 'net_price', 'percent']);

  (gridOptions.api.getColumns() || []).forEach(col => {
    const id = col.getColId();
    if (!col.isVisible()) return;

    if (numericCols.has(id)) {
      let val = Number(t?.[id] || 0);
      if (id === 'qty') {
        row[id] = nf0.format(val);
      } else if (id === 'percent') {
        row[id] = val;
      } else {
        row[id] = 'Rp ' + nf0.format(val);
      }
    } else {
      if (row[id] == null) row[id] = '';
    }
  });

  return row;
}

function renderFooterTotals() {
  if (!window.gridOptions?.api) return;
  const t = computeDisplayedTotals();
  if (!t) { gridOptions.api.setGridOption('pinnedBottomRowData', []); return; }
  const row = buildFooterRowFromTotals(t);
  gridOptions.api.setGridOption('pinnedBottomRowData', [row]);
}


// ===========================
// Sort (server-side)
// ===========================
const SERVER_SORTABLE = new Set([
  'id','date','brand','category','title','price','qty','customer','type',
  'type_sub','price_total','price_total','net_price','created_at','updated_at','status','is_recurring', 'percent',
]);

function reloadWithSort(colId, order){
  const params = new URLSearchParams(window.location.search);
  if (colId && SERVER_SORTABLE.has(colId)) {
    params.set('sort_column', colId);
    params.set('sort_order', (order || 'asc').toUpperCase());
  } else {
    params.delete('sort_column'); params.delete('sort_order');
  }
  params.set('page','1');
  const p = buildParamsWithFilters(params);
  fetchGridDataWithParams(p);
}

function handleInitialSortState(){
  const urlParams = new URLSearchParams(window.location.search);
  const col = urlParams.get('sort_column');
  const order = (urlParams.get('sort_order') || '').toLowerCase();
  if (!col || !window.gridOptions?.api) return;
  window.gridOptions.api.applyColumnState({
    defaultState:{sort:null},
    state:[{ colId: col, sort: order==='asc'?'asc':(order==='desc'?'desc':null) }]
  });
}

// ===========================
// External Filter State - SERVER SIDE
// ===========================
const valueFilters = Object.create(null);
const filterableFields = [
  'id','date','brand','category','title','desc','price','qty','customer_text','customer',
  'type','type_sub','price_total','price_total','net_price','created_by','updated_by',
  'created_at','updated_at','status','is_recurring','recurring_type','recurring_day',
  'recurring_date','last_generated_at', 'percent'
];
const distinctCache = Object.create(null);

function buildParamsWithFilters(baseParams = new URLSearchParams(window.location.search)){
  const params = new URLSearchParams(baseParams.toString());
  delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
  Object.entries(valueFilters).forEach(([field, values])=>{
    if (values instanceof Set && values.size>0){
      for (const v of values){
        appendArr(params,'filter_field',field);
        appendArr(params,'filter_value',v);
        appendArr(params,'filter_operator','equals');
      }
    }
  });
  params.set('page','1');
  return params;
}

async function fetchGridDataWithParams(params){
  const newUrl = `${window.location.pathname}?${params.toString()}`;
  window.history.pushState({}, '', newUrl);
  const url = `${LIST_URL}?${params.toString()}`;
  try {
    const res = await fetch(url, { headers:{'Accept':'application/json'} });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Fetch failed');
    if (window.gridOptions?.api){
      window.gridOptions.api.setGridOption('rowData', json.rows || []);
      renderFooterTotals();
    }
  } catch (err){ console.error('fetchGridDataWithParams error:', err); }
}


function applyServerSideFilters(){
  const params = new URLSearchParams(window.location.search);
  delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
  Object.entries(valueFilters).forEach(([field, values])=>{
    if (values instanceof Set && values.size>0){
      Array.from(values).forEach(value=>{
        appendArr(params,'filter_field',field);
        appendArr(params,'filter_value',value);
        appendArr(params,'filter_operator','equals');
      });
    }
  });
  params.set('page','1');
  const p = buildParamsWithFilters(params);
  fetchGridDataWithParams(p);
}

function clearAllFilters(){
  filterableFields.forEach(f=>{ if (valueFilters[f]) valueFilters[f].clear(); });
  const paramsNow = new URLSearchParams(window.location.search);
  delAll(paramsNow,'filter_field'); delAll(paramsNow,'filter_value'); delAll(paramsNow,'filter_operator');
  paramsNow.set('page','1');
  sessionStorage.removeItem('agGridFilters');
  const p = buildParamsWithFilters(paramsNow);
  fetchGridDataWithParams(p);
}

function loadFilterStateFromURL(){
  const params = new URLSearchParams(window.location.search);
  const filterFields = getAllEither(params,'filter_field');
  const filterValues = getAllEither(params,'filter_value');
  Object.keys(valueFilters).forEach(k=>{ if (valueFilters[k] instanceof Set) valueFilters[k].clear(); });
  if (filterFields.length === filterValues.length && filterFields.length>0){
    for (let i=0;i<filterFields.length;i++){
      const field = filterFields[i], value = filterValues[i];
      if (!valueFilters[field]) valueFilters[field] = new Set();
      valueFilters[field].add(value);
    }
  } else if (filterFields.length === 0){
    try{
      const saved = sessionStorage.getItem('agGridFilters');
      if (saved){
        const filters = JSON.parse(saved);
        Object.entries(filters).forEach(([field, values])=>{
          if (Array.isArray(values)){
            if (!valueFilters[field]) valueFilters[field] = new Set();
            values.forEach(v=>valueFilters[field].add(v));
          }
        });
      }
    }catch(e){ console.error('Error loading filters from sessionStorage:', e); }
  }
}

async function fetchDistinct(field){
  if (distinctCache[field]) return distinctCache[field];
  try {
    const params = new URLSearchParams(window.location.search);
    params.set('field', field);
    delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
    const url = `${DISTINCT_URL}?${params.toString()}`;
    const res = await fetch(url, { headers:{'Accept':'application/json'} });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Failed to fetch distinct values');
    distinctCache[field] = json.values || [];
    return distinctCache[field];
  } catch (error){
    console.error(`Error fetching distinct values for ${field}:`, error);
    if (window.gridOptions?.api){
      const values = new Set();
      window.gridOptions.api.forEachNode(node=>{
        const value = node.data[field];
        values.add(value !== undefined && value !== null && value !== '' ? String(value) : '-');
      });
      distinctCache[field] = Array.from(values).sort();
      return distinctCache[field];
    }
    return [];
  }
}

document.addEventListener('DOMContentLoaded', function(){
  loadFilterStateFromURL();
  const btnReset = document.getElementById('btnResetFilters');
  if (btnReset) btnReset.addEventListener('click', () => clearAllFilters());
});

// ===========================
// Header renderer dengan dropdown checkbox
// ===========================
// === GANTI bagian sorting di headerWithFilter ===
function headerWithFilter(field, title) {
  const id = 'hdrdd_' + field + '_' + Math.random().toString(36).slice(2);
  return {
    headerName: title,
    field,
    filter: (field === 'percent' || field === 'total') ? 'agNumberColumnFilter' : 'agTextColumnFilter',
    floatingFilter: false,
    cellRendererParams: {},
    headerComponent: class {
      init(params) {
        this.params = params;
        const e = this.eGui = document.createElement('div');
        e.className = 'hdr-filter ag-header-cell sortable';

        const titleEl = document.createElement('span');
        titleEl.textContent = title;
        titleEl.className = 'hdr-title';
        titleEl.tabIndex = 0;

        const sortIcon = document.createElement('i');
        sortIcon.className = 'bi bi-arrow-down-up';

        const btn = document.createElement('button');
        btn.className = 'btn-filter bi bi-funnel';
        btn.setAttribute('aria-label','Filter');

        this.btn = btn;
        this.field = field;
        this.id = id;
        this.isOpen = false; // <- state panel header

        const doSort = () => {
          const currentSort = this.params.column.getSort();
          let newSort = currentSort === 'asc' ? 'desc' : (currentSort === 'desc' ? null : 'asc');
          if (newSort) {
            this.params.api.applyColumnState({ defaultState:{sort:null}, state:[{ colId:this.field, sort:newSort }] });
          } else {
            this.params.column.setSort(null);
          }
          reloadWithSort(newSort ? this.field : null, newSort);
        };

        titleEl.addEventListener('click', doSort);
        titleEl.addEventListener('keydown', (ev) => {
          if (ev.key==='Enter'||ev.key===' ') { ev.preventDefault(); doSort(); }
        });

        const updateSortIcon = () => {
          const s = this.params.column.getSort();
          sortIcon.className = s==='asc' ? 'bi bi-arrow-up' : (s==='desc' ? 'bi bi-arrow-down' : 'bi bi-arrow-down-up');
          sortIcon.style.visibility='visible';
        };
        updateSortIcon();
        this.params.column.addEventListener('sortChanged', updateSortIcon);

        btn.addEventListener('click', (ev) => { ev.stopPropagation(); this.toggle(); });

        e.appendChild(titleEl);
        e.appendChild(sortIcon);
        e.appendChild(btn);

        // lazy load nilai filter
        this._ensureLoaded = async () => {
          if (this._loaded) return;
          const values = await fetchDistinct(field);
          this._allValues = values;
          if (!valueFilters[field]) valueFilters[field] = new Set();
          this._loaded = true;
        };

        this.toggle = async () => {
          if (!this._loaded) await this._ensureLoaded();
          document.querySelectorAll('.hdr-dd-portal').forEach(el => el.parentNode.removeChild(el));
          if (!this.isOpen) this.openDropdown(); else this.isOpen = false;
        };

        this.openDropdown = () => {
          this.isOpen = true;
          const wrapper = document.getElementById('agGridWrapper');
          const portal  = document.getElementById('filter-portal');

          const dd = document.createElement('div');
          dd.className = 'hdr-dd-portal';
          dd.innerHTML = `
            <input type="text" class="form-control-sm search" placeholder="Cari nilai..." />
            <div class="list"></div>
            <div class="actions">
              <button type="button" class="btn-2xs btn-clear">Clear</button>
              <button type="button" class="btn-2xs btn-apply">Apply</button>
            </div>
          `;
          portal.appendChild(dd);

          const position = () => {
            const btnRect  = this.btn.getBoundingClientRect();
            const wrapRect = wrapper.getBoundingClientRect();
            let left = btnRect.left - wrapRect.left;
            let top  = btnRect.bottom - wrapRect.top;
            const margin = 8;
            const ddWidth = dd.offsetWidth || 260;
            const maxLeft = wrapper.clientWidth - ddWidth - margin;
            if (left > maxLeft) left = Math.max(margin, maxLeft);
            if (left < margin) left = margin;
            dd.style.left = left + 'px';
            dd.style.top  = top  + 'px';
          };

          this.renderList(dd, this._allValues || []);

          // --------- EVENTS
          dd.querySelector('.btn-apply').addEventListener('click', async () => {
            const paramsNow = new URLSearchParams(window.location.search);
            const params    = buildParamsWithFilters(paramsNow);
            await fetchGridDataWithParams(params);
            close(); // <- pakai close() arrow di bawah
          });

          dd.querySelector('.btn-clear').addEventListener('click', async () => {
            valueFilters[this.field] = new Set();
            this.renderList(dd, this._allValues || []);
            const paramsNow = new URLSearchParams(window.location.search);
            const params    = buildParamsWithFilters(paramsNow);
            await fetchGridDataWithParams(params);
          });

          dd.querySelector('.search').addEventListener('input', (evt) => {
            const q = evt.target.value.toLowerCase();
            const values = (this._allValues || []).filter(v => String(v).toLowerCase().includes(q));
            this.renderList(dd, values);
            position();
          });

          const onResize   = () => position();
          const onScroll   = () => position();
          const viewport   = document.querySelector('.ag-center-cols-viewport');
          window.addEventListener('resize', onResize, { passive: true });
          window.addEventListener('scroll', onScroll, true);
          viewport?.addEventListener('scroll', onScroll, { passive: true });

          const clickOutsideHandler = (ev) => {
            if (!dd.contains(ev.target) && !this.btn.contains(ev.target)) close();
          };
          setTimeout(() => document.addEventListener('click', clickOutsideHandler, { once: true }), 0);

          position();

          // ⬇⬇⬇ PENTING: pakai arrow function supaya `this` tetap instance komponen
          const close = () => {
            if (!this.isOpen) return;
            this.isOpen = false;
            if (dd.parentNode) dd.parentNode.removeChild(dd);
            window.removeEventListener('resize', onResize);
            window.removeEventListener('scroll', onScroll, true);
            viewport?.removeEventListener('scroll', onScroll);
          };
          // simpan agar bisa dipanggil di luar kalau perlu
          this._closeDropdown = close;
        };

        this.renderList = (dd, values) => {
          const list = dd.querySelector('.list');
          const sel = valueFilters[this.field] || new Set();
          const html = [
            `<label class="d-block mb-1"><input type="checkbox" data-role="all" ${sel.size === 0 ? 'checked' : ''}> (Semua)</label>`
          ].concat(values.map(v => {
            const val = String(v);
            const checked = sel.size === 0 ? false : sel.has(val);
            return `<label class="d-block mb-1"><input type="checkbox" data-val="${escHtml(val)}" ${checked ? 'checked' : ''}> ${escHtml(val)}</label>`;
          })).join('');
          list.innerHTML = html;

          list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', (ev) => {
              const cbx = ev.target;
              if (cbx.dataset.role === 'all') {
                valueFilters[this.field] = new Set();
                list.querySelectorAll('input[type="checkbox"][data-val]').forEach(x => x.checked = false);
              } else {
                const val = cbx.getAttribute('data-val');
                if (!valueFilters[this.field]) valueFilters[this.field] = new Set();
                if (cbx.checked) valueFilters[this.field].add(val); else valueFilters[this.field].delete(val);
                const hasAny = valueFilters[this.field].size > 0;
                const allCbx = list.querySelector('input[type="checkbox"][data-role="all"]');
                if (allCbx) allCbx.checked = !hasAny;
              }
            });
          });
        };
      }
      getGui(){ return this.eGui; }
      refresh(){ return false; }
    }
  };
}


// ===========================
// Data dari PHP
// ===========================
const rowData = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;

// ===========================
// ColumnDefs untuk EXPENSE
// ===========================
const columnDefs = [
  { 
    ...headerWithFilter('date','Tanggal'),
    width: 120,
    suppressSizeToFit: true,
    cellClass: 'ta-center',
    cellRenderer: p => {
      const isPinnedBottom = p.node?.rowPinned === 'bottom';
      const dateText = p.value || '-';
      const hasRecurring = !isPinnedBottom && String(p.data?.recurring_type || '').trim() !== '';

      const markerClass = hasRecurring ? 'is-pink' : 'is-empty';
      return `<div class="cell-date"><span class="marker ${markerClass}"></span>${escHtml(dateText)}</div>`;
    }
  },
  { 
    ...headerWithFilter('brand','Brand'),
    width: 110,
    suppressSizeToFit: true
  },
  { 
    ...headerWithFilter('category','Kategori'),
    width: 140,
    suppressSizeToFit: true
  },

  // Kolom fleksibel: biarkan flex yang atur
  {
    ...headerWithFilter('title','Judul'),
    flex: 2, minWidth: 220, // << proporsional dan ada batas minimal
    cellRenderer:p=>{
      const title = p.value || '-';
      const isRec = +p.data.is_recurring === 1;
      return `${escHtml(title)} ${isRec?badge('bg-blue','Recurring'):''}`;
    }
  },
  { ...headerWithFilter('desc','Deskripsi'), flex: 1.4, minWidth: 160 },

  // Total & Persentase juga fleksibel
  {
    ...headerWithFilter('price_total','Total'),
    type:'numericColumn',
    flex: 1, minWidth: 110,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.price_total_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  {
    ...headerWithFilter('percent','Persentase'),
    type:'numericColumn',
    flex: 1, minWidth: 120,
    valueFormatter:p=> p.value!=null ? parseFloat(p.data.percent || p.value).toFixed(2) + '%' : '-'
  },

  // Aksi fix & di-pin kanan
  {
    headerName: 'Aksi',
    field: 'id',
    width: 70,
    suppressSizeToFit: true, // << jangan ikut fit
    sortable: false,
    filter: false,
    pinned: 'right',
    lockPinned: true,
    cellRenderer: params => {
      const d = params.data;
      return `
        <div class="dropdown">
          <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots-vertical fs-16"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a href="#!" class="dropdown-item" onclick="edit('${d.id}')"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>
            <li><a href="#!" class="dropdown-item text-danger" onclick="remove('${d.id}')"><i class="bi bi-trash me-2"></i>Delete</a></li>
          </ul>
        </div>
      `;
    }
  }
];


// ===========================
// Grid Options & Mount
// ===========================
const COLSTATE_KEY = `agGrid:columns:${location.pathname}`;

function _getCurrentColumnState(api){
  return (api.getColumnState() || []).map(s=>({
    colId:s.colId, hide:!!s.hide, width:s.width, pinned:s.pinned||null, sort:s.sort||null,
    sortIndex:(typeof s.sortIndex==='number')?s.sortIndex:null
  }));
}
function saveColumnStateToSession(){
  try{ if(!window.gridOptions?.api) return; const state=_getCurrentColumnState(window.gridOptions.api);
    sessionStorage.setItem(COLSTATE_KEY, JSON.stringify(state)); }catch(e){ console.warn('save state fail',e); }
}
function loadColumnStateFromSession(){
  try{ if(!window.gridOptions?.api) return false; const txt=sessionStorage.getItem(COLSTATE_KEY);
    if(!txt) return false; const state=JSON.parse(txt); if(!Array.isArray(state)||state.length===0) return false;
    window.gridOptions.api.applyColumnState({ state, applyOrder:true }); return true;
  }catch(e){ console.warn('load state fail',e); return false; }
}

if (window.gridOptions?.api) { try { window.gridOptions.api.destroy(); } catch(e){} }
window._defaultColState = null;

window.gridOptions = {
  theme: 'legacy',
  defaultColDef: {
    sortable: true,
    filter: true,
    resizable: false,       // tetap terkunci dari drag user
    floatingFilter: false,
    suppressMovable: true,
    lockPinned: true
  },
  suppressMovableColumns: true,
  columnDefs, rowData, animateRows:true,
  pagination: true,
  paginationPageSize: 50,
  paginationPageSizeSelector: [25,50,100,200],
  rowSelection: { mode:'multiRow', checkboxes:true, headerCheckbox:true, selectAll:'filtered', enableClickSelection:false },

  onGridReady: (params) => {
    const cols = params.api.getColumns() || [];
    window._defaultColState = cols.map((c,i)=>({ colId:c.getColId(), hide:!c.isVisible(), order:i }));
    loadColumnStateFromSession();
    setTimeout(()=>{ handleInitialSortState(); loadFilterStateFromURL(); renderFooterTotals(); fitCols(); }, 100);
  },
  onFirstDataRendered: () => { fitCols(); renderFooterTotals(); },
  onRowDataUpdated: () => renderFooterTotals(),
  onModelUpdated: () => renderFooterTotals(),
  onFilterChanged: () => renderFooterTotals(),
  onSortChanged: () => renderFooterTotals(),
  onPaginationChanged: () => renderFooterTotals(),


  onColumnVisible(){ saveColumnStateToSession(); renderFooterTotals(); fitCols(); },
  onColumnPinned(){ saveColumnStateToSession(); renderFooterTotals(); fitCols(); },
  onGridSizeChanged: debounce(()=>{ fitCols(); renderFooterTotals(); }, 80),
};

// 4) Re-fit saat zoom/resize & perubahan ukuran kontainer
const debouncedFit = debounce(fitCols, 80);
window.addEventListener('resize', debouncedFit); // termasuk zoom
const gridWrap = document.getElementById('agGridWrapper');
if (window.ResizeObserver && gridWrap) {
  const ro = new ResizeObserver(debouncedFit);
  ro.observe(gridWrap);
}

(function(){
  const eGridDiv = document.getElementById('myGrid');
  if (!eGridDiv) return;
  const api = agGrid.createGrid(eGridDiv, window.gridOptions);
  window.gridOptions.api = api;
})();

// ===========================
// Column Chooser (tetap)
// ===========================
(function initProgressiveColumnChooser(){
  const btn = document.getElementById('btnColumns');
  const wrapper = document.getElementById('agGridWrapper') || document.body;
  if (!btn || !wrapper) return;

  let isOpen=false, ddRef=null, cleanupFns=[];
  btn.addEventListener('click', ()=>{ isOpen?close():open(); });

  function open(){
    if (!window.gridOptions?.api || isOpen) return;
    const dd = document.createElement('div');
    dd.className = 'dropdown-panel shadow rounded p-2 bg-white border';
    dd.style.position='absolute'; dd.style.width='280px'; dd.style.zIndex=1050; ddRef=dd;

    const cols = gridOptions.api.getColumns()
      .filter(c=>{
        const def=c.getColDef();
        return def && def.headerName && def.field!=='id' && def.headerName!=='Aksi';
      })
      .map(c=>({ id:c.getColId(), title:c.getColDef().headerName||c.getColId(), visible:c.isVisible() }));

    dd.innerHTML = `
      <div class="mb-2">
        <input type="text" class="form-control form-control-sm" placeholder="Cari kolom..." data-role="search">
      </div>
      <div class="mb-2 small d-flex align-items-center gap-2">
        <label class="mb-0"><input type="checkbox" data-role="toggle-all"> Tampilkan semua</label>
        <button type="button" class="btn btn-xs btn-link p-0 ms-auto" data-role="defaults">Defaults</button>
      </div>
      <div class="list" style="max-height:260px;overflow:auto"></div>
      <div class="d-flex justify-content-end gap-2 mt-2">
        <button type="button" class="btn btn-sm btn-light" data-role="cancel">Batal</button>
        <button type="button" class="btn btn-sm btn-primary" data-role="apply">Terapkan</button>
      </div>
    `;
    wrapper.appendChild(dd); isOpen=true;

    const listEl = dd.querySelector('.list');
    const render=(items)=>{ listEl.innerHTML = items.map(it=>`
      <label class="d-block mb-1">
        <input type="checkbox" data-col="${escHtml(it.id)}" ${it.visible?'checked':''}> ${escHtml(it.title)}
      </label>`).join(''); };
    render(cols);

    const position=()=>{
      const b=btn.getBoundingClientRect(), w=wrapper.getBoundingClientRect();
      let left = b.left - w.left, top = b.bottom - w.top + 6, margin=8;
      const maxLeft = wrapper.clientWidth - (dd.offsetWidth||280) - margin;
      if (left>maxLeft) left = Math.max(margin, maxLeft);
      if (left<margin) left = margin;
      dd.style.left = left+'px'; dd.style.top = top+'px';
    };
    position();

    dd.querySelector('[data-role="search"]').addEventListener('input', (e)=>{
      const q = e.target.value.toLowerCase();
      const filtered = cols.filter(c => c.title.toLowerCase().includes(q) || c.id.toLowerCase().includes(q));
      render(filtered); position();
    });

    dd.querySelector('[data-role="toggle-all"]').addEventListener('change', (e)=>{
      const checked = e.target.checked;
      listEl.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb=>cb.checked=checked);
    });

    dd.querySelector('[data-role="defaults"]').addEventListener('click', ()=>{
      if (!window._defaultColState) return;
      gridOptions.api.applyColumnState({
        state: window._defaultColState.map(s=>({ colId:s.colId, hide:s.hide })),
        applyOrder: true
      });
      const currentCols = gridOptions.api.getColumns() || [];
      const vis = Object.fromEntries(currentCols.map(c=>[c.getColId(), c.isVisible()]));
      listEl.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb=>{
        const id = cb.getAttribute('data-col'); cb.checked = !!vis[id];
      });
      saveColumnStateToSession(); position();
    });

    dd.querySelector('[data-role="cancel"]').addEventListener('click', ()=>close());

    dd.querySelector('[data-role="apply"]').addEventListener('click', ()=>{
      const checks = Array.from(listEl.querySelectorAll('input[type="checkbox"][data-col]'));
      const state = checks.map(cb=>({ colId: cb.getAttribute('data-col'), hide: !cb.checked }));
      gridOptions.api.applyColumnState({ state, applyOrder:false });
      saveColumnStateToSession();
      close();
    });

    const clickOutside=(ev)=>{ if (ddRef && !ddRef.contains(ev.target) && ev.target!==btn) close(); };
    setTimeout(()=>document.addEventListener('mousedown', clickOutside), 0);

    const onResize=()=>position(); const onScroll=()=>position(); const viewport=document.querySelector('.ag-center-cols-viewport');
    window.addEventListener('resize', onResize, {passive:true});
    window.addEventListener('scroll', onScroll, true);
    viewport?.addEventListener('scroll', onScroll, {passive:true});

    cleanupFns.push(
      ()=>dd.remove(),
      ()=>document.removeEventListener('mousedown', clickOutside),
      ()=>window.removeEventListener('resize', onResize),
      ()=>window.removeEventListener('scroll', onScroll, true),
      ()=>viewport?.removeEventListener('scroll', onScroll)
    );
  }
  function close(){ if(!isOpen) return; isOpen=false; while(cleanupFns.length){ try{cleanupFns.pop()();}catch(e){} } ddRef=null; }
})();

// ===========================
// Mount & init
// ===========================
setTimeout(()=>{ renderFooterTotals(); }, 100);

})();
function syncSelectedIds(){
  const rows = gridOptions.api.getSelectedRows() || [];
  const ids = rows.map(r => String(r.id));
  document.getElementById('id_selected')?.setAttribute('value', ids.join(','));
  window.list_id_v2 = ids.join(',');
}

$(document).off('change', '.checkAll').on('change', '.checkAll', function(){
  this.checked ? gridOptions.api.selectAll() : gridOptions.api.deselectAll();
});
</script>

<script>
(function () {
    document.addEventListener('show.bs.dropdown', function (e) {
        const toggle = e.target.querySelector('[data-bs-toggle="dropdown"]') || e.target;
        const menu = toggle.nextElementSibling;
        if (!menu) return;
        menu.__origParent = menu.parentNode;
        document.body.appendChild(menu);
        const r = toggle.getBoundingClientRect();
        menu.style.position = 'absolute';
        menu.style.left = (window.scrollX + r.right - menu.offsetWidth) + 'px';
        menu.style.top  = (window.scrollY + r.bottom) + 'px';
        menu.style.zIndex = 2000;
    });
    document.addEventListener('hidden.bs.dropdown', function (e) {
        const toggle = e.target.querySelector('[data-bs-toggle="dropdown"]') || e.target;
        const menu = document.querySelector('.dropdown-menu.show') || toggle.nextElementSibling;
        const menus = document.querySelectorAll('body > .dropdown-menu');
        menus.forEach(function (m) {
            if (m.__origParent) {
                m.__origParent.appendChild(m);
                m.style.position = '';
                m.style.left = '';
                m.style.top = '';
                m.style.zIndex = '';
                delete m.__origParent;
            }
        });
    });
})();
</script>
