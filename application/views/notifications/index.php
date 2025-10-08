<style>
    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-warning {
        background-color: #faad14;
        border-color: #faad14;
    }

    .btn-warning:hover {
        background-color: #ffc53d;
        border-color: #ffc53d;
    }

    .btn-danger {
        background-color: #ff4d4f;
        border-color: #ff4d4f;
    }

    .btn-danger:hover {
        background-color: #ff7875;
        border-color: #ff7875;
    }

    /* Notification Item Styling */
    .notification-item-page {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 16px;
        margin-bottom: 8px;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
        background: #fff;
        transition: all 0.3s ease;
    }

    .notification-item-page:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.09);
    }

    .notification-item-page.unread {
        border-left: 3px solid #1890ff;
        background: #f6fbff;
    }

    .notification-content {
        flex: 1;
        padding-right: 16px;
    }

    .notification-header-page {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .notification-type {
        margin-right: 10px;
        font-size: 16px;
    }

    .type-info { color: #1890ff; }
    .type-success { color: #52c41a; }
    .type-warning { color: #faad14; }
    .type-danger { color: #ff4d4f; }

    .notification-title-page {
        font-weight: 500;
        font-size: 14px;
        flex: 1;
        margin-right: 10px;
        color: rgba(0, 0, 0, 0.85);
    }

    .notification-time-page {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
        white-space: nowrap;
    }

    .notification-message-page {
        color: rgba(0, 0, 0, 0.65);
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .notification-actions {
        margin-top: 8px;
    }

    .notification-controls {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Empty State Styling */
    .empty-state {
        padding: 40px 0;
        text-align: center;
    }

    .empty-state-icon {
        font-size: 48px;
        color: rgba(0, 0, 0, 0.25);
        margin-bottom: 16px;
    }

    .empty-state-title {
        font-size: 16px;
        color: rgba(0, 0, 0, 0.45);
        margin-bottom: 8px;
    }

    .empty-state-description {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.45);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
        margin-bottom: 16px;
        border: 1px solid transparent;
    }

    .alert-success {
        background-color: #f6ffed;
        border-color: #b7eb8f;
        color: #52c41a;
    }

    .alert-error {
        background-color: #fff2f0;
        border-color: #ffccc7;
        color: #ff4d4f;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Notifikasi</h5>
                <div>
                    <button class="btn btn-warning" onclick="markAllAsRead()" style="margin-right: 8px;">
                        <i class="bi bi-check-all"></i> Tandai Semua Dibaca
                    </button>
                    <button class="btn btn-danger" onclick="clearReadNotifications()">
                        <i class="bi bi-trash"></i> Hapus yang Sudah Dibaca
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Flash Messages -->
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= $this->session->flashdata('success') ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle"></i> <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <h4 class="empty-state-title">Tidak ada notifikasi</h4>
                    <p class="empty-state-description">Semua notifikasi akan muncul di sini</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item-page <?= $notification['is_read'] == '0' ? 'unread' : '' ?>" data-id="<?= $notification['id'] ?>">
                            <div class="notification-content">
                                <div class="notification-header-page">
                                    <span class="notification-type type-<?= $notification['type'] ?>">
                                        <?php
                                        $icons = [
                                            'info' => 'bi-info-circle',
                                            'success' => 'bi-check-circle',
                                            'warning' => 'bi-exclamation-triangle',
                                            'danger' => 'bi-x-circle'
                                        ];
                                        $icon = $icons[$notification['type']] ?? 'bi-info-circle';
                                        ?>
                                        <i class="bi <?= $icon ?>"></i>
                                    </span>
                                    <span class="notification-title-page"><?= htmlspecialchars($notification['title']) ?></span>
                                    <span class="notification-time-page"><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></span>
                                </div>
                                <div class="notification-message-page">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </div>
                                <?php if ($notification['related_table'] && $notification['related_id']): ?>
                                    <div class="notification-actions">
                                        <?php if ($notification['related_table'] == 'endorse'): ?>
                                            <a href="<?= base_url('endorse/edit/' . $notification['related_id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Lihat Endorse
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="notification-controls">
                                <?php if ($notification['is_read'] == '0'): ?>
                                    <button class="btn btn-sm btn-success" onclick="markAsRead(<?= $notification['id'] ?>)" title="Tandai sudah dibaca">
                                        <i class="bi bi-check"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteNotification(<?= $notification['id'] ?>)" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <?= $pagination ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    Swal.fire({
        title: 'Yakin?',
        text: 'Tandai notifikasi ini sebagai sudah dibaca?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= base_url("notifications/mark_read") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`[data-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        const markButton = item.querySelector('.btn-success');
                        if (markButton) {
                            markButton.style.display = 'none';
                        }
                    }
                    Swal.fire('Berhasil!', 'Notifikasi ditandai sebagai dibaca.', 'success');
                } else {
                    Swal.fire('Gagal!', 'Gagal menandai notifikasi sebagai dibaca.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
            });
        }
    });
}

function markAllAsRead() {
    Swal.fire({
        title: 'Yakin?',
        text: 'Tandai semua notifikasi sebagai sudah dibaca?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= base_url("notifications/mark_all_read") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', 'Semua notifikasi sudah dibaca.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', 'Gagal menandai semua notifikasi.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
            });
        }
    });
}

function clearReadNotifications() {
    Swal.fire({
        title: 'Yakin?',
        text: 'Hapus semua notifikasi yang sudah dibaca?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= base_url("notifications/clear_read") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', 'Notifikasi terbaca sudah dihapus.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', 'Gagal menghapus notifikasi.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
            });
        }
    });
}

function deleteNotification(notificationId) {
    Swal.fire({
        title: 'Yakin?',
        text: 'Hapus notifikasi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= base_url("notifications/delete/") ?>' + notificationId;
        }
    });
}

</script>