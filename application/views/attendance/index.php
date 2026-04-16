<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Attendance Confirmation</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
            <div style="display:inline-flex; border:1px solid #d9d9d9; border-radius:2px; overflow:hidden;">
                <button type="button" class="attendance-tab-btn" data-tab="regular" style="background-color:#1890ff; color:#fff; border:none; height:36px; padding:0 16px; font-size:14px;">
                    Kantor
                </button>
                <button type="button" class="attendance-tab-btn" data-tab="out-of-town" style="background-color:#fff; color:rgba(0,0,0,0.65); border:none; border-left:1px solid #d9d9d9; height:36px; padding:0 16px; font-size:14px;">
                    Dinas Luar Kota
                </button>
            </div>
            <button id="view-logs-btn" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                <i class="bi bi-clock-history"></i> View Logs
            </button>
        </div>

        <div id="attendance-tab-regular">
            <p style="font-size: 14px; color: rgba(0,0,0,0.65); margin-bottom: 16px;">Confirm your attendance only when you are at the office location.</p>

            <div style="margin-bottom: 16px;">
                <button id="confirm-in" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                    <i class="bi bi-box-arrow-in-right"></i> Confirm IN
                </button>
                <button id="confirm-out" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-box-arrow-left"></i> Confirm OUT
                </button>
            </div>

            <div id="geo-status" style="margin-bottom: 12px; font-size: 14px;"></div>
            <div id="status-panel" style="border: 1px solid #d9d9d9; border-radius: 2px; padding: 16px; margin-bottom: 16px; background-color: #fafafa;">
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Current Location:</strong> <span id="user-location" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Office Target:</strong> <span id="office-location" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Distance:</strong> <span id="distance-info" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Status:</strong> <span id="status-flags" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Schedule:</strong> <span id="schedule-info" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Guidance:</strong> <span id="status-guidance" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Eligibility:</strong> <span id="status-eligibility" style="color: rgba(0,0,0,0.65);">-</span></div>
            </div>
        </div>

        <div id="attendance-tab-out-of-town" style="display:none;">
            <p style="font-size: 14px; color: rgba(0,0,0,0.65); margin-bottom: 16px;">Use this tab for Dinas Luar Kota attendance. GPS is still recorded for audit, office radius and Wi-Fi checks are skipped, and a new photo is required for each IN and OUT.</p>

            <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                <div style="flex:1 1 320px; border:1px solid #d9d9d9; border-radius:2px; padding:16px; background-color:#fafafa;">
                    <div style="margin-bottom:8px; font-size:14px;"><strong style="color: rgba(0,0,0,0.85);">Mode:</strong> <span style="color: rgba(0,0,0,0.65);">Dinas Luar Kota</span></div>
                    <div style="margin-bottom:8px; font-size:14px;"><strong style="color: rgba(0,0,0,0.85);">Photo Status:</strong> <span id="out-of-town-photo-status" style="color: rgba(0,0,0,0.65);">No photo uploaded.</span></div>
                    <div style="margin-bottom:8px; font-size:14px;"><strong style="color: rgba(0,0,0,0.85);">Current Location:</strong> <span id="out-of-town-location" style="color: rgba(0,0,0,0.65);">-</span></div>
                    <div style="font-size:14px;"><strong style="color: rgba(0,0,0,0.85);">Upload:</strong> <span id="out-of-town-upload-info" style="color: rgba(0,0,0,0.65);">-</span></div>
                </div>
                <div style="flex:0 0 240px; border:1px dashed #d9d9d9; border-radius:2px; padding:12px; background-color:#fff; min-height:220px; display:flex; align-items:center; justify-content:center; text-align:center;">
                    <div id="out-of-town-preview-placeholder" style="font-size:13px; color:rgba(0,0,0,0.45); line-height:1.6;">
                        Photo preview will appear here after you take a picture.
                    </div>
                    <img id="out-of-town-photo-preview" alt="Attendance proof preview" style="display:none; width:100%; height:auto; max-height:220px; object-fit:cover; border-radius:2px;">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <input type="file" id="out-of-town-photo-input" accept="image/jpeg,image/png,image/*" capture="environment" style="display:none;">
                <button id="out-of-town-photo-btn" class="btn btn-primary" style="background-color:#1890ff; border-color:#1890ff; height:32px; padding:4px 15px; border-radius:2px; font-size:14px; margin-right:8px;">
                    <i class="bi bi-camera"></i> Take Photo
                </button>
                <button id="out-of-town-retake-btn" class="btn btn-outline-secondary" disabled style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right:8px;">
                    <i class="bi bi-arrow-repeat"></i> Retake Photo
                </button>
                <button id="out-of-town-confirm-in" class="btn btn-primary" style="background-color:#52c41a; border-color:#52c41a; height:32px; padding:4px 15px; border-radius:2px; font-size:14px; margin-right:8px;">
                    <i class="bi bi-box-arrow-in-right"></i> Confirm IN
                </button>
                <button id="out-of-town-confirm-out" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-box-arrow-left"></i> Confirm OUT
                </button>
            </div>

            <div id="out-of-town-status" style="margin-bottom: 12px; font-size: 14px; color:#faad14;">Take a photo before confirming Dinas Luar Kota attendance.</div>
        </div>

        <div id="api-response" style="font-size: 14px;"></div>

        <div id="logs-panel" style="display:none; margin-top: 16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <input type="month" id="logs-month" value="" style="height:32px; padding:4px 11px; border:1px solid #d9d9d9; border-radius:2px; font-size:14px;">
                <button id="logs-load-btn" class="btn btn-primary" style="background-color:#1890ff; border-color:#1890ff; height:32px; padding:4px 15px; border-radius:2px; font-size:14px;">
                    <i class="bi bi-search"></i> Load
                </button>
            </div>
            <div id="logs-table-wrap"></div>
        </div>
    </div>
</div>

