<?php
$group_by = [
    'Rencana Upload' => 'rencana_at',
    'Tanggal Posting' => 'upload_at',
];

function build_url($base_url, $params = [])
{
    return $base_url . '?' . http_build_query($params);
}
?>

<div class="w-100">
    <style>
        .fc table {
            font-size: 0 !important;
        }

        .fc table tr th,
        .fc table tr td {
            padding: 0px !important;
            border-radius: 0 !important;
            background: transparent !important;
        }

        .fc table tr td:first-child,
        .fc table tr td:last-child,
        .fc table tr th:first-child,
        .fc table tr th:last-child {
            border-radius: 0 !important;
        }

        table tr td {
            background: #fff !important;
            padding: 0px 0px !important;
        }

        table tr:first-child th:last-child {
            padding: 0px 0px !important;
        }

        table tr:first-child td:first-child {
            padding: 0px 0px !important;
        }

        /* === Container Layout === */
        #calendar-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            flex: 2;
            position: relative;
        }

        #calendar {
            height: 600px !important;
            overflow: hidden;
        }

        /* === Event Details Summary Stats === */
        .event-details-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }

        .summary-stats {
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .summary-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            flex: 1;
            text-align: center;
        }

        .summary-item.rencana {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            border-left: 3px solid #F05A7E;
            border: 1px solid #F05A7E20;
        }

        .summary-item.berhasil {
            background: linear-gradient(135deg, #f5f0f5 0%, #e8d8e8 100%);
            border-left: 3px solid #640D5F;
            border: 1px solid #640D5F20;
        }

        .summary-label {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin: 0;
            line-height: 1.2;
        }

        .summary-count {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .summary-count.rencana {
            color: #F05A7E;
        }

        .summary-count.berhasil {
            color: #640D5F;
        }

        .fc-daygrid-day.selected-date {
            background-color: #fae0e9ff !important;
            /* pink agak tua */
            border: 2px solid #ffc8daff !important;
            /* pink tua untuk border */
        }


        #event-details {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: white;
            border-radius: 10px;
        }

        #event-details h4 {
            font-size: 1.3em;
            margin-bottom: 1px;
        }

        #event-details p {
            font-size: 0.9em;
            line-height: 1.4;
        }

        /* === Calendar Toolbar (Month/Year) === */
        .fc-toolbar {
            margin-bottom: 5px !important;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 0;
        }

        .fc-toolbar-title {
            font-size: 1.1em !important;
            margin: 0 15px !important;
            order: 2;
        }

        .fc-prev-button {
            order: 1;
            background-color: transparent !important;
            color: #333 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .fc-next-button {
            order: 3;
            background-color: transparent !important;
            color: #333 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .fc-button {
            padding: 5px 10px !important;
            font-size: 0.9em !important;
        }

        /* Light theme adjustments */
        .fc-theme-standard {
            background-color: white !important;
        }

        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid #e0e0e0 !important;
        }

        .fc-daygrid-day {
            background-color: white !important;
            border: 1px solid #e0e0e0 !important;
        }

        .fc-col-header-cell {
            background-color: #f5f5f5 !important;
        }

        .fc-daygrid-day-number {
            color: #333 !important;
        }

        /* === Day Header (Min-Sab) === */
        .fc-theme-standard .fc-col-header {
            height: 24px !important;
        }

        .fc-col-header-cell {
            padding: 0 !important;
            margin: 0 !important;
            height: 10px !important;
            vertical-align: top !important;
            border: none !important;
        }

        .fc-col-header-cell-cushion {
            all: unset;
            display: block;
            text-align: center;
            font-size: 12px;
            font-weight: normal;
            color: #000;
        }

        /* === Calendar Grid Cells === */
        th.fc-col-header-cell {
            padding: 0 !important;
            margin: 0 !important;
            height: 0px !important;
            line-height: 0 !important;
            vertical-align: middle !important;
            background-color: #fae0e9ff !important;
        }

        .fc-daygrid-day {
            height: 80px !important;
            padding: 1px !important;
            border: 1px solid #ddd !important;
            position: relative;
            box-sizing: border-box;
            cursor: pointer;
        }

        .fc-daygrid-day-frame {
            height: 100% !important;
            min-height: unset !important;
            padding: 0 !important;
        }

        .fc-daygrid-day-top {
            display: flex;
            flex-direction: row-reverse;
            justify-content: space-between;
            align-items: flex-start;
            padding: 2px 4px !important;
            margin-left: auto !important;
        }

        .fc-daygrid-day-number {
            font-size: 12px !important;
            font-weight: 500 !important;
            padding: 0 2px !important;
            margin: 0 !important;
            color: #000 !important;
            text-decoration: none !important;
        }

        .fc-daygrid-body-balanced .fc-daygrid-day-events {
            min-height: 0 !important;
        }

        .fc-daygrid-day:hover {
            background-color: #fce2ebff !important;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* === Event Count Badges in Cell === */
        .fc-daygrid-day .event-count-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2px;
            position: absolute;
            bottom: 2px;
            left: 2px;
            right: auto;
        }

        .fc-daygrid-day .event-count {
            padding: 1px 3px !important;
            font-size: 10px !important;
            border-radius: 1px;
        }

        /* === Grid Container Borders === */
        .fc-scrollgrid {
            border: 1px solid #ddd !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }

        /* === Responsive Adjustments === */
        @media (max-width: 768px) {
            .fc-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .fc-toolbar-chunk {
                justify-content: flex-start;
                width: 100%;
                margin-bottom: 3px;
            }

            .fc-toolbar-title {
                font-size: 14px !important;
            }

            .fc-button {
                font-size: 10px !important;
                padding: 2px 4px !important;
            }

            #calendar {
                height: 500px !important;
            }

            .event-details-summary {
                margin-bottom: 15px;
                padding: 15px;
            }

            .summary-stats {
                flex-direction: column;
                gap: 12px;
            }

            .summary-item {
                min-width: auto;
                padding: 12px 15px;
            }

            .summary-count {
                font-size: 18px;
            }
        }

        /* === Form Elements & Select2 === */
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

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: #495057;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            font-size: 14px;
            font-weight: 600;
        }
    </style>

    <div class="col-lg-12">
        <div class="bg-white p-3 rounded shadow-sm mb-3">
            <form action="" method="GET">
                <div class="col-md-12">
                    <?php
                    $arr = ["External", "Internal", "Semua Campaign"];
                    $current_campaign = $_GET['campaign'] ?? '';
                    $group_by = $_GET['group_by'] ?? [];

                    foreach ($arr as $k => $val) {
                        $class = "btn-outline-secondary";

                        $value = $val;
                        if ($k == 0) {
                            $value = '';
                        }
                        $value = str_replace('&', '', $value);

                        if ($current_campaign == $value) {
                            $class = "btn-primary";
                        }

                        $url_params = [
                            'group_by' => $group_by,
                            'campaign' => $value
                        ];
                        $url = build_url(base_url('calendar'), $url_params);
                    ?>
                        <a href="<?= $url ?>" class="btn <?= $class ?> btn-sm me-2 mb-2"><?= $val ?></a>
                    <?php } ?>

                </div>
                <div class="row g-2 align-items-center">
                    <!-- Filter Group By -->
                    <div class="col-md-4">
                        <label for="group_by" class="form-label medium">Tampilkan Berdasarkan</label>
                        <select class="form-control form-control-md select2" name="group_by[]" id="group_by" multiple>
                            <option value="rencana_at" <?= in_array('rencana_at', (array) $this->input->get('group_by')) ? 'selected' : '' ?>>Rencana Upload</option>
                            <option value="posting_at" <?= in_array('posting_at', (array) $this->input->get('group_by')) ? 'selected' : '' ?>>Tanggal Posting</option>
                        </select>
                    </div>
                    <!-- Filter Campaign -->
                    <div class="col-md-2">
                        <label for="campaign" class="form-label small">Campaign</label>
                        <select class="form-control form-control-sm select2" name="ids_campaign[]" id="campaign" multiple>
                            <?php
                            $ids = $_GET['ids_campaign'] ?? [];
                            foreach ($campaign as $val) :
                                $selected = in_array($val['id'], $ids) ? 'selected' : '';
                            ?>
                                <option <?= $selected ?> value="<?= $val["id"] ?>"><?= $val["title"] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Filter PIC -->
                    <div class="col-md-2">
                        <label for="pic" class="form-label small">PIC</label>
                        <input type="text" id="pic" name="pic" class="form-control form-control-sm" placeholder="Cari PIC.." value="<?= $this->input->get('pic') ?>">
                    </div>
                    <!-- Filter Product -->
                    <div class="col-md-2">
                        <label for="product" class="form-label small">Produk</label>
                        <select class="form-control form-control-sm select2" name="ids_product[]" id="product" multiple>
                            <?php
                            $ids = $_GET['ids_product'] ?? [];
                            foreach ($product as $val) :
                                $selected = in_array($val['id'], $ids) ? 'selected' : '';
                            ?>
                                <option <?= $selected ?> value="<?= $val["id"] ?>"><?= $val["name"] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2" style="align-items: center;">
                        <div class="d-flex gap-1">
                            <button class="btn btn-primary btn-sm flex-fill" type="submit">Cari</button>
                            <button class="btn btn-danger btn-sm flex-fill" type="button" onclick="clearFilter()">Reset</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row bg-white" style="height: auto; border-radius: 10px; margin-left: 1px; margin-right: 1px">
        <div class="col-lg-9" id="calendar-container">
            <div id="calendar" class="p-3" style="height: 650px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 10px; border: 2px solid #e9ecef;"></div>
        </div>
        <div class="col-lg-3 bg-white" id="event-details" style="padding: 20px; height: 700px; overflow-y: auto; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
            <div style="text-align: center;">
                <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Pilih tanggal terlebih dahulu</p>
                <p style="color: gray; margin-top: -10px; font-size: 16px;">Klik tanggal di kalender untuk melihat event.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var eventDetailsEl = document.getElementById('event-details');

            var initialView = window.innerWidth <= 500 ? 'timeGridWeek' : 'dayGridMonth';
            var events = <?php echo $events; ?>;

            (function injectHoverCss() {
                const styleEl = document.createElement('style');
                styleEl.textContent = `
                /* Sorot range saat hover (tanpa mengganggu selected-date) */
                .fc-daygrid-day.hover-preview {
                    background-color: #fae0e9ff !important;      /* pink muda */
                    outline: 2px dashed #fbcfdfff !important;    /* garis putus-putus pink */
                    outline-offset: -2px !important;
                }
                .fc-daygrid-day.selected-date.hover-preview {
                    box-shadow: inset 0 0 0 2px #fce2ebff !important; /* pink tegas untuk overlap */
                }
            `;
                document.head.appendChild(styleEl);
            })();



            // ===== Akumulasi event per tanggal =====
            var eventCountByDate = {};
            events.forEach(function(event) {
                var eventDate = new Date(event.start);
                if (!isNaN(eventDate)) {
                    var eventDateString = toYMD(eventDate);
                    if (!eventCountByDate[eventDateString]) {
                        eventCountByDate[eventDateString] = {
                            'RENCANA UPLOAD': 0,
                            'SUDAH UPLOAD': 0
                        };
                    }
                    if (event.extendedProps.type === 'RENCANA UPLOAD') {
                        eventCountByDate[eventDateString]['RENCANA UPLOAD']++;
                    } else if (event.extendedProps.type === 'SUDAH UPLOAD') {
                        eventCountByDate[eventDateString]['SUDAH UPLOAD']++;
                    }
                }
            });

            // ===== State lama =====
            var selectedDates = new Set();

            // ===== State untuk RANGE selection (klik pertama = anchor; klik-klik berikutnya = end baru) =====
            let rangeAnchor = null; // 'YYYY-MM-DD'
            let rangeEnd = null; // 'YYYY-MM-DD'

            // ===== Helpers umum =====
            function toYMD(d) {
                // normalisasi ke lokal tanpa offset TZ
                return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().split('T')[0];
            }

            function buildDateRange(startStr, endStr) {
                const dates = [];
                const start = new Date(startStr + 'T00:00:00');
                const end = new Date(endStr + 'T00:00:00');
                const a = start < end ? start : end;
                const b = start < end ? end : start;
                for (let d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
                    dates.push(toYMD(d));
                }
                return dates;
            }

            // ====== HOVER PREVIEW STATE & HELPERS ======
            let lastHoverStr = null; // 'YYYY-MM-DD' terakhir yang dipreview

            function clearHoverPreview() {
                document.querySelectorAll('.fc-daygrid-day.hover-preview').forEach((cell) => {
                    cell.classList.remove('hover-preview');
                });
                lastHoverStr = null;
            }

            function updateHoverPreview(hoverStr) {
                if (!rangeAnchor) return;
                if (lastHoverStr === hoverStr) return;

                clearHoverPreview();

                // Sorot semua tanggal dari anchor → hover (inklusif)
                const previewDates = buildDateRange(rangeAnchor, hoverStr);
                previewDates.forEach((d) => {
                    const cell = document.querySelector('.fc-daygrid-day[data-date="' + d + '"]');
                    if (cell) cell.classList.add('hover-preview');
                });

                lastHoverStr = hoverStr;
            }


            function getStatusStyle(status) {
                status = (status || '').toLowerCase();
                switch (status) {
                    case 'acc':
                        return {
                            bg: '#1DCD9F', color: '#000'
                        };
                    case 'draft content':
                        return {
                            bg: '#d4edbc', color: '#000'
                        };
                    case 'posted content':
                        return {
                            bg: '#7bd3ea', color: '#000'
                        };
                    default:
                        return {
                            bg: '#e6e6e6', color: '#000'
                        };
                }
            }

            // === Helper: hitung total pada rentang tanggal (inklusif) ===
            function computeTotalsInRange(rangeStart, rangeEnd) {
                let totalRencana = 0;
                let totalBerhasil = 0;
                const startStr = toYMD(rangeStart);
                const endStr = toYMD(rangeEnd);
                Object.keys(eventCountByDate).forEach(function(d) {
                    if (d >= startStr && d <= endStr) {
                        totalRencana += (eventCountByDate[d]['RENCANA UPLOAD'] || 0);
                        totalBerhasil += (eventCountByDate[d]['SUDAH UPLOAD'] || 0);
                    }
                });
                return {
                    totalRencana,
                    totalBerhasil
                };
            }

            // === Helper: ambil rentang bulan dari view aktif (pakai arg dari datesSet jika ada) ===
            function getCurrentMonthRange(arg) {
                if (arg && arg.start && arg.end) {
                    const center = new Date(arg.start.getFullYear(), arg.start.getMonth(), 15);
                    const start = new Date(center.getFullYear(), center.getMonth(), 1);
                    const end = new Date(center.getFullYear(), center.getMonth() + 1, 0);
                    return {
                        start,
                        end
                    };
                } else {
                    const d = calendar.getDate();
                    const start = new Date(d.getFullYear(), d.getMonth(), 1);
                    const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
                    return {
                        start,
                        end
                    };
                }
            }

            // === Get Summary Stats for selected dates or current month ===
            function getSummaryStats(argForMonth) {
                if (selectedDates.size > 0) {
                    let totalRencana = 0;
                    let totalBerhasil = 0;
                    selectedDates.forEach(function(dateString) {
                        if (eventCountByDate[dateString]) {
                            totalRencana += eventCountByDate[dateString]['RENCANA UPLOAD'] || 0;
                            totalBerhasil += eventCountByDate[dateString]['SUDAH UPLOAD'] || 0;
                        }
                    });
                    return {
                        totalRencana,
                        totalBerhasil,
                        isSelection: true
                    };
                } else {
                    const {
                        start,
                        end
                    } = getCurrentMonthRange(argForMonth);
                    const totals = computeTotalsInRange(start, end);
                    return {
                        totalRencana: totals.totalRencana,
                        totalBerhasil: totals.totalBerhasil,
                        isSelection: false
                    };
                }
            }

            function updateSelectedDatesVisual() {
                document.querySelectorAll('.fc-daygrid-day.selected-date').forEach(function(cell) {
                    cell.classList.remove('selected-date');
                });
                selectedDates.forEach(function(dateString) {
                    var cell = document.querySelector('.fc-daygrid-day[data-date="' + dateString + '"]');
                    if (cell) cell.classList.add('selected-date');
                });
            }

            function renderEventDetailsPlaceholder() {
                const stats = getSummaryStats();
                eventDetailsEl.innerHTML = `
                <div>
                    <h6 class="mb-3">Ringkasan Bulan Ini</h6>
                    <div class="event-details-summary">
                        <div class="summary-stats">
                            <div class="summary-item rencana">
                                <div class="summary-text">
                                    <p class="summary-label">Rencana Upload</p>
                                    <p class="summary-count rencana">${stats.totalRencana}</p>
                                </div>
                            </div>
                            <div class="summary-item berhasil">
                                <div class="summary-text">
                                    <p class="summary-label">Berhasil Upload</p>
                                    <p class="summary-count berhasil">${stats.totalBerhasil}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 20px; max-width: 200px;">
                    <p style="font-weight: 600; margin-top: 10px; font-size: 20px;">Pilih tanggal terlebih dahulu</p>
                    <p style="color: gray; margin-top: -10px; font-size: 14px;">Klik tanggal di kalender untuk melihat detail event.</p>
                </div>
            `;
            }

            // === Render detail untuk tanggal terpilih (range atau multi tanggal) ===
            function renderEventDetailsForSelection() {
                if (selectedDates.size === 0) {
                    renderEventDetailsPlaceholder();
                    return;
                }

                const stats = getSummaryStats();
                eventDetailsEl.innerHTML = `
                <div class="d-flex justify-content-between align-items-center px-2">
                    <h6 class="mb-0" style="margin-left: 4px">Detail Endorse</h6>
                    <h6 class="mb-0" style="margin-right: 4px">${selectedDates.size} tanggal dipilih</h6>
                </div>
                <div class="event-details-summary">
                    <div class="summary-stats">
                        <div class="summary-item rencana">
                            <div class="summary-text">
                                <p class="summary-label">Rencana Upload</p>
                                <p class="summary-count rencana">${stats.totalRencana}</p>
                            </div>
                        </div>
                        <div class="summary-item berhasil">
                            <div class="summary-text">
                                <p class="summary-label">Berhasil Upload</p>
                                <p class="summary-count berhasil">${stats.totalBerhasil}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                let allSelectedEvents = [];
                selectedDates.forEach(function(dateString) {
                    const selectedDateEvents = events.filter(function(event) {
                        const eventDate = new Date(event.start);
                        return !isNaN(eventDate) && toYMD(eventDate) === dateString;
                    });
                    allSelectedEvents = allSelectedEvents.concat(selectedDateEvents);
                });

                if (allSelectedEvents.length === 0) {
                    eventDetailsEl.innerHTML += `
                    <div style="text-align: center;">
                        <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                        <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Oops... Data Kosong</p>
                        <p style="color: gray; margin-top: -10px; font-size: 16px;">Tidak ada postingan pada tanggal yang dipilih</p>
                    </div>
                `;
                    return;
                }

                const eventsByDate = {};
                allSelectedEvents.forEach(function(event) {
                    const dateString = toYMD(new Date(event.start));
                    if (!eventsByDate[dateString]) eventsByDate[dateString] = [];
                    eventsByDate[dateString].push(event);
                });

                Object.keys(eventsByDate).sort().forEach(function(dateString) {
                    const dateHeader = document.createElement('div');
                    dateHeader.style.cssText = 'background-color: #f8f9fa; padding: 8px; margin: 10px 0 5px 0; border-radius: 5px; font-weight: 600; font-size: 13px; text-align: center; color: #495057;';
                    dateHeader.textContent = new Date(dateString + 'T00:00:00').toLocaleDateString('id-ID', {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    eventDetailsEl.appendChild(dateHeader);

                    eventsByDate[dateString].forEach(function(event) {
                        const eventItem = document.createElement('div');
                        eventItem.classList.add('event-item');

                        const borderColor = (event.extendedProps.type === 'RENCANA UPLOAD') ? '#F05A7E' : '#640D5F';

                        let platformLogo = '';
                        if (event.extendedProps.platform === 'Tiktok') {
                            platformLogo = '<img src="<?= base_url() ?>assets/img/marketplace/3.png" alt="Tiktok Logo" class="rounded-circle mb-1" style="width: 30px;">';
                        } else if (event.extendedProps.platform === 'Instagram') {
                            platformLogo = '<img src="<?= base_url() ?>assets/img/marketplace/7.png" alt="Instagram Logo" class="rounded-circle mb-1" style="width: 30px;">';
                        }

                        const status = (event.extendedProps.type === 'RENCANA UPLOAD') ?
                            (event.extendedProps.status || '') :
                            event.extendedProps.type;

                        const style = (event.extendedProps.type === 'RENCANA UPLOAD') ?
                            getStatusStyle(status) : {
                                bg: '#60B5FF',
                                color: '#fff'
                            };

                        eventItem.innerHTML = `
                        <a href="<?= base_url() ?>endorse/detail?id=${event.extendedProps.id}" class="text-dark" style="text-decoration: none; display: block; width: 100%; height: 14vh; padding: 10px;">
                            <div style="border-left: 4px solid ${borderColor}; border-top: 1px solid #E8D8E8; border-bottom: 1px solid #E8D8E8; border-right: 1px solid #E8D8E8; position: relative; padding: 10px; background-color: #fff; border-radius: 8px;">
                                <ul style="list-style-type: none; margin: 0; padding: 0;">
                                    <li style="margin-bottom: 2px;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="font-size: 14px; font-weight: 500;">${event.title}</span>
                                            <p class="mb-0 br-10 fs-12 text-white"
                                            style="background-color:${style.bg}; color:${style.color};
                                                    font-size:12px; padding:2px 8px; border-radius:6px;">
                                            ${(status || '').toString().toUpperCase()}
                                            </p>
                                        </div>
                                    </li>
                                    <li style="font-weight: 400; font-size: 12px; margin-bottom: 4px; color: gray;">${event.extendedProps.pic || ''}</li>
                                    <li style="margin-bottom: 2px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: 400; font-size: 12px;">
                                                ${event.extendedProps.campaign || ''}
                                            </span>
                                            <span>${platformLogo}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </a>
                    `;
                        eventDetailsEl.appendChild(eventItem);
                    });
                });
            }

            // ======= CLEAR FILTER: reset range & kembali ke summary bulanan =======
            window.clearFilter = function() {
                selectedDates.clear();
                rangeAnchor = null;
                rangeEnd = null;
                clearHoverPreview();

                updateSelectedDatesVisual();
                renderEventDetailsPlaceholder();
            };

            // ======= FullCalendar =======
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: initialView,
                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                locale: 'id',
                height: 'auto',
                contentHeight: 500,
                events: '',

                datesSet: function(arg) {
                    var allDayCells = document.querySelectorAll('.fc-daygrid-day');
                    allDayCells.forEach(function(cell) {
                        var dateString = cell.getAttribute('data-date');

                        if (eventCountByDate[dateString]) {
                            var rencanaUploadCount = eventCountByDate[dateString]['RENCANA UPLOAD'];
                            var sudahUploadCount = eventCountByDate[dateString]['SUDAH UPLOAD'];

                            var eventCountWrapper = cell.querySelector('.event-count-wrapper');
                            if (eventCountWrapper) eventCountWrapper.remove();

                            if (rencanaUploadCount > 0 || sudahUploadCount > 0) {
                                var eventCountDiv = document.createElement('div');
                                eventCountDiv.classList.add('event-count-wrapper');

                                var dayFrame = cell.querySelector('.fc-daygrid-day-frame');
                                (dayFrame || cell).appendChild(eventCountDiv);

                                if (rencanaUploadCount > 0) {
                                    eventCountDiv.innerHTML += `<div class="event-count" style="background-color: #EBEBEB; border-left: 4px solid #F05A7E; color: #F05A7E;">${rencanaUploadCount} Rencana</div>`;
                                }
                                if (sudahUploadCount > 0) {
                                    eventCountDiv.innerHTML += `<div class="event-count" style="background-color: #E8D8E8; border-left: 4px solid #640D5F; color: #640D5F;">${sudahUploadCount} Berhasil</div>`;
                                }
                            }
                        }
                    });

                    updateSelectedDatesVisual();
                    // Summary stats handled by renderEventDetailsPlaceholder();
                },

                // ======= RANGE PICK SEKALI KLIK =======
                dateClick: function(info) {
                    const clicked = info.dateStr;

                    if (!rangeAnchor) {
                        rangeAnchor = clicked;
                        rangeEnd = clicked;
                    } else {
                        rangeEnd = clicked;
                    }

                    selectedDates.clear();
                    buildDateRange(rangeAnchor, rangeEnd).forEach(d => selectedDates.add(d));

                    updateSelectedDatesVisual();
                    clearHoverPreview();
                    renderEventDetailsForSelection();
                }
            });

            calendar.render();

            // === Styling setelah render ===
            setTimeout(function() {
                document.querySelectorAll('a').forEach(function(link) {
                    link.style.color = '';
                    link.style.textDecoration = '';
                });
                var tableElement = document.querySelector('table.fc-scrollgrid');
                if (tableElement) {
                    tableElement.style.border = "1px solid #ddd";
                    tableElement.style.borderRadius = "10px";
                }
            }, 500);

            calendarEl.addEventListener('mouseover', function(e) {
                const cell = e.target.closest('.fc-daygrid-day');
                if (!cell) return;
                if (!rangeAnchor) return;
                const dateStr = cell.getAttribute('data-date');
                if (!dateStr) return;
                updateHoverPreview(dateStr);
            });

            calendarEl.addEventListener('mouseleave', function() {
                clearHoverPreview();
            });


            new ResizeObserver(() => {
                calendar.updateSize();
            }).observe(calendarEl);

            renderEventDetailsPlaceholder();
        });
    </script>


</div>