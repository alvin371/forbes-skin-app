<div class="form-message"></div>
<style>
    .select2-container .select2-selection--multiple {
        min-height: 45px;
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
        background-color: #f8f9fa;
        color: #212529;
        border-radius: 0.25rem;
        margin-right: 4px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
        font-size: 14px;
    }
</style>

<form action="<?= base_url() ?>/endorse-campaign/store" method="POST" id="form-modal" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <input type="hidden" name="id_campaign" value="<?= $data['id'] ?>">

    <div class="d-flex align-items-center">
        <div class="form-check me-3">
            <input class="form-check-input" type="checkbox" id="internal_cb" name="dt[is_internal]" value="1"
                <?= (isset($_GET['p']) && $_GET['p'] === 'internal') ? 'checked' : '' ?>
                onclick="handleCheckboxChange('internal')">
            <label class="form-check-label" for="internal_cb">
                Internal
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="external_cb" name="dt[is_internal]" value="0"
                <?= (isset($_GET['p']) && $_GET['p'] === 'external') ? 'checked' : '' ?>
                onclick="handleCheckboxChange('external')">
            <label class="form-check-label" for="external_cb">
                External
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Judul Campaign</label>
            <input type="text" class="form-control" name="dt[title]" value="<?= $data['title'] ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Produk</label>
            <select class="form-control select2" id="product-select" multiple>
                <?php foreach ($produk as $v): ?>
                    <?php $selected = in_array($v['id'], explode(',', $data['product'] ?? '')) ? 'selected' : ''; ?>
                    <option <?= $selected ?> value="<?= $v['id'] ?>" data-product_text="<?= htmlspecialchars($v['name']) ?>">
                        <?= htmlspecialchars($v['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="dt[product]" id="product-hidden">
            <input type="hidden" name="dt[product_text]" id="product-text">
        </div>

        <div class="col-md-6 mb-3">
            <label>Brand</label>
            <select class="form-control" name="dt[brand]">
                <?php foreach ($brand as $v2): ?>
                    <option <?= $data['brand'] == $v2['code'] ? 'selected' : '' ?> value="<?= $v2['code'] ?>">
                        <?= $v2['code'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="brand" value="<?= $data['brand'] ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>PIC</label>
            <select class="form-control select2" name="dt[pic]">
                <?php
                $selectedPic = !empty($data['pic']) ? $data['pic'] : $user['full_name'];
                foreach ($pic as $v2) {
                    $text = $selectedPic == $v2['full_name'] ? 'selected' : '';
                    echo "<option $text value='{$v2['full_name']}'>{$v2['full_name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Tanggal Mulai</label>
            <input type="date" class="form-control" name="dt[start_at]" value="<?= DATE("Y-m-d") ?>">
        </div>

        <div class="col-md-6">
			<label for="">SPV</label>
			<select type="text" class="form-control select2" name="dt[spv]">
				<?php
				$selectedPic = !empty($data['spv']) ? $data['spv'] : $user['full_name'];

				foreach ($spv as $v2) {
					$text = $selectedPic == $v2['full_name'] ? 'selected' : '';
					echo "<option $text value='{$v2['full_name']}'>{$v2['full_name']}</option>";
				}
				?>
			</select>
		</div>

        <div class="col-md-6 mb-3">
            <label>Tanggal Selesai</label>
            <input type="date" class="form-control" name="dt[until_at]" value="<?= DATE("Y-m-d") ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Budget</label>
            <input type="text" class="form-control" id="budget_formatted" value="<?= $data['budget'] ?>">
            <input type="hidden" name="dt[budget]" id="budget" value="<?= $data['budget'] ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Status</label>
            <select class="form-control" name="dt[status]">
                <?php foreach (["Aktif", "Tidak Aktif"] as $v2): ?>
                    <option <?= $data['status'] == $v2 ? 'selected' : '' ?> value="<?= $v2 ?>"><?= $v2 ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Deskripsi</label>
            <textarea class="form-control" name="dt[desc]" style="min-height:160px!important"><?= $data['desc'] ?></textarea>
        </div>

        <div class="col-md-12 mb-3">
            <label>Media (Photo/Video)</label>
            <small class="text-muted d-block mb-2">Upload gambar (JPG, PNG, max 2MB) atau video (MP4, MOV, max 10MB)</small>
            <input type="file" class="form-control" id="media_file" name="media_file" accept="image/jpeg,image/jpg,image/png,video/mp4,video/quicktime">
            <div id="media_preview" class="mt-3" style="display:none;">
                <img id="image_preview" src="" alt="Image Preview" style="max-width: 300px; max-height: 300px; display:none;">
                <video id="video_preview" controls style="max-width: 400px; max-height: 300px; display:none;">
                    <source src="" type="">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        <button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function () {
        $('.select2').select2();

        $('#product-select').on('change', function () {
            const selectedOptions = $(this).find(':selected');
            const values = selectedOptions.map(function () { return this.value; }).get().join(',');
            const texts = selectedOptions.map(function () { return $(this).text().trim(); }).get().join(',');

            $('#product-hidden').val(values);
            $('#product-text').val(texts);
        });

        $('#product-select').trigger('change');

    });

    function handleCheckboxChange(value) {
        if (value === 'internal') {
            $('#external_cb').prop('checked', false);
        } else if (value === 'external') {
            $('#internal_cb').prop('checked', false);
        }
    }

    document.getElementById('budget_formatted').addEventListener('input', function (e) {
        let value = this.value.replace(/[^0-9]/g, '');
        document.getElementById('budget').value = value;
        if (value.length > 0) value = parseInt(value).toLocaleString('id-ID');
        this.value = value;
    });

    // Media file preview
    document.getElementById('media_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const fileType = file.type;
            const previewContainer = document.getElementById('media_preview');
            const imagePreview = document.getElementById('image_preview');
            const videoPreview = document.getElementById('video_preview');

            // Hide both previews first
            imagePreview.style.display = 'none';
            videoPreview.style.display = 'none';

            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else if (fileType.startsWith('video/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    videoPreview.querySelector('source').src = e.target.result;
                    videoPreview.querySelector('source').type = fileType;
                    videoPreview.load();
                    videoPreview.style.display = 'block';
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
    });

    $("#form-modal").submit(function () {
        // Validate is_internal field - at least one checkbox must be selected
        if (!$('#internal_cb').is(':checked') && !$('#external_cb').is(':checked')) {
            alert('Silakan pilih tipe campaign: Internal atau External!');
            return false;
        }

        var form = $(this);
        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function (response) {
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function () {
                        window.location.href = "";
                    }, 2500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                }
                $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
            },
            error: function (xhr) {
                $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>

