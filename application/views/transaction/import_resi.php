<div class="form-message"></div>
<form action="<?= base_url() ?>/transaction/import-resi-process" method="POST" enctype="multipart/form-data" id="form-modal">
    <p class="mb-1">Silahkan upload file upload file excel!
    <br>
        <!-- <b>Manual</b><br> -->
        <a href="<?= base_url() ?>/transaction/download-resi<?=$param?>&p=MANUAL" target="_blank">Download Template</a>
       <!-- <br>
        <b>Marketplace</b><br>
        <a href="<?= base_url() ?>/transaction/download-template<?=$param?>&p=MARKETPLACE" target="_blank">Download Template Marketplace <?=$file_name?></a> <br> -->
    </p>
    <input type="hidden" name="param" value="<?=$param?>">
    <div class="form-group mt-3 d-none">
        <select class="form-control" name="marketplace">
            <option value="MANUAL">MANUAL</option>
            <!-- <option value="MARKETPLACE">MARKETPLACE</option> -->
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