<style>
    .summary-container {
        max-width: 100%;
        font-family: inherit;
    }

    table.custom-table {
        width: 100%;
        border-collapse: collapse;
    }
    table.custom-table td {
        border: 1px solid #000;
        padding: 8px;
        vertical-align: top;
    }
    .custom-table .label {
        width: 25%;
        white-space: nowrap;
    }
    .custom-table .value {
        width: 25%;
    }

    .evaluation-table {
        width: 100%;
        margin-bottom: 30px;
        background-color: #fff;
        border: 1px solid #dee2e6;
    }

    .evaluation-table td,
    .evaluation-table th {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: break-word;
    }


    .evaluation-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .evaluation-table .score-options {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .evaluation-table .score-option label {
        margin-left: 4px;
    }

    .section-title {
        font-weight: bold;
        font-size: 1rem;
        background-color: #e9ecef;
        padding: 10px;
        margin-top: 25px;
        border-left: 4px solid #1890ff;
    }

    textarea {
        width: 100%;
        min-height: 80px;
        resize: vertical;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ced4da;
    }

    .form-footer {
        margin-top: 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn {
        padding: 8px 15px;
        border-radius: 4px;
        font-size: 14px;
    }

    .btn-primary {
        background-color: #1890ff;
        color: #fff;
        border: none;
    }

    .btn-secondary {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ccc;
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 12px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .evaluation-table {
        table-layout: fixed; /* Membuat kolom dengan lebar tetap */
        width: 100%;
        word-wrap: break-word; /* Memungkinkan text wrapping */
    }
    
    .evaluation-table .criteria-col {
        width: 20%; /* Lebar kolom kriteria */
        word-break: break-word; /* Memastikan text wrapping */
    }
    
    .evaluation-table .question-col {
        width: 40%;
        word-break: break-word;
        white-space: normal;
        overflow-wrap: break-word;
    }

    
    .evaluation-table .score-col {
        width: 10%; /* Lebar kolom score */
        text-align: center;
    }
    
    .evaluation-table .notes-col {
        width: 30%; /* Lebar kolom catatan */
        word-break: break-word;
    }
    
    .evaluation-table td {
        padding: 10px;
        border: 1px solid #ddd;
        vertical-align: top;
        overflow-wrap: break-word; /* Untuk modern browsers */
    }
    
    /* Style khusus untuk textarea */
    .evaluation-table textarea {
        width: 100%;
        min-height: 80px;
        max-height: 150px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical; /* Memungkinkan resize vertikal saja */
    }

    @media (max-width: 768px) {
        .header-table td {
            display: block;
            width: 100%;
        }

        .evaluation-table th,
        .evaluation-table td {
            font-size: 13px;
        }

        .score-options {
            flex-wrap: wrap;
            justify-content: flex-start;
        }
    }
</style>


<div class="container-fluid">
    <div class="summary-container">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; width: 25%; border-radius: 0 !important;">Nama Kandidat</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; width: 25%; border-radius: 0 !important;">: <?= htmlspecialchars($interview['nama_lengkap']) ?></td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; width: 25%; border-radius: 0 !important;">Diselesaikan oleh</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; width: 25%; border-radius: 0 !important;">: <?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?> (Human Resource)</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">Domisili</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">: <?= htmlspecialchars($interview['domisili']) ?></td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">Pewawancara</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">: <?= !empty($interview['interviewer']) ? htmlspecialchars($interview['interviewer']) : '' ?></td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">Posisi</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">: <?= htmlspecialchars($interview['posisi_dilamar']) ?></td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">Tgl/Jam</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">: <?= !empty($interview['interview_datetime']) ? date('d-m-Y (H.i \W\I\B)', strtotime($interview['interview_datetime'])) : '' ?></td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">Testcase 1</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">
                    : <?php if (!empty($interview['link_testcase'])): ?>
                        <a href="<?= htmlspecialchars($interview['link_testcase']) ?>" target="_blank">📄 Buka Testcase</a>
                    <?php endif; ?>
                </td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">CV/ Portofolio</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left !important; border-radius: 0 !important;">
                    : <?php if (!empty($interview['cv_portofolio'])): ?>
                        <a href="<?= htmlspecialchars($interview['cv_portofolio']) ?>" target="_blank">📎 Buka Portofolio</a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>


        

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="success-message">
                Data interview summary berhasil disimpan!
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url() ?>interview/summary/<?= $interview['id'] ?>">
            <input type="hidden" name="interview_id" value="<?= $interview['id'] ?>">
            
            <?php 
            $current_category = null;
            foreach ($questions as $question): 
                if ($current_category != $question['category_id']):
                    $current_category = $question['category_id'];
            ?>
                <div class="section-title"><?= htmlspecialchars($question['category_name']) ?></div>
                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th class="criteria-col">Kriteria</th>
                            <th class="score-col">Score (1-4)</th>
                            <th class="notes-col">Penilaian / Hasil Pengamatan</th>
                        </tr>
                    </thead>
                    <tbody>
            <?php endif; ?>

                        <tr>
                            <td>
                                <?= nl2br(htmlspecialchars($question['question_text'])) ?>
                            </td>
                            <td>
                                <?php if ($question['question_type'] == 'score'): ?>
                                    <div class="score-options">
                                        <?php 
                                        $score_range = explode('-', $question['score_range']);
                                        $min_score = $score_range[0] ?? 1;
                                        $max_score = $score_range[1] ?? 4;
                                        
                                        $answer = $answers[$question['id']] ?? null;
                                        for ($i = $min_score; $i <= $max_score; $i++): 
                                        ?>
                                            <div class="score-option">
                                                <input type="radio" name="scores[<?= $question['id'] ?>]" 
                                                       value="<?= $i ?>" 
                                                       id="score_<?= $question['id'] ?>_<?= $i ?>"
                                                       <?= ($answer && $answer['score'] == $i) ? 'checked' : '' ?>>
                                                <label for="score_<?= $question['id'] ?>_<?= $i ?>"><?= $i ?></label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <textarea name="notes[<?= $question['id'] ?>]" placeholder="Catatan pengamatan..."><?= !empty($answers[$question['id']]['notes']) ? htmlspecialchars($answers[$question['id']]['notes']) : '' ?></textarea>
                            </td>
                        </tr>

            <?php 
                $next_index = array_search($question, $questions) + 1;
                if ($next_index >= count($questions) || $questions[$next_index]['category_id'] != $current_category):
            ?>
                    </tbody>
                </table>
            <?php 
                endif;
            endforeach; 
            ?>

            <div class="form-footer">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Kembali</button>
                <button type="submit" class="btn btn-primary">Simpan Evaluasi</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('textarea').each(function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    }).on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    $('form').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            interview_id: $('input[name="interview_id"]').val(),
            interviewer: $('input[name="interviewer"]').val(),
            interview_datetime: $('input[name="interview_datetime"]').val(),
            portfolio_link: $('input[name="portfolio_link"]').val(),
            questions: {},
            scores: {},
            notes: {}
        };

        $('input[name^="questions["]').each(function() {
            const questionId = $(this).attr('name').match(/\[(\d+)\]/)[1];
            formData.questions[questionId] = $(this).val();
        });

        $('input[name^="scores["]:checked').each(function() {
            const questionId = $(this).attr('name').match(/\[(\d+)\]/)[1];
            formData.scores[questionId] = $(this).val();
        });

        $('textarea[name^="notes["]').each(function() {
            const questionId = $(this).attr('name').match(/\[(\d+)\]/)[1];
            formData.notes[questionId] = $(this).val();
        });

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = window.location.href + '?success=1';
                } else {
                    alert('Gagal menyimpan: ' + response.message);
                    submitBtn.prop('disabled', false).text('Simpan Evaluasi');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data');
                submitBtn.prop('disabled', false).text('Simpan Evaluasi');
            }
        });
    });
});
</script>