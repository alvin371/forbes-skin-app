<?php
$d = isset($data) && is_array($data) ? $data : [];
$v = function ($k, $def = '') use ($d) { return isset($d[$k]) && $d[$k] !== null ? $d[$k] : $def; };
$dt_local = function ($val) {
    if (empty($val)) return '';
    $ts = strtotime($val);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
};
$cur_category = $v('category');
$cur_subcategory = $v('subcategory');
?>
<div class="row mb-3">
    <div class="col-md-8">
        <label class="form-label">Judul <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="dt[title]" id="ann-title" value="<?= htmlspecialchars($v('title'), ENT_QUOTES) ?>" required>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="dt[is_pinned]" id="ann-pinned" value="1" <?= (int) $v('is_pinned', 0) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="ann-pinned">Pin announcement ini</label>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">Kategori <span class="text-danger">*</span></label>
        <select class="form-select" name="dt[category]" id="ann-category" required>
            <option value="">- Pilih Kategori -</option>
            <?php foreach (array_keys($categories) as $cat): ?>
                <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>" <?= $cur_category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Subkategori <span class="text-danger">*</span></label>
        <select class="form-select" name="dt[subcategory]" id="ann-subcategory" data-current="<?= htmlspecialchars($cur_subcategory, ENT_QUOTES) ?>" required>
            <option value="">- Pilih Subkategori -</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select" name="dt[status]" id="ann-status" required>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>" <?= $v('status', 'DRAFT') === $st ? 'selected' : '' ?>><?= ucfirst(strtolower($st)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Prioritas <span class="text-danger">*</span></label>
        <select class="form-select" name="dt[priority]" id="ann-priority" required>
            <?php foreach ($priorities as $pr): ?>
                <option value="<?= $pr ?>" <?= $v('priority', 'MEDIUM') === $pr ? 'selected' : '' ?>><?= ucfirst(strtolower($pr)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Publikasi Mulai</label>
        <input type="datetime-local" class="form-control" name="dt[publish_start_at]" value="<?= $dt_local($v('publish_start_at')) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Publikasi Selesai</label>
        <input type="datetime-local" class="form-control" name="dt[publish_end_at]" value="<?= $dt_local($v('publish_end_at')) ?>">
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <label class="form-label">Konten <span class="text-danger">*</span></label>
        <textarea class="form-control" name="dt[content]" id="ann-content" rows="10"><?= htmlspecialchars($v('content'), ENT_QUOTES) ?></textarea>
    </div>
</div>

<script>
    var ANN_FORM_CATEGORIES = <?= json_encode($categories) ?>;
</script>
