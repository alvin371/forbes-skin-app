<?php
$k = $start;

foreach ($data as $v) {
    $v['img'] = $v['img'] ? base_url() . 'assets/img/product/' . $v['img'] : base_url() . 'assets/img/icon/icon-no.png';

    $stock_display = $v['is_varian'] ? ($v['total_stock'] ?? 0) : $v['stock'];
    $has_variants = $v['is_varian'] && !empty($v['variants']);
?>
    <tbody>
        <tr class="product-row" data-product-id="<?= $v['id'] ?>">
            <td style="width: 40px;">
                <input class="form-check-input" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
            </td>
            <td style="width: 60px;">
                <strong><?= $k + 1 ?></strong>
            </td>
            <td style="width: 30px;">
                <?php if ($has_variants): ?>
                    <i class="bi bi-chevron-right expand-icon" style="cursor: pointer;"></i>
                <?php endif; ?>
            </td>
            <td class="text-start">
                <div class="d-flex align-items-center">
                    <div class="me-3" style="width: 50px;">
                        <a href="<?= $v['img'] ?>" target="_blank">
                            <img src="<?= $v['img'] ?>" alt="<?= $v['name'] ?>"
                                style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #f0f0f0;">
                        </a>
                    </div>
                    <div class="flex-grow-1">
                        <div>
                            <strong style="color: #1890ff; font-size: 14px;"><?= $v['name'] ?></strong>
                        </div>
                        <div class="text-muted" style="font-size: 12px;">
                            <span>Brand: <?= $v['brand'] ?></span> | <span>SKU: <?= $v['sku'] ?></span>
                        </div>
                    </div>
                </div>
            </td>

            <td class="text-start" style="width: 150px;">
                <?php if (!$v['is_varian']): ?>
                    <div style="font-size: 14px;">
                        <?php if (in_array($user['role'], array('1', '3'))) { ?>
                            <div class="text-muted mb-1" style="font-size: 12px;">HPP: <?= $template->separator_only($v['price_buy']) ?></div>
                        <?php } ?>
                        <div>
                            <strong><?= $template->separator_only($v['price_normal']) ?></strong>
                            <i class="bi bi-eye ms-1" id="eye-<?= $v['id'] ?>" style="color: #1890ff; cursor: pointer;"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </td>

            <td class="text-end" style="width: 100px;">
                <span class="badge <?= $stock_display <= 0 ? 'bg-danger' : 'bg-primary' ?>">
                    <?= $template->separator_only($stock_display) ?>
                </span>
            </td>

            <?php if (in_array($user['role'], array('1', '3', '2', '6'))) { ?>
                <td class="text-end" style="width: 120px;">
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <?php if ($has_variants): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input parent-toggle" type="checkbox"
                                    id="status_switch_<?= $v['id'] ?>"
                                    name="status_<?= $v['id'] ?>"
                                    <?= ($v['status'] == 'Aktif') ? 'checked' : '' ?>
                                    data-has-variants="true"
                                    data-parent-id="<?= $v['id'] ?>"
                                    onchange="updateParentStatus('<?= $v['id'] ?>', this.checked ? 'Aktif' : 'Tidak Aktif')">
                            </div>
                        <?php elseif (!$has_variants): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    id="status_switch_<?= $v['id'] ?>"
                                    name="status_<?= $v['id'] ?>"
                                    <?= ($v['status'] == 'Aktif') ? 'checked' : '' ?>
                                    onchange="updateStatus('<?= $v['id'] ?>', this.checked ? 'Aktif' : 'Tidak Aktif')">
                            </div>
                        <?php endif; ?>

                        <a href="#!" onclick="edit('<?= $v['id'] ?>')" title="Edit"
                            style="color: #1890ff; font-size: 14px; text-decoration: none;">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="#!" onclick="remove('<?= $v['id'] ?>')" title="Delete"
                            style="color: #ff4d4f; font-size: 14px; text-decoration: none;">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            <?php } ?>
        </tr>

        <?php if ($has_variants): ?>
            <?php foreach ($v['variants'] as $variant): ?>
                <tr class="variant-row" data-parent-id="<?= $v['id'] ?>">
                    <td></td>
                    <td></td>
                    <td></td>

                    <td class="text-start">
                        <div class="d-flex align-items-center ps-4">
                            <div class="me-3" style="width: 50px;">
                                <img src="<?= !empty($variant['img'])
                                                ? base_url('assets/img/product/' . $variant['img'])
                                                : base_url('assets/img/icon/icon-no.png') ?>"
                                    alt="<?= $variant['name'] ?>"
                                    style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #f0f0f0;">
                            </div>

                            <div class="flex-grow-1">
                                <div>
                                    <a href="<?= base_url() ?>product/stock?id=<?= $variant['id'] ?>"
                                        style="color: #1890ff; font-size: 14px; text-decoration: none; font-weight: 500;">
                                        <?= $variant['name'] ?>
                                    </a>
                                </div>
                                <div class="text-muted" style="font-size: 12px;">SKU: <?= $variant['sku'] ?></div>
                            </div>
                        </div>
                    </td>

                    <td class="text-start" style="width: 150px;">
                        <div style="font-size: 14px;">
                            <?php if (in_array($user['role'], array('1', '3'))) { ?>
                                <div class="text-muted mb-1" style="font-size: 12px;">HPP: <?= $template->separator_only($variant['price_buy']) ?></div>
                            <?php } ?>
                            <div>
                                <strong><?= $template->separator_only($variant['price_normal']) ?></strong>
                                <i class="bi bi-eye ms-1" id="eye-<?= $variant['id'] ?>" style="color: #1890ff; cursor: pointer;"></i>
                            </div>
                        </div>
                    </td>

                    <td class="text-end" style="width: 100px;">
                        <span class="badge <?= $variant['stock'] <= 0 ? 'bg-danger' : 'bg-success' ?>">
                            <?= $template->separator_only($variant['stock']) ?>
                        </span>
                    </td>

                    <?php if (in_array($user['role'], array('1', '3', '2', '6'))) { ?>
                        <td class="text-end" style="width: 120px;">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input variant-toggle" type="checkbox"
                                        id="status_switch_<?= $variant['id'] ?>"
                                        name="status_<?= $variant['id'] ?>"
                                        <?= ($variant['status'] == 'Aktif') ? 'checked' : '' ?>
                                        data-parent-id="<?= $v['id'] ?>"
                                        onchange="updateStatus('<?= $variant['id'] ?>', this.checked ? 'Aktif' : 'Tidak Aktif')">
                                </div>

                                <a href="#!" onclick="remove('<?= $variant['id'] ?>')" title="Delete"
                                    style="color: #ff4d4f; font-size: 14px; text-decoration: none;">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    <?php } ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
<?php
    $k += 1;
} ?>

