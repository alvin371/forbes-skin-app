<form action="<?= base_url('endorse/generate_mou') ?>" method="POST" id="form-mou">
	<input type="hidden" name="id" value="<?= $data['id']  ?>">
	<input type="hidden" name="id_campaign" value="<?= $data['id_campaign'] ?>">
	<input type="hidden" name="nama_creator" value="<?= $data['nama_creator'] ?>">
	<input type="hidden" name="status_endorse" value="<?= $data['status_endorse'] ?>">
	<input type="hidden" name="status_payment" value="<?= $data['status_payment'] ?>">

	<!-- Progress Wizard Indicator -->
	<div class="wizard-progress mb-4">
		<div class="wizard-step" data-wizard-step="1">
			<div class="wizard-step-circle active">
				<i class="bi bi-person-fill"></i>
			</div>
			<span class="wizard-step-label">Data Pribadi</span>
		</div>
		<div class="wizard-connector"></div>
		<div class="wizard-step" data-wizard-step="2">
			<div class="wizard-step-circle">
				<i class="bi bi-file-text"></i>
			</div>
			<span class="wizard-step-label">Pilih Konten</span>
		</div>
		<div class="wizard-connector"></div>
		<div class="wizard-step" data-wizard-step="3">
			<div class="wizard-step-circle">
				<i class="bi bi-send-check"></i>
			</div>
			<span class="wizard-step-label">Generate</span>
		</div>
	</div>

	<!-- SECTION 1: DATA PRIBADI -->
	<div class="form-section" id="section-data-pribadi">
		<div class="card-enhanced mb-3">
			<div class="form-check-custom mb-3">
				<input class="form-check-input" type="checkbox" id="use_text_generator">
				<label class="form-check-label" for="use_text_generator">
					<i class="bi bi-magic me-1"></i>Gunakan Generator Teks (Isi Cepat)
				</label>
			</div>
			<div id="generator_wrap" class="generator-box" style="display:none;">
				<textarea class="form-control-custom" id="generator_text" placeholder="Paste data influencer di sini..." rows="6"></textarea>
				<button type="button" class="btn-custom btn-custom-primary mt-2" id="btn-parse-generator">
					<i class="bi bi-lightning-charge me-1"></i>Generate ke Form
				</button>
			</div>
		</div>

		<?php
			$inf = $data;
			$val = [
				'full_name'       => $inf['full_name']       ?? ($DEF['full_name'] ?? ''),
				'nik'             => $inf['nik']             ?? ($DEF['nik'] ?? ''),
				'alamat'          => $inf['alamat']          ?? ($DEF['alamat'] ?? ''),
				'phone'           => $inf['phone']           ?? ($DEF['phone'] ?? ''),
				'email'           => $inf['email']           ?? ($DEF['email'] ?? ''),
				'pemilik_rekening'=> $inf['pemilik_rekening']?? ($DEF['pemilik_rekening'] ?? ''),
				'bank'            => $inf['bank']            ?? ($DEF['bank'] ?? ''),
				'no_rekening'     => $inf['no_rekening']     ?? ($DEF['no_rekening'] ?? ''),
				'max_revisi'      => $inf['max_revisi']      ?? ($DEF['max_revisi'] ?? 3),
				'pembayaran_aman' => $inf['pembayaran_aman'] ?? ($DEF['pembayaran_aman'] ?? 'Aman'),
			];
		?>

		<div class="card-enhanced">
			<div class="row g-3">
				<div class="col-md-6">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-person me-1"></i>Nama Lengkap
						</label>
						<input type="text" class="form-control-custom" name="full_name" id="full_name" value="<?= html_escape($val['full_name']) ?>" placeholder="Masukkan nama lengkap">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-card-text me-1"></i>NIK
						</label>
						<input type="text" class="form-control-custom" name="nik" id="nik" value="<?= html_escape($val['nik']) ?>" placeholder="16 digit NIK">
					</div>
				</div>
				<div class="col-md-12">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-geo-alt me-1"></i>Alamat
						</label>
						<textarea class="form-control-custom" name="alamat" id="alamat" rows="2" placeholder="Alamat lengkap"><?= html_escape($val['alamat']) ?></textarea>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-telephone me-1"></i>No. Telp
						</label>
						<input type="text" class="form-control-custom" name="phone" id="phone" value="<?= html_escape($val['phone']) ?>" placeholder="08xxx">
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-envelope me-1"></i>Email
						</label>
						<input type="email" class="form-control-custom" name="email" id="email" value="<?= html_escape($val['email']) ?>" placeholder="email@example.com">
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-arrow-repeat me-1"></i>Maksimal Revisi
						</label>
						<input type="number" min="0" class="form-control-custom" name="max_revisi" id="max_revisi" value="<?= (int)$val['max_revisi'] ?>">
					</div>
				</div>

				<div class="col-md-8">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-bank me-1"></i>Rekening
						</label>
						<input 
							type="text" 
							class="form-control-custom" 
							id="rekening_combo" 
							value="<?= html_escape($val['pemilik_rekening'] . ', ' . $val['bank'] . ', ' . $val['no_rekening']) ?>"
							placeholder="Nama Pemilik, Nama Bank, No. Rekening"
						>
						<small class="form-hint">Format: <strong>Nama, Bank, No.Rek</strong></small>
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group-custom">
						<label class="form-label-custom">
							<i class="bi bi-shield-check me-1"></i>Pembayaran Setelah Draft
						</label>
						<select class="form-control-custom" name="pembayaran_aman" id="pembayaran_aman">
							<?php $pa = strtolower(trim($val['pembayaran_aman'])) === 'aman' ? 'Aman' : 'Aman'; ?>
							<option value="Aman" <?= $pa === 'Aman' ? 'selected' : '' ?>>Aman</option>
							<option value="Tidak" <?= $pa === 'Tidak' ? 'selected' : '' ?>>Tidak</option>
						</select>
					</div>
				</div>

				<input type="hidden" name="pemilik_rekening" id="pemilik_rekening" value="<?= html_escape($val['pemilik_rekening']) ?>">
				<input type="hidden" name="bank" id="bank" value="<?= html_escape($val['bank']) ?>">
				<input type="hidden" name="no_rekening" id="no_rekening" value="<?= html_escape($val['no_rekening']) ?>">
			</div>
		</div>

		<div class="form-navigation">
			<button type="button" class="btn-custom btn-custom-primary" id="btn-next-1">
				Selanjutnya <i class="bi bi-arrow-right ms-1"></i>
			</button>
		</div>
	</div>

	<!-- SECTION 2: PILIH KONTEN -->
	<div class="form-section" id="section-konten" style="display:none;">
		<div id="mou_items" class="row g-3 mb-4"></div>

		<!-- Tambahan: SOW -->
		<!-- === SOW BUILDER === -->
		<div class="card-enhanced mt-3">
			<label class="form-label mb-2">Scope of Work (SOW)</label>

			<!-- rows container -->
			<div id="sow_rows"></div>

			<div class="d-flex gap-2 mt-2">
				<button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-sow-row">
					+ Tambah Baris
				</button>
			</div>

			<div class="form-check mt-3">
				<input class="form-check-input" type="checkbox" id="is_ads">
				<label class="form-check-label" for="is_ads">Termasuk Ads</label>
			</div>

			<!-- textarea show (otomatis diisi) -->
			<span class="text-muted mt-3">Preview SOW</span>
			<textarea class="form-control mt-3" name="sow" id="sow" rows="4" style="height:100px;" placeholder="Otomatis terisi dari builder..." readonly></textarea>

			<!-- hidden JSON untuk backend -->
			<input type="hidden" name="sow_json" id="sow_json" value="[]">
			<input type="hidden" name="is_ads" id="is_ads_hidden" value="0">
		</div>

		<!-- Tambahan: Alur Kerjasama -->
		<div class="mb-3">
			<label class="form-label">Produk Kerjasama</label>
			<input type="text" class="form-control" name="produk_kerjasama" id="produk_kerjasama" placeholder="Masukkan produk kerjasama (pisahkan dengan koma)">
		</div>

		<div class="mb-3">
			<label class="form-label">Deadline Postingan</label>
			<input type="text" class="form-control" name="deadline_postingan" id="deadline_postingan" placeholder="Contoh: 6 Oktober & 13 Oktober 2025">
		</div>

		<!-- Tambahan: Pembayaran Awal -->
		<div class="mb-3">
			<label class="form-label">Pembayaran Awal</label>
			<select class="form-control" name="pembayaran_awal" id="pembayaran_awal">
				<option value="DP">DP</option>
				<option value="FP">FP</option>
			</select>
		</div>

		<!-- Persentase Pembayaran Awal (hanya muncul jika DP) -->
		<div class="mb-3" id="wrap_persen_dp" style="display:none;">
			<label class="form-label">Persentase Pembayaran Awal (%)</label>
			<input type="number" class="form-control" name="persentase_pembayaran_awal" id="persentase_pembayaran_awal" min="1" max="100" value="50">
		</div>

		<div class="card-enhanced">
			<div class="row g-3">
				<div class="col-md-6">
					<div class="total-cost-box">
						<label class="cost-label">Total Cost (Otomatis)</label>
						<div class="cost-value">
							<span class="currency">Rp</span>
							<input type="text" class="cost-input" id="total_cost_auto" value="0" readonly>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="total-cost-box editable">
						<label class="cost-label">
							<i class="bi bi-pencil me-1"></i>Total Cost (Override)
						</label>
						<div class="cost-value">
							<span class="currency">Rp</span>
							<input type="text" class="cost-input" name="total_cost_override" id="total_cost_override" value="0">
						</div>
					</div>
					<input type="hidden" name="total_cost_override_raw" id="total_cost_override_raw" value="0">
				</div>
			</div>
		</div>

		<input type="hidden" name="mou_item_ids" id="mou_item_ids" value="">

		<div class="form-navigation">
			<button type="button" class="btn-custom btn-custom-secondary btn-sm" id="btn-prev-2">
				<i class="bi bi-arrow-left me-1"></i>Kembali
			</button>
			<button type="button" class="btn-custom btn-custom-primary btn-sm" id="btn-next-2">
				Selanjutnya <i class="bi bi-arrow-right ms-1"></i>
			</button>
		</div>
	</div>

	<!-- SECTION 3: PROSES GENERATE -->
	<div class="form-section" id="section-proses" style="display:none;">
		<div class="card-enhanced">
			<div class="process-steps">
				<div class="process-step" data-step="1">
					<div class="process-step-icon">
						<span class="spinner-border spinner-border-sm d-none"></span>
						<i class="bi bi-person-check"></i>
					</div>
					<div class="process-step-content">
						<div class="process-step-label">Simpan Data Influencer</div>
						<div class="process-step-status badge bg-secondary">Menunggu</div>
					</div>
				</div>
				
				<div class="process-step" data-step="2">
					<div class="process-step-icon">
						<span class="spinner-border spinner-border-sm d-none"></span>
						<i class="bi bi-file-earmark-pdf"></i>
					</div>
					<div class="process-step-content">
						<div class="process-step-label">Generate PDF MoU</div>
						<div class="process-step-status badge bg-secondary">Menunggu</div>
					</div>
				</div>
				
				<div class="process-step" data-step="3">
					<div class="process-step-icon">
						<span class="spinner-border spinner-border-sm d-none"></span>
						<i class="bi bi-envelope-check"></i>
					</div>
					<div class="process-step-content">
						<div class="process-step-label">Kirim Email ke Influencer</div>
						<div class="process-step-status badge bg-secondary">Menunggu</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Preview Section -->
		<div class="card-enhanced mt-3" id="preview-section" style="display:none;">
			<p class="text-muted small mb-3">Dokumen MoU telah berhasil dibuat. Silakan preview terlebih dahulu sebelum mengirim email.</p>
			
			<div class="action-buttons">
				<a target="_blank" rel="noopener" id="btn-preview" class="btn-custom btn-custom-outline" href="javascript:void(0)">
					<i class="bi bi-eye me-1"></i>Preview MoU
				</a>
				<a target="_blank" rel="noopener" id="btn-wa" class="btn-custom btn-custom-whatsapp" href="javascript:void(0)">
					<i class="bi bi-whatsapp me-1"></i>Reminder via WhatsApp
				</a>
				<button type="button" class="btn-custom btn-custom-success" id="btn-send-email">
					<i class="bi bi-envelope-check me-1"></i>Kirim Email
				</button>
			</div>
		</div>

		<div class="form-navigation">
			<button type="button" class="btn-custom btn-custom-secondary" id="btn-prev-3">
				<i class="bi bi-arrow-left me-1"></i>Kembali
			</button>
			<button type="button" class="btn-custom btn-custom-primary" id="btn-generate-only">
				<i class="bi bi-file-earmark-pdf me-1"></i>Generate Dokumen
			</button>
		</div>
	</div>
