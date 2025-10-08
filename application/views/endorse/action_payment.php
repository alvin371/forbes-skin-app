<div class="form-message"></div>

<?php $isRollback = ($data['code'] ?? '') === 'rollback_payment'; ?>

<form action="<?= base_url() ?>/payment/action-process" method="POST" id="form-action">
    <input type="hidden" name="code" value="<?= $data['code'] ?>">
    <input type="hidden" name="id_selected" value="<?= $this->input->get('id_selected', true) ?>">

    <p><?= $question ?></p>

    <?php if (!$isRollback): ?>
        <div class="mb-2">
            <label>Link Telegram</label>
            <input type="url" name="link_telegram" class="form-control" placeholder="https://t.me/..." required>
        </div>
        <div class="mb-2">
            <label>Tanggal TF</label>
            <input type="date" name="tgl_tf" class="form-control" required>
        </div>
    <?php else: ?>
        <div class="alert alert-info py-2">
            Data akan dikembalikan ke status <strong>Pengajuan Payment (logs)</strong>.
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary btn-send"><?= $btn ?></button>
    </div>
</form>

<script>
  $("#form-action").submit(function() {
    var form = $(this);
    var mydata = new FormData(this);
    $.ajax({
      type: "POST",
      url: form.attr("action"),
      data: mydata,
      cache: false,
      contentType: false,
      processData: false,
      beforeSend: function() {
        $(".btn-send")
          .addClass("disabled")
          .html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>')
          .attr('disabled', true);
        form.find(".form-message").slideUp().html("");
      },
      success: function(response) {
        $(".form-message").hide().html(response).slideDown("fast");
        if (response.indexOf("success") !== -1) {
          setTimeout(function(){ location.reload(); }, 1500);
        } else {
          $(".btn-send").removeClass("disabled").html('<?= $btn ?>').attr('disabled', false);
        }
      },
      error: function(xhr) {
        $(".btn-send").removeClass("disabled").html('<?= $btn ?>').attr('disabled', false);
        $(".form-message").hide().html(xhr.responseText || 'Request error').slideDown("fast");
      }
    });
    return false;
  });
</script>
