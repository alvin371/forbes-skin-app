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
            <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Guidance:</strong> <span id="status-guidance" style="color: rgba(0,0,0,0.65);">-</span></div>
            <div style="font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Eligibility:</strong> <span id="status-eligibility" style="color: rgba(0,0,0,0.65);">-</span></div>
        </div>
        <div id="geo-data" style="font-family: monospace; margin-bottom: 12px; font-size: 12px; color: rgba(0,0,0,0.65);"></div>
        <div id="api-response" style="font-size: 14px;"></div>
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
        var geoData = document.getElementById('geo-data');
        var apiResponse = document.getElementById('api-response');
        var userLocationEl = document.getElementById('user-location');
        var officeLocationEl = document.getElementById('office-location');
        var distanceInfoEl = document.getElementById('distance-info');
        var statusFlagsEl = document.getElementById('status-flags');
        var statusGuidanceEl = document.getElementById('status-guidance');
        var statusEligibilityEl = document.getElementById('status-eligibility');
        var lastStatus = null;
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

        function handleConfirm(type) {
            setStatus('Requesting GPS location...', false);
            setResponse('', false);
            geoData.textContent = '';
            toggleButtons(true);

            requestLocation()
                .then(function (position) {
                    geoData.textContent = formatGeo(position);
                    setStatus('Location acquired. Checking status...', false);
                    return fetchStatus(position).then(function (statusResult) {
                        if (!statusResult || !statusResult.computed) {
                            throw new Error('Unable to evaluate status.');
                        }
                        if (!statusResult.computed.can_confirm) {
                            setResponse('You cannot confirm yet. Please review the status panel.', true);
                            return null;
                        }
                        setStatus('Status OK. Sending confirmation...', false);
                        return postAttendance(type, position);
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
                    toggleButtons(false);
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
            var messages = [];
            if (!computed.inside_radius) {
                messages.push('Move closer within ' + office.radius_m + 'm of the office.');
            }
            if (!computed.accuracy_ok) {
                messages.push('Turn on high-accuracy GPS or go outdoors for better signal.');
            }
            if (office.has_ip_rule && !computed.ip_ok) {
                messages.push('Connect to office Wi-Fi or network (must be on office IP range).');
            }
            if (Array.isArray(computed.reasons) && computed.reasons.indexOf('WIFI_REQUIRED') !== -1) {
                messages.push('Provide office Wi-Fi proof (SSID/BSSID) for this office.');
            } else if (Array.isArray(computed.reasons) && computed.reasons.indexOf('WIFI_NOT_ALLOWED') !== -1) {
                messages.push('Current Wi-Fi does not match office Wi-Fi allowlist.');
            }
            if (messages.length === 0) {
                messages.push('You are good to confirm attendance.');
            }
            return messages.join(' ');
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
            statusFlagsEl.textContent =
                (computed.inside_radius ? 'Inside radius' : 'Outside radius') + ' | ' +
                (computed.accuracy_ok ? 'Accuracy OK' : 'Accuracy too low') + ' | ' +
                (office.has_ip_rule ? (computed.ip_ok ? 'Network OK' : 'Network blocked') : 'Network check skipped') + ' | ' +
                (wifi && wifi.has_rules ? (wifi.ok ? 'Wi-Fi OK' : 'Wi-Fi blocked') : 'Wi-Fi check skipped');
            statusGuidanceEl.textContent = buildGuidance(computed, office);
            statusEligibilityEl.textContent = computed.can_confirm ? 'You can confirm attendance.' : 'You cannot confirm attendance.';
            statusEligibilityEl.style.color = computed.can_confirm ? '#52c41a' : '#ff4d4f';
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
                    geoData.textContent = formatGeo(position);
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
    })();
</script>