</form>

<style>
/* =================== GLOBAL STYLES =================== */
#form-mou {
	padding: 16px;
	background: #ffff;
}

/* =================== WIZARD PROGRESS =================== */
.wizard-progress {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12px;
	padding: 20px;
	background: rgba(255,255,255,0.95);
	border-radius: 12px;
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.wizard-step {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	position: relative;
}

.wizard-step-circle {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	background: #e5e7eb;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
	color: #6b7280;
	transition: all 0.3s ease;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.wizard-step-circle.active {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	transform: scale(1.1);
	box-shadow: 0 4px 16px rgba(102,126,234,0.4);
}

.wizard-step-circle.completed {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	color: white;
}

.wizard-step-label {
	font-size: 12px;
	font-weight: 600;
	color: #374151;
	text-align: center;
}

.wizard-connector {
	width: 60px;
	height: 3px;
	background: #e5e7eb;
	border-radius: 2px;
	margin: 0 8px;
}

/* =================== SECTION STYLES =================== */
.form-section {
	background: white;
	border-radius: 12px;
	padding: 24px;
	box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.section-header {
	display: flex;
	align-items: center;
	gap: 16px;
	margin-bottom: 24px;
	padding-bottom: 16px;
	border-bottom: 2px solid #f3f4f6;
}

.section-icon {
	width: 56px;
	height: 56px;
	border-radius: 12px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24px;
	color: white;
	box-shadow: 0 4px 12px rgba(102,126,234,0.3);
}

.section-title {
	margin: 0;
	font-size: 20px;
	font-weight: 700;
	color: #1f2937;
}

.section-subtitle {
	margin: 0;
	font-size: 14px;
	color: #6b7280;
}

/* =================== CARD ENHANCED =================== */
.card-enhanced {
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 12px;
	padding: 20px;
	margin-bottom: 16px;
	transition: all 0.3s ease;
}

.card-enhanced:hover {
	box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* =================== FORM CONTROLS =================== */
.form-group-custom {
	margin-bottom: 0;
}

.form-label-custom {
	display: flex;
	align-items: center;
	font-size: 13px;
	font-weight: 600;
	color: #374151;
	margin-bottom: 8px;
}

.form-control-custom {
	width: 100%;
	padding: 10px 14px;
	font-size: 14px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	background: white;
	transition: all 0.2s ease;
}

.form-control-custom:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.form-control-custom::placeholder {
	color: #9ca3af;
}

.form-hint {
	display: block;
	margin-top: 4px;
	font-size: 12px;
	color: #6b7280;
}

/* =================== CUSTOM CHECKBOX =================== */
.form-check-custom {
	display: flex;
	align-items: center;
	gap: 8px;
}

.form-check-custom .form-check-input {
	width: 20px;
	height: 20px;
	border: 2px solid #d1d5db;
	border-radius: 6px;
	cursor: pointer;
}

.form-check-custom .form-check-input:checked {
	background-color: #667eea;
	border-color: #667eea;
}

.form-check-custom .form-check-label {
	font-size: 14px;
	font-weight: 500;
	color: #374151;
	cursor: pointer;
	display: flex;
	align-items: center;
}

/* =================== GENERATOR BOX =================== */
.generator-box {
	background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
	border: 2px dashed #f59e0b;
	border-radius: 12px;
	padding: 16px;
	margin-top: 12px;
}

.generator-box textarea {
	border: 2px solid #f59e0b;
	background: white;
}

/* =================== BUTTONS =================== */
.btn-custom {
	padding: 10px 24px;
	font-size: 14px;
	font-weight: 600;
	border: none;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.3s ease;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	text-decoration: none;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-custom-primary {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
}

.btn-custom-primary:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 16px rgba(102,126,234,0.4);
}

.btn-custom-secondary {
	background: #e5e7eb;
	color: #374151;
}

.btn-custom-secondary:hover {
	background: #d1d5db;
}

.btn-custom-success {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	color: white;
}

.btn-custom-success:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}

.btn-custom-whatsapp {
	background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
	color: white;
}

.btn-custom-whatsapp:hover:not(.disabled) {
	transform: translateY(-2px);
	box-shadow: 0 4px 16px rgba(37,211,102,0.4);
}

.btn-custom-outline {
	background: white;
	color: #667eea;
	border: 2px solid #667eea;
}

.btn-custom-outline:hover:not(.disabled) {
	background: #667eea;
	color: white;
}

.btn-custom.disabled {
	opacity: 0.5;
	cursor: not-allowed;
	transform: none !important;
}

/* =================== FORM NAVIGATION =================== */
.form-navigation {
	display: flex;
	justify-content: flex-end;
	gap: 12px;
	margin-top: 24px;
	padding-top: 20px;
	border-top: 2px solid #f3f4f6;
}

.action-buttons {
	display: flex;
	justify-content: center;
	gap: 12px;
	margin-top: 16px;
	flex-wrap: wrap;
}

/* =================== MOU ITEMS =================== */
.mou-card {
	background: white;
	border: 2px solid #e5e7eb;
	border-radius: 12px;
	padding: 12px;
	transition: all 0.3s ease;
}

.mou-card:hover {
	border-color: #667eea;
	box-shadow: 0 4px 16px rgba(102,126,234,0.2);
	transform: translateY(-2px);
}

.mou-card h6 {
	margin: 0;
	font-weight: 600;
	font-size: 14px;
	color: #1f2937;
}

.mou-card .item-check {
	width: 20px;
	height: 20px;
	cursor: pointer;
}

/* =================== TOTAL COST BOX =================== */
.total-cost-box {
	background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
	border-radius: 12px;
	padding: 16px;
	border: 2px solid #d1d5db;
}

.total-cost-box.editable {
	background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
	border-color: #3b82f6;
}

.cost-label {
	display: block;
	font-size: 12px;
	font-weight: 600;
	color: #6b7280;
	margin-bottom: 8px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.cost-value {
	display: flex;
	align-items: center;
	gap: 8px;
}

.currency {
	font-size: 16px;
	font-weight: 700;
	color: #374151;
}

.cost-input {
	flex: 1;
	font-size: 24px;
	font-weight: 700;
	color: #1f2937;
	border: none;
	background: transparent;
	padding: 0;
}

.cost-input:focus {
	outline: none;
}

/* =================== PROCESS STEPS =================== */
.process-steps {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.process-step {
	display: flex;
	align-items: flex-start;
	gap: 16px;
	padding: 16px;
	background: white;
	border: 2px solid #e5e7eb;
	border-radius: 12px;
	transition: all 0.3s ease;
}

.process-step.active {
	border-color: #3b82f6;
	background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%);
}

.process-step.done {
	border-color: #10b981;
	background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 50%);
}

.process-step.error {
	border-color: #ef4444;
	background: linear-gradient(135deg, #fee2e2 0%, #fecaca 50%);
}

.process-step-icon {
	width: 48px;
	height: 48px;
	border-radius: 12px;
	background: #f3f4f6;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
	color: #6b7280;
	flex-shrink: 0;
	position: relative;
}

.process-step.active .process-step-icon {
	background: #3b82f6;
	color: white;
}

.process-step.done .process-step-icon {
	background: #10b981;
	color: white;
}

.process-step.error .process-step-icon {
	background: #ef4444;
	color: white;
}

.process-step-icon .spinner-border {
	position: absolute;
	width: 20px;
	height: 20px;
}

.process-step-content {
	flex: 1;
}

.process-step-label {
	font-size: 15px;
	font-weight: 600;
	color: #1f2937;
	margin-bottom: 4px;
}

.process-step-status {
	display: inline-block;
	font-size: 11px;
	padding: 4px 8px;
	border-radius: 6px;
}

/* =================== RESPONSIVE =================== */
@media (max-width: 768px) {
	#form-mou {
		padding: 16px;
	}

	.wizard-progress {
		flex-wrap: wrap;
		gap: 8px;
	}

	.wizard-step-circle {
		width: 40px;
		height: 40px;
		font-size: 16px;
	}

	.wizard-connector {
		width: 30px;
	}

	.section-header {
		flex-direction: column;
		text-align: center;
	}

	.form-navigation {
		flex-direction: column;
	}

	.btn-custom {
		width: 100%;
	}

	.action-buttons {
		flex-direction: column;
		align-items: center;
	}
}
</style>
<style>
.sow-row .form-label{margin-bottom:6px;font-size:12px;font-weight:600}
.sow-row .form-select,.sow-row .form-control{height:36px;padding:.25rem .5rem}
</style>

<script>
(() => {
	const form = document.getElementById('form-mou')
	if (!form) return

	const qS  = s => form.querySelector(s)
	const qSA = s => [...form.querySelectorAll(s)]

	const namaCreator = qS('input[name="nama_creator"]').value
	const idCampaign  = qS('input[name="id_campaign"]').value

	const moneyFmt = n => (parseInt(n||0)).toLocaleString('id-ID')
	const moneyRaw = s => (s||'').toString().replace(/[^0-9]/g,'')

	// Update wizard progress
	function updateWizardProgress(step) {
		document.querySelectorAll('.wizard-step').forEach((el, i) => {
			const circle = el.querySelector('.wizard-step-circle')
			if (i + 1 < step) {
				circle.classList.remove('active')
				circle.classList.add('completed')
			} else if (i + 1 === step) {
				circle.classList.add('active')
				circle.classList.remove('completed')
			} else {
				circle.classList.remove('active', 'completed')
			}
		})
	}

	// === Generator Teks ===
	const cbGen = qS('#use_text_generator')
	const wrapGen = qS('#generator_wrap')
	const taGen = qS('#generator_text')

	cbGen.addEventListener('change', () => {
		wrapGen.style.display = cbGen.checked ? 'block':'none'
	})

	qS('#btn-parse-generator').addEventListener('click', () => {
		const t = (taGen.value||'')
		const pick = label => {
			const r = new RegExp(label + '\\s*:\\s*(.+)', 'i')
			const m = t.match(r)
			return (m && m[1]) ? m[1].trim() : ''
		}

		const nm = pick('Nama Lengkap')
		const nk = pick('NIK')
		const al = pick('Alamat')
		const ph = pick('No. Telp')
		const em = pick('Email')
		const rk = pick('Rekening.*')
		const rv = pick('Revisi.*')
		const am = pick('Apakah aman.*|Pembayaran.*final')

		if (nm) qS('#full_name').value = nm
		if (nk) qS('#nik').value = nk
		if (al) qS('#alamat').value = al
		if (ph) qS('#phone').value = ph
		if (em) qS('#email').value = em
		if (rk) qS('#rekening_combo').value = rk
		if (rv) qS('#max_revisi').value = parseInt(rv.replace(/[^0-9]/g,'')) || 3
		if (am) qS('#pembayaran_aman').value = /aman/i.test(am) ? 'Aman' : 'Tidak'

		Swal.fire('Berhasil!','Generator teks berhasil di-apply ke form.','success')
	})

	// === Pecah rekening ke hidden input ===
	function splitRekening() {
		const s = (qS('#rekening_combo').value || '').trim()
		const parts = s.split(',').map(x => x.trim()).filter(Boolean)
		const [nama, bank, norek] = [parts[0] || '', parts[1] || '', parts[2] || '']
		qS('#pemilik_rekening').value = nama
		qS('#bank').value             = bank
		qS('#no_rekening').value      = norek
	}
	qS('#rekening_combo').addEventListener('keyup', splitRekening)
	qS('#rekening_combo').addEventListener('change', splitRekening)
	splitRekening()

	// === Load Items ===
	const mouItemsWrap = document.getElementById('mou_items')
	const idsInput     = document.getElementById('mou_item_ids')
	const totalAuto    = document.getElementById('total_cost_auto')
	const totalOver    = document.getElementById('total_cost_override')
	const totalOverRaw = document.getElementById('total_cost_override_raw')

	function loadItems() {
		mouItemsWrap.innerHTML = '<div class="col-12 text-muted">Memuat item…</div>'
		$.getJSON("<?= base_url('endorse/mou_content') ?>",
			{ id_campaign:idCampaign, nama_creator:namaCreator },
			res => {
				const rows = res?.data || []
				__mouProducts = [];
				const pickProductLabel = (r) => r.product_text;
				const seen = new Map();
				rows.forEach(r => {
					const label = String(pickProductLabel(r)).trim();
					const id    = r.id;
					if (!label) return;
					if (!seen.has(label)) {
						seen.set(label, { id, label });
					}
				});
				__mouProducts = Array.from(seen.values());
				$('#sow_rows').empty();
				const firstProduct = __mouProducts[0]?.label ?? '';
				if (firstProduct.includes(',')) {
					const productArray = firstProduct.split(',').map(p => p.trim()).filter(Boolean);
					productArray.forEach((produk, index) => {
						addSowRow({ 
							total: 1, 
							produk: produk,
							jenis: 'kerkun' 
						});
					});
				} else {
					addSowRow({ 
						total: 1, 
						produk: firstProduct,
						jenis: 'kerkun' 
					});
				}
				if (!rows.length) {
					mouItemsWrap.innerHTML = '<div class="col-12 text-warning">Tidak ada item untuk MoU.</div>'
					idsInput.value = ''
					setTotals(0)
					return
				}
				mouItemsWrap.innerHTML = ''
				let ids=[], total=0
				rows.forEach(r => {
					const col = document.createElement('div')
					col.className = 'col-md-4'
					col.innerHTML = `
						<div class="mou-card">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="small text-muted">${r.nama_creator} | ${r.status_endorse||'-'}</div>
								<div class="form-check">
									<input class="form-check-input item-check" type="checkbox" value="${r.id}" data-cost="${r.total_cost||0}" checked>
								</div>
							</div>
							<div class="mt-2" style="font-size: 16px; font-weight: 600; color: #667eea;">
								Rp ${moneyFmt(r.total_cost||0)}
							</div>
							<div class="small text-secondary mt-2">${r.desc || '-'}</div>
						</div>`
					mouItemsWrap.appendChild(col)
					ids.push(r.id)
					total += (r.total_cost||0)
				})
				idsInput.value = ids.join(',')
				setTotals(total)
				mouItemsWrap.querySelectorAll('.item-check').forEach(chk => {
					chk.addEventListener('change', recalcTotals)
				})
			}
		)
	}
	function recalcTotals() {
		let ids=[], total=0
		qSA('.item-check').forEach(c => {
			if (c.checked) { ids.push(c.value); total+=parseInt(c.dataset.cost||0) }
		})
		idsInput.value = ids.join(',')
		setTotals(total)
	}
	function setTotals(total) {
		totalAuto.value = moneyFmt(total)
		totalOver.value = moneyFmt(total)
		totalOverRaw.value = String(total)
	}
	totalOver.addEventListener('input', () => {
		totalOverRaw.value = moneyRaw(totalOver.value)
	})

	// ====== SOW BUILDER ======
	let __mouProducts = []; // diisi setelah loadItems()

	function escapeHtml(str){
		return (str||'').toString()
			.replace(/&/g,'&amp;').replace(/</g,'&lt;')
			.replace(/>/g,'&gt;').replace(/"/g,'&quot;')
			.replace(/'/g,'&#39;');
	}

	function productOptionsHtml(){
		if (!__mouProducts.length) return '<option value="">(Isi konten dulu)</option>';
		return __mouProducts
			.map(p => `<option value="${escapeHtml(p.id)}">${escapeHtml(p.label)}</option>`)
			.join('');
	}

	function addSowRow(pref){
		const idx = document.querySelectorAll('#sow_rows .sow-row').length + 1;
		
		const produkList = (pref?.produk || '').split(',').map(p => p.trim()).filter(Boolean);
		
		if (produkList.length > 1) {
			produkList.forEach((produk, i) => {
				const rowIdx = idx + i;
				const html = `
					<div class="row g-2 sow-row align-items-end mb-2" data-idx="${rowIdx}">
						<div class="col-3">
							<label class="form-label">Total Konten</label>
							<input type="number" min="1" class="form-control" name="sow_rows[${rowIdx}][total]" value="${pref?.total || 1}">
						</div>
						<div class="col-5">
							<label class="form-label">Produk</label>
							<input type="text" class="form-control" name="sow_rows[${rowIdx}][produk]" 
									value="${escapeHtml(produk)}" placeholder="Nama produk...">
						</div>
						<div class="col-3">
							<div class="form-check" style="margin-bottom: 12px;">
								<input class="form-check-input" type="checkbox" name="sow_rows[${rowIdx}][jenis]" value="kerkun" ${pref?.jenis === 'kerkun' ? 'checked' : ''}>
								<label class="form-check-label">
									Keranjang Kuning
								</label>
							</div>
						</div>
						<div class="col-1 text-end">
							<button type="button" class="btn btn-outline-danger btn-remove-sow-row" style="height: 30px; padding: 0; margin-bottom: 12px;"><i class="bi bi-x"></i></button>
						</div>
					</div>`;
				$('#sow_rows').append(html);

				const $newRow = $('#sow_rows .sow-row').last();
				$newRow.find('input').on('input change', rebuildSowText);
				$newRow.find('.btn-remove-sow-row').on('click', function(){
					$(this).closest('.sow-row').remove();
					rebuildSowText();
				});
			});
		} else {
			const html = `
				<div class="row g-2 sow-row align-items-end mb-2" data-idx="${idx}">
					<div class="col-3">
						<label class="form-label">Total Konten</label>
						<input type="number" min="1" class="form-control" name="sow_rows[${idx}][total]" value="${pref?.total||1}">
					</div>
					<div class="col-5">
						<label class="form-label">Produk</label>
						<input type="text" class="form-control" name="sow_rows[${idx}][produk]" 
								value="${escapeHtml(pref?.produk || '')}" placeholder="Nama produk...">
					</div>
					<div class="col-3">
						<div class="form-check" style="margin-bottom: 12px;">
							<input class="form-check-input" type="checkbox" name="sow_rows[${idx}][jenis]" value="kerkun" ${pref?.jenis === 'kerkun' ? 'checked' : ''}>
							<label class="form-check-label">
								Keranjang Kuning
							</label>
						</div>
					</div>
					<div class="col-1 text-end">
						<button type="button" class="btn btn-outline-danger btn-remove-sow-row" style="height: 30px; padding: 0; margin-bottom: 12px;"><i class="bi bi-x"></i></button>
					</div>
				</div>`;
			$('#sow_rows').append(html);

			const $last = $('#sow_rows .sow-row').last();
			if (pref?.produk) $last.find('input[name$="[produk]"]').val(pref.produk);

			// event: setiap input berubah -> rebuild text & json
			$last.find('input').on('input change', rebuildSowText);
			$last.find('.btn-remove-sow-row').on('click', function(){
				$(this).closest('.sow-row').remove();
				rebuildSowText();
			});
		}

		rebuildSowText();
	}

	function serializeSowRows(){
		const rows = [];
		$('#sow_rows .sow-row').each(function(){
			const total  = parseInt($(this).find('input[name$="[total]"]').val()||'1', 10);
			const produk = ($(this).find('input[name$="[produk]"]').val() || '').trim();
			const jenis  = $(this).find('input[name$="[jenis]"]').is(':checked') ? 'kerkun' : 'non-kerkun';
			rows.push({ total, produk, jenis });
		});
		return rows;
	}

	function terbilang(n) {
		n = Math.floor(Math.abs(n));
		const b = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
		if (n < 12) return b[n] || "";
		if (n < 20) return terbilang(n - 10) + " belas";
		if (n < 100) return terbilang(n / 10) + " puluh " + terbilang(n % 10);
		if (n < 200) return "seratus " + terbilang(n - 100);
		if (n < 1000) return terbilang(n / 100) + " ratus " + terbilang(n % 100);
		return String(n); // fallback
	}

	function letterIdx(i){ return String.fromCharCode(97 + i); } // a,b,c,...

	function rebuildSowText(){
		const rows = serializeSowRows();
		const lines = [];

		// Ambil semua produk unik dari SOW rows untuk produk_kerjasama
		const produkList = [];
		rows.forEach(row => {
			if (row.produk && !produkList.includes(row.produk)) {
				produkList.push(row.produk);
			}
		});

		// Format produk kerjasama dengan "dan" untuk produk terakhir
		let produkKerjasamaText = '';
		if (produkList.length === 1) {
			produkKerjasamaText = produkList[0];
		} else if (produkList.length === 2) {
			produkKerjasamaText = produkList[0] + ' dan ' + produkList[1];
		} else if (produkList.length > 2) {
			// Gabungkan semua kecuali terakhir dengan koma, lalu tambahkan "dan" untuk yang terakhir
			const semuaKecualiTerakhir = produkList.slice(0, -1).join(', ');
			const produkTerakhir = produkList[produkList.length - 1];
			produkKerjasamaText = semuaKecualiTerakhir + ', dan ' + produkTerakhir;
		}

		// Isi otomatis produk_kerjasama
		$('#produk_kerjasama').val(produkKerjasamaText);

		rows.forEach((row, i) => {
			const label = (row.produk || 'Produk');
			const qty   = Math.max(1, parseInt(row.total||'1',10));
			const qtyTerbilang = terbilang(qty).trim() || String(qty);

			const keranjangText = (row.jenis === 'kerkun')
				? 'dengan Keranjang Kuning'
				: 'tanpa Keranjang Kuning';

			lines.push(
				`${letterIdx(i)}. ${qty} (${qtyTerbilang}) Content Review Video Tiktok produk ${label} ${keranjangText}`
			);
		});

		if ($('#is_ads').is(':checked')) {
			const i = rows.length; // baris berikutnya
			lines.push(`${letterIdx(i)}. Scanbarcode untuk ads`);
			$('#is_ads_hidden').val('1');
		} else {
			$('#is_ads_hidden').val('0');
		}

		$('#sow').val(lines.join('\n'));
		$('#sow_json').val(JSON.stringify(rows));
	}

	// tombol tambah baris
	$('#btn-add-sow-row').on('click', () => addSowRow());

	// toggle ads
	$('#is_ads').on('change', rebuildSowText);

	// === Progress Stepper ===
	const steps = [...document.querySelectorAll('.process-step')]
	const progress = {
		set(step, state='active', note='Sedang diproses') {
			steps.forEach((el,i)=>{
				const idx=i+1
				const icon=el.querySelector('.process-step-icon')
				const spin=icon?.querySelector('.spinner-border')
				const badge=el.querySelector('.process-step-status')

				el.classList.remove('active','done','error')
				if (idx<step) { 
					el.classList.add('done'); 
					if (badge) { badge.className='process-step-status badge bg-success'; badge.textContent='Selesai' }
					spin?.classList.add('d-none'); 
					return 
				}
				if (idx===step) {
					el.classList.add(state)
					if (state==='active') { 
						if (badge) { badge.className='process-step-status badge bg-info'; badge.textContent=note }
						spin?.classList.remove('d-none') 
					} else if (state==='error') { 
						if (badge) { badge.className='process-step-status badge bg-danger'; badge.textContent=note }
						spin?.classList.add('d-none') 
					} else if (state==='done') { 
						if (badge) { badge.className='process-step-status badge bg-success'; badge.textContent='Selesai' }
						spin?.classList.add('d-none') 
					}
				}
				if (idx>step) { 
					if (badge) { badge.className='process-step-status badge bg-secondary'; badge.textContent='Menunggu' }
					spin?.classList.add('d-none') 
				}
			})
		},
		done(step){ this.set(step,'done','Selesai') },
		error(step,msg='Gagal'){ this.set(step,'error',msg) }
	}

	// WA + Preview
	const aWA = document.getElementById('btn-wa')
	const aPV = document.getElementById('btn-preview')
	const previewSection = document.getElementById('preview-section')
	const enableLink = (a,url) => { a.href=url; a.classList.remove('disabled') }

	function buildWaUrl() {
		const phone = (qS('#phone').value || '').replace(/[^0-9]/g, '');
		const msg = encodeURIComponent(
			`Halo kak ${qS('#full_name').value}, MoU untuk kerja sama sudah kami kirim ke email (${qS('#email').value}). ` +
			`Silakan cek dan tandatangani dokumen MoU tersebut. Terima kasih.`
		);
		return phone ? `https://wa.me/${phone}?text=${msg}` : `https://wa.me/?text=${msg}`;
	}

	aWA.addEventListener('click', (e) => {
		if (aWA.classList.contains('disabled')) { e.preventDefault(); return }
		aWA.href = buildWaUrl()
	})

	// === Helper: FD builder ===
	function buildPdfFormData() {
		const ids = (document.getElementById('mou_item_ids').value || '')
			.split(',').map(s=>s.trim()).filter(Boolean)
		if (!ids.length) throw new Error('Pilih minimal 1 item')
		const fd = new FormData(form)
		fd.set('mou_item_ids', ids.join(','))
		return { fd, ids }
	}

	// === Helper: auto download ===
	function triggerDownload(url, filename = '') {
		const a = document.createElement('a')
		a.href = url
		if (filename) a.download = filename
		a.rel = 'noopener'
		a.target = '_blank'
		document.body.appendChild(a)
		a.click()
		document.body.removeChild(a)
	}

	// === Generate Only (tanpa kirim email) ===
	document.getElementById('btn-generate-only').addEventListener('click', async () => {
		const { ids } = buildPdfFormData()
		if (!ids.length) return Swal.fire('Oops','Pilih minimal 1 item MoU.','warning')

		// STEP 1: Save
		try {
			progress.set(1,'active','Menyimpan data')
			await stepSaveInfluencer()
			progress.done(1)
		} catch(e){ progress.error(1,e.message); return Swal.fire('Error',e.message,'error') }

		// STEP 2: Generate
		try {
			progress.set(2,'active','Membuat dokumen')
			const { fd } = buildPdfFormData()
			const r = await fetch("<?= base_url('endorse/action_generate_mou_pdf_gdocs') ?>", { method:'POST', body:fd })
			const j = await r.json()

			if (j?.status === 'redirect' && j?.redirect) {
				window.open(j.redirect, '_blank', 'noopener')
				throw new Error('Silakan login Google dulu, lalu klik Jalankan Proses lagi.')
			}
			if (!j?.success) throw new Error(j?.message || 'Gagal membuat dokumen')

			docId       = j.doc_id
			docUrl      = j.doc_url
			downloadUrl = j.download_url
			fileName    = (j.filename || 'MoU').replace(/\.gdoc$/i, '.pdf')

			// Tampilkan preview section
			previewSection.style.display = 'block'
			
			// Setup tombol preview dan WA
			aPV.href = docUrl
			aWA.href = buildWaUrl()
			
			// Auto download PDF
			triggerDownload(downloadUrl, fileName)
			
			progress.done(2)
			
			Swal.fire('Berhasil!','Dokumen MoU telah dibuat. Silakan preview sebelum mengirim email.','success')
		} catch(e) { 
			progress.error(2,e.message); 
			return Swal.fire('Error', e.message, 'error') 
		}
	})

	// === Kirim Email ===
	document.getElementById('btn-send-email').addEventListener('click', async () => {
		try {
			progress.set(3,'active','Mengirim email')
			await stepSendEmail(docId, docUrl)
			progress.done(3)
			
			Swal.fire('Sukses!','Email telah dikirim ke influencer.','success')
		} catch(e) {
			progress.error(3,e.message)
			return Swal.fire('Error', e.message, 'error')
		}
	})

	// === Step Functions ===
	async function stepSaveInfluencer() {
		splitRekening()
		const fd=new FormData(form)
		const r=await fetch("<?= base_url('endorse/action_save_influencer') ?>",{method:'POST',body:fd})
		const j=await r.json(); if(!j?.success) throw new Error(j?.message||'Gagal simpan data')
	}

	async function stepSendEmail(docId, docUrl) {
		const fd = new FormData(form)
		fd.set('doc_id', docId)
		fd.set('doc_url', docUrl)
		const r = await fetch("<?= base_url('googlemou/action_send_mou_email') ?>", { method:'POST', body:fd })
		const j = await r.json()
		if (!j?.success) throw new Error(j?.message || 'Gagal kirim email')
	}

	// ===== WIZARD NAV =====
	const sectionDataPribadi = document.getElementById('section-data-pribadi')
	const sectionKonten      = document.getElementById('section-konten')
	const sectionProses      = document.getElementById('section-proses')

	const btnNext1 = document.getElementById('btn-next-1')
	const btnPrev2 = document.getElementById('btn-prev-2')
	const btnNext2 = document.getElementById('btn-next-2')
	const btnPrev3 = document.getElementById('btn-prev-3')

	let currentStep = 1
	let itemsLoaded = false

	function showStep(step){
		currentStep = step
		sectionDataPribadi.style.display = (step===1)?'block':'none'
		sectionKonten.style.display      = (step===2)?'block':'none'
		sectionProses.style.display      = (step===3)?'block':'none'
		updateWizardProgress(step)
		
		// Reset preview section ketika kembali ke step 3
		if (step === 3) {
			previewSection.style.display = 'none'
		}
	}

	function isDataPribadiValid(){
		const nama  = (qS('#full_name')?.value||'').trim()
		const nik   = (qS('#nik')?.value||'').trim()
		const phone = (qS('#phone')?.value||'').trim()
		const email = (qS('#email')?.value||'').trim()
		if(!nama || !nik || !phone || !email) return false
		if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return false
		return true
	}

	function isKontenValid(){
		const ids = (document.getElementById('mou_item_ids').value||'')
			.split(',').map(s=>s.trim()).filter(Boolean)
		return ids.length>0
	}

	btnNext1.addEventListener('click', () => {
		if(!isDataPribadiValid()){
			Swal.fire('Lengkapi Dulu','Nama, NIK, No. Telp, dan Email wajib diisi dengan benar.','warning')
			return
		}
		if(!itemsLoaded){
			loadItems()
			itemsLoaded = true
		}
		showStep(2)
	})

	btnPrev2.addEventListener('click', () => {
		showStep(1)
	})

	btnNext2.addEventListener('click', () => {
		if(!isKontenValid()){
			Swal.fire('Pilih Item Dulu','Minimal 1 item MoU harus dipilih.','warning')
			return
		}
		showStep(3)
	})

	btnPrev3.addEventListener('click', () => {
		showStep(2)
	})

	showStep(1)

	$(function () {
		const $pembayaranAwal = $("#pembayaran_awal");
		const $wrapPersen = $("#wrap_persen_dp");

		$pembayaranAwal.on("change", function () {
			if ($(this).val() === "DP") {
				$wrapPersen.show();
			} else {
				$wrapPersen.hide();
			}
		}).trigger("change");
	});
	loadItems()
})()
</script>