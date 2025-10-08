<div class="form-message"></div>
<form action="<?= base_url() ?>/transaction/import-customer-process" method="POST" enctype="multipart/form-data" id="form-modal">
    <p class="mb-1">Silahkan upload file upload file excel!
        <!-- <br> -->
        <!-- <a href="<?= base_url() ?>/transaction/download-customer-template?start_date=keyword=<?= $keyword ?>&brand=<?= $brand ?>&marketplace=<?= $marketplace ?>&cs=<?= $cs ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>" target="_blank">Download Template TRX <?= DATE('d-M-Y', strtotime($start_date)) ?> - <?= DATE('d-M-Y', strtotime($until_date)) ?></a> -->
    </p>
    <div class="form-group mt-3">
        <select class="form-control" name="marketplace">
            <option value="TIKTOK">TIKTOK</option>
            <option value="SHOPEE">SHOPEE</option>
            <option value="LAZADA">LAZADA</option>
        </select>
    </div>
    <div class="form-group mt-3">
        <input type="hidden" name="start_date" value="<?= $start_date ?>">
        <input type="hidden" name="until_date" value="<?= $until_date ?>">
        <input type="file" name="file" class="form-control" accept=".xlsx">
    </div>
    <div class="form-group mt-3">
        <button type="submit" class="btn btn-primary btn-modal">Import Data</button>
    </div>
</form>
<script type="text/javascript">
    $("#form-modal").submit(function() {
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
                $(".btn-modal").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                var str = response;
                console.log(str);
                if (str.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "";
                        $(".btn-modal").removeClass("disabled").html('Import Data').attr('disabled', false);
                    }, 2500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-modal").removeClass("disabled").html('Import Data').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-modal").removeClass("disabled").html('Import Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>