<script>
    (function () {
        var attendanceApi = {
            statusUrl: "<?php echo site_url('api/hrms/attendance/status'); ?>",
            checkInUrl: "<?php echo site_url('api/hrms/attendance/check-in'); ?>",
            checkOutUrl: "<?php echo site_url('api/hrms/attendance/check-out'); ?>",
            outOfTownCheckInUrl: "<?php echo site_url('api/hrms/attendance/out-of-town/check-in'); ?>",
            outOfTownCheckOutUrl: "<?php echo site_url('api/hrms/attendance/out-of-town/check-out'); ?>",
            historyUrl: "<?php echo site_url('api/hrms/attendance/history'); ?>",
            reasonBaseUrl: "<?php echo site_url('api/hrms/attendance'); ?>",
            uploadUrl: "<?php echo site_url('api/hrms/upload'); ?>"
        };
        var csrfName = "<?php echo isset($csrf_name) ? $csrf_name : ''; ?>";
        var csrfHash = "<?php echo isset($csrf_hash) ? $csrf_hash : ''; ?>";
        var confirmInBtn = document.getElementById('confirm-in');
        var confirmOutBtn = document.getElementById('confirm-out');
        var outOfTownConfirmInBtn = document.getElementById('out-of-town-confirm-in');
        var outOfTownConfirmOutBtn = document.getElementById('out-of-town-confirm-out');
        var geoStatus = document.getElementById('geo-status');
        var outOfTownStatus = document.getElementById('out-of-town-status');
        var apiResponse = document.getElementById('api-response');
        var userLocationEl = document.getElementById('user-location');
        var officeLocationEl = document.getElementById('office-location');
        var distanceInfoEl = document.getElementById('distance-info');
        var statusFlagsEl = document.getElementById('status-flags');
        var scheduleInfoEl = document.getElementById('schedule-info');
        var statusGuidanceEl = document.getElementById('status-guidance');
        var statusEligibilityEl = document.getElementById('status-eligibility');
        var outOfTownPhotoInput = document.getElementById('out-of-town-photo-input');
        var outOfTownPhotoBtn = document.getElementById('out-of-town-photo-btn');
        var outOfTownRetakeBtn = document.getElementById('out-of-town-retake-btn');
        var outOfTownPhotoStatusEl = document.getElementById('out-of-town-photo-status');
        var outOfTownUploadInfoEl = document.getElementById('out-of-town-upload-info');
        var outOfTownLocationEl = document.getElementById('out-of-town-location');
        var outOfTownPhotoPreview = document.getElementById('out-of-town-photo-preview');
        var outOfTownPreviewPlaceholder = document.getElementById('out-of-town-preview-placeholder');
        var tabButtons = document.querySelectorAll('.attendance-tab-btn');
        var tabRegular = document.getElementById('attendance-tab-regular');
        var tabOutOfTown = document.getElementById('attendance-tab-out-of-town');
        var todayDone = { IN: false, OUT: false };
        var currentLogs = [];
        var activeTab = 'regular';
        var searchParams = new URLSearchParams(window.location.search || '');
        var outOfTownState = {
            uploading: false,
            photo: null,
            previewUrl: null
        };

        function setStatus(message, isError) {
            geoStatus.textContent = message;
            geoStatus.style.color = isError ? '#ff4d4f' : '#52c41a';
        }

        function setOutOfTownStatus(message, isError) {
            outOfTownStatus.textContent = message;
            outOfTownStatus.style.color = isError ? '#ff4d4f' : '#52c41a';
        }

        function setResponse(message, isError) {
            apiResponse.textContent = message;
            apiResponse.style.color = isError ? '#ff4d4f' : '#52c41a';
        }

        function toggleActionButtons(disabled) {
            confirmInBtn.disabled = disabled;
            confirmOutBtn.disabled = disabled;
            outOfTownConfirmInBtn.disabled = disabled;
            outOfTownConfirmOutBtn.disabled = disabled;
            outOfTownPhotoBtn.disabled = disabled || outOfTownState.uploading;
            outOfTownRetakeBtn.disabled = disabled || outOfTownState.uploading || (!outOfTownState.previewUrl && !(outOfTownState.photo && outOfTownState.photo.storedPath));
        }

        function applyRegularButtonState() {
            confirmInBtn.disabled = todayDone.IN;
            confirmOutBtn.disabled = todayDone.OUT;
        }

        function applyOutOfTownButtonState() {
            var hasPhoto = !!(outOfTownState.photo && outOfTownState.photo.storedPath);
            outOfTownConfirmInBtn.disabled = todayDone.IN || outOfTownState.uploading || !hasPhoto;
            outOfTownConfirmOutBtn.disabled = todayDone.OUT || outOfTownState.uploading || !hasPhoto;
        }

        function applyOutOfTownPhotoButtonState() {
            outOfTownPhotoBtn.disabled = outOfTownState.uploading;
            outOfTownRetakeBtn.disabled = outOfTownState.uploading || (!outOfTownState.previewUrl && !(outOfTownState.photo && outOfTownState.photo.storedPath));
        }

        function applyButtonState() {
            applyRegularButtonState();
            applyOutOfTownButtonState();
            applyOutOfTownPhotoButtonState();
        }

        function resetTodayDone() {
            todayDone = { IN: false, OUT: false };
        }

        function setActiveTab(tabName) {
            activeTab = tabName === 'out-of-town' ? 'out-of-town' : 'regular';
            tabRegular.style.display = activeTab === 'regular' ? 'block' : 'none';
            tabOutOfTown.style.display = activeTab === 'out-of-town' ? 'block' : 'none';

            Array.prototype.forEach.call(tabButtons, function (button) {
                var isActive = button.getAttribute('data-tab') === activeTab;
                button.style.backgroundColor = isActive ? '#1890ff' : '#fff';
                button.style.color = isActive ? '#fff' : 'rgba(0,0,0,0.65)';
            });
        }

        function loadTodayStatus() {
            var month = (new Date()).toISOString().slice(0, 7);
            var today = (new Date()).toISOString().slice(0, 10);
            apiFetch(attendanceApi.historyUrl + '?month=' + encodeURIComponent(month))
                .then(function (data) {
                    resetTodayDone();
                    if (!data || !Array.isArray(data.data)) {
                        return;
                    }

                    data.data.forEach(function (row) {
                        if (row.created_at && row.created_at.slice(0, 10) === today) {
                            if (row.type === 'IN') {
                                todayDone.IN = true;
                            }
                            if (row.type === 'OUT') {
                                todayDone.OUT = true;
                            }
                        }
                    });

                    applyButtonState();
                })
                .catch(function () {});
        }

        function apiFetch(url, options) {
            var requestOptions = options ? Object.assign({}, options) : {};
            var headers = Object.assign({ 'Accept': 'application/json' }, requestOptions.headers || {});
            requestOptions.headers = headers;
            requestOptions.credentials = 'same-origin';

            if (csrfName && csrfHash && !(requestOptions.body instanceof FormData)) {
                headers['X-CSRF-Token'] = csrfHash;
            }

            return fetch(url, requestOptions).then(function (response) {
                return response.text().then(function (text) {
                    var data = {};
                    if (text) {
                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            data = { message: 'Invalid server response.' };
                        }
                    }

                    if (!response.ok) {
                        var message = data && data.message ? data.message : 'Request failed.';
                        var err = new Error(message);
                        err.response = response;
                        err.data = data;
                        throw err;
                    }

                    return data;
                });
            });
        }

        function requestLocation() {
            return new Promise(function (resolve, reject) {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation is not supported by this browser.'));
                    return;
                }

                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
            });
        }

        function parseCsvList(value) {
            if (!value) {
                return [];
            }

            return String(value)
                .split(/[\r\n,]+/)
                .map(function (item) { return item.trim(); })
                .filter(function (item) { return item !== ''; });
        }

        function normalizeWifiProof(value) {
            if (!value) {
                return null;
            }

            if (typeof value === 'string') {
                var trimmed = value.trim();
                return trimmed === '' ? null : trimmed;
            }

            if (typeof value !== 'object') {
                return null;
            }

            var result = {};
            if (value.bssid && String(value.bssid).trim() !== '') {
                result.bssid = String(value.bssid).trim();
            }
            if (value.ssid && String(value.ssid).trim() !== '') {
                result.ssid = String(value.ssid).trim();
            }
            if (Array.isArray(value.bssids)) {
                var bssids = value.bssids
                    .map(function (item) { return String(item).trim(); })
                    .filter(function (item) { return item !== ''; });
                if (bssids.length) {
                    result.bssids = bssids;
                }
            }
            if (Array.isArray(value.ssids)) {
                var ssids = value.ssids
                    .map(function (item) { return String(item).trim(); })
                    .filter(function (item) { return item !== ''; });
                if (ssids.length) {
                    result.ssids = ssids;
                }
            }

            return Object.keys(result).length ? result : null;
        }

        function getWifiProof() {
            if (typeof window.getAttendanceWifiProof === 'function') {
                try {
                    return normalizeWifiProof(window.getAttendanceWifiProof());
                } catch (error) {}
            }

            if (typeof window.AttendanceWifiProof !== 'undefined') {
                return normalizeWifiProof(window.AttendanceWifiProof);
            }

            if (searchParams.has('wifi_proof')) {
                var wifiProofRaw = searchParams.get('wifi_proof');
                try {
                    return normalizeWifiProof(JSON.parse(wifiProofRaw));
                } catch (error) {
                    return normalizeWifiProof(wifiProofRaw);
                }
            }

            var bssid = searchParams.get('bssid');
            var ssid = searchParams.get('ssid');
            var bssids = parseCsvList(searchParams.get('bssids'));
            var ssids = parseCsvList(searchParams.get('ssids'));
            var proof = {};

            if (bssid && bssid.trim() !== '') {
                proof.bssid = bssid.trim();
            }
            if (ssid && ssid.trim() !== '') {
                proof.ssid = ssid.trim();
            }
            if (bssids.length) {
                proof.bssids = bssids;
            }
            if (ssids.length) {
                proof.ssids = ssids;
            }

            return Object.keys(proof).length ? proof : null;
        }

        function appendWifiProofToQuery(url, wifiProof) {
            if (!wifiProof) {
                return url;
            }

            if (typeof wifiProof === 'string') {
                return url + '&bssid=' + encodeURIComponent(wifiProof);
            }

            var query = url;
            if (wifiProof.bssid) {
                query += '&bssid=' + encodeURIComponent(wifiProof.bssid);
            }
            if (wifiProof.ssid) {
                query += '&ssid=' + encodeURIComponent(wifiProof.ssid);
            }
            if (Array.isArray(wifiProof.bssids) && wifiProof.bssids.length) {
                query += '&bssids=' + encodeURIComponent(wifiProof.bssids.join(','));
            }
            if (Array.isArray(wifiProof.ssids) && wifiProof.ssids.length) {
                query += '&ssids=' + encodeURIComponent(wifiProof.ssids.join(','));
            }

            return query;
        }

        function postAttendance(type, position) {
            var payload = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                gpsAccuracy: position.coords.accuracy
            };
            var wifiProof = getWifiProof();
            if (wifiProof) {
                payload.wifiProof = wifiProof;
            }

            return apiFetch(type === 'IN' ? attendanceApi.checkInUrl : attendanceApi.checkOutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
        }

        function postOutOfTownAttendance(type, position) {
            return apiFetch(type === 'IN' ? attendanceApi.outOfTownCheckInUrl : attendanceApi.outOfTownCheckOutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    gpsAccuracy: position.coords.accuracy,
                    attachment_path: outOfTownState.photo ? outOfTownState.photo.storedPath : ''
                })
            });
        }

        function submitAttendanceReason(attendanceLogId, reason, file) {
            var url = attendanceApi.reasonBaseUrl + '/' + encodeURIComponent(attendanceLogId) + '/reason';

            if (file) {
                var formData = new FormData();
                if (reason !== null && typeof reason !== 'undefined') {
                    formData.append('reason', reason);
                }
                formData.append('attachment', file);
                if (csrfName && csrfHash) {
                    formData.append(csrfName, csrfHash);
                }

                return apiFetch(url, {
                    method: 'POST',
                    body: formData
                });
            }

            return apiFetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason: reason || '' })
            });
        }

        function formatDistance(meters) {
            var value = Number(meters);
            if (Number.isNaN(value)) {
                return '-';
            }
            if (value >= 1000) {
                return (value / 1000).toFixed(2) + ' km';
            }
            return Math.round(value) + ' m';
        }

        function formatFileSize(bytes) {
            var value = Number(bytes || 0);
            if (!value) {
                return '-';
            }
            if (value >= 1024 * 1024) {
                return (value / (1024 * 1024)).toFixed(2) + ' MB';
            }
            return Math.round(value / 1024) + ' KB';
        }

        function parseHHMM(hhmm) {
            var parts = String(hhmm).split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function buildScheduleNoteHtml(type, schedule) {
            if (!schedule) {
                return '';
            }

            var now = new Date();
            var nowMinutes = now.getHours() * 60 + now.getMinutes();
            if (type === 'IN') {
                var lateMinutes = parseHHMM(schedule.late_threshold);
                if (nowMinutes > lateMinutes) {
                    var lateDiff = nowMinutes - parseHHMM(schedule.start_time);
                    return '<div style="background-color:#fff2f0;border:1px solid #ffccc7;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                        '<span style="color:#ff4d4f;">&#9888; Late check-in</span> &mdash; ' + lateDiff + ' min after start time.' +
                        '</div>';
                }

                return '<div style="background-color:#f6ffed;border:1px solid #b7eb8f;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                    '<span style="color:#52c41a;">&#10003; On time</span> &mdash; grace period ends at ' + schedule.late_threshold + '.' +
                    '</div>';
            }

            var earlyMinutes = parseHHMM(schedule.early_threshold);
            if (nowMinutes < earlyMinutes) {
                var earlyDiff = parseHHMM(schedule.end_time) - nowMinutes;
                return '<div style="background-color:#fffbe6;border:1px solid #ffe58f;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                    '<span style="color:#faad14;">&#9888; Early checkout</span> &mdash; ' + earlyDiff + ' min before end time.' +
                    '</div>';
            }

            return '<div style="background-color:#f6ffed;border:1px solid #b7eb8f;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                '<span style="color:#52c41a;">&#10003; Normal checkout</span> &mdash; end time is ' + schedule.end_time + '.' +
                '</div>';
        }

        function buildConfirmHtml(type, statusResult) {
            var now = new Date();
            var timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            var office = statusResult.office;
            var computed = statusResult.computed;
            var sch = statusResult.schedule || null;
            var scheduleHtml = '';

            if (sch) {
                var scheduleBadge = sch.is_special
                    ? '<span style="background-color:#fff1f0;color:#cf1322;border:1px solid #ffa39e;padding:0 6px;border-radius:2px;font-size:12px;margin-right:5px;">Special</span>'
                    : '<span style="background-color:#f6ffed;color:#389e0d;border:1px solid #b7eb8f;padding:0 6px;border-radius:2px;font-size:12px;margin-right:5px;">Default</span>';

                scheduleHtml = '<div><strong>Schedule:</strong> ' + scheduleBadge + sch.start_time + ' \u2013 ' + sch.end_time + '</div>' +
                    buildScheduleNoteHtml(type, sch);
            }

            return '<div style="text-align:left;font-size:14px;line-height:2.2;">' +
                '<div><strong>Current Time:</strong> ' + timeStr + '</div>' +
                '<div><strong>Office:</strong> ' + escapeHtml(office.name) + '</div>' +
                '<div><strong>Distance:</strong> ' + formatDistance(computed.distance_m) + ' from office center</div>' +
                scheduleHtml +
                '<div style="margin-top:6px;color:#52c41a;"><strong>&#10003; Your location matches the office</strong></div>' +
                '</div>';
        }

        function buildOutOfTownConfirmHtml(type, position) {
            var now = new Date();
            var timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            return '<div style="text-align:left;font-size:14px;line-height:2.1;">' +
                '<div><strong>Current Time:</strong> ' + timeStr + '</div>' +
                '<div><strong>Attendance Mode:</strong> Dinas Luar Kota</div>' +
                '<div><strong>Latitude:</strong> ' + escapeHtml(position.coords.latitude) + '</div>' +
                '<div><strong>Longitude:</strong> ' + escapeHtml(position.coords.longitude) + '</div>' +
                '<div><strong>GPS Accuracy:</strong> ' + escapeHtml(Math.round(position.coords.accuracy)) + ' m</div>' +
                '<div><strong>Photo:</strong> Uploaded and ready</div>' +
                '<div style="margin-top:6px;color:#d46b08;"><strong>&#9888; A new photo will be required for the next action.</strong></div>' +
                '</div>';
        }

        function buildRejectionMessage(data) {
            if (!data) {
                return 'Attendance confirmation requirements were not met.';
            }

            var reasons = Array.isArray(data.reasons)
                ? data.reasons
                : (data.computed && Array.isArray(data.computed.reasons) ? data.computed.reasons : []);

            if (data.message && !reasons.length) {
                return data.message;
            }

            var labels = reasons.map(function (reason) {
                switch (reason) {
                    case 'OUTSIDE_RADIUS':
                        return 'You are outside the office radius.';
                    case 'GPS_ACCURACY_LOW':
                        return 'GPS accuracy is too low.';
                    case 'WIFI_REQUIRED':
                        return 'Office Wi-Fi proof is required.';
                    case 'WIFI_NOT_ALLOWED':
                        return 'You are not connected to an allowed office Wi-Fi.';
                    case 'IP_NOT_ALLOWED':
                        return 'Your network is not allowed for attendance.';
                    default:
                        return reason;
                }
            });

            return labels.length ? labels.join(' ') : (data.message || 'Attendance confirmation requirements were not met.');
        }

        function buildReasonPromptHtml(result) {
            var kind = result.flags && result.flags.late ? 'late check-in' : 'early checkout';
            var minutes = result.flags && result.flags.late
                ? result.minutes && result.minutes.late
                : result.minutes && result.minutes.early_checkout;
            var detail = minutes ? ' (' + minutes + ' min)' : '';

            return '<div style="text-align:left;font-size:14px;line-height:1.8;">' +
                '<div>Your attendance has been recorded successfully.</div>' +
                '<div style="margin-top:8px;">This record is marked as <strong>' + escapeHtml(kind) + '</strong>' + escapeHtml(detail) + '.</div>' +
                '<div style="margin-top:8px;">You can optionally add a reason now. Regular attendance can also include a supporting attachment.</div>' +
                '</div>';
        }

        function openReasonModal(logRow, options) {
            var row = logRow || {};
            var title = options && options.title ? options.title : 'Add reason / attachment';
            var confirmButtonText = options && options.confirmButtonText ? options.confirmButtonText : 'Save details';
            var existingReason = row.attendance_reason || row.reason || '';
            var existingAttachment = row.attachmentPath || '';
            var isOutOfTown = !!row.isOutOfTown || row.attendanceCategory === 'OUT_OF_TOWN';
            var allowAttachment = !isOutOfTown;
            var existingHtml = '';

            if (existingAttachment) {
                existingHtml += '<div style="margin-top:8px;font-size:12px;color:rgba(0,0,0,0.65);">' +
                    (isOutOfTown ? 'Current proof photo: ' : 'Current attachment: ') +
                    '<a href="' + escapeAttribute(existingAttachment) + '" target="_blank" rel="noopener noreferrer">View file</a>' +
                    '</div>';
            }

            return Swal.fire({
                title: title,
                html: '' +
                    '<div style="text-align:left;">' +
                        '<label for="attendance-reason-input" style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;">Reason (optional)</label>' +
                        '<textarea id="attendance-reason-input" class="swal2-textarea" style="display:block;width:100%;min-height:120px;margin:0;" placeholder="Example: business trip, client visit, emergency, traffic, etc.">' + escapeHtml(existingReason) + '</textarea>' +
                        (allowAttachment
                            ? '<label for="attendance-attachment-input" style="display:block;font-size:13px;font-weight:500;margin-top:12px;margin-bottom:6px;">Attachment (optional)</label>' +
                              '<input id="attendance-attachment-input" type="file" accept=".pdf,.jpg,.jpeg,.png,image/jpeg,image/png,application/pdf" class="swal2-file" style="display:block;width:100%;margin:0;">' +
                              '<div style="margin-top:8px;font-size:12px;color:rgba(0,0,0,0.45);">Accepted formats: PDF, JPG, JPEG, PNG.</div>'
                            : '<div style="margin-top:12px;font-size:12px;color:rgba(0,0,0,0.45);">Proof photo is locked for Dinas Luar Kota attendance and cannot be replaced.</div>') +
                        existingHtml +
                    '</div>',
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Close',
                confirmButtonColor: '#1890ff',
                cancelButtonColor: '#d9d9d9',
                focusConfirm: false,
                preConfirm: function () {
                    var reasonInput = Swal.getPopup().querySelector('#attendance-reason-input');
                    var fileInput = Swal.getPopup().querySelector('#attendance-attachment-input');
                    var reason = reasonInput ? reasonInput.value.trim() : '';
                    var file = allowAttachment && fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

                    if (!reason && !file && !existingReason && (!existingAttachment || isOutOfTown)) {
                        Swal.showValidationMessage('Add a reason, or close this dialog.');
                        return false;
                    }

                    if (!reason && !file && !existingReason && !existingAttachment) {
                        Swal.showValidationMessage('Add a reason or attachment, or close this dialog.');
                        return false;
                    }

                    return submitAttendanceReason(row.id || row.attendance_log_id, reason, file)
                        .catch(function (error) {
                            Swal.showValidationMessage(error && error.message ? error.message : 'Failed to save details.');
                            return false;
                        });
                }
            }).then(function (result) {
                if (!result.isConfirmed || !result.value) {
                    return null;
                }

                setResponse('Reason or attachment saved.', false);
                loadLogs();
                return Swal.fire({
                    title: 'Saved',
                    text: 'The attendance record has been updated.',
                    icon: 'success',
                    confirmButtonColor: '#1890ff'
                });
            });
        }

        function maybePromptOptionalReason(result) {
            if (!result || !result.flags || (!result.flags.late && !result.flags.early_checkout)) {
                return Promise.resolve();
            }

            return Swal.fire({
                title: 'Attendance saved',
                html: buildReasonPromptHtml(result),
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Add reason / attachment',
                cancelButtonText: 'Done',
                confirmButtonColor: '#1890ff',
                cancelButtonColor: '#d9d9d9'
            }).then(function (modalResult) {
                if (!modalResult.isConfirmed) {
                    return null;
                }

                return openReasonModal({
                    id: result.attendance_log_id,
                    type: result.type,
                    attendanceCategory: result.attendanceCategory || 'REGULAR',
                    isOutOfTown: result.attendanceCategory === 'OUT_OF_TOWN',
                    attachmentPath: result.attachmentPath || ''
                }, {
                    title: 'Add reason / attachment',
                    confirmButtonText: 'Save details'
                });
            });
        }

        function buildGuidance(computed, office) {
            if (!computed.inside_radius) {
                return 'Move closer within ' + office.radius_m + 'm of the office.';
            }
            return 'You are good to confirm attendance.';
        }

        function renderStatus(result) {
            if (!result || !result.computed) {
                statusGuidanceEl.textContent = 'Unable to load status.';
                return;
            }

            var office = result.office;
            var user = result.user;
            var computed = result.computed;

            userLocationEl.textContent = 'lat ' + user.lat + ', lng ' + user.lng + ' (accuracy ' + user.accuracy + 'm)';
            officeLocationEl.textContent = office.name + ' | lat ' + office.lat + ', lng ' + office.lng +
                ' | radius ' + office.radius_m + 'm | min accuracy ' + office.min_accuracy_m + 'm';
            distanceInfoEl.textContent = formatDistance(computed.distance_m);
            statusFlagsEl.textContent = computed.inside_radius ? 'Inside radius' : 'Outside radius';

            if (result.schedule) {
                var sch = result.schedule;
                var schText = sch.start_time + ' \u2013 ' + sch.end_time +
                    ' (late after ' + sch.late_threshold + ' | early checkout before ' + sch.early_threshold + ')';
                if (sch.is_special) {
                    scheduleInfoEl.innerHTML = '<span style="background-color:#fff1f0;color:#cf1322;border:1px solid #ffa39e;padding:0 6px;height:20px;display:inline-flex;align-items:center;border-radius:2px;font-size:12px;margin-right:5px;">Special</span>' + schText;
                } else {
                    scheduleInfoEl.textContent = schText;
                }
            }

            statusGuidanceEl.textContent = buildGuidance(computed, office);
            if (!computed.can_confirm) {
                statusGuidanceEl.textContent = buildRejectionMessage(result);
            }
            statusEligibilityEl.textContent = computed.can_confirm ? 'You can confirm attendance.' : 'You cannot confirm attendance.';
            statusEligibilityEl.style.color = computed.can_confirm ? '#52c41a' : '#ff4d4f';
        }

        function fetchStatus(position) {
            var url = attendanceApi.statusUrl +
                '?lat=' + encodeURIComponent(position.coords.latitude) +
                '&lng=' + encodeURIComponent(position.coords.longitude) +
                '&accuracy=' + encodeURIComponent(position.coords.accuracy);
            url = appendWifiProofToQuery(url, getWifiProof());

            return apiFetch(url)
                .then(function (data) {
                    renderStatus(data);
                    return data;
                })
                .catch(function (error) {
                    statusGuidanceEl.textContent = error && error.message ? error.message : 'Unable to load status.';
                    statusGuidanceEl.style.color = '#ff4d4f';
                    throw error;
                });
        }

        function loadStatus() {
            setStatus('Checking your location status...', false);
            requestLocation()
                .then(function (position) {
                    return fetchStatus(position);
                })
                .then(function (data) {
                    if (data && data.computed) {
                        setStatus('Status updated.', false);
                    } else {
                        setStatus('Unable to evaluate status.', true);
                    }
                })
                .catch(function (error) {
                    var message = error && error.message ? error.message : 'Unable to get location.';
                    setStatus(message, true);
                    statusGuidanceEl.textContent = 'Please allow location access to see status.';
                    statusGuidanceEl.style.color = '#ff4d4f';
                });
        }

        function handleConfirm(type) {
            setStatus('Requesting GPS location...', false);
            setResponse('', false);
            toggleActionButtons(true);

            requestLocation()
                .then(function (position) {
                    setStatus('Location acquired. Checking status...', false);
                    return fetchStatus(position).then(function (statusResult) {
                        if (!statusResult || !statusResult.computed) {
                            throw new Error('Unable to evaluate status.');
                        }
                        if (!statusResult.computed.can_confirm) {
                            setResponse(buildRejectionMessage(statusResult), true);
                            return null;
                        }
                        setStatus('Status OK. Please confirm...', false);
                        return Swal.fire({
                            title: 'Confirm Clock ' + type + '?',
                            html: buildConfirmHtml(type, statusResult),
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Clock ' + type,
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#1890ff',
                            cancelButtonColor: '#d9d9d9',
                            reverseButtons: true
                        }).then(function (result) {
                            if (!result.isConfirmed) {
                                setResponse('', false);
                                return null;
                            }
                            setStatus('Sending confirmation...', false);
                            return postAttendance(type, position);
                        });
                    });
                })
                .then(function (result) {
                    if (!result) {
                        return;
                    }
                    var successMessage = 'Attendance saved for ' + result.type + ' at ' + formatDistance(result.distanceMeters) + '.';
                    if (Array.isArray(result.notes) && result.notes.length) {
                        successMessage += ' ' + result.notes.join(' ');
                    }
                    setResponse(successMessage, false);
                    todayDone[type] = true;
                    applyButtonState();
                    loadLogs();
                    loadTodayStatus();
                    return maybePromptOptionalReason(result);
                })
                .catch(function (error) {
                    var message = error && error.message ? error.message : 'Unable to get location.';
                    if (message === 'Unable to get location.') {
                        setStatus(message, true);
                        setResponse('Please allow location access and try again.', true);
                        return;
                    }
                    setStatus('Unable to complete attendance.', true);
                    setResponse(message, true);
                })
                .finally(function () {
                    applyButtonState();
                });
        }

        function updateOutOfTownLocation(position) {
            outOfTownLocationEl.textContent = 'lat ' + position.coords.latitude + ', lng ' + position.coords.longitude + ' (accuracy ' + Math.round(position.coords.accuracy) + 'm)';
        }

        function clearOutOfTownPreview() {
            if (outOfTownState.previewUrl) {
                URL.revokeObjectURL(outOfTownState.previewUrl);
                outOfTownState.previewUrl = null;
            }
            outOfTownPhotoPreview.style.display = 'none';
            outOfTownPhotoPreview.removeAttribute('src');
            outOfTownPreviewPlaceholder.style.display = 'block';
        }

        function setOutOfTownPreview(file) {
            clearOutOfTownPreview();
            outOfTownState.previewUrl = URL.createObjectURL(file);
            outOfTownPhotoPreview.src = outOfTownState.previewUrl;
            outOfTownPhotoPreview.style.display = 'block';
            outOfTownPreviewPlaceholder.style.display = 'none';
        }

        function resetOutOfTownPhoto(preserveStatus) {
            outOfTownState.uploading = false;
            outOfTownState.photo = null;
            clearOutOfTownPreview();
            outOfTownPhotoInput.value = '';
            outOfTownPhotoStatusEl.textContent = 'No photo uploaded.';
            outOfTownUploadInfoEl.textContent = '-';
            if (!preserveStatus) {
                setOutOfTownStatus('Take a photo before confirming Dinas Luar Kota attendance.', false);
            }
            applyButtonState();
        }

        function validateOutOfTownFile(file) {
            if (!file) {
                return 'Photo file is required.';
            }
            if (file.type && file.type.indexOf('image/') !== 0) {
                return 'Attendance proof must be an image file.';
            }
            return '';
        }

        function uploadOutOfTownPhoto(file) {
            var validationMessage = validateOutOfTownFile(file);
            if (validationMessage) {
                setOutOfTownStatus(validationMessage, true);
                return Promise.reject(new Error(validationMessage));
            }

            outOfTownState.uploading = true;
            outOfTownState.photo = null;
            outOfTownPhotoStatusEl.textContent = 'Uploading photo...';
            outOfTownUploadInfoEl.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
            setOutOfTownStatus('Uploading photo proof...', false);
            applyButtonState();

            var formData = new FormData();
            formData.append('type', 'attendance');
            formData.append('file', file, file.name || ('attendance-' + Date.now() + '.jpg'));
            if (csrfName && csrfHash) {
                formData.append(csrfName, csrfHash);
            }

            return apiFetch(attendanceApi.uploadUrl, {
                method: 'POST',
                body: formData
            }).then(function (result) {
                if (!result || !result.storedPath) {
                    throw new Error('Upload did not return a stored photo path.');
                }

                outOfTownState.photo = result;
                outOfTownPhotoStatusEl.textContent = 'Photo uploaded and ready.';
                outOfTownUploadInfoEl.textContent = (result.originalName || result.filename || file.name) + ' (' + formatFileSize(result.sizeBytes || file.size) + ')';
                outOfTownRetakeBtn.disabled = false;
                setOutOfTownStatus('Photo uploaded. GPS will be captured when you confirm attendance.', false);
                return result;
            }).catch(function (error) {
                outOfTownState.photo = null;
                outOfTownPhotoStatusEl.textContent = 'Upload failed.';
                setOutOfTownStatus(error && error.message ? error.message : 'Failed to upload attendance photo.', true);
                throw error;
            }).finally(function () {
                outOfTownState.uploading = false;
                applyButtonState();
            });
        }

        function handleOutOfTownPhotoSelection() {
            var file = outOfTownPhotoInput.files && outOfTownPhotoInput.files[0] ? outOfTownPhotoInput.files[0] : null;
            if (!file) {
                return;
            }

            setOutOfTownPreview(file);
            outOfTownPhotoStatusEl.textContent = 'Photo selected. Uploading...';
            uploadOutOfTownPhoto(file).catch(function () {});
        }

        function handleOutOfTownConfirm(type) {
            if (!outOfTownState.photo || !outOfTownState.photo.storedPath) {
                setOutOfTownStatus('Take and upload a photo before confirming attendance.', true);
                return;
            }

            setResponse('', false);
            setOutOfTownStatus('Requesting GPS location...', false);
            toggleActionButtons(true);

            requestLocation()
                .then(function (position) {
                    updateOutOfTownLocation(position);
                    setOutOfTownStatus('Location acquired. Please confirm...', false);
                    return Swal.fire({
                        title: 'Confirm Dinas Luar Kota ' + type + '?',
                        html: buildOutOfTownConfirmHtml(type, position),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Confirm ' + type,
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#1890ff',
                        cancelButtonColor: '#d9d9d9',
                        reverseButtons: true
                    }).then(function (modalResult) {
                        if (!modalResult.isConfirmed) {
                            return null;
                        }

                        setOutOfTownStatus('Sending confirmation...', false);
                        return postOutOfTownAttendance(type, position);
                    });
                })
                .then(function (result) {
                    if (!result) {
                        return;
                    }

                    var successMessage = 'Dinas Luar Kota attendance saved for ' + result.type + '.';
                    if (Array.isArray(result.notes) && result.notes.length) {
                        successMessage += ' ' + result.notes.join(' ');
                    }

                    setResponse(successMessage, false);
                    todayDone[type] = true;
                    resetOutOfTownPhoto(true);
                    setOutOfTownStatus('Attendance saved. Take a new photo for the next action.', false);
                    loadLogs();
                    loadTodayStatus();
                    return maybePromptOptionalReason(result);
                })
                .catch(function (error) {
                    var message = error && error.message ? error.message : 'Unable to get location.';
                    if (message === 'Unable to get location.') {
                        setOutOfTownStatus(message, true);
                        setResponse('Please allow location access and try again.', true);
                        return;
                    }
                    setOutOfTownStatus('Unable to complete Dinas Luar Kota attendance.', true);
                    setResponse(message, true);
                })
                .finally(function () {
                    applyButtonState();
                });
        }

        confirmInBtn.addEventListener('click', function () {
            handleConfirm('IN');
        });

        confirmOutBtn.addEventListener('click', function () {
            handleConfirm('OUT');
        });

        outOfTownConfirmInBtn.addEventListener('click', function () {
            handleOutOfTownConfirm('IN');
        });

        outOfTownConfirmOutBtn.addEventListener('click', function () {
            handleOutOfTownConfirm('OUT');
        });

        outOfTownPhotoBtn.addEventListener('click', function () {
            if (outOfTownState.uploading) {
                return;
            }
            outOfTownPhotoInput.value = '';
            outOfTownPhotoInput.click();
        });

        outOfTownRetakeBtn.addEventListener('click', function () {
            if (outOfTownState.uploading) {
                return;
            }
            resetOutOfTownPhoto(true);
            outOfTownPhotoInput.click();
        });

        outOfTownPhotoInput.addEventListener('change', handleOutOfTownPhotoSelection);

        Array.prototype.forEach.call(tabButtons, function (button) {
            button.addEventListener('click', function () {
                setActiveTab(button.getAttribute('data-tab'));
            });
        });

        var logsPanel = document.getElementById('logs-panel');
        var logsMonthEl = document.getElementById('logs-month');
        var logsLoadBtn = document.getElementById('logs-load-btn');
        var logsTableWrap = document.getElementById('logs-table-wrap');
        var viewLogsBtn = document.getElementById('view-logs-btn');

        logsMonthEl.value = (new Date()).toISOString().slice(0, 7);

        viewLogsBtn.addEventListener('click', function () {
            if (logsPanel.style.display === 'none') {
                logsPanel.style.display = 'block';
                loadLogs();
            } else {
                logsPanel.style.display = 'none';
            }
        });

        logsLoadBtn.addEventListener('click', function () {
            loadLogs();
        });

        function loadLogs() {
            var month = logsMonthEl.value || (new Date()).toISOString().slice(0, 7);
            logsTableWrap.innerHTML = '<p style="font-size:14px;color:rgba(0,0,0,0.45);">Loading...</p>';

            apiFetch(attendanceApi.historyUrl + '?month=' + encodeURIComponent(month))
                .then(function (data) {
                    if (!data.data || data.data.length === 0) {
                        currentLogs = [];
                        logsTableWrap.innerHTML = '<p style="font-size:14px;color:rgba(0,0,0,0.45);">No records found for this month.</p>';
                        return;
                    }
                    currentLogs = data.data;
                    logsTableWrap.innerHTML = buildLogsTable(data.data);
                    bindReasonButtons();
                })
                .catch(function (error) {
                    logsTableWrap.innerHTML = '<p style="color:#ff4d4f;font-size:14px;">' + escapeHtml(error && error.message ? error.message : 'Failed to load logs.') + '</p>';
                });
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escapeAttribute(value) {
            return escapeHtml(value);
        }

        function renderReasonStatus(row) {
            if (!row.reasonEligible) {
                return '<span style="color:rgba(0,0,0,0.45);">-</span>';
            }

            if (row.attendance_reason && !row.isOutOfTown && row.attachmentPath) {
                return '<span style="color:#389e0d;">Reason + attachment added</span>';
            }
            if (row.attendance_reason) {
                return '<span style="color:#389e0d;">Reason added</span>';
            }
            if (!row.isOutOfTown && row.attachmentPath) {
                return '<span style="color:#389e0d;">Attachment added</span>';
            }

            return '<span style="color:#faad14;">No reason provided</span>';
        }

        function renderFlags(row) {
            var badges = [];
            if (row.flags && row.flags.late) {
                badges.push('<span style="display:inline-flex;align-items:center;height:22px;padding:0 8px;border-radius:11px;background:#fff2f0;color:#cf1322;border:1px solid #ffccc7;font-size:12px;margin-right:4px;">Late</span>');
            }
            if (row.flags && row.flags.early_checkout) {
                badges.push('<span style="display:inline-flex;align-items:center;height:22px;padding:0 8px;border-radius:11px;background:#fffbe6;color:#d48806;border:1px solid #ffe58f;font-size:12px;">Early checkout</span>');
            }

            return badges.length ? badges.join('') : '<span style="color:rgba(0,0,0,0.45);">On time</span>';
        }

        function renderAttachment(row) {
            if (!row.attachmentPath) {
                return '<span style="color:rgba(0,0,0,0.45);">-</span>';
            }

            return '<a href="' + escapeAttribute(row.attachmentPath) + '" target="_blank" rel="noopener noreferrer">View</a>';
        }

        function renderAttendanceMode(row) {
            if (row.isOutOfTown) {
                return '<span style="background:#fff7e6;color:#d46b08;border:1px solid #ffd591;padding:2px 8px;border-radius:2px;font-size:12px;">Dinas Luar Kota</span>';
            }

            return '<span style="background:#f6ffed;color:#389e0d;border:1px solid #b7eb8f;padding:2px 8px;border-radius:2px;font-size:12px;">Kantor</span>';
        }

        function reasonActionLabel(row) {
            if (!row.reasonEligible) {
                return '';
            }

            return (row.attendance_reason || (!row.isOutOfTown && row.attachmentPath)) ? 'Edit' : 'Add';
        }

        function bindReasonButtons() {
            Array.prototype.forEach.call(document.querySelectorAll('.attendance-reason-btn'), function (button) {
                button.addEventListener('click', function () {
                    var attendanceId = parseInt(button.getAttribute('data-id'), 10);
                    var row = currentLogs.find(function (item) {
                        return Number(item.id) === attendanceId;
                    });

                    if (!row) {
                        return;
                    }

                    openReasonModal(row, {
                        title: (reasonActionLabel(row) === 'Edit' ? 'Edit' : 'Add') + ' reason / attachment',
                        confirmButtonText: 'Save details'
                    });
                });
            });
        }

        function buildLogsTable(rows) {
            var typeColors = { IN: '#52c41a', OUT: '#fa8c16' };
            var html = '<div class="table-responsive"><table style="width:100%;border-collapse:collapse;font-size:13px;">' +
                '<thead><tr style="background:#fafafa;">' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">#</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Date</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Time</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Type</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Mode</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Office</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Distance</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Notes</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Flags</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Reason</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Attachment</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Action</th>' +
                '</tr></thead><tbody>';

            rows.forEach(function (row, i) {
                var dt = new Date(row.created_at);
                var dateStr = dt.toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                var timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                var typeColor = typeColors[row.type] || '#1890ff';
                var badge = '<span style="background:' + typeColor + ';color:#fff;padding:2px 8px;border-radius:2px;font-size:12px;">' + escapeHtml(row.type) + '</span>';
                var notes = Array.isArray(row.notes) && row.notes.length ? escapeHtml(row.notes.join(', ')) : '-';
                var dist = row.distance_m !== null ? Math.round(row.distance_m) + ' m' : '-';
                var office = escapeHtml(row.office_name || '-');
                var actionLabel = reasonActionLabel(row);
                var actionHtml = row.reasonEligible
                    ? '<button type="button" class="btn btn-outline-secondary attendance-reason-btn" data-id="' + escapeAttribute(row.id) + '" style="height:28px;padding:0 10px;border-radius:2px;font-size:12px;">' + actionLabel + '</button>'
                    : '<span style="color:rgba(0,0,0,0.45);">-</span>';
                var reasonText = row.attendance_reason ? escapeHtml(row.attendance_reason) : renderReasonStatus(row);

                html += '<tr style="border-bottom:1px solid #f0f0f0;">' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.45);">' + (i + 1) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + escapeHtml(dateStr) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + escapeHtml(timeStr) + '</td>' +
                    '<td style="padding:8px;">' + badge + '</td>' +
                    '<td style="padding:8px;">' + renderAttendanceMode(row) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + office + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + escapeHtml(dist) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + notes + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + renderFlags(row) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + reasonText + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + renderAttachment(row) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + actionHtml + '</td>' +
                    '</tr>';
            });

            html += '</tbody></table></div>';
            return html;
        }

        setActiveTab(searchParams.get('tab') === 'out-of-town' ? 'out-of-town' : 'regular');
        loadStatus();
        loadTodayStatus();
        resetOutOfTownPhoto(true);
    })();
</script>