<style>
    .tippy-box[data-theme~='light'] {
        background-color: #ffffff !important;
        color: #333333 !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e5e7eb !important;
    }

    .tippy-box[data-theme~='light'] .tippy-arrow {
        color: #ffffff !important;
    }

    .tippy-box[data-theme~='light'] .tippy-content {
        color: inherit !important;
    }
</style>

<script type="text/javascript">
    $('input[name="list_id"]').change(function() {
        get_id();
    });

    function toggleVariants(button) {
        const row = $(button).closest('tr');
        const details = row.find('.variant-details');
        const icon = $(button).find('i');

        if (details.is(':visible')) {
            details.slideUp();
            icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            $(button).html('<i class="bi bi-chevron-down"></i> Lihat Varian');
        } else {
            details.slideDown();
            icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
            $(button).html('<i class="bi bi-chevron-up"></i> Sembunyikan');
        }
    }

    $(document).ready(function() {
        $('[id^="eye-"]').each(function() {
            const eyeIcon = this;
            const productId = eyeIcon.id.replace('eye-', '');

            tippy(eyeIcon, {
                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                allowHTML: true,
                interactive: true,
                placement: 'right',
                theme: 'light',
                maxWidth: 350,
                onShow(instance) {
                    console.log('Memuat data...');
                    $.ajax({
                        url: '<?= base_url() ?>product/get_price_by_id',
                        type: 'POST',
                        data: {
                            id_product: productId
                        },
                        dataType: 'json',
                        success: function(data) {
                            console.log('Data diterima:', data);
                            if (data) {
                                const tooltipContent = `
                                    <div class="report-card p-2" style="min-width: 250px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Harga Reseller</span>
                                            <span>${data.price_reseller || 0}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Harga Distributor</span>
                                            <span>${data.price_distributor || 0}</span>
                                        </div>
                                    </div>
                                `;
                                instance.setContent(tooltipContent);
                            } else {
                                throw new Error("Data tidak valid");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data influencer
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                        }
                    });
                }
            });
        });
    });
</script>