<div class="card">
    <div class="card-body">
        <h3>Submit Leave Request</h3>

        <?php if (!empty($errors)): ?>
            <div style="margin: 10px 0; color: #b71c1c;">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>" enctype="multipart/form-data">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 12px;">
                <label>Leave Type</label><br>
                <select name="leave_type_id" required>
                    <option value="">Select leave type</option>
                    <?php foreach ($leave_types as $leaveType): ?>
                        <option value="<?php echo (int) $leaveType['id']; ?>" <?php echo ((int) $request['leave_type_id'] === (int) $leaveType['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($leaveType['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Start Date</label><br>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($request['start_date']); ?>" required>
            </div>

            <div style="margin-bottom: 12px;">
                <label>End Date</label><br>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($request['end_date']); ?>" required>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Reason</label><br>
                <textarea name="reason" rows="4" cols="40"><?php echo htmlspecialchars($request['reason']); ?></textarea>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Attachment (PDF/JPG/PNG, max 2MB)</label><br>
                <input type="file" name="attachment">
            </div>

            <div>
                <button type="submit">Submit</button>
                <a href="<?php echo site_url('leave'); ?>">Back</a>
            </div>
        </form>
    </div>
</div>
