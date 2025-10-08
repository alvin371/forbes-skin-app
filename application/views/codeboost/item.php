<?php foreach ($data as $v): 
    if ($v['platform'] == "Tiktok") {
        $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
    } else if ($v['platform'] == "Instagram") {
        $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
    } else if ($v['platform'] == "Youtube") {
        $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
    } else if ($v['platform'] == "Facebook") {
        $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
    } else if ($v['platform'] == "Twitter") {
        $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
    } else {
        $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
    }
    
    if ($v['link_upload']) {
        $v['img'] = '<a href="' . $v['link_upload'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
    } else {
        $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
    }
?>
<tr>
    <td class="text-start text-blue fw-700">#<?= $k + 1 ?></td>
    <td class="text-start">
        <div>
            <?= $v['nama_creator'] ?>
        </div>
    </td>
    <td class="text-start"><?= $v['product_text'] ? $v['product_text'] : '-' ?></td>
    <td class="text-center" data-sort="<?= strtotime($v['posting_at']) ?>"><?= $v['posting_at'] ?></td>
    <td class="text-center" data-sort="<?= $v['views'] ?>"><?= $this->template->separator_only($v['views']); ?></td>
    <td>
        <?php if (!empty($v['kode_ads'])): ?>
            <p class="mb-1">
                <a href="javascript:void(0)"
                class="text-decoration-none btn-copy"
                data-kode="<?= htmlspecialchars($v['kode_ads']) ?>"
                title="Salin Kode">
                    <i class="bi bi-copy fs-16"></i>
                </a>
            </p>
        <?php endif; ?>
        <div class="firstDivImg">
            <?= $v['img'] ?>
        </div>
    </td>


</tr>
<?php $k += 1; ?>
<?php endforeach; ?>

<script>
    document.querySelectorAll('.btn-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            const kode = this.getAttribute('data-kode');
            const icon = this.querySelector('i');

            if (!kode || !icon) return;

            const originalClass = icon.className;

            icon.className = 'spinner-border spinner-border-sm';

            navigator.clipboard.writeText(kode).then(() => {
                setTimeout(() => {
                    icon.className = 'bi bi-check fs-16 text-success';
                }, 500);

                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            }).catch((err) => {
                console.error('Gagal menyalin:', err);
                icon.className = 'bi bi-x fs-16 text-danger';
                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            });
        });
    });

</script>