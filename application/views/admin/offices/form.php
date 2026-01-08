<div class="card">
    <div class="card-body">
        <h3><?php echo $office['id'] ? 'Edit Office' : 'Create Office'; ?></h3>

        <?php if (!empty($errors)): ?>
            <div style="margin: 10px 0; color: #b00020;">
                Please fix the errors below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 12px;">
                <label>Name</label><br>
                <input type="text" name="name" value="<?php echo htmlspecialchars($office['name']); ?>" style="width: 100%;">
                <?php if (!empty($errors['name'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Latitude</label><br>
                <input type="text" id="lat" name="lat" value="<?php echo htmlspecialchars($office['lat']); ?>" style="width: 100%;">
                <?php if (!empty($errors['lat'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['lat']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Longitude</label><br>
                <input type="text" id="lng" name="lng" value="<?php echo htmlspecialchars($office['lng']); ?>" style="width: 100%;">
                <?php if (!empty($errors['lng'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['lng']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <button type="button" id="geo-fill">Get my current location</button>
                <span id="geo-status" style="margin-left: 10px;"></span>
            </div>

            <div id="geo-preview" style="border: 1px solid #ddd; padding: 12px; margin-bottom: 12px;">
                <div><strong>Preview Location:</strong> <span id="preview-location">-</span></div>
                <div><strong>Office Target:</strong> <span id="preview-office">-</span></div>
                <div><strong>Distance:</strong> <span id="preview-distance">-</span></div>
                <div><strong>Status:</strong> <span id="preview-status">-</span></div>
                <div><strong>Guidance:</strong> <span id="preview-guidance">-</span></div>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Radius (meters)</label><br>
                <input type="number" name="radius_m" value="<?php echo htmlspecialchars($office['radius_m']); ?>" style="width: 100%;">
                <?php if (!empty($errors['radius_m'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['radius_m']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Minimum Accuracy (meters)</label><br>
                <input type="number" name="min_accuracy_m" value="<?php echo htmlspecialchars($office['min_accuracy_m']); ?>" style="width: 100%;">
                <?php if (!empty($errors['min_accuracy_m'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['min_accuracy_m']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Allowed IP CIDRs (one per line)</label><br>
                <textarea name="allowed_ip_cidrs" rows="5" style="width: 100%;"><?php echo htmlspecialchars($office['allowed_ip_cidrs']); ?></textarea>
                <?php if (!empty($errors['allowed_ip_cidrs'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['allowed_ip_cidrs']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Allowed BSSIDs (one per line)</label><br>
                <textarea name="allowed_bssids" rows="4" style="width: 100%;"><?php echo htmlspecialchars($office['allowed_bssids']); ?></textarea>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Allowed SSIDs (one per line)</label><br>
                <textarea name="allowed_ssids" rows="4" style="width: 100%;"><?php echo htmlspecialchars($office['allowed_ssids']); ?></textarea>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Attendance Response Times (HH:MM, one per line)</label><br>
                <textarea name="attendance_response_times" rows="3" style="width: 100%;"><?php echo htmlspecialchars($office['attendance_response_times']); ?></textarea>
                <?php if (!empty($errors['attendance_response_times'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['attendance_response_times']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Attendance History Days</label><br>
                <input type="number" name="attendance_history_days" value="<?php echo htmlspecialchars($office['attendance_history_days']); ?>" style="width: 100%;">
                <?php if (!empty($errors['attendance_history_days'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['attendance_history_days']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Attendance Recap Months</label><br>
                <input type="number" name="attendance_recap_months" value="<?php echo htmlspecialchars($office['attendance_recap_months']); ?>" style="width: 100%;">
                <?php if (!empty($errors['attendance_recap_months'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['attendance_recap_months']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) $office['is_active'] === 1) ? 'checked' : ''; ?>>
                    Set as active office
                </label>
            </div>

            <div style="margin-top: 16px;">
                <button type="submit">Save</button>
                <a href="<?php echo site_url('admin/offices'); ?>" style="margin-left: 8px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var button = document.getElementById('geo-fill');
        var statusEl = document.getElementById('geo-status');
        var latInput = document.getElementById('lat');
        var lngInput = document.getElementById('lng');
        var nameInput = document.querySelector('input[name="name"]');
        var radiusInput = document.querySelector('input[name="radius_m"]');
        var accuracyInput = document.querySelector('input[name="min_accuracy_m"]');
        var cidrInput = document.querySelector('textarea[name="allowed_ip_cidrs"]');
        var previewLocationEl = document.getElementById('preview-location');
        var previewOfficeEl = document.getElementById('preview-office');
        var previewDistanceEl = document.getElementById('preview-distance');
        var previewStatusEl = document.getElementById('preview-status');
        var previewGuidanceEl = document.getElementById('preview-guidance');
        var lastPosition = null;

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

        function updatePreview(position) {
            if (!position) {
                return;
            }

            var officeLat = latInput.value;
            var officeLng = lngInput.value;
            var radius = radiusInput.value;
            var minAccuracy = accuracyInput.value;
            var officeName = nameInput.value || 'Preview Office';
            var allowedCidrs = cidrInput.value || '';

            if (!officeLat || !officeLng) {
                previewGuidanceEl.textContent = 'Fill office latitude and longitude to preview.';
                previewGuidanceEl.style.color = '#b00020';
                return;
            }

            var url = "<?php echo site_url('api/attendance/status'); ?>" +
                '?lat=' + encodeURIComponent(position.coords.latitude) +
                '&lng=' + encodeURIComponent(position.coords.longitude) +
                '&accuracy=' + encodeURIComponent(position.coords.accuracy) +
                '&office_lat=' + encodeURIComponent(officeLat) +
                '&office_lng=' + encodeURIComponent(officeLng) +
                '&office_radius_m=' + encodeURIComponent(radius) +
                '&office_min_accuracy_m=' + encodeURIComponent(minAccuracy) +
                '&office_name=' + encodeURIComponent(officeName) +
                '&allowed_ip_cidrs=' + encodeURIComponent(allowedCidrs);

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data || data.status !== 'ok') {
                        previewGuidanceEl.textContent = data.message || 'Unable to preview office status.';
                        previewGuidanceEl.style.color = '#b00020';
                        return;
                    }

                    var office = data.office;
                    var user = data.user;
                    var computed = data.computed;

                    previewLocationEl.textContent = 'lat ' + user.lat + ', lng ' + user.lng + ' (accuracy ' + user.accuracy + 'm)';
                    previewOfficeEl.textContent = office.name + ' | lat ' + office.lat + ', lng ' + office.lng +
                        ' | radius ' + office.radius_m + 'm | min accuracy ' + office.min_accuracy_m + 'm';
                    previewDistanceEl.textContent = formatDistance(computed.distance_m);
                    previewStatusEl.textContent =
                        (computed.inside_radius ? 'Inside radius' : 'Outside radius') + ' | ' +
                        (computed.accuracy_ok ? 'Accuracy OK' : 'Accuracy too low') + ' | ' +
                        (office.has_ip_rule ? (computed.ip_ok ? 'Network OK' : 'Network blocked') : 'Network check skipped');

                    var guidance = [];
                    if (!computed.inside_radius) {
                        guidance.push('Move closer within ' + office.radius_m + 'm.');
                    }
                    if (!computed.accuracy_ok) {
                        guidance.push('Improve GPS accuracy.');
                    }
                    if (office.has_ip_rule && !computed.ip_ok) {
                        guidance.push('Connect to office network.');
                    }
                    if (guidance.length === 0) {
                        guidance.push('Office settings look good for your current location.');
                    }
                    previewGuidanceEl.textContent = guidance.join(' ');
                    previewGuidanceEl.style.color = computed.can_confirm ? '#0a6b2b' : '#b00020';
                })
                .catch(function () {
                    previewGuidanceEl.textContent = 'Unable to preview office status.';
                    previewGuidanceEl.style.color = '#b00020';
                });
        }

        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            statusEl.textContent = 'Requesting location...';
            statusEl.style.color = '#333';

            if (!navigator.geolocation) {
                statusEl.textContent = 'Geolocation is not supported.';
                statusEl.style.color = '#b00020';
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                lastPosition = position;
                if (!latInput.value) {
                    latInput.value = position.coords.latitude;
                }
                if (!lngInput.value) {
                    lngInput.value = position.coords.longitude;
                }
                statusEl.textContent = 'Location updated.';
                statusEl.style.color = '#0a6b2b';
                updatePreview(position);
            }, function () {
                statusEl.textContent = 'Unable to get location.';
                statusEl.style.color = '#b00020';
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });
        });

        var inputs = [latInput, lngInput, radiusInput, accuracyInput, cidrInput, nameInput];
        inputs.forEach(function (input) {
            if (!input) {
                return;
            }
            input.addEventListener('input', function () {
                if (lastPosition) {
                    updatePreview(lastPosition);
                }
            });
        });
    })();
</script>
