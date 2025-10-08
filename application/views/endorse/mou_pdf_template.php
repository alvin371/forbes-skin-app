<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>MoU - <?= html_escape($creator['full_name'] ?? '-') ?></title>
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#111; }
  h1,h2,h3 { margin:0 0 8px 0; }
  .muted { color:#666; }
  .table { border-collapse: collapse; width:100%; }
  .table th, .table td { border:1px solid #ddd; padding:6px; }
  .right { text-align:right; }
  .mt-2 { margin-top:12px; }
  .mt-3 { margin-top:18px; }
</style>
</head>
<body>
  <h2>Memorandum of Understanding (MoU)</h2>
  <div class="muted">Tanggal: <?= html_escape($date) ?></div>

  <h3 class="mt-2">Pihak Influencer</h3>
  <table class="table">
    <tr><th>Nama Lengkap</th><td><?= html_escape($creator['full_name'] ?? '-') ?></td></tr>
    <tr><th>NIK</th><td><?= html_escape($creator['nik'] ?? '-') ?></td></tr>
    <tr><th>Alamat</th><td><?= nl2br(html_escape($creator['alamat'] ?? '-')) ?></td></tr>
    <tr><th>No. Telp</th><td><?= html_escape($creator['phone'] ?? '-') ?></td></tr>
    <tr><th>Email</th><td><?= html_escape($creator['email'] ?? '-') ?></td></tr>
    <tr><th>Rekening</th><td><?= html_escape(($creator['pemilik_rekening']??'-').' - '.($creator['bank']??'-').' - '.($creator['no_rekening']??'-')) ?></td></tr>
    <tr><th>Maks. Revisi</th><td><?= (int)($creator['max_revisi'] ?? 3) ?> kali</td></tr>
    <tr><th>Pembayaran setelah draft final</th><td><?= html_escape($creator['pembayaran_aman'] ?? 'Aman') ?></td></tr>
  </table>

  <h3 class="mt-3">Rincian Konten</h3>
  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>Campaign / Deskripsi</th>
        <th class="right">Total Cost</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($items as $it): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td>
            <b><?= html_escape($campaign['title'] ?? 'Campaign') ?></b><br>
            ID Endorse: <?= (int)$it['id'] ?> — Status: <?= html_escape($it['status_endorse'] ?? '-') ?><br>
            <span class="muted"><?= html_escape($it['desc'] ?? '') ?></span>
          </td>
          <td class="right">Rp <?= number_format((int)($it['total_cost'] ?? 0), 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="2" class="right"><b>Total (untuk diproses)</b></td>
        <td class="right"><b>Rp <?= number_format((int)$total, 0, ',', '.') ?></b></td>
      </tr>
    </tbody>
  </table>

  <p class="mt-3">
    Dengan ini kedua belah pihak sepakat atas ruang lingkup kerja sama, timeline produksi,
    maksimal revisi, serta ketentuan pembayaran sebagaimana tercantum di atas.
  </p>

  <table class="table mt-2">
    <tr>
      <th style="width:50%">PIHAK PERUSAHAAN</th>
      <th style="width:50%">PIHAK INFLUENCER</th>
    </tr>
    <tr>
      <td style="height:80px; vertical-align:bottom;">(___________________)</td>
      <td style="height:80px; vertical-align:bottom;"><?= html_escape($creator['full_name'] ?? '-') ?></td>
    </tr>
  </table>
</body>
</html>
