<div class="form-message"></div>
<style>
	.select2-container .select2-selection--multiple {
        min-height: 45px;
        /* box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07) !important; */
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
    }
</style>
<form action="<?= base_url() ?>/influencer/update" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="row">
		<div class="col-md-6 mb-3">
			<label>Status Creator</label>
			<select class="form-control" name="dt[status_reach]">
				<?php
				$arr = ["Affiliate", "Belum Reachout", "Sudah Reachout", "Off Endorsement", "Pernah Kerjasama", "Repeat Kerjasama", "Blacklist / Ghosting"];
				foreach ($arr as $v2) {
					$selected = ($data['status_reach'] == $v2) ? 'selected' : '';
					echo "<option $selected value=\"$v2\">$v2</option>";
				}
				?>
			</select>
		</div>
		<div class="col-md-6 mb-3">
			<label>Username</label>
			<input type="text" class="form-control" name="dt[username]" value="<?= $data['username'] ?>">
		</div>

		<div class="col-md-6 mb-3">
			<label>Brand</label>
			<select class="form-control select2" name="dt[brand][]" multiple>
				<?php
				$selectedBrands = !empty($data['brand']) ? explode(', ', $data['brand']) : [];
				foreach ($brand as $v2) {
					$selected = in_array($v2['code'], $selectedBrands) ? 'selected' : '';
					echo "<option $selected value=\"{$v2['code']}\">{$v2['code']}</option>";
				}
				?>
			</select>
		</div>
		<div class="col-md-6 mb-3">
			<label>PIC</label>
			<select class="form-control select2" name="dt[pic][]" multiple>
				<?php
				$selectedPic = !empty($data['pic']) ? explode(', ', $data['pic']) : [];
				foreach ($pic as $v2) {
					$selected = in_array($v2['full_name'], $selectedPic) ? 'selected' : '';
					echo "<option $selected value=\"{$v2['full_name']}\">{$v2['full_name']}</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-6 mb-3">
			<label>Platform</label>
			<select class="form-control" name="dt[type]">
				<?php
				$arr = ["Tiktok", "Instagram", "Twitter", "Youtube"];
				foreach ($arr as $v2) {
					$selected = ($data['type'] == $v2) ? 'selected' : '';
					echo "<option $selected value=\"$v2\">$v2</option>";
				}
				?>
			</select>
		</div>
		<div class="col-md-6 mb-3">
			<label>URL</label>
			<input type="text" class="form-control" name="dt[url]" value="<?= $data['url'] ?>">
		</div>

		<div class="col-md-6 mb-3">
			<label>Category KOL</label>
			<select class="form-control" name="dt[niche]">
				<option value="">Pilih Category KOL</option>
				<?php
				foreach ($niche as $v2) {
					$selected = ($data['niche'] == $v2['niche']) ? 'selected' : '';
					echo "<option $selected value=\"{$v2['niche']}\">{$v2['niche']}</option>";
				}
				?>
			</select>
		</div>
		<div class="col-md-6 mb-3">
			<label>Range Ratecard</label>
			<input type="text" class="form-control" name="dt[ratecard]" value="<?= $data['ratecard'] ?>">
		</div>

		<div class="col-md-6 mb-3">
			<label>Tipe Kontak</label>
			<select class="form-control" name="dt[tipe_kontak]">
				<?php
				$arr = ["WA", "IG", "Email", "HP"];
				foreach ($arr as $v2) {
					$selected = ($data['tipe_kontak'] == $v2) ? 'selected' : '';
					echo "<option $selected value=\"$v2\">$v2</option>";
				}
				?>
			</select>
		</div>
		<div class="col-md-6 mb-3">
			<label>URL Kontak</label>
			<input type="text" class="form-control" name="dt[contact]" value="<?= $data['contact'] ?>">
		</div>

		<div class="col-md-2">
			<label>Bank</label>
			<input type="text" class="form-control" name="dt[bank]" value="<?= $data['bank'] ?>">
		</div>

		<div class="col-md-4">
			<label>No Rekening</label>
			<input type="text" class="form-control" name="dt[no_rekening]" value="<?= $data['no_rekening'] ?>">
		</div>

		<div class="col-md-6">
			<label>Pemilik Rekening</label>
			<input type="text" class="form-control" name="dt[pemilik_rekening]" value="<?= $data['pemilik_rekening'] ?>">
		</div>

		<div class="col-md-6 mb-3">
			<label>Keterangan</label>
			<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
		</div>
		<div class="col-md-6 mb-3">
			<label>Status</label>
			<select class="form-control" name="dt[status]">
				<?php
				$arr = ["Aktif", "Tidak Aktif"];
				foreach ($arr as $v2) {
					$selected = ($data['status'] == $v2) ? 'selected' : '';
					echo "<option $selected value=\"$v2\">$v2</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-12 mt-1">
			<button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
		</div>
	</div>
	<?php if (!empty($log_text)): ?>
		<div class="col-md-12 my-3">
			<div class="alert alert-info py-2" style="font-size:13px;">
				<i class="bi bi-info-circle me-1"></i>
				<?= $log_text ?>
			</div>
		</div>
	<?php endif; ?>

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
						$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
	$(document).ready(function() {
		$('.select2').select2();
	});
</script>