<?php
$btn_1 = "btn-outline-secondary";
$btn_2 = "btn-outline-secondary";
if (!isset($_GET['p']) || $_GET['p'] == "") {
    $btn_1 = "btn-primary";
} else {
    $btn_2 = "btn-primary";
}
?>
<div class="mb-3">
    <div class="d-flex gap-2">
        <a href="<?= base_url() ?>product" class="btn <?= $btn_1 ?>">
            <i class="bi bi-box me-1"></i> Produk Utama
        </a>
        <a href="<?= base_url() ?>product?p=operasional" class="btn <?= $btn_2 ?>">
            <i class="bi bi-gear me-1"></i> Produk Operasional
        </a>
    </div>
</div>