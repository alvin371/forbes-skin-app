<form action="<?= base_url() ?>/endorse/set_batal_pengajuan_payment" method="POST" id="form-modal">
    <input type="hidden" name="id" value="<?= $data[0]['id']  ?>">
    <input type="hidden" name="id_campaign" value="<?= $data[0]['id_campaign'] ?>">
    <input type="hidden" name="nama_creator" value="<?= $data[0]['nama_creator'] ?>">
    <input type="hidden" name="status_endorse_existing" value="<?= $data['data'][0]['status_endorse'] ?>">
    <p>Apakah kamu yakin membatalkan pengajuan payment?</p>
    <!-- <ul>
		<li>Nama Creator: <strong><?= $data['nama_creator'] ?></strong></li>
		<li>Campaign ID: <strong><?= $data['id_campaign'] ?></strong></li>
	</ul> -->
    <div class="col-md-12 mt-3">
        <button type="submit" class="btn btn-primary btn-send">Batalkan</button>
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
                $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                var str = response;
                console.log(str);
                if (str.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "";
                        $(".btn-send").removeClass("disabled").html('Batalkan').attr('disabled', false);
                    }, 2500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('Batalkan').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('Batalkan').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>