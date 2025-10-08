<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Tambah Main Quest</h5>
                <a href="<?= base_url() ?>quest" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>

            <!-- Quest Counter and Controls -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2" id="quest-counter">Quest 1 dari 1</span>
                        <small class="text-muted">Anda dapat menambahkan beberapa quest sekaligus</small>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-success btn-sm" id="add-quest-btn">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Quest Lain
                    </button>
                </div>
            </div>

            <form action="<?= base_url() ?>quest/main_quest_store" method="POST" id="form-create">
                <div id="quest-container">
                    <!-- Quest Row Template -->
                    <div class="quest-row border rounded p-3 mb-3" data-quest-index="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-primary">
                                <i class="bi bi-trophy me-1"></i> Quest #<span class="quest-number">1</span>
                            </h6>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-quest-btn" style="display: none;">
                                <i class="bi bi-trash me-1"></i> Hapus
                            </button>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Judul Quest <span class="text-danger">*</span></label>
                                <input type="text" class="form-control quest-title" name="dt[0][title]"
                                       placeholder="Masukkan judul main quest..." required>
                                <small class="text-muted">Judul yang menarik dan jelas untuk quest ini</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Required Position <span class="text-danger">*</span></label>
                                <select class="form-control quest-position" name="dt[0][required_position_id]" required>
                                    <option value="">Pilih Position</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?= $position['id'] ?>"><?= $position['name'] ?> (<?= $position['level_name'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Position minimum untuk mengerjakan quest ini</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi Quest <span class="text-danger">*</span></label>
                                <textarea class="form-control quest-description" name="dt[0][description]" rows="4"
                                          placeholder="Jelaskan detail quest, kriteria keberhasilan, dan ekspektasi yang diharapkan..." required></textarea>
                                <small class="text-muted">Berikan deskripsi yang detail dan jelas tentang apa yang harus dikerjakan</small>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> <span class="submit-text">Simpan Quest</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    let questCount = 1;

    // Update quest counter display
    function updateQuestCounter() {
        $("#quest-counter").text(`Quest 1 dari ${questCount}`);

        // Show/hide remove buttons based on quest count
        if (questCount > 1) {
            $(".remove-quest-btn").show();
        } else {
            $(".remove-quest-btn").hide();
        }

        // Update quest numbers
        $(".quest-row").each(function(index) {
            $(this).find(".quest-number").text(index + 1);
        });

        // Update submit button text
        if (questCount > 1) {
            $(".submit-text").text(`Simpan ${questCount} Quest`);
        } else {
            $(".submit-text").text("Simpan Quest");
        }
    }

    // Add new quest row
    $("#add-quest-btn").click(function() {
        const positions = <?= json_encode($positions) ?>;
        let positionOptions = '<option value="">Pilih Position</option>';

        positions.forEach(function(position) {
            positionOptions += `<option value="${position.id}">${position.name} (${position.level_name})</option>`;
        });

        const questRowHtml = `
            <div class="quest-row border rounded p-3 mb-3" data-quest-index="${questCount}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 text-primary">
                        <i class="bi bi-trophy me-1"></i> Quest #<span class="quest-number">${questCount + 1}</span>
                    </h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-quest-btn">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Judul Quest <span class="text-danger">*</span></label>
                        <input type="text" class="form-control quest-title" name="dt[${questCount}][title]"
                               placeholder="Masukkan judul main quest..." required>
                        <small class="text-muted">Judul yang menarik dan jelas untuk quest ini</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Required Position <span class="text-danger">*</span></label>
                        <select class="form-control quest-position" name="dt[${questCount}][required_position_id]" required>
                            ${positionOptions}
                        </select>
                        <small class="text-muted">Position minimum untuk mengerjakan quest ini</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Deskripsi Quest <span class="text-danger">*</span></label>
                        <textarea class="form-control quest-description" name="dt[${questCount}][description]" rows="4"
                                  placeholder="Jelaskan detail quest, kriteria keberhasilan, dan ekspektasi yang diharapkan..." required></textarea>
                        <small class="text-muted">Berikan deskripsi yang detail dan jelas tentang apa yang harus dikerjakan</small>
                    </div>
                </div>
            </div>
        `;

        $("#quest-container").append(questRowHtml);
        questCount++;
        updateQuestCounter();

        // Smooth scroll to new quest
        $('html, body').animate({
            scrollTop: $(".quest-row:last").offset().top - 100
        }, 500);
    });

    // Remove quest row
    $(document).on('click', '.remove-quest-btn', function() {
        if (questCount > 1) {
            const questRow = $(this).closest('.quest-row');

            // Animate removal
            questRow.fadeOut(300, function() {
                questRow.remove();
                questCount--;

                // Re-index remaining quest rows
                $(".quest-row").each(function(index) {
                    $(this).attr('data-quest-index', index);
                    $(this).find('input, select, textarea').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            const newName = name.replace(/dt\[\d+\]/, `dt[${index}]`);
                            $(this).attr('name', newName);
                        }
                    });
                });

                updateQuestCounter();
            });
        }
    });

    // Form submission with enhanced validation
    $("#form-create").submit(function() {
        var form = $(this);
        var mydata = new FormData(this);

        // Validate each quest row
        let isValid = true;
        let emptyFields = [];

        $(".quest-row").each(function(index) {
            const questNumber = index + 1;
            const title = $(this).find('.quest-title').val().trim();
            const position = $(this).find('.quest-position').val();
            const description = $(this).find('.quest-description').val().trim();

            if (!title) {
                emptyFields.push(`Quest #${questNumber}: Judul`);
                isValid = false;
            }
            if (!position) {
                emptyFields.push(`Quest #${questNumber}: Position`);
                isValid = false;
            }
            if (!description) {
                emptyFields.push(`Quest #${questNumber}: Deskripsi`);
                isValid = false;
            }
        });

        if (!isValid) {
            const errorMsg = `<div class="alert alert-danger">
                <h6><i class="bi bi-exclamation-triangle me-2"></i>Field berikut masih kosong:</h6>
                <ul class="mb-0">${emptyFields.map(field => `<li>${field}</li>`).join('')}</ul>
            </div>`;

            $(".form-message").hide().html(errorMsg).slideDown("fast");
            return false;
        }

        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                const buttonText = questCount > 1 ? `Menyimpan ${questCount} Quest...` : 'Menyimpan...';
                $(".btn-send").addClass("disabled").html(`<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>${buttonText}`).attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>quest";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    const submitText = questCount > 1 ? `Simpan ${questCount} Quest` : 'Simpan Quest';
                    $(".btn-send").removeClass("disabled").html(`<i class="bi bi-save me-1"></i> ${submitText}`).attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                const submitText = questCount > 1 ? `Simpan ${questCount} Quest` : 'Simpan Quest';
                $(".btn-send").removeClass("disabled").html(`<i class="bi bi-save me-1"></i> ${submitText}`).attr('disabled', false);
                $(".form-message").hide().html('<div class="alert alert-danger">Terjadi kesalahan saat menyimpan quest. Silakan coba lagi.</div>').slideDown("fast");
            }
        });
        return false;
    });

    // Initialize counter on page load
    updateQuestCounter();
</script>