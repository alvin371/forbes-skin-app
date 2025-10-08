<?php

?>
<div class="form-message"></div>
<form action="<?= base_url() ?>/product-3rd/sync-process" method="POST" id="form-modal">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <p class="mb-0">Apakah kamu yakin ingin melakukan sync data produk?</p>
	<div class="mb-1 mt-1">
        <button id="btn-sync-all" type="button" class="btn btn-success">Sync All</button>
    </div>

    <?php foreach ($store as $k => $v) {
	if ($v['marketplace'] == "TIKTOK") {
		$v['img'] = base_url() . '/assets/img/marketplace/3.png';
	} else if ($v['marketplace'] == "SHOPEE") {
		$v['img'] = base_url() . '/assets/img/marketplace/1.png';
	} else if ($v['marketplace'] == "LAZADA") {
		$v['img'] = base_url() . '/assets/img/marketplace/2.png';
	} else if ($v['marketplace'] == "META") {
		$v['img'] = base_url() . '/assets/img/marketplace/5.png';
	} 
	?>
		
        <hr class="mb-2">
        <div class="d-flex align-items-center mb-2">
			<span class="me-2 fw-600"><?= $k + 1 ?>.</span>
			<img src="<?= $v['img'] ?>" class="rounded-circle border me-2" style="width: 35px; height: 35px;">
			<div>
				<div class="fw-600"><?= $v['opt'] ?></div>
				<div class="text-muted small"><?= !empty($v['shop_code']) ? $v['shop_code'] : '-' ?></div>
			</div>
		</div>


        <div class="mb-1 mt-1">
            <button form="form-modal-refresh-<?= $k ?>" type="submit" class="btn btn-edit btn-send-refresh">Refresh Token</button>
            <button form="form-modal-sync-<?= $k ?>" type="submit" class="btn btn-primary btn-send-sync">Sync Data</button>
        </div>
    <?php } ?>
    
    <hr class="mb-2">
   
</form>


<?php

$arr = array();
foreach ($store as $k => $v) {
?>
	<form action="<?= base_url() ?>/product-3rd/sync-process?marketplace=<?= $v['marketplace'] ?>&shop_id=<?= $v['id'] ?>" method="POST" id="form-modal-sync-<?= $k ?>"></form>
	<form action="<?= base_url() ?>/marketplace-account/refresh-token-process?marketplace=<?= $v['marketplace'] ?>&shop_id=<?= $v['id'] ?>" method="POST" id="form-modal-refresh-<?= $k ?>"></form>

	<script type="text/javascript">
		$("#form-modal-sync-<?= $k ?>").submit(function() {
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
					$(".btn-send-sync")
						.addClass("disabled")
						.html(
							'<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>'
						)
						.attr("disabled", true);
					form.find(".form-message").slideUp().html("");
				},
				success: function(response, textStatus, xhr) {
					var str = response;
					console.log(str);
					if (str.indexOf("success") != -1) {
						$(".form-message").hide().html(response).slideDown("fast");
						setTimeout(function() {
							window.location.href = "";
							$(".btn-send-sync")
								.removeClass("disabled")
								.html("Sync Data")
								.attr("disabled", false);
						}, 2500);
					} else {
						$(".form-message").hide().html(response).slideDown("fast");
						$(".btn-send-sync")
							.removeClass("disabled")
							.html("Sync Data")
							.attr("disabled", false);
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					$(".btn-send-sync")
						.removeClass("disabled")
						.html("Sync Data")
						.attr("disabled", false);
					$(".form-message").hide().html(xhr).slideDown("fast");
				},
			});
			return false;
		});
	</script>

	<script type="text/javascript">
		$("#form-modal-refresh-<?= $k ?>").submit(function() {
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
					$(".btn-send-refresh")
						.addClass("disabled")
						.html(
							'<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>'
						)
						.attr("disabled", true);
					form.find(".form-message").slideUp().html("");
				},
				success: function(response, textStatus, xhr) {
					var str = response;
					console.log(str);
					if (str.indexOf("success") != -1) {
						$(".form-message").hide().html(response).slideDown("fast");
						setTimeout(function() {
							window.location.href = "";
							$(".btn-send-refresh")
								.removeClass("disabled")
								.html("Refresh Token")
								.attr("disabled", false);
						}, 2500);
					} else {
						$(".form-message").hide().html(response).slideDown("fast");
						$(".btn-send-refresh")
							.removeClass("disabled")
							.html("Refresh Token")
							.attr("disabled", false);
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					$(".btn-send-refresh")
						.removeClass("disabled")
						.html("Refresh Token")
						.attr("disabled", false);
					$(".form-message").hide().html(xhr).slideDown("fast");
				},
			});
			return false;
		});
	</script>

<?php } ?>

<script type="text/javascript">
    $("#btn-sync-all").click(function() {
        var btn = $(this);
        var forms = [];
        
        // Collect all sync forms
        <?php foreach ($store as $k => $v) { ?>
            forms.push($("#form-modal-sync-<?= $k ?>"));
        <?php } ?>
        
        btn.addClass("disabled")
            .html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>')
            .attr("disabled", true);
        
        $(".form-message").slideUp().html("");
        
        // Process forms sequentially
        var processForm = function(index) {
            if (index >= forms.length) {
                // All forms processed
                btn.removeClass("disabled")
                    .html("Sync All Stores")
                    .attr("disabled", false);
                return;
            }
            
            var form = forms[index];
            var mydata = new FormData(form[0]);
            
            $.ajax({
                type: "POST",
                url: form.attr("action"),
                data: mydata,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response, textStatus, xhr) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message").hide().append('<div class="alert alert-success">' + 
                            'Store ' + (index+1) + ' synced successfully</div>').slideDown("fast");
                    } else {
                        $(".form-message").hide().append('<div class="alert alert-danger">' + 
                            'Error syncing store ' + (index+1) + '</div>').slideDown("fast");
                    }
                    
                    // Process next form
                    processForm(index + 1);
                },
                error: function(xhr, textStatus, errorThrown) {
                    $(".form-message").hide().append('<div class="alert alert-danger">' + 
                        'Error syncing store ' + (index+1) + '</div>').slideDown("fast");
                    // Continue with next form even if there's an error
                    processForm(index + 1);
                }
            });
        };
        
        // Start processing from first form
        processForm(0);
    });
</script>