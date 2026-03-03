<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Attendance Confirmation</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <p style="font-size: 14px; color: rgba(0,0,0,0.65); margin-bottom: 16px;">Confirm your attendance only when you are at the office location.</p>

        <div style="margin-bottom: 16px;">
            <button id="confirm-in" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                <i class="bi bi-box-arrow-in-right"></i> Confirm IN
            </button>
            <button id="confirm-out" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                <i class="bi bi-box-arrow-left"></i> Confirm OUT
            </button>
            <button id="view-logs-btn" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                <i class="bi bi-clock-history"></i> View Logs
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
        var apiUrl = "<?php echo site_url('api/attendance/confirm'); ?>";
        var statusUrl = "<?php echo site_url('api/attendance/status'); ?>";
        var csrfName = "<?php echo isset($csrf_name) ? $csrf_name : ''; ?>";
        var csrfHash = "<?php echo isset($csrf_hash) ? $csrf_hash : ''; ?>";
        var confirmInBtn = document.getElementById('confirm-in');
        var confirmOutBtn = document.getElementById('confirm-out');
        var geoStatus = document.getElementById('geo-status');
        var apiResponse = document.getElementById('api-response');
        var userLocationEl = document.getElementById('user-location');
        var officeLocationEl = document.getElementById('office-location');
        var distanceInfoEl = document.getElementById('distance-info');
        var statusFlagsEl = document.getElementById('status-flags');
        var scheduleInfoEl = document.getElementById('schedule-info');
        var statusGuidanceEl = document.getElementById('status-guidance');
        var statusEligibilityEl = document.getElementById('status-eligibility');
        var lastStatus = null;
        var logsApiUrl = "<?php echo site_url('api/attendance/logs'); ?>";
        var todayDone  = { IN: false, OUT: false };
        var searchParams = new URLSearchParams(window.location.search || '');

        function setStatus(message, isError) {
            geoStatus.textContent = message;
            geoStatus.style.color = isError ? '#ff4d4f' : '#52c41a';
        }

        function setResponse(message, isError) {
            apiResponse.textContent = message;
            apiResponse.style.color = isError ? '#ff4d4f' : '#52c41a';
        }

        function formatGeo(position) {
            return [
                'lat: ' + position.coords.latitude,
                'lng: ' + position.coords.longitude,
                'accuracy_m: ' + position.coords.accuracy
            ].join(' | ');
        }

        function toggleButtons(disabled) {
            confirmInBtn.disabled = disabled;
            confirmOutBtn.disabled = disabled;
        }

        function applyButtonState() {
            confirmInBtn.disabled  = todayDone['IN'];
            confirmOutBtn.disabled = todayDone['OUT'];
        }

        function loadTodayStatus() {
            var month = (new Date()).toISOString().slice(0, 7);
            var today = (new Date()).toISOString().slice(0, 10);
            fetch(logsApiUrl + '?month=' + encodeURIComponent(month), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status !== 'ok' || !data.data) { return; }
                    data.data.forEach(function (row) {
                        if (row.created_at && row.created_at.slice(0, 10) === today) {
                            if (row.type === 'IN')  { todayDone['IN']  = true; }
                            if (row.type === 'OUT') { todayDone['OUT'] = true; }
                        }
                    });
                    applyButtonState();
                })
                .catch(function () {});
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
                type: type,
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            var wifiProof = getWifiProof();
            if (wifiProof) {
                payload.wifiProof = wifiProof;
            }

            var headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            if (csrfName && csrfHash) {
                headers['X-CSRF-Token'] = csrfHash;
            }

            return fetch(apiUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, data: data };
                });
            });
        }

        function parseHHMM(hhmm) {
            var parts = String(hhmm).split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function buildConfirmHtml(type, statusResult) {
            var now = new Date();
            var timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            var office = statusResult.office;
            var computed = statusResult.computed;
            var sch = statusResult.schedule || null;

            var scheduleHtml = '';
            var noteHtml = '';

            if (sch) {
                var scheduleBadge = sch.is_special
                    ? '<span style="background-color:#fff1f0;color:#cf1322;border:1px solid #ffa39e;padding:0 6px;border-radius:2px;font-size:12px;margin-right:5px;">Special</span>'
                    : '<span style="background-color:#f6ffed;color:#389e0d;border:1px solid #b7eb8f;padding:0 6px;border-radius:2px;font-size:12px;margin-right:5px;">Default</span>';

                scheduleHtml = '<div><strong>Schedule:</strong> ' + scheduleBadge +
                    sch.start_time + ' \u2013 ' + sch.end_time + '</div>';

                var nowMinutes = now.getHours() * 60 + now.getMinutes();

                if (type === 'IN') {
                    var lateMinutes = parseHHMM(sch.late_threshold);
                    if (nowMinutes > lateMinutes) {
                        var diff = nowMinutes - parseHHMM(sch.start_time);
                        noteHtml = '<div style="background-color:#fff2f0;border:1px solid #ffccc7;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                            '<span style="color:#ff4d4f;">&#9888; Late check-in</span> &mdash; ' +
                            diff + ' min after start time (grace ends at ' + sch.late_threshold + ')' +
                            '</div>';
                    } else {
                        noteHtml = '<div style="background-color:#f6ffed;border:1px solid #b7eb8f;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                            '<span style="color:#52c41a;">&#10003; On time</span> &mdash; grace period ends at ' + sch.late_threshold +
                            '</div>';
                    }
                } else if (type === 'OUT') {
                    var earlyMinutes = parseHHMM(sch.early_threshold);
                    if (nowMinutes < earlyMinutes) {
                        var diff = parseHHMM(sch.end_time) - nowMinutes;
                        noteHtml = '<div style="background-color:#fffbe6;border:1px solid #ffe58f;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                            '<span style="color:#faad14;">&#9888; Early checkout</span> &mdash; ' +
                            diff + ' min before end time (early cutoff at ' + sch.early_threshold + ')' +
                            '</div>';
                    } else {
                        noteHtml = '<div style="background-color:#f6ffed;border:1px solid #b7eb8f;border-radius:2px;padding:6px 10px;margin-top:6px;">' +
                            '<span style="color:#52c41a;">&#10003; Normal checkout</span> &mdash; end time is ' + sch.end_time +
                            '</div>';
                    }
                }
            }

            return '<div style="text-align:left;font-size:14px;line-height:2.2;">' +
                '<div><strong>Current Time:</strong> ' + timeStr + '</div>' +
                '<div><strong>Office:</strong> ' + office.name + '</div>' +
                '<div><strong>Distance:</strong> ' + formatDistance(computed.distance_m) + ' from office center</div>' +
                scheduleHtml +
                noteHtml +
                '<div style="margin-top:6px;color:#52c41a;"><strong>&#10003; Your location matches the office</strong></div>' +
                '</div>';
        }

        function handleConfirm(type) {
            setStatus('Requesting GPS location...', false);
            setResponse('', false);
            toggleButtons(true);

            requestLocation()
                .then(function (position) {
                    setStatus('Location acquired. Checking status...', false);
                    return fetchStatus(position).then(function (statusResult) {
                        if (!statusResult || !statusResult.computed) {
                            throw new Error('Unable to evaluate status.');
                        }
                        if (!statusResult.computed.inside_radius) {
                            setResponse('You are outside the office radius. Please move closer and try again.', true);
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
                    if (result.data && result.data.status === 'ok') {
                        var message = result.data.message + ' (Distance: ' + result.data.distance_m + ' m)';
                        if (Array.isArray(result.data.notes) && result.data.notes.length) {
                            message += ' | ' + result.data.notes.join(' ');
                        }
                        if (result.data.flags && result.data.flags.special_schedule && result.data.schedule) {
                            message += ' | Special schedule: ' + result.data.schedule.start_time + ' - ' + result.data.schedule.end_time;
                        }
                        setResponse(message, false);
                        todayDone[type] = true;
                    } else {
                        var message = result.data && result.data.message ? result.data.message : 'Failed to confirm attendance.';
                        setResponse(message, true);
                    }
                })
                .catch(function (error) {
                    var message = error && error.message ? error.message : 'Unable to get location.';
                    setStatus(message, true);
                    setResponse('Please allow location access and try again.', true);
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

        function buildGuidance(computed, office) {
            if (!computed.inside_radius) {
                return 'Move closer within ' + office.radius_m + 'm of the office.';
            }
            return 'You are good to confirm attendance.';
        }

        function renderStatus(result) {
            if (!result || result.status !== 'ok') {
                statusGuidanceEl.textContent = 'Unable to load status.';
                return;
            }

            var office = result.office;
            var user = result.user;
            var computed = result.computed;
            var wifi = result.wifi || null;
            lastStatus = result;

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
                    scheduleInfoEl.innerHTML = '<span style="background-color:#fff1f0;color:#cf1322;border:1px solid #ffa39e;padding:0 6px;height:20px;display:inline-flex;align-items:center;border-radius:2px;font-size:12px;margin-right:5px;">Special</span>' +
                        schText;
                } else {
                    scheduleInfoEl.textContent = schText;
                }
            }

            statusGuidanceEl.textContent = buildGuidance(computed, office);
            statusEligibilityEl.textContent = computed.inside_radius ? 'You can confirm attendance.' : 'You cannot confirm attendance.';
            statusEligibilityEl.style.color = computed.inside_radius ? '#52c41a' : '#ff4d4f';
        }

        function fetchStatus(position) {
            var url = statusUrl +
                '?lat=' + encodeURIComponent(position.coords.latitude) +
                '&lng=' + encodeURIComponent(position.coords.longitude) +
                '&accuracy=' + encodeURIComponent(position.coords.accuracy);
            url = appendWifiProofToQuery(url, getWifiProof());

            return fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    renderStatus(data);
                    return data;
                })
                .catch(function () {
                    statusGuidanceEl.textContent = 'Unable to load status.';
                    statusGuidanceEl.style.color = '#ff4d4f';
                    return null;
                });
        }

        function loadStatus() {
            setStatus('Checking your location status...', false);
            requestLocation()
                .then(function (position) {
                    return fetchStatus(position);
                })
                .then(function (data) {
                    if (data && data.status === 'ok') {
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

        loadStatus();
        loadTodayStatus();

        // --- View Logs ---
        var logsPanel     = document.getElementById('logs-panel');
        var logsMonthEl   = document.getElementById('logs-month');
        var logsLoadBtn   = document.getElementById('logs-load-btn');
        var logsTableWrap = document.getElementById('logs-table-wrap');
        var viewLogsBtn   = document.getElementById('view-logs-btn');

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

            fetch(logsApiUrl + '?month=' + encodeURIComponent(month), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status !== 'ok') {
                        logsTableWrap.innerHTML = '<p style="color:#ff4d4f;font-size:14px;">Failed to load logs.</p>';
                        return;
                    }
                    if (!data.data || data.data.length === 0) {
                        logsTableWrap.innerHTML = '<p style="font-size:14px;color:rgba(0,0,0,0.45);">No records found for this month.</p>';
                        return;
                    }
                    logsTableWrap.innerHTML = buildLogsTable(data.data);
                })
                .catch(function () {
                    logsTableWrap.innerHTML = '<p style="color:#ff4d4f;font-size:14px;">Failed to load logs.</p>';
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
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Office</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Distance</th>' +
                '<th style="padding:8px;border-bottom:1px solid #f0f0f0;font-weight:500;color:rgba(0,0,0,0.85);">Notes</th>' +
                '</tr></thead><tbody>';

            rows.forEach(function (row, i) {
                var dt = new Date(row.created_at);
                var dateStr = dt.toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                var timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                var typeColor = typeColors[row.type] || '#1890ff';
                var badge = '<span style="background:' + typeColor + ';color:#fff;padding:2px 8px;border-radius:2px;font-size:12px;">' + row.type + '</span>';
                var notes = Array.isArray(row.notes) && row.notes.length ? row.notes.join(', ') : '-';
                var dist = row.distance_m !== null ? Math.round(row.distance_m) + ' m' : '-';
                var office = row.office_name || '-';

                html += '<tr style="border-bottom:1px solid #f0f0f0;">' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.45);">' + (i + 1) + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + dateStr + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + timeStr + '</td>' +
                    '<td style="padding:8px;">' + badge + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + office + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + dist + '</td>' +
                    '<td style="padding:8px;color:rgba(0,0,0,0.65);">' + notes + '</td>' +
                    '</tr>';
            });

            html += '</tbody></table></div>';
            return html;
        }
    })();
</script>
