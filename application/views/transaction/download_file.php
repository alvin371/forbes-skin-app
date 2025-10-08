<div class="form-message"></div>
<form action="<?= base_url() ?>/transaction/download-process<?= $param ?>" method="POST" id="form-modal">
	<p class="mb-2">Apakah kamu yakin ingin membuat file excel data order ini?</p>

	<div class="col-md-12 mt-0">
		<button type="submit" class="btn btn-primary btn-send mt-0">Buat File Download</button>
	</div>

	<div class="col-md-12" id="div-download">

	</div>
</form>
<script type="text/javascript">
	function loadDownload() {
		$.ajax({
			type: 'GET',
			url: "<?= base_url() ?>/transaction/download-ajax",
			success: function(data) {
				$('#div-download').html(data.html);
			},
			error: function(xhr, status, error) {}
		});
	}
	loadDownload();
	setInterval(loadDownload, 5000);

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
					loadDownload();
					$(".form-message").hide().html(response).slideDown("fast");
					setTimeout(function() {
						$(".btn-send").removeClass("disabled").html('Buat File Download').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Buat File Download').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Buat File Download').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>