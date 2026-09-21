<?php if (empty($rows)) { ?>
    <p class="mb-0">Tidak ada data duplikasi ditemukan.</p>
<?php } else { ?>
    <p class="text-muted mb-3">Video TikTok ini terdeteksi di <strong><?= count($rows) ?></strong> data endorse:</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Creator / PIC</th>
                    <th>Ditambahkan</th>
                    <th>Tanggal Publish Video</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) {
                    $is_current = ((int) $row['id'] === (int) $current_id);
                    $added_at = !empty($row['created_at']) ? DATE("d/m/Y", strtotime($row['created_at'])) : '-';
                    $posting_at = !empty($row['posting_at']) ? DATE("d/m/Y", strtotime($row['posting_at'])) : '-';
                ?>
                    <tr <?= $is_current ? 'class="table-warning"' : '' ?>>
                        <td>
                            <a href="<?= base_url() ?>endorse?id_campaign=<?= $row['id_campaign'] ?>" target="_blank">
                                #<?= $row['id_campaign'] ?> <?= htmlspecialchars($row['campaign_title'] ?? '') ?>
                            </a>
                            <?= $is_current ? ' <span class="badge bg-secondary">Kartu ini</span>' : '' ?>
                        </td>
                        <td><?= htmlspecialchars($row['nama_creator'] ?? '-') ?> / <?= htmlspecialchars($row['pic'] ?? '-') ?></td>
                        <td><?= $added_at ?></td>
                        <td><?= $posting_at ?></td>
                        <td><a href="<?= htmlspecialchars($row['link_upload'] ?? '') ?>" target="_blank">Buka</a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small mb-0">Tanggal publish video diambil dari data TikTok yang sudah tersimpan (tidak melakukan pengecekan ulang ke RapidAPI saat ini dibuka).</p>
<?php } ?>
