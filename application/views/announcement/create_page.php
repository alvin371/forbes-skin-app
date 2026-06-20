<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0,0,0,0.85);">Tambah Announcement</h5>
                <a href="<?= base_url() ?>announcement" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>announcement/store" method="POST" id="form-announcement">
                <?php $this->load->view('announcement/_form_fields', ['data' => [], 'categories' => $categories, 'statuses' => $statuses, 'priorities' => $priorities]); ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->load->view('announcement/_form_script', ['redirect' => base_url() . 'announcement']); ?>
