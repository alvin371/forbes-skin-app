<?php
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : ($start_date ?? '');
$until_date = !empty($_GET['until_date']) ? $_GET['until_date'] : ($until_date ?? '');

$spend_ads  = $spend_ads  ?? [];
$spend_kol  = $spend_kol  ?? [];
$net_sales  = (float)($net_sales ?? 0);
$total_expense_all_cat = (float)($total_expense_all_cat ?? 0);
$expense_by_category = $expense_by_category ?? [];
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-primary fw-600">Laporan Pengeluaran</h2>
  </div>

  <!-- Filter -->
  <form action="<?= $url ?>" method="GET">
    <div class="row">
      <div class="col-md-3">
        <select class="form-control select2" name="brand" id="brand">
          <option value="">Brand</option>
          <?php foreach ($brands as $val) :
            $text = (($_GET["brand"] ?? '') == $val["code"]) ? "selected" : ""; ?>
            <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
        <input type="hidden" name="start_date" id="start_date" value="<?= html_escape($_GET['start_date'] ?? $start_date) ?>">
        <input type="hidden" name="until_date" id="end_date" value="<?= html_escape($_GET['until_date'] ?? $until_date) ?>">
      </div>

      <div class="col-md-2">
        <button class="btn btn-primary w-100 form-control" type="submit">
          <i class="bi bi-search fs-16"></i> Cari Data
        </button>
      </div>
    </div>

    <script>
      get_filter();
      function get_filter() {
        $.ajax({
          dataType: "json",
          url: '<?= base_url() ?>/ajax/get-filter',
          data: {
            start_date: "<?= html_escape($_GET['start_date'] ?? $start_date) ?>",
            until_date: "<?= html_escape($_GET['until_date'] ?? $until_date) ?>",
          },
          success: function(response) {
            $("#tanggal").after(response.html);
          },
          error: function(xhr, status, error) {
            console.error("Error loading filter:", error);
          }
        });
      }
    </script>
  </form>

  <div class="row g-4 mt-1">
    <div class="col-md-8">
      <div class="report-card">

        <?php
        $ads_total = (float)($spend_ads['total_spend_ads'] ?? 0);
        $kol_total = (float)($spend_kol['total_spend_kol'] ?? 0);

        $all_spend = $ads_total + $kol_total + $total_expense_all_cat;

        $total_percentage = ($net_sales > 0) ? round(($all_spend / $net_sales) * 100, 2) : 0;
        $ads_percentage   = ($net_sales > 0) ? round(($ads_total  / $net_sales) * 100, 2) : 0;
        $kol_percentage   = ($net_sales > 0) ? round(($kol_total  / $net_sales) * 100, 2) : 0;
        ?>

        <h4 class="fw-bold d-flex justify-content-between align-items-center">
          <span>Total Pengeluaran</span>
          <span class="text-danger fw-bolder">
            -<?= number_format($all_spend, 0, ',', '.') ?>
            <small class="text-muted">(<?= $total_percentage ?>%)</small>
          </span>
        </h4>

        <div class="cat-list mt-2">
          <a href="<?= base_url() ?>overview?t=ads&start_date=<?= html_escape($start_date) ?>&until_date=<?= html_escape($until_date) ?>"
             class="cat-row text-secondary fw-bold">
            <span>Ads</span>
            <span class="text-danger">
              -<?= number_format($ads_total, 0, ',', '.') ?>
              <small class="text-muted">(<?= $ads_percentage ?>%)</small>
            </span>
          </a>

          <a href="<?= base_url() ?>payment?start_date=<?= html_escape($start_date) ?>&until_date=<?= html_escape($until_date) ?>"
             class="cat-row text-secondary fw-bold">
            <span>KOL</span>
            <span class="text-danger">
              -<?= number_format($kol_total, 0, ',', '.') ?>
              <small class="text-muted">(<?= $kol_percentage ?>%)</small>
            </span>
          </a>
        </div>

        <div class="hr-dashed my-3"></div>
        <div class="fw-bold mb-1">Per Kategori</div>

        <?php if (!empty($expense_by_category)) : ?>
          <?php
          $cat_normal = [];
          $cat_other  = [];

          foreach ($expense_by_category as $row) {
            $rawCat = $row['category'] ?? '';
            $catLbl = (trim((string)$rawCat) === '') ? 'Lain-lain' : $rawCat;
            $row['_cat_label'] = $catLbl;

            $isOther = (strcasecmp($catLbl, 'Lain-lain') === 0)
                       || ($catLbl === '(Tanpa Kategori)')
                       || (trim((string)$rawCat) === '');

            if ($isOther) {
              $cat_other[] = $row;
            } else {
              $cat_normal[] = $row;
            }
          }
          $cat_merged = array_merge($cat_normal, $cat_other);
          ?>

          <div class="cat-list">
            <?php foreach ($cat_merged as $row): 
              $cat = $row['_cat_label'];
              $tot = (float)($row['total_spend'] ?? 0);
              $pct = ($net_sales > 0) ? round(($tot / $net_sales) * 100, 2) : 0;

              $href = base_url() . "expense?brand="
                . urlencode($_GET['brand'] ?? '')
                . "&keyword_category=Nama+Pelanggan"
                . "&keyword="
                . "&start_date=" . urlencode($start_date)
                . "&until_date=" . urlencode($until_date)
                . "&category=" . urlencode($cat);
            ?>
              <a href="<?= $href ?>" class="cat-row text-secondary fw-bold">
                <span><?= html_escape($cat) ?></span>
                <span class="text-danger">
                  -<?= number_format($tot, 0, ',', '.') ?>
                  <small class="text-muted">(<?= $pct ?>%)</small>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-muted mt-2">Tidak ada data pengeluaran pada rentang tanggal ini.</div>
        <?php endif; ?>

        <div class="text-end text-muted" style="font-size:12px; margin-top:8px;">
          <?= html_escape($start_date) ?> - <?= html_escape($until_date) ?>
        </div>

      </div>
    </div>
  </div>

  <style>
    .report-card { background:#fff; padding:25px; border-radius:12px; }
    .report-card a { text-decoration:none; }

    .hr-dashed { border-top:1px dashed #d0d7de; }

    .cat-list .cat-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:8px 0;
      border-top:0;
    }
  </style>
</div>
