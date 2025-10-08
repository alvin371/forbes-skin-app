<?php
$view = isset($_GET['view']) ? $_GET['view'] : 'card';
if ($view == 'table'):
?>

<?php
// Helper aman untuk print string/array (hindari htmlspecialchars(array))
if (!function_exists('escv')) {
    function escv($v) {
        if (is_array($v)) {
            return implode(',', array_map('html_escape', $v));
        }
        return html_escape((string)$v);
    }
}

// 0) Kumpulkan semua product id dari JSON di seluruh $data
$allProductIds = [];
foreach ($data as $v) {
    if (empty($v['json'])) continue;
    $obj = json_decode($v['json'], true);
    if (!is_array($obj)) continue;
    foreach ($obj as $it) {
        if (!empty($it['product'])) {
            $allProductIds[] = (int)$it['product'];
        }
    }
}
$allProductIds = array_values(array_unique(array_filter($allProductIds)));

// 1) Prefetch price_buy untuk semua product id
$priceBuyMap = [];
if (!empty($allProductIds)) {
    // aman: semua id sudah integer
    $in = implode(',', $allProductIds);
    $rowsPB = $this->mymodel->selectWithQuery("
        SELECT id, price_buy
        FROM product
        WHERE id IN ($in)
    ");
    foreach ($rowsPB as $r) {
        $priceBuyMap[(int)$r['id']] = (float)$r['price_buy'];
    }
}
?>

<!-- Toolbar -->
<div id="tableToolbar" class="mb-2 d-flex align-items-center gap-2">
  <button id="btnColumns" type="button" class="btn btn-sm btn-outline-primary" 
    style="padding: 2px 6px !important; font-size: 12px !important; line-height: 1.2 !important; height: 24px !important;">
    <i class="bi bi-layout-three-columns me-1"></i>Tampilkan Kolom
  </button>
  <button id="btnResetFilters" type="button" class="btn btn-sm btn-outline-danger" 
    style="padding: 2px 6px !important; font-size: 12px !important; line-height: 1.2 !important; height: 24px !important;">
    <i class="bi bi-x-circle me-1"></i>Reset Filter
  </button>
</div>


<!-- (Opsional) Ringkasan filter aktif (pakai $filters dari Controller) -->
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

<!-- Portal dropdown umum (untuk pemilih kolom) -->
<div id="ui-portal"></div>

<div id="agGridWrapper" class="ag-theme-quartz">
    <div id="myGrid" style="width:100%; height:100%;"></div>
    <!-- Portal dropdown untuk header filter -->
    <div id="filter-portal"></div>
</div>

<style>
  /* Rapiin panel dropdown */
  .dropdown-panel { max-width: 320px; z-index: 1050;}
  .btn-xs { font-size:.8rem; line-height:1; }
  .hdr-filter .btn-filter { background:none; border:none; margin-left:.5rem; cursor:pointer; }
  .hdr-dd-portal {
    position:absolute; z-index:1050; width:260px; background:#fff; border:1px solid #ddd;
    border-radius:.5rem; padding:.5rem; box-shadow:0 10px 30px rgba(0,0,0,.08);
  }
  .hdr-dd-portal .actions { display:flex; gap:.5rem; justify-content:flex-end; margin-top:.5rem; }
  .btn-2xs { font-size:.75rem; padding:.15rem .4rem; border:1px solid #ddd; border-radius:.35rem; background:#f8f9fa; }
  .bg-blue.badge { background:#0d6efd !important; }
  .bg-green.badge { background:#198754 !important; }
  .bg-red.badge { background:#dc3545 !important; }
  .bg-grey.badge { background:#6c757d !important; }
</style>

<script>

(function(){
// ===========================
// Helpers
// ===========================

// ===========================
// FOOTER TOTALS
// ===========================
let __lastTotals = null;
// === Tambahan penting: seed totals dari server untuk initial load
const __initialTotals = <?= isset($totals) ? json_encode($totals, JSON_UNESCAPED_UNICODE) : 'null' ?>;
if (__initialTotals) { __lastTotals = __initialTotals; }

function formatRupiah(n) {
  if (n == null) return '-';
  try { return (+n).toLocaleString('id-ID'); } catch { return 'Rp ' + n; }
}

function buildFooterRowFromTotals(totals) {
  // label "TOTAL" diletakkan di kolom pertama yang terlihat
  const cols = gridOptions.api.getColumnDefs();
  const displayed = gridOptions.api.getColumns().filter(c => c.isVisible());

  const row = {};
  if (displayed.length) {
    const firstColId = displayed[0].getColId();
    row[firstColId] = 'TOTAL';
  }

  // mapping kolom numerik yang ingin ditotal + formatter
  const numericCols = new Set([
    'pesanan_count','customer_price','dana_pencairan','omset_kotor',
    'diskon_penjual','biaya_lainnya','omset_bersih','marketplace_fee',
    'komisi_afiliasi','hpp'
  ]);

  // Masukkan nilai ke field yang sesuai
  (gridOptions.api.getColumns() || []).forEach(col => {
    const id = col.getColId();
    if (!col.isVisible()) return; // hanya kolom aktif
    if (numericCols.has(id)) {
      const val = totals?.[id] ?? 0;
      row[id] = (id === 'pesanan_count') ? val : formatRupiah(val);
    } else {
      // biarkan kosong untuk kolom non-numerik (kecuali yang sudah kita isi 'TOTAL')
      if (row[id] == null) row[id] = '';
    }
  });

  return row;
}

function renderFooterTotals() {
  if (!window.gridOptions?.api) return;

  if (!__lastTotals) {
    // kosongkan footer kalau belum ada totals
    gridOptions.api.setGridOption('pinnedBottomRowData', []);
    return;
  }

  const row = buildFooterRowFromTotals(__lastTotals);
  gridOptions.api.setGridOption('pinnedBottomRowData', [row]);
}


function delAll(params, base) {
  params.delete(base);
  params.delete(base + '[]');
}
function getAllEither(params, base) {
  const a = params.getAll(base);
  if (a && a.length) return a;
  return params.getAll(base + '[]');
}
function appendArr(params, base, value) {
  params.append(base + '[]', value);
}

function badge(cls, text) { if (!text) text = '-'; return `<span class="${cls} badge">${text}</span>`; }
function escHtml(s) {
  return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}
function handleInitialSortState() {
  const urlParams = new URLSearchParams(window.location.search);
  const col = urlParams.get('sort_column');
  const order = (urlParams.get('sort_order') || '').toLowerCase();
  if (!col || !window.gridOptions?.api) return;
  window.gridOptions.api.applyColumnState({
    defaultState: { sort: null },
    state: [{ colId: col, sort: order === 'asc' ? 'asc' : order === 'desc' ? 'desc' : null }]
  });
}

// Build URLSearchParams dari valueFilters (pakai [] array)
function buildParamsWithFilters(baseParams = new URLSearchParams(window.location.search)) {
  const params = new URLSearchParams(baseParams.toString());
  delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
  Object.entries(valueFilters).forEach(([field, values]) => {
    if (values instanceof Set && values.size > 0) {
      for (const v of values) {
        appendArr(params,'filter_field',field);
        appendArr(params,'filter_value',v);
        appendArr(params,'filter_operator','equals');
      }
    }
  });
  params.set('page','1');
  return params;
}

// Fetch JSON list (AJAX only, tidak reload full page)
async function fetchGridDataWithParams(params) {
  const newUrl = `${window.location.pathname}?${params.toString()}`;
  window.history.pushState({}, '', newUrl);
  const url = `<?= base_url() ?>transaction/item?${params.toString()}`;
  try {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Fetch failed');
    if (window.gridOptions?.api) {
      window.gridOptions.api.setGridOption('rowData', json.rows || []);
      __lastTotals = json.totals || null;
      renderFooterTotals();
    }
  } catch (err) { console.error('fetchGridDataWithParams error:', err); }
}

function dateFormat(dateStr) {
  if (!dateStr || dateStr === '-') return null;
  const d = new Date(dateStr); if (isNaN(d)) return null;
  const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0');
  return `${y}-${m}-${day}`;
}
function parseProdukLineSingle(jsonStr) {
  if (!jsonStr) return '';
  try {
    const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
    if (!obj || typeof obj !== 'object') return '';
    const parts = [];
    for (const k of Object.keys(obj)) {
      const v2 = obj[k] || {};
      const qty = v2.qty ?? 0;
      const productText = String(v2.product_text ?? '').replace(/amp;/g, '');
      if (qty > 0 && productText) {
        parts.push(`${qty}x ${productText}`);
      }
    }
    return parts.join(' + ');
  } catch {
    return '';
  }
}

const SERVER_SORTABLE = new Set(['id','date','order_id','customer_text','phone','awb_number','order_status','payment_status','pesanan_count','customer_price', 'hpp']);
function reloadWithSort(colId, order) {
  const params = new URLSearchParams(window.location.search);
  if (colId && SERVER_SORTABLE.has(colId)) {
    params.set('sort_column', colId);
    params.set('sort_order', (order || 'asc').toUpperCase());
  } else {
    params.delete('sort_column'); params.delete('sort_order');
  }
  params.set('page','1');
  // **AJAX only**: jangan full reload
  const p = buildParamsWithFilters(params);
  fetchGridDataWithParams(p);
}

// ===========================
// Column State: save/restore to sessionStorage
// ===========================
const COLSTATE_KEY = `agGrid:columns:${location.pathname}`;

// Ambil state kolom yang relevan (ag-Grid akan abaikan properti aneh)
function _getCurrentColumnState(api) {
	return (api.getColumnState() || []).map(s => ({
		colId: s.colId,
		hide: !!s.hide,
		width: s.width,
		pinned: s.pinned || null,
		sort: s.sort || null,
		sortIndex: (typeof s.sortIndex === 'number') ? s.sortIndex : null
	}));
}

function saveColumnStateToSession() {
	try {
		if (!window.gridOptions?.api) return;
		const state = _getCurrentColumnState(window.gridOptions.api);
		sessionStorage.setItem(COLSTATE_KEY, JSON.stringify(state));
	} catch (e) {
		console.warn('saveColumnStateToSession failed:', e);
	}
}

function loadColumnStateFromSession() {
	try {
		if (!window.gridOptions?.api) return false;
		const txt = sessionStorage.getItem(COLSTATE_KEY);
		if (!txt) return false;
		const state = JSON.parse(txt);
		if (!Array.isArray(state) || state.length === 0) return false;
		// applyOrder:true agar urutan kolom ikut state yang tersimpan
		window.gridOptions.api.applyColumnState({ state, applyOrder: true });
		return true;
	} catch (e) {
		console.warn('loadColumnStateFromSession failed:', e);
		return false;
	}
}

function clearSavedColumnState() {
	try { sessionStorage.removeItem(COLSTATE_KEY); } catch {}
}


// ===========================
// Data dari PHP
// ===========================
const rowData = <?php
  $rows = [];
  foreach ($data as $v) {
    $fmt = function($n){ return is_numeric($n) ? number_format($n,0,'','.') : ($n ?? ''); };

    $marketplaceRow = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '".($v['marketplace']??'')."'");
    $marketplaceImg = !empty($marketplaceRow[0]['img']) ? (base_url().'/assets/img/marketplace/'.$marketplaceRow[0]['img']) : (base_url().'/assets/img/marketplace/default.png');

    $shippingRow = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '".($v['shipping']??'')."'");
    $shippingImg = !empty($shippingRow[0]['img']) ? (base_url().'/assets/img/shipping/'.$shippingRow[0]['img']) : (base_url().'/assets/img/shipping/default.png');

    $date_text = $v['date'] ? date('Y-m-d H:i:s', strtotime($v['date'])) : null;
    $customer_price_raw = (float)($v['customer_price'] ?? 0);

    // 2a) Hitung HPP dari JSON x price_buy (join by product id)
    $hppCalc = 0.0;
    if (!empty($v['json'])) {
        $obj = json_decode($v['json'], true);
        if (is_array($obj)) {
            foreach ($obj as $it) {
                $pid = isset($it['product']) ? (int)$it['product'] : 0;
                $qty = (float)($it['qty'] ?? 0);
                $priceBuy = $priceBuyMap[$pid] ?? 0.0;  // kalau id tidak ditemukan, asumsikan 0
                $hppCalc += ($qty * $priceBuy);
            }
        }
    }
    // Jika kamu tetap ingin “mengutamakan” kolom HPP dari DB (jika ada), pakai baris di bawah:
    // $hppFinal = (isset($v['hpp']) && (float)$v['hpp'] > 0) ? (float)$v['hpp'] : $hppCalc;
    // Tapi karena maunya ambil dari join product, langsung pakai $hppCalc:
    $hppFinal = $hppCalc;

    $rows[] = [
      'id'=>(int)$v['id'],
      'order_id'=>$v['order_id'],
      'date_raw'=>$v['date'],
      'date_text'=>$date_text,
      'customer_id'=>(int)$v['customer'],
      'customer_text'=>$v['customer_text'] ?: '-',
      'pesanan_count'=>(int)($v['pesanan_count'] ?? 0),
      'customer_price'=>$customer_price_raw,
      'customer_price_fmt'=>$fmt($customer_price_raw),
      'pencairan_status'=>$v['pencairan_status'] ?: '-',
      'pencairan_at'=>$v['pencairan_at'] ?: null,
      'order_status'=>$v['order_status'] ?: '-',
      'reverse_status'=>$v['reverse_status'] ?: '',
      'awb_number'=>$v['awb_number'] ?: '-',
      'marketplace'=>$v['marketplace'] ?: '-',
      'shop_name'=>$v['shop_name'] ?: 'Manual',
      'is_manual'=>(int)($v['is_manual'] ?? 0),
      'brand'=>$v['brand'] ?: '',
      'rts_at'=>$v['rts_at'] ?: '',
      'phone'=>$v['phone'] ?: '-',
      'payment_type'=>$v['payment_type'] ?? '-',
      'c_username'=>!empty($v['c_username']) ? $v['c_username'] : '-',
      'cs'=>!empty($v['cs']) ? $v['cs'] : '-',
      'shipping'=>!empty($v['shipping']) ? $v['shipping'] : '-',
      'shipping_status'=>'-',
      'reverse_id'=>!empty($v['reverse_id']) ? $v['reverse_id'] : '',
      'return_status'=>!empty($v['return_status']) ? $v['return_status'] : '',
      'payment_status'=>!empty($v['payment_status']) ? $v['payment_status'] : '-',
      'pay_at'=>!empty($v['pay_at']) ? date('Y-m-d H:i:s', strtotime($v['pay_at'])) : '-',
      'dana_pencairan'=>(float)($v['dana_pencairan'] ?? 0),
      'dana_pencairan_fmt'=>$fmt($v['dana_pencairan'] ?? 0),
      'omset_kotor'=>(float)($v['omset_kotor'] ?? 0),
      'omset_kotor_fmt'=>$fmt($v['omset_kotor'] ?? 0),
      'diskon_penjual'=>(float)($v['diskon_penjual'] ?? 0),
      'diskon_penjual_fmt'=>$fmt($v['diskon_penjual'] ?? 0),
      'biaya_lainnya'=>(float)($v['biaya_lainnya'] ?? 0),
      'biaya_lainnya_fmt'=>$fmt($v['biaya_lainnya'] ?? 0),
      'omset_bersih'=>(float)($v['omset_bersih'] ?? 0),
      'omset_bersih_fmt'=>$fmt($v['omset_bersih'] ?? 0),
      'marketplace_fee'=>(float)($v['marketplace_fee'] ?? 0),
      'marketplace_fee_fmt'=>$fmt($v['marketplace_fee'] ?? 0),
      'komisi_afiliasi'=>(float)($v['komisi_afiliasi'] ?? 0),
      'komisi_afiliasi_fmt'=>$fmt($v['komisi_afiliasi'] ?? 0),
      'marketplace_img'=>$marketplaceImg,
      'shipping_img'=>$shippingImg,
      'json'=>$v['json'] ?? '',
      // ⬇⬇⬇ Hasil hitung HPP dari join product
      'hpp'=>$hppFinal,
      'hpp_fmt'=>$fmt($hppFinal),
      'c_type'=>$v['c_type']
    ];
  }
  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
?>;

// ===========================
// External Filter State - SERVER SIDE
// ===========================
const valueFilters = Object.create(null);
const filterableFields = [
  'order_id','customer_text','pesanan_count','customer_price',
  'pencairan_status','order_status','awb_number','marketplace',
  'shop_name','brand','phone','is_manual','payment_type',
  'c_username','cs','shipping','shipping_status','reverse_id',
  'return_status','payment_status','pay_at','dana_pencairan',
  'omset_kotor','diskon_penjual','biaya_lainnya','omset_bersih',
  'marketplace_fee','komisi_afiliasi','marketplace_img','shipping_img', 'c_type', 'hpp'
];
const distinctCache = Object.create(null);

function applyServerSideFilters() {
  const params = new URLSearchParams(window.location.search);
  delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
  Object.entries(valueFilters).forEach(([field, values]) => {
    if (values instanceof Set && values.size > 0) {
      Array.from(values).forEach(value => {
        appendArr(params,'filter_field',field);
        appendArr(params,'filter_value',value);
        appendArr(params,'filter_operator','equals');
      });
    }
  });
  params.set('page','1');
  // AJAX only
  const p = buildParamsWithFilters(params);
  fetchGridDataWithParams(p);
}

function clearAllFilters() {
  filterableFields.forEach(field => { if (valueFilters[field]) valueFilters[field].clear(); });
  const paramsNow = new URLSearchParams(window.location.search);
  delAll(paramsNow,'filter_field'); delAll(paramsNow,'filter_value'); delAll(paramsNow,'filter_operator');
  paramsNow.set('page','1');
  // Simpan state kosong ke session (opsional)
  sessionStorage.removeItem('agGridFilters');
  // AJAX only (tanpa reload)
  const p = buildParamsWithFilters(paramsNow);
  fetchGridDataWithParams(p);
}

function loadFilterStateFromURL() {
  const params = new URLSearchParams(window.location.search);
  const filterFields = getAllEither(params,'filter_field');
  const filterValues = getAllEither(params,'filter_value');
  Object.keys(valueFilters).forEach(key => { if (valueFilters[key] instanceof Set) valueFilters[key].clear(); });

  if (filterFields.length === filterValues.length && filterFields.length > 0) {
    for (let i=0;i<filterFields.length;i++) {
      const field = filterFields[i], value = filterValues[i];
      if (!valueFilters[field]) valueFilters[field] = new Set();
      valueFilters[field].add(value);
    }
  } else if (filterFields.length === 0) {
    try {
      const saved = sessionStorage.getItem('agGridFilters');
      if (saved) {
        const filters = JSON.parse(saved);
        Object.entries(filters).forEach(([field, values]) => {
          if (Array.isArray(values)) {
            if (!valueFilters[field]) valueFilters[field] = new Set();
            values.forEach(v => valueFilters[field].add(v));
          }
        });
      }
    } catch(e){ console.error('Error loading filters from sessionStorage:', e); }
  }
}

function updateFilterIndicators() {
  const filterBtn = document.querySelector('.filter-toggle-btn');
  if (!filterBtn) return;
  const active = Object.values(valueFilters).reduce((c,s)=>c+((s instanceof Set && s.size>0)?1:0),0);
  let badge = filterBtn.querySelector('.filter-badge');
  if (!badge) {
    badge = document.createElement('span');
    badge.className = 'filter-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
    badge.style.fontSize = '0.6em';
    filterBtn.appendChild(badge);
  }
  if (active>0) { badge.textContent = active; badge.style.display='block'; }
  else { badge.style.display='none'; }
}

async function fetchDistinct(field) {
  if (distinctCache[field]) return distinctCache[field];
  try {
    const params = new URLSearchParams(window.location.search);
    params.set('field', field);
    delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
    const url = `<?= base_url() ?>transaction/filter_values?${params.toString()}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Failed to fetch distinct values');
    distinctCache[field] = json.values || [];
    return distinctCache[field];
  } catch (error) {
    console.error(`Error fetching distinct values for ${field}:`, error);
    if (window.gridOptions?.api) {
      const values = new Set();
      window.gridOptions.api.forEachNode(node => {
        const value = node.data[field];
        values.add(value !== undefined && value !== null && value !== '' ? String(value) : '-');
      });
      distinctCache[field] = Array.from(values).sort();
      return distinctCache[field];
    }
    return [];
  }
}

document.addEventListener('DOMContentLoaded', function() {
  loadFilterStateFromURL();
  updateFilterIndicators();

  const btnReset = document.getElementById('btnResetFilters');
  if (btnReset) btnReset.addEventListener('click', () => clearAllFilters());
});

// Export (opsional)
window.agGridFilters = { valueFilters, applyServerSideFilters, clearAllFilters, loadFilterStateFromURL, updateFilterIndicators };

// ===========================
// Header renderer dengan dropdown checkbox
// ===========================
function headerWithFilter(field, title) {
  const id = 'hdrdd_' + field + '_' + Math.random().toString(36).slice(2);
  return {
    headerName: title,
    field,
    filter: (field === 'pesanan_count' || field === 'customer_price' || field === 'hpp') ? 'agNumberColumnFilter' : 'agTextColumnFilter',
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
// ColumnDefs
// ===========================
const columnDefs = [
  headerWithFilter('order_id','Pesanan'),
  headerWithFilter('customer_text','Customer'),
  {
    headerName:'Produk', field:'json', flex:1.4, filter:'agTextColumnFilter',
    valueGetter: p => parseProdukLineSingle(p.data?.json),
    cellRenderer: p => {
      const line = parseProdukLineSingle(p.data?.json);
      if (!line) return '-';
      return `<span class="a-none text-blue fw-700">${escHtml(line)}</span>`;
    }
  },
  {
    ...headerWithFilter('customer_price','Harga Pesanan'),
    type:'numericColumn', filter:'agNumberColumnFilter', flex:0.9,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.customer_price_fmt || p.value.toLocaleString('id-ID')) : '-',
    cellRenderer:params=>{
      const amt = params.data.customer_price_fmt || (params.value ?? 0).toLocaleString('id-ID');
      const st = params.data.pencairan_status || '-';
      const at = params.data.pencairan_at || '-';
      const stCls = (st === 'Settlement') ? 'bg-green' : 'bg-red';
      return `<div><div class="fw-bold">Rp ${amt}</div><div>${badge(stCls, st)} ${badge('bg-grey', at)}</div></div>`;
    }
  },
  {
    ...headerWithFilter('order_status','Status'),
    flex:1.1,
    cellRenderer:p=>{
      const st = p.value || '-';
      const awb = p.data.awb_number || '-';
      const okGreen = ['DELIVERED','COMPLETED'].includes(st);
      const okBlue  = ['READY_TO_SHIP','PENDING','PROCESSED','SHIPPED','TO_CONFIRM_RECEIVE'].includes(st);
      const stCls = okGreen ? 'bg-green' : (okBlue ? 'bg-blue' : 'bg-red');
      const reverse = p.data.reverse_status ? badge('bg-red', p.data.reverse_status) : '';
      return `<div><div>${badge(stCls, st)} ${reverse}</div><div>${awb}</div></div>`;
    }
  },
  { ...headerWithFilter('marketplace','Marketplace'), flex:0.8, hide:true },
  { ...headerWithFilter('shop_name','Toko'), flex:0.8, hide:true },
  { ...headerWithFilter('brand','Brand'), flex:0.8, hide:true },
  { ...headerWithFilter('phone','Phone'), flex:0.8, hide:true },
  { ...headerWithFilter('is_manual','Type'), flex:0.6, hide:true, valueFormatter:p=> (p.value===1||p.value==='1')?'Manual':'Marketplace' },
  { ...headerWithFilter('payment_type','Tipe Order'), flex:0.8, hide:true },
  { ...headerWithFilter('c_username','Username'), flex:0.8, hide:true },
  { ...headerWithFilter('cs','CS'), flex:0.8, hide:true },
  {
    ...headerWithFilter('payment_status','Status Bayar'), flex:0.8, hide:true,
    cellRenderer:p=>{
      const st = p.value || '-';
      const cls = (st === 'Paid') ? 'bg-green' : 'bg-red';
      const payAt = dateFormat(p.data.pay_at) || '-';
      return `<div>${badge(cls, st)}</div>`;
    }
  },
  { ...headerWithFilter('pay_at','Dibayar Pada'), flex:0.9, hide:true },
  { ...headerWithFilter('dana_pencairan','Dana Pencairan'), type:'numericColumn', flex:0.8, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.dana_pencairan_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('omset_kotor','Omset Kotor'), type:'numericColumn', flex:0.8, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.omset_kotor_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('diskon_penjual','Diskon/Voucher Penjual'), type:'numericColumn', flex:0.9, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.diskon_penjual_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('biaya_lainnya','Biaya Lainnya'), type:'numericColumn', flex:0.8, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.biaya_lainnya_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('omset_bersih','Omset Bersih'), type:'numericColumn', flex:0.8, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.omset_bersih_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('marketplace_fee','Marketplace Fee'), type:'numericColumn', flex:0.9, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.marketplace_fee_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('komisi_afiliasi','Affiliate Fee'), type:'numericColumn', flex:0.9, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.komisi_afiliasi_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('shipping','Kurir'), flex:0.8, hide:true },
  { ...headerWithFilter('shipping_status','Status Pengiriman'), flex:1.0, hide:true },
  { ...headerWithFilter('reverse_id','No Pengajuan'), flex:0.8, hide:true },
  { ...headerWithFilter('return_status','Status Return'), flex:0.8, hide:true },
  {
    ...headerWithFilter('marketplace_img','Logo Marketplace'), flex:0.8, hide:true, filter:'agTextColumnFilter',
    cellRenderer:p=> p.value ? `<img src="${escHtml(p.value)}" style="height:24px;border-radius:6px">` : '-'
  },
  {
    ...headerWithFilter('shipping_img','Logo Kurir'), flex:0.8, hide:true, filter:'agTextColumnFilter',
    cellRenderer:p=> p.value ? `<img src="${escHtml(p.value)}" style="height:24px;border-radius:6px">` : '-'
  },
  { ...headerWithFilter('hpp','HPP'), type:'numericColumn', flex:0.9, hide:true,
    valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.hpp_fmt || p.value.toLocaleString('id-ID')) : '-'
  },
  { ...headerWithFilter('c_type','Kategori'), flex:0.8, hide:true },
  {
    headerName:'Aksi', field:'id', width:130, sortable:false, filter:false,
    cellRenderer:params=>{
      const d = params.data;
      const base = `<?= base_url() ?>transaction`;
      const isManual = +d.is_manual === 1;
      const printUrl = `${base}/print?id=${d.id}`;
      const trackUrl = `${base}/tracking?id=${d.id}&order_id=${encodeURIComponent(d.order_id)}&package_number=${encodeURIComponent(d.awb_number || '')}&marketplace=${encodeURIComponent(d.marketplace || '')}`;
      const removeBtn = isManual ? `<li><a href="#!" class="dropdown-item text-danger" onclick="remove('${d.id}')"><i class="bi bi-trash me-2"></i> Hapus Order</a></li>` : '';
      const editBtn   = isManual ? `<li><a href="${base}/edit?id=${d.id}" class="dropdown-item"><i class="bi bi-pencil-square me-2"></i> Edit Order</a></li>` : '';
      const refreshBtn= !isManual ? `<li><a href="#!" class="dropdown-item" onclick="refresh('${d.order_id}','${d.marketplace}')"><i class="bi bi-newspaper me-2"></i> Refresh</a></li>` : '';
      return `
        <div class="dropdown">
          <a href="#" class="text-muted" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical fs-16"></i></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a href="${printUrl}" target="_blank" class="dropdown-item"><i class="bi bi-printer me-2"></i> Print</a></li>
            <li><a href="#!" class="dropdown-item" onclick="set_cs('${d.id}')"><i class="bi bi-people me-2"></i> CS</a></li>
            <li><a href="#!" class="dropdown-item" onclick="set_resi('${d.id}')"><i class="bi bi-truck me-2"></i> No Resi</a></li>
            <li><a href="#!" class="dropdown-item" onclick="set_return('${d.id}')"><i class="bi bi-backspace me-2"></i> Return</a></li>
            ${removeBtn}
            ${refreshBtn}
            <li><a href="${trackUrl}" target="_blank" class="dropdown-item"><i class="bi bi-truck me-2"></i> Lacak Resi</a></li>
            ${editBtn}
          </ul>
        </div>
      `;
    }
  },
];

// ===========================
// Grid Options & Mount
// ===========================
if (window.gridOptions?.api) { try { window.gridOptions.api.destroy(); } catch(e){} }
window._defaultColState = null;

window.gridOptions = {
  theme: 'legacy',
  defaultColDef: { sortable:true, filter:true, resizable:true, floatingFilter:false },
  columnDefs, rowData, animateRows:true,
  rowSelection: { mode:'multiRow', checkboxes:true, headerCheckbox:true, selectAll:'filtered', enableClickSelection:false },
  isExternalFilterPresent: () => Object.values(valueFilters).some(s => s instanceof Set && s.size>0),
  doesExternalFilterPass: (node) => {
    for (const [field, setVals] of Object.entries(valueFilters)) {
      if (!(setVals instanceof Set) || setVals.size === 0) continue;
      const raw = node.data[field];
      const val = (raw == null || raw === '') ? '-' : String(raw);
      if (!setVals.has(val)) return false;
    }
    return true;
  },
  onSelectionChanged: syncSelectedIds,
  onGridReady: function (params) {
    // Simpan "default state" manual dari daftar kolom yang ada saat grid ready
    const cols = params.api.getColumns() || [];
    window._defaultColState = cols.map((c, i) => ({
        colId: c.getColId(),
        hide: !c.isVisible(),
        // simpan urutan awal, ag-Grid akan pakai urutan array state kalau applyOrder:true
        order: i,
    }));

    loadColumnStateFromSession();

    setTimeout(() => {
        handleInitialSortState();
        loadFilterStateFromURL();
        updateFilterIndicators();
        renderFooterTotals();
    }, 100);
  },
  onColumnVisible: function() {
    saveColumnStateToSession();
    renderFooterTotals();
  },
  onColumnMoved: function() {
    saveColumnStateToSession();
    renderFooterTotals();
  },
  onColumnPinned: function() {
    saveColumnStateToSession();
    renderFooterTotals();
  },
  onColumnResized: function () {
    clearTimeout(window.__colResizeTimer);
    window.__colResizeTimer = setTimeout(function(){
      saveColumnStateToSession();
      renderFooterTotals();
    }, 300);
  },



};

(function(){
  const eGridDiv = document.getElementById('myGrid');
  if (!eGridDiv) return;
  const api = agGrid.createGrid(eGridDiv, window.gridOptions);
  window.gridOptions.api = api;
})();

// ===========================
// Progressive Column Chooser
// ===========================
(function initProgressiveColumnChooser(){
  const btn = document.getElementById('btnColumns');
  const wrapper = document.getElementById('agGridWrapper') || document.body;
  if (!btn || !wrapper) return;

  let isOpen = false;
  let ddRef = null;      // simpan elemen panel
  let cleanupFns = [];   // simpan listener untuk dibersihkan saat close

  btn.addEventListener('click', () => { isOpen ? close() : open(); });

  function open() {
    if (!window.gridOptions?.api || isOpen) return;

    const dd = document.createElement('div');
    dd.className = 'dropdown-panel shadow rounded p-2 bg-white border';
    dd.style.position = 'absolute';
    dd.style.width = '280px';
    dd.style.zIndex = 1050;
    ddRef = dd;

    const cols = gridOptions.api.getColumns()
      .filter(c => {
        const def = c.getColDef();
        return def && def.headerName && def.headerName !== 'Aksi' && def.field !== 'id';
      })
      .map(c => ({
        id: c.getColId(),
        title: c.getColDef().headerName || c.getColId(),
        visible: c.isVisible()
      }));

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

    wrapper.appendChild(dd);
    isOpen = true;

    const listEl = dd.querySelector('.list');
    const render = (items) => {
      listEl.innerHTML = items.map(it => `
        <label class="d-block mb-1">
          <input type="checkbox" data-col="${escHtml(it.id)}" ${it.visible ? 'checked' : ''}> ${escHtml(it.title)}
        </label>
      `).join('');
    };
    render(cols);

    const position = () => {
      const b = btn.getBoundingClientRect();
      const w = wrapper.getBoundingClientRect();
      let left = b.left - w.left;
      let top  = b.bottom - w.top + 6;
      const margin = 8;
      const maxLeft = wrapper.clientWidth - (dd.offsetWidth || 280) - margin;
      if (left > maxLeft) left = Math.max(margin, maxLeft);
      if (left < margin) left = margin;
      dd.style.left = left + 'px';
      dd.style.top  = top  + 'px';
    };
    position();

    // events internal
    const onSearch = (e) => {
      const q = e.target.value.toLowerCase();
      const filtered = cols.filter(c => c.title.toLowerCase().includes(q) || c.id.toLowerCase().includes(q));
      render(filtered);
      position();
    };
    dd.querySelector('[data-role="search"]').addEventListener('input', onSearch);

    const onToggleAll = (e) => {
      const checked = e.target.checked;
      listEl.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb => cb.checked = checked);
    };
    dd.querySelector('[data-role="toggle-all"]').addEventListener('change', onToggleAll);

    const onDefaults = () => {
      if (!window._defaultColState) return;
      gridOptions.api.applyColumnState({
        state: window._defaultColState.map(s => ({ colId: s.colId, hide: s.hide })),
        applyOrder: true
      });
      saveColumnStateToSession();
      const currentCols = gridOptions.api.getColumns() || [];
      const vis = Object.fromEntries(currentCols.map(c => [c.getColId(), c.isVisible()]));
      listEl.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb => {
        const id = cb.getAttribute('data-col');
        cb.checked = !!vis[id];
      });
    };
    dd.querySelector('[data-role="defaults"]').addEventListener('click', onDefaults);

    const onCancel = () => close();
    dd.querySelector('[data-role="cancel"]').addEventListener('click', onCancel);

    const onApply = () => {
      const checks = Array.from(listEl.querySelectorAll('input[type="checkbox"][data-col]'));
      const state = checks.map(cb => ({ colId: cb.getAttribute('data-col'), hide: !cb.checked }));
      gridOptions.api.applyColumnState({ state, applyOrder: false });

      // === NEW: simpan ke session
      saveColumnStateToSession();

      close();
    };

    dd.querySelector('[data-role="apply"]').addEventListener('click', onApply);

    // click di luar
    const clickOutside = (ev) => {
      if (ddRef && !ddRef.contains(ev.target) && ev.target !== btn) close();
    };
    setTimeout(() => document.addEventListener('mousedown', clickOutside), 0);

    // reposition on resize/scroll
    const onResize = () => position();
    const onScroll = () => position();
    const viewport = document.querySelector('.ag-center-cols-viewport');

    window.addEventListener('resize', onResize, { passive: true });
    window.addEventListener('scroll', onScroll, true);
    viewport?.addEventListener('scroll', onScroll, { passive: true });

    // simpan cleanup
    cleanupFns.push(
      () => dd.remove(),
      () => document.removeEventListener('mousedown', clickOutside),
      () => window.removeEventListener('resize', onResize),
      () => window.removeEventListener('scroll', onScroll, true),
      () => viewport?.removeEventListener('scroll', onScroll)
    );
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    // cleanup listeners & DOM
    while (cleanupFns.length) { try { cleanupFns.pop()(); } catch(e){} }
    ddRef = null;
  }
})();

// ===========================
// Sinkronkan selection -> hidden input lama
// ===========================
})();

function syncSelectedIds() {
  const rows = gridOptions.api.getSelectedRows() || [];
  const ids = rows.map(r => String(r.id));
  document.getElementById('id_selected')?.setAttribute('value', ids.join(','));
  window.list_id_v2 = ids.join(',');
}

// Kompat checkbox lama "Pilih Semua Data"
$(document).off('change', '.checkAll').on('change', '.checkAll', function() {
  this.checked ? gridOptions.api.selectAll() : gridOptions.api.deselectAll();
});
</script>


<?php
// END if table
else:
  // Card view
    foreach ($data as $v) {
        $marketplace = $v['marketplace'];
        $marketplace = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '$marketplace'");
        $marketplace = $marketplace[0];

        if ($marketplace['img']) {
            $marketplace['img'] = base_url() . '/assets/img/marketplace/' . $marketplace['img'];
        } else {
            $marketplace['img'] = base_url() . '/assets/img/marketplace/default.png';
        }

        $shipping = $v['shipping'];
        $shipping = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '$shipping'");
        $shipping = $shipping[0];

        if ($shipping['img']) {
            $shipping['img'] = base_url() . '/assets/img/shipping/' . $shipping['img'];
        } else {
            $shipping['img'] = base_url() . '/assets/img/shipping/default.png';
        }

        $v['customer_price'] = number_format($v['customer_price'], 0, '', '.');
        $v['dana_pencairan'] = number_format($v['dana_pencairan'], 0, '', '.');
        $v['omset_kotor'] = number_format($v['omset_kotor'], 0, '', '.');
        $v['diskon_penjual'] = number_format($v['diskon_penjual'], 0, '', '.');
        $v['omset_bersih'] = number_format($v['omset_bersih'], 0, '', '.');
        $v['marketplace_fee'] = number_format($v['marketplace_fee'], 0, '', '.');
        $v['komisi_afiliasi'] = number_format($v['komisi_afiliasi'], 0, '', '.');
        $v['biaya_lainnya'] = number_format($v['biaya_lainnya'], 0, '', '.');

        $day = array(
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        );

        $month = array(
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        if ($v['pay_at']) {
            $v['pay_at'] = DATE('d', strtotime($v['pay_at'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['pay_at'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['pay_at']));
        } else {
            $v['pay_at'] = '-';
        }
        if (empty($v['payment_status'])) {
            $v['payment_status'] = '-';
        }
        if ($v['pencairan_at']) {
            $v['pencairan_at'] = DATE('d', strtotime($v['pencairan_at'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['pencairan_at'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['pencairan_at']));
        } else {
            $v['pencairan_at'] = '-';
        }
        if (empty($v['pencairan_status'])) {
            $v['pencairan_status'] = '-';
        }

        if ($v['date']) {
            $v['date'] = $day[(DATE('l', strtotime($v['date'])))] . ', ' . DATE('d', strtotime($v['date'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['date'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['date'])) . ' ' . DATE('H:i:s', strtotime($v['date']));
        } else {
            $v['date'] = '-';
        }

        $payment_class = "bg-red";
        if ($v['payment_status'] == "Paid") {
            $payment_class = "bg-green";
        }
        $settlement_class = "bg-red";
        if ($v['pencairan_status'] == "Settlement") {
            $settlement_class = "bg-green";
        }

        $order_class = "bg-red";

        $reverse_class = "bg-red";

        // if (strpos($data['data']['reverse_status'], 'COMPLETE') !== false) {
        //     $reverse_class = "bg-green";
        // }


        $v['shipping_status'] = '-';

        if ($v['order_status'] == "READY_TO_SHIP") {
            $v['shipping_status'] = "Paket Menunggu Diproses";
            $order_class = "bg-blue";
        } else if ($v['order_status'] == "PENDING") {
            $v['shipping_status'] = "Paket Menunggu Diproses";
            $order_class = "bg-blue";
        } else if ($v['order_status'] == "PROCESSED") {
            $v['shipping_status'] = "Paket Menunggu Diserahkan ke Ekspedisi";
            $order_class = "bg-blue";
        } else if ($v['order_status'] == "SHIPPED") {
            $v['shipping_status'] = "Paket Dalam Proses Pengiriman";
            $order_class = "bg-blue";
        } else if ($v['order_status'] == "DELIVERED") {
            $v['shipping_status'] = "Paket Diterima Customer";
            $order_class = "bg-green";
        } else if ($v['order_status'] == "COMPLETED") {
            $v['shipping_status'] = "Paket Diterima Customer";
            if ($v['dana_pencairan'] > 0 && $v['is_disbursement'] > 0) {
                $v['shipping_status'] = "Sudah Dicairkan";
            }
            $order_class = "bg-green";
        } else if ($v['order_status'] == "CANCELLED") {
            $v['shipping_status'] = "Order Dibatalkan";
        } else if ($v['order_status'] == "IN_CANCELLED") {
            $v['shipping_status'] = "Order Dibatalkan";
        } else if ($v['order_status'] == "UNPAID") {
            $v['shipping_status'] = "Belum Dibayar";
        } else if ($v['order_status'] == "TO_CONFIRM_RECEIVE") {
            $v['shipping_status'] = "Menunggu Diterima Pelanggan";
            $order_class = "bg-blue";
        }

        $cancel_by = '';
        if ($v['order_status'] == "CANCELLED") {
            if ($v['cancel_by']) {
                $cancel_by = ' by ' . ucwords(strtolower($v['cancel_by']));
            }
            if ($v['cancel_status']) {
                $cancel_by .= ' | ' . $v['cancel_status'];
            }
        }

        $return_class = "bg-red";
        if ($v['return_status'] == "ACCEPTED") {
            $return_class = "bg-green";
        }

        if (empty($v['c_username'])) {
            $v['c_username'] = '-';
        }

        if (empty($v['shipping'])) {
            $v['shipping'] = '-';
        }
        if (empty($v['awb_number'])) {
            $v['awb_number'] = '-';
        }

        if (empty($v['customer_text'])) {
            $v['customer_text'] = '-';
        }

        if (empty($v['phone'])) {
            $v['phone'] = '-';
        }
        if (empty($v['shop_name'])) {
            $v['shop_name'] = "Manual";
        }
        if (substr($v['phone'], 0, 1) === "0") {
            $v['phone'] = "62" . substr($v['phone'], 1);
        }


        $url_wa = 'https://api.whatsapp.com/send/?phone=' . $v['phone'] . '&text=Hi ' . $v['customer_text'] . ', apakah pesanan kamu dengan order id ' . $v['order_id'] . ' sudah diterima?';

        if ($v['rts_at']) {
            $v['rts_at'] = DATE('d', strtotime($v['rts_at'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['rts_at'])))], 0, 3) . ' ' . DATE('Y H:i:s', strtotime($v['rts_at']));
        } else {
            $v['rts_at'] = "-";
        }
    ?>
        <div class="card mb-3">
            <div class="row">
                <div class="col-lg-7">
                    <input class="d-none" type="text" id="box-order-id-<?= $v['id'] ?>" value="<?= $v['order_id'] ?>">
                    <p class="mb-1"><a href="<?= base_url() ?>crm/detail?id=<?= $v['customer'] ?>" class="a-none text-blue fw-700 fs-16">#<?= $k ?> | <?= $v['order_id'] ?></a> <a href="#!" onclick="copy('<?= $v['id'] ?>')"><i class="bi bi-copy"></i></a></p>
                    <p class="mb-0">Dari <span class="fw-700"><?= $v['marketplace'] ?></span> - <?= $v['shop_name'] ?> (<?= $v['date'] ?>)</p>
                    <p class="mb-1">RTS : <span><?= $v['rts_at'] ?></span></p>
                    <?php if ($v['reverse_id']) { ?>
                        <p class="mb-1">No Pengajuan <?= $v['reverse_id'] ?></p>
                    <?php } ?>
                    <p class="mb-1" id="order_status-<?= $v['id'] ?>"><span class="<?= $order_class ?> br-10 fs-12 text-white"><?= $v['order_status'] ?></span></span>
                        <?php if ($v['reverse_status']) { ?>
                            <span class="<?= $reverse_class ?> br-10 fs-12 text-white"><?= $v['reverse_status'] ?></span>
                        <?php } ?>
                    </p>

                </div>
                <div class="col-lg-5 text-lg-end text-start">
                    <?php



                    $arr = array();
                    if ($v['order_status'] != 'RETURN') {
                        $arr[0]['icon'] = "icon-1a.png";
                        $arr[0]['class'] = "text-icon";
                        $arr[1]['icon'] = "icon-2a.png";
                        $arr[1]['class'] = "text-icon-2";
                        $arr[2]['icon'] = "icon-3a.png";
                        $arr[2]['class'] = "text-icon-2";
                        $arr[3]['icon'] = "icon-4a.png";
                        $arr[3]['class'] = "text-icon";
                        $arr[4]['icon'] = "icon-6a.png";
                        $arr[4]['class'] = "text-icon";

                        if ($v['order_status'] == "PROCESSED") {
                            $arr[0]['icon'] = "icon-1b.png";
                        } else if ($v['order_status'] == "READY_TO_SHIP") {
                            $arr[0]['icon'] = "icon-1b.png";
                            $arr[1]['icon'] = "icon-2b.png";
                        } else if ($v['order_status'] == "SHIPPED") {
                            $arr[0]['icon'] = "icon-1b.png";
                            $arr[1]['icon'] = "icon-2b.png";
                            $arr[2]['icon'] = "icon-3b.png";
                        } else if ($v['order_status'] == "COMPLETED" || $v['order_status'] == "DELIVERED") {
                            $arr[0]['icon'] = "icon-1b.png";
                            $arr[1]['icon'] = "icon-2b.png";
                            $arr[2]['icon'] = "icon-3b.png";
                            $arr[3]['icon'] = "icon-4b.png";
                            $arr[4]['icon'] = "icon-6b.png";
                        } else if ($v['order_status'] == "CANCELLED") {
                            $arr[0]['icon'] = "icon-1c.png";
                        } else if ($v['order_status'] == "IN_CANCELLED") {
                            $arr[0]['icon'] = "icon-1c.png";
                        } else if ($v['order_status'] == "TO_CONFIRM_RECEIVE") {
                            $arr[0]['icon'] = "icon-1b.png";
                            $arr[1]['icon'] = "icon-2b.png";
                            $arr[2]['icon'] = "icon-3b.png";
                            $arr[3]['icon'] = "icon-4b.png";
                        }
                    } else {
                        $arr[0]['icon'] = "icon-5a.png";
                        $arr[0]['class'] = "text-icon-3";
                        $arr[1]['icon'] = "icon-5b.png";
                        $arr[1]['class'] = "text-icon-3";
                        if ($v['return_status'] == "ACCEPTED") {
                            $arr[1]['icon'] = "icon-5b.png";
                            $v['shipping_status'] = "Return Diterima";
                        } else if ($v['return_status'] == "CANCELLED") {
                            $arr[1]['icon'] = "icon-5c.png";
                            $v['shipping_status'] = "Return Ditolak";
                        } else {
                            unset($arr[1]);
                        }
                    }
                    if (empty($v['cs'])) {
                        $v['cs'] = "-";
                    }
                    foreach ($arr as $k2 => $v2) {
                    ?>
                        <img src="<?= base_url() ?>assets/img/icon/<?= $v2['icon'] ?>" class="ms-3 <?= $v2['class'] ?>" alt="">
                    <?php } ?>
                    <p class="mb-1">Status Pengiriman : <?= $v['shipping_status'] ?></p>
                </div>
                <div class="col-lg-12">
                    <hr>
                </div>
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <p class="mb-1 fs-16">Tipe Order</p>
                            <p class="mb-1 fs-16 fw-700"><?= $v['payment_type'] ?></p>
                            <p class="mb-1 fs-16">Username</p>
                            <p class="mb-1 fs-16 fw-700"><?= $v['c_username'] ?></p>
                            <p class="mb-1 fs-16">No HP</p>
                            <p class="mb-1 fs-16 fw-700"><a href="<?= $url_wa ?>" target="_blank"><?= $v['phone'] ?></a></p>
                            <p class="mb-1 fs-16">Nama Pembeli</p>
                            <p class="mb-2 fs-16 fw-700"><a href="<?= base_url() ?>crm/detail?id=<?= $v['customer'] ?>"><?= $v['customer_text'] ?></a></p>
                            <p class="mb-1 fs-16">CS</p>
                            <p class="mb-1 fs-16 fw-700" id="cs-<?= $v['id'] ?>"><?= $v['cs'] ?></p>
                            <img style="width:55px;border-radius:10px;" src="<?= $marketplace['img'] ?>">
                        </div>
                        <div class="col-lg-4 mb-3">
                            <p class="mb-1 fs-16">Status Bayar & Total Bayar</p>
                            <div class="box-border mb-2">
                                <p class="mb-1 fs-20 fw-700">Rp <?= $v['customer_price'] ?></p>
                                <p class="mb-2 text-white"><span class="<?= $payment_class ?> br-10 fs-12"><?= $v['payment_status'] ?></span> <span class="bg-grey br-10 fs-12"><?= $v['pay_at'] ?></span></p>

                                <p class="mb-0 mt-3 fs-16">Total Dana Pencairan : </p>
                                <p class="mb-1 fs-20 fw-700">Rp <?= $v['dana_pencairan'] ?></p>
                                <p class="mb-2 text-white"><span class="<?= $settlement_class ?> br-10 fs-12"><?= $v['pencairan_status'] ?></span> <span class="bg-grey br-10 fs-12"><?= $v['pencairan_at'] ?></span></p>

                                <a href="#!" data-bs-toggle="modal" data-bs-target="#modal-<?= $k ?>">Buka Detail Pencairan</a>
                                <div class="modal fade" id="modal-<?= $k ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Detail Pencairan</h4>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Omset Kotor</p>
                                                    </div>
                                                    <div class="col-6 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['omset_kotor'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-3">
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Diskon & Voucher Penjual</p>
                                                    </div>
                                                    <div class="col-6 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['diskon_penjual'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-2">
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Biaya Lainnya</p>
                                                    </div>
                                                    <div class="col-6 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['biaya_lainnya'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-2">
                                                    </div>
                                                    <div class="col-6 bg-b pt-1">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Omset Bersih</p>
                                                    </div>
                                                    <div class="col-6 bg-b pt-1 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['omset_bersih'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-3">
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Marketplace Fee</p>
                                                    </div>
                                                    <div class="col-6 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['marketplace_fee'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-3">
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Affiliate Fee</p>
                                                    </div>
                                                    <div class="col-6 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['komisi_afiliasi'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-2">
                                                    </div>
                                                    <div class="col-6 bg-b pt-1">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Total Dana Pencairan</p>
                                                    </div>
                                                    <div class="col-6 bg-b pt-1 text-lg-end text-start">
                                                        <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['dana_pencairan'] ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="mt-0 mb-3">
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- <div class="modal-footer">
                                <button type="button" class="btn mb-2 btn-danger" data-bs-dismiss="modal">Close</button>
                            </div> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-1 fs-16">Kurir</p>
                            <div class="box-border mb-2">
                                <div class="row">
                                    <div class="col-12" style="position:relative">
                                        <div class="row">
                                            <div class="firstDivImg">
                                                <a href="<?= $shipping['img'] ?>" target="_blank"><img class="divIcon" src="<?= $shipping['img'] ?>" alt=""></a>
                                            </div>
                                            <div class="secondDivImg">
                                                <p class="mb-1 fs-16 fw-700"><?= $v['shipping'] ?></p>
                                                <p class="mb-1 fs-16">No Resi : <span id="awb_number-<?= $v['id'] ?>"><?= $v['awb_number'] ?></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <?php if ($v['is_manual'] == 0) { ?>
                                <p class="mb-1 fs-16">Produk Marketplace (Total <?= $v['pesanan_count'] ?> item)</p>
                                <?php foreach (json_decode($v['pesanan'], true) as $k2 => $v2) {
                                    if ($v2['name_parent'] && $v2['name']) {
                                        $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['name_parent']) . ' | ' . str_replace('amp;', '', $v2['name']);
                                    } else {
                                        $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['name_parent']);
                                    }
                                    $class = "text-blue";
                                    if ($v2['is_empty']) {
                                        $class = "text-red";
                                        $v2['item_name'] .= '<br>ID Product : ' . $v2['id_product_parent'];
                                        $v2['item_name'] .= '<br>ID Varian : ' . $v2['id_product'];
                                        $v2['item_name'] .= '<br>SKU : ' . $v2['sku'];
                                    }
                                ?>
                                    <p class="mb-1 a-none <?= $class ?> fw-700 fs-16"><?= $v2['item_name'] ?></p>
                                <?php  } ?>
                                <hr>
                                <p class="mb-1 fs-16">Produk </p>
                                <?php foreach (json_decode($v['json'], true) as $k2 => $v2) {
                                    $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['product_text']) . ' | ' . str_replace('amp;', '', $v2['sku']);
                                ?>
                                    <p class="mb-1 a-none text-blue fw-700 fs-16"><?= $v2['item_name'] ?></p>
                                <?php  } ?>
                            <?php } else { ?>
                                <p class="mb-1 fs-16">Produk (Total <?= $v['pesanan_count'] ?> item)</p>
                                <?php foreach (json_decode($v['pesanan'], true) as $k2 => $v2) {
                                    if ($v2['item_name'] != $v2['model_name'] && $v2['model_name'] != '') {
                                        $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['item_name']) . ' | ' . str_replace('amp;', '', $v2['model_name']);
                                    } else {
                                        $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['item_name']);
                                    }
                                ?>
                                    <p class="mb-1 a-none text-blue fw-700 fs-16"><?= $v2['item_name'] ?></p>
                                <?php  } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <hr class="mt-0">
                </div>
                <div class="col-lg-12 pb-2">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="checkbox-wrapper-13 d-inline">
                                <input class="checkItem" style="top:10px" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                            </div>
                            <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k - $start ?>]" form="form-action">
                            <a href="<?= base_url() ?>transaction/print?id=<?= $v['id'] ?>" target="_blank" class="btn mb-2 btn-edit  mt-0 ms-1"><i class="bi bi-printer fs-16"></i> Print</a>
                            <a onclick="set_cs('<?= $v['id'] ?>')" class="btn mb-2 btn-sync  mt-0 ms-1"><i class="bi bi-people fs-16"></i> CS</a>
                            <a onclick="set_resi('<?= $v['id'] ?>')" class="btn mb-2 btn-sync  mt-0 ms-1"><i class="bi bi-truck fs-16"></i> No Resi</a>
                            <a onclick="set_return('<?= $v['id'] ?>')" class="btn mb-2 btn-delete  mt-0 ms-1"><i class="bi bi-backspace fs-16"></i> Return</a>
                        </div>
                        <div class="col-lg-6 text-lg-end text-start">
                            <?php
                            if ($v['is_manual'] == 1) { ?>
                                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn mb-2 btn-delete  mt-0 mb-3"><i class="bi bi-trash fs-16"></i> Hapus Order</a>
                                <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" class="btn mb-2 btn-sync  mt-0 ms-1 mb-3"><i class="bi bi-truck fs-16"></i> Lacak Resi</a>


                                <a href="<?= base_url() ?>transaction/edit?id=<?= $v['id'] ?>" class="btn mb-2 btn-edit  mt-0 ms-1 mb-3"><i class="bi bi-pencil-square fs-16"></i> Edit Order</a>
                            <?php } else { ?>
                                <?php if ($v['is_manual'] == 0) { ?>
                                    <a onclick="refresh('<?= $v['order_id'] ?>','<?= $v['marketplace'] ?>')" class="btn mb-2 btn-sync  mt-0 ms-2 mb-3"><i class="bi bi-newspaper fs-16"></i> Refresh</a>
                                <?php } ?>
                                <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" class="btn mb-2 btn-sync  mt-0 ms-1 mb-3"><i class="bi bi-truck fs-16"></i> Lacak Resi</a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php $k += 1;
    } 
endif;
?>

<script>
    $('input[name="list_id"]').change(function() {
        get_id();
    });
</script>