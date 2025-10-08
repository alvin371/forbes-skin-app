<style>
    .form-control, .form-select {
        height: 40px;
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .form-control:hover, .form-select:hover {
        border-color: #40a9ff;
    }

    .form-control:focus, .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    textarea.form-control {
        height: auto;
        min-height: 100px;
    }

    .card {
        border-radius: 4px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 20px;
    }

    .card-body {
        padding: 20px;
    }

    .btn {
        border-radius: 4px;
        padding: 8px 16px;
        font-size: 14px;
        height: 40px;
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

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }

    .form-label {
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
        margin-bottom: 8px;
    }

    .text-muted {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
    }

    .alert {
        border-radius: 4px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }

    .alert-success {
        background-color: #f6ffed;
        border-color: #b7eb8f;
        color: #389e0d;
    }
</style>

<div class="container-fluid py-3">
    <?php $this->load->view('recruitment/menu') ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Interview Question</h5>
                <a href="<?= base_url() ?>interview/questions" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Questions
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['success']) && $_GET['success'] == '2'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>Question updated successfully!
                </div>
            <?php endif; ?>

            <form method="post" id="questionForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="position" class="form-label">Position <span class="required">*</span></label>
                        <select class="form-control" id="position" name="position_id" required>
                            <option value="" disabled <?= empty($question['position_id']) ? 'selected' : '' ?>>Select Position</option>
                            <?php if (!empty($positions)) : ?>
                                <?php foreach ($positions as $pos) : ?>
                                    <option value="<?= htmlspecialchars($pos['id']) ?>" 
                                        <?= $question['position_id'] == $pos['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pos['name'] . ' - ' . $pos['level_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $question['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="question_text" class="form-label">Criteria Text <span class="text-danger">*</span></label>
                    <textarea class="form-control" 
                              id="question_text" 
                              name="question_text" 
                              rows="4" 
                              placeholder="Enter the interview question"
                              required><?= htmlspecialchars($question['question_text']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="question_type" name="question_type" required>
                                <option value="">Select Type</option>
                                <option value="text" <?= $question['question_type'] == 'text' ? 'selected' : '' ?>>Text Answer</option>
                                <option value="score" <?= $question['question_type'] == 'score' ? 'selected' : '' ?>>Score Based</option>
                                <option value="multiple_choice" <?= $question['question_type'] == 'multiple_choice' ? 'selected' : '' ?>>Multiple Choice</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="score_range" class="form-label">Score Range</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="score_range" 
                                   name="score_range" 
                                   value="<?= htmlspecialchars($question['score_range']) ?>"
                                   placeholder="e.g., 1-4"
                                   <?= $question['question_type'] != 'score' ? 'disabled' : '' ?>>
                            <div class="text-muted">Only for score-based questions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="sort_order" 
                                   name="sort_order" 
                                   value="<?= $question['sort_order'] ?>"
                                   placeholder="0"
                                   min="0">
                            <div class="text-muted">Order of display (0 = first)</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="optionsDiv" style="display: <?= $question['question_type'] == 'multiple_choice' ? 'block' : 'none' ?>;">
                    <label for="options" class="form-label">Multiple Choice Options</label>
                    <textarea class="form-control" 
                              id="options" 
                              name="options" 
                              rows="3" 
                              placeholder="Enter options separated by new lines"><?= htmlspecialchars($question['options']) ?></textarea>
                    <div class="text-muted">Only for multiple choice questions. Enter each option on a new line.</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url() ?>interview/questions" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Update Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle question type change
    $('#question_type').change(function() {
        const selectedType = $(this).val();
        
        if (selectedType === 'score') {
            $('#score_range').prop('disabled', false);
            $('#optionsDiv').hide();
            $('#options').prop('required', false);
        } else if (selectedType === 'multiple_choice') {
            $('#score_range').prop('disabled', true);
            $('#optionsDiv').show();
            $('#options').prop('required', true);
        } else {
            $('#score_range').prop('disabled', true);
            $('#optionsDiv').hide();
            $('#options').prop('required', false);
        }
    });

    // Form validation
    $('#questionForm').submit(function(e) {
        const questionType = $('#question_type').val();
        
        if (questionType === 'score' && !$('#score_range').val()) {
            e.preventDefault();
            alert('Please enter score range for score-based questions');
            return false;
        }
        
        if (questionType === 'multiple_choice' && !$('#options').val()) {
            e.preventDefault();
            alert('Please enter options for multiple choice questions');
            return false;
        }
        
        return true;
    });
});
</script>