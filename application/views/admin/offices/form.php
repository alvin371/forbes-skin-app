<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $office['id'] ? 'Edit Office' : 'Create Office'; ?></h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> Please fix the errors below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($office['name']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                <?php if (!empty($errors['name'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Latitude</label>
                        <input type="text" id="lat" name="lat" class="form-control" value="<?php echo htmlspecialchars($office['lat']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['lat'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['lat']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Longitude</label>
                        <input type="text" id="lng" name="lng" class="form-control" value="<?php echo htmlspecialchars($office['lng']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['lng'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['lng']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <button type="button" id="geo-fill" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-geo-alt"></i> Get my current location
                </button>
                <span id="geo-status" style="margin-left: 10px; font-size: 14px;"></span>
            </div>

            <div id="geo-preview" style="border: 1px solid #d9d9d9; border-radius: 2px; padding: 16px; margin-bottom: 16px; background-color: #fafafa;">
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Preview Location:</strong> <span id="preview-location" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Office Target:</strong> <span id="preview-office" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Distance:</strong> <span id="preview-distance" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="margin-bottom: 8px; font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Status:</strong> <span id="preview-status" style="color: rgba(0,0,0,0.65);">-</span></div>
                <div style="font-size: 14px;"><strong style="color: rgba(0,0,0,0.85);">Guidance:</strong> <span id="preview-guidance" style="color: rgba(0,0,0,0.65);">-</span></div>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Radius (meters)</label>
                        <input type="number" name="radius_m" class="form-control" value="<?php echo htmlspecialchars($office['radius_m']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['radius_m'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['radius_m']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Minimum Accuracy (meters)</label>
                        <input type="number" name="min_accuracy_m" class="form-control" value="<?php echo htmlspecialchars($office['min_accuracy_m']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['min_accuracy_m'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['min_accuracy_m']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Allowed IP CIDRs <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(one per line)</span></label>
                <textarea name="allowed_ip_cidrs" rows="5" class="form-control" style="padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;"><?php echo htmlspecialchars($office['allowed_ip_cidrs']); ?></textarea>
                <?php if (!empty($errors['allowed_ip_cidrs'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['allowed_ip_cidrs']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Allowed BSSIDs <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(one per line)</span></label>
                        <textarea name="allowed_bssids" rows="4" class="form-control" style="padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;"><?php echo htmlspecialchars($office['allowed_bssids']); ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Allowed SSIDs <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(one per line)</span></label>
                        <textarea name="allowed_ssids" rows="4" class="form-control" style="padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;"><?php echo htmlspecialchars($office['allowed_ssids']); ?></textarea>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Attendance Response Times <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(HH:MM, one per line)</span></label>
                <textarea name="attendance_response_times" rows="3" class="form-control" style="padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;"><?php echo htmlspecialchars($office['attendance_response_times']); ?></textarea>
                <?php if (!empty($errors['attendance_response_times'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['attendance_response_times']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Attendance History Days</label>
                        <input type="number" name="attendance_history_days" class="form-control" value="<?php echo htmlspecialchars($office['attendance_history_days']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['attendance_history_days'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['attendance_history_days']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Attendance Recap Months</label>
                        <input type="number" name="attendance_recap_months" class="form-control" value="<?php echo htmlspecialchars($office['attendance_recap_months']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['attendance_recap_months'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['attendance_recap_months']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85);">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) $office['is_active'] === 1) ? 'checked' : ''; ?> style="margin-right: 8px;">
                    Set as active office
                </label>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                    <i class="bi bi-check-circle"></i> Save
                </button>
                <a href="<?php echo site_url('admin/offices'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
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
                previewGuidanceEl.style.color = '#ff4d4f';
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
                        previewGuidanceEl.style.color = '#ff4d4f';
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
                    previewGuidanceEl.style.color = computed.can_confirm ? '#52c41a' : '#ff4d4f';
                })
                .catch(function () {
                    previewGuidanceEl.textContent = 'Unable to preview office status.';
                    previewGuidanceEl.style.color = '#ff4d4f';
                });
        }

        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            statusEl.textContent = 'Requesting location...';
            statusEl.style.color = 'rgba(0,0,0,0.65)';

            if (!navigator.geolocation) {
                statusEl.textContent = 'Geolocation is not supported.';
                statusEl.style.color = '#ff4d4f';
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
                statusEl.style.color = '#52c41a';
                updatePreview(position);
            }, function () {
                statusEl.textContent = 'Unable to get location.';
                statusEl.style.color = '#ff4d4f';
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
