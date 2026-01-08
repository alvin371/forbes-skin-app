<div class="card">
    <div class="card-body">
        <h3>Attendance Confirmation</h3>
        <p>Confirm your attendance only when you are at the office location.</p>

        <div style="margin-bottom: 16px;">
            <button id="confirm-in" class="btn btn-primary">Confirm IN</button>
            <button id="confirm-out" class="btn btn-outline-primary">Confirm OUT</button>
        </div>

        <div id="geo-status" style="margin-bottom: 8px;"></div>
        <div id="status-panel" style="border: 1px solid #ddd; padding: 12px; margin-bottom: 12px;">
            <div><strong>Current Location:</strong> <span id="user-location">-</span></div>
            <div><strong>Office Target:</strong> <span id="office-location">-</span></div>
            <div><strong>Distance:</strong> <span id="distance-info">-</span></div>
            <div><strong>Status:</strong> <span id="status-flags">-</span></div>
            <div><strong>Guidance:</strong> <span id="status-guidance">-</span></div>
            <div><strong>Eligibility:</strong> <span id="status-eligibility">-</span></div>
        </div>
        <div id="geo-data" style="font-family: monospace; margin-bottom: 8px;"></div>
        <div id="api-response"></div>
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

        function setStatus(message, isError) {
            geoStatus.textContent = message;
            geoStatus.style.color = isError ? '#b00020' : '#0a6b2b';
        }

        function setResponse(message, isError) {
            apiResponse.textContent = message;
            apiResponse.style.color = isError ? '#b00020' : '#0a6b2b';
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

        function postAttendance(type, position) {
            var payload = {
                type: type,
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy
            };

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
                        setResponse(result.data.message + ' (Distance: ' + result.data.distance_m + ' m)', false);
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
            lastStatus = result;

            userLocationEl.textContent = 'lat ' + user.lat + ', lng ' + user.lng + ' (accuracy ' + user.accuracy + 'm)';
            officeLocationEl.textContent = office.name + ' | lat ' + office.lat + ', lng ' + office.lng +
                ' | radius ' + office.radius_m + 'm | min accuracy ' + office.min_accuracy_m + 'm';
            distanceInfoEl.textContent = formatDistance(computed.distance_m);
            statusFlagsEl.textContent =
                (computed.inside_radius ? 'Inside radius' : 'Outside radius') + ' | ' +
                (computed.accuracy_ok ? 'Accuracy OK' : 'Accuracy too low') + ' | ' +
                (office.has_ip_rule ? (computed.ip_ok ? 'Network OK' : 'Network blocked') : 'Network check skipped');
            statusGuidanceEl.textContent = buildGuidance(computed, office);
            statusEligibilityEl.textContent = computed.can_confirm ? 'You can confirm attendance.' : 'You cannot confirm attendance.';
            statusEligibilityEl.style.color = computed.can_confirm ? '#0a6b2b' : '#b00020';
        }

        function fetchStatus(position) {
            var url = statusUrl +
                '?lat=' + encodeURIComponent(position.coords.latitude) +
                '&lng=' + encodeURIComponent(position.coords.longitude) +
                '&accuracy=' + encodeURIComponent(position.coords.accuracy);

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
                    statusGuidanceEl.style.color = '#b00020';
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
                    statusGuidanceEl.style.color = '#b00020';
                });
        }

        loadStatus();
    })();
</script>
