<div class="form-message"></div>
<form action="<?= base_url() ?>/crm/fu-2-process" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<p>Apakah kamu yakin ingin melakukan follow up h-7?</p>
    <p>Nama Lengkap : <?=$data['full_name']?></p>
    <p>WA : <?=$data['phone']?></p>
    <p>Tanggal Join : <?=$data['join']?></p>
    <p>Tanggal FU H-7 : <?=$data['waktu_fu_ro']?></p>
    <p>Tanggal FU H-7 Sebelumnya : <?=$data['last_fu_2_at']?></p>
	<div class="col-md-12 mt-3">
		<button type="submit" class="btn btn-primary btn-send">KIRIM FU</button>
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
            dataType: "json",
			beforeSend: function() {
				$(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
				form.find(".form-message").slideUp().html("");
			},
			success: function(response, textStatus, xhr) {
                console.log(response.status);
				if (response.status) {
					$(".form-message").hide().html(response.msg).slideDown("fast");
					setTimeout(function() {
                        url = response.url;
                        window.open(url, '_blank');
						window.location.href = "";
						$(".btn-send").removeClass("disabled").html('KIRIM FU').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response.msg).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('KIRIM FU').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('KIRIM FU').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>