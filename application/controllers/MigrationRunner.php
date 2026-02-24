<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MigrationRunner extends CI_Controller
{
    private $allowed_ips = array('127.0.0.1', '::1');
    private $endorse_logs_fix_migration_version = 20260224103000;
    private $endorse_logs_fix_lock_name = 'migration_runner_fix_endorse_logs';

    public function __construct()
    {
        parent::__construct();
    }

    public function latest()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->loadMigrationLibrary();

        if ($this->migration->latest() === FALSE) {
            $error = $this->migration->error_string();
            if ($this->isEndorseLogsFixMigrationError($error)) {
                $repair = $this->runEndorseLogsFix(2000, 300, TRUE);
                if (!empty($repair['success'])) {
                    $this->markMigrationAsApplied($this->endorse_logs_fix_migration_version);
                    $this->loadMigrationLibrary();

                    if ($this->migration->latest() === FALSE) {
                        $this->jsonError(500, array(
                            'success' => false,
                            'error' => $this->migration->error_string(),
                            'repair' => $repair,
                        ));
                        return;
                    }

                    $this->jsonOk(array(
                        'success' => true,
                        'message' => 'Migrations applied with endorse_logs repair fallback.',
                        'repair' => $repair,
                    ));
                    return;
                }

                $this->jsonError(500, array(
                    'success' => false,
                    'error' => 'Endorse logs repair did not complete. Re-run: php index.php MigrationRunner fix_endorse_logs',
                    'migration_error' => $error,
                    'repair' => $repair,
                ));
                return;
            }

            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => $error,
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Migrations applied.',
            )));
    }

    // CLI: php index.php MigrationRunner fix_endorse_logs [chunk] [maxSeconds]
    public function fix_endorse_logs($chunk = 2000, $maxSeconds = 300)
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        if (!is_cli()) {
            $this->jsonError(400, array(
                'success' => false,
                'error' => 'This command is CLI-only.',
            ));
            return;
        }

        $chunk = max(100, min(20000, (int) $chunk));
        $maxSeconds = max(30, min(3600, (int) $maxSeconds));

        $repair = $this->runEndorseLogsFix($chunk, $maxSeconds, TRUE);
        if (!empty($repair['success'])) {
            $this->markMigrationAsApplied($this->endorse_logs_fix_migration_version);
        }

        $status = !empty($repair['success']) ? 200 : 500;
        if (!empty($repair['partial'])) {
            $status = 202;
        }

        $payload = array(
            'success' => !empty($repair['success']),
            'partial' => !empty($repair['partial']),
            'message' => !empty($repair['success'])
                ? 'Endorse logs fix completed.'
                : 'Endorse logs fix not completed yet. Re-run this command.',
            'repair' => $repair,
            'rerun_command' => 'php index.php MigrationRunner fix_endorse_logs ' . $chunk . ' ' . $maxSeconds,
        );

        if ($status >= 400) {
            $this->jsonError($status, $payload);
            return;
        }
        $this->jsonOk($payload, $status);
    }

    private function runEndorseLogsFix($chunk, $maxSeconds, $tryFinalizeUnique)
    {
        $started = time();
        $result = array(
            'success' => false,
            'partial' => false,
            'chunk' => (int) $chunk,
            'max_seconds' => (int) $maxSeconds,
            'updated_rows' => 0,
            'deleted_rows' => 0,
            'index_created' => false,
            'unique_index_created' => false,
            'errors' => array(),
        );

        if (!$this->db->table_exists('endorse_logs')) {
            $result['success'] = true;
            return $result;
        }

        $lock = $this->db->query(
            "SELECT GET_LOCK(" . $this->db->escape($this->endorse_logs_fix_lock_name) . ", 0) AS lock_status"
        )->row_array();
        $hasLock = !empty($lock) && (int) $lock['lock_status'] === 1;
        if (!$hasLock) {
            $result['errors'][] = 'Another endorse_logs fix process is already running.';
            return $result;
        }

        try {
            if (!$this->indexExists('endorse_logs', 'idx_endorse_logs_endorse_date')) {
                $indexOk = $this->db->query(
                    "ALTER TABLE endorse_logs ADD INDEX idx_endorse_logs_endorse_date (`id_endorse`, `date`)"
                );
                if ($indexOk) {
                    $result['index_created'] = true;
                } else {
                    $dbErr = $this->db->error();
                    if (!empty($dbErr['message'])) {
                        $result['errors'][] = 'Add index failed: ' . $dbErr['message'];
                    }
                }
            }

            while (true) {
                $updated = $this->fixInvalidViewsBatch($chunk);
                $deleted = $this->deleteDuplicateLogsBatch($chunk);

                $result['updated_rows'] += (int) $updated;
                $result['deleted_rows'] += (int) $deleted;

                if ($updated === 0 && $deleted === 0) {
                    break;
                }

                if ((time() - $started) >= $maxSeconds) {
                    $result['partial'] = true;
                    break;
                }
            }

            if (empty($result['partial']) && $tryFinalizeUnique) {
                if (!$this->indexExists('endorse_logs', 'uniq_endorse_logs_endorse_date')) {
                    $uniqueOk = $this->db->query(
                        "ALTER TABLE endorse_logs ADD UNIQUE INDEX uniq_endorse_logs_endorse_date (`id_endorse`, `date`)"
                    );
                    if ($uniqueOk) {
                        $result['unique_index_created'] = true;
                    } else {
                        $dbErr = $this->db->error();
                        if (!empty($dbErr['message'])) {
                            $result['errors'][] = 'Add unique index failed: ' . $dbErr['message'];
                        }
                        $result['partial'] = true;
                    }
                } else {
                    $result['unique_index_created'] = true;
                }
            }

            if (empty($result['partial']) && !$this->hasInvalidViewsRows() && !$this->hasDuplicateLogsRows()) {
                $result['success'] = true;
            } else if (empty($result['partial']) && !empty($tryFinalizeUnique) && $this->indexExists('endorse_logs', 'uniq_endorse_logs_endorse_date')) {
                $result['success'] = true;
            }
        } finally {
            $this->db->query(
                "SELECT RELEASE_LOCK(" . $this->db->escape($this->endorse_logs_fix_lock_name) . ")"
            );
        }

        return $result;
    }

    private function fixInvalidViewsBatch($chunk)
    {
        $chunk = (int) $chunk;
        $sql = "
            UPDATE endorse_logs
            SET
                views_after = GREATEST(COALESCE(views_after, 0), COALESCE(views_before, 0)),
                views = GREATEST(COALESCE(views_after, 0), COALESCE(views_before, 0)) - COALESCE(views_before, 0),
                cpm_after = CASE
                    WHEN COALESCE(total_cost, 0) > 0 AND GREATEST(COALESCE(views_after, 0), COALESCE(views_before, 0)) > 0
                        THEN (COALESCE(total_cost, 0) / GREATEST(COALESCE(views_after, 0), COALESCE(views_before, 0))) * 1000
                    ELSE 0
                END,
                cpm = CASE
                    WHEN COALESCE(total_cost, 0) > 0 AND (GREATEST(COALESCE(views_after, 0), COALESCE(views_before, 0)) - COALESCE(views_before, 0)) > 0
                        THEN (COALESCE(total_cost, 0) / (GREATEST(COALESCE(views_after, 0), COALESCE(views_before, 0)) - COALESCE(views_before, 0))) * 1000
                    ELSE 0
                END
            WHERE COALESCE(views_after, 0) < COALESCE(views_before, 0) OR COALESCE(views, 0) < 0
            LIMIT $chunk
        ";
        $ok = $this->db->query($sql);
        if (!$ok) {
            return 0;
        }
        return (int) $this->db->affected_rows();
    }

    private function deleteDuplicateLogsBatch($chunk)
    {
        $chunk = (int) $chunk;
        $sql = "
            DELETE FROM endorse_logs
            WHERE id IN (
                SELECT id FROM (
                    SELECT l1.id
                    FROM endorse_logs l1
                    INNER JOIN endorse_logs l2
                        ON l1.id_endorse = l2.id_endorse
                        AND l1.`date` = l2.`date`
                        AND l1.id < l2.id
                    LIMIT $chunk
                ) d
            )
        ";
        $ok = $this->db->query($sql);
        if (!$ok) {
            return 0;
        }
        return (int) $this->db->affected_rows();
    }

    private function hasInvalidViewsRows()
    {
        $row = $this->db->query("
            SELECT 1
            FROM endorse_logs
            WHERE COALESCE(views_after, 0) < COALESCE(views_before, 0) OR COALESCE(views, 0) < 0
            LIMIT 1
        ")->row_array();
        return !empty($row);
    }

    private function hasDuplicateLogsRows()
    {
        $row = $this->db->query("
            SELECT 1
            FROM endorse_logs l1
            INNER JOIN endorse_logs l2
                ON l1.id_endorse = l2.id_endorse
                AND l1.`date` = l2.`date`
                AND l1.id < l2.id
            LIMIT 1
        ")->row_array();
        return !empty($row);
    }

    private function markMigrationAsApplied($version)
    {
        $version = (int) $version;
        if (!$this->db->table_exists('migrations')) {
            return;
        }

        $rows = $this->db->query("SELECT version FROM migrations")->result_array();
        if (empty($rows)) {
            $this->db->insert('migrations', array('version' => $version));
            return;
        }

        $this->db->query("UPDATE migrations SET version = GREATEST(version, " . $version . ")");
    }

    private function indexExists($table, $indexName)
    {
        $table = $this->db->escape($table);
        $indexName = $this->db->escape($indexName);
        $row = $this->db->query("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = $table
              AND index_name = $indexName
            LIMIT 1
        ")->row_array();
        return !empty($row);
    }

    private function isEndorseLogsFixMigrationError($error)
    {
        if (!$error) {
            return false;
        }
        $needleA = '20260224103000_fix_endorse_logs_views_and_uniqueness';
        $needleB = 'idx_endorse_logs_endorse_date';
        return (stripos($error, $needleA) !== false) || (stripos($error, $needleB) !== false);
    }

    private function loadMigrationLibrary()
    {
        $this->config->load('migration');
        $this->load->library('migration', array(
            'migration_enabled' => TRUE,
            'migration_type' => $this->config->item('migration_type'),
            'migration_path' => $this->config->item('migration_path'),
            'migration_table' => $this->config->item('migration_table'),
            'migration_auto_latest' => $this->config->item('migration_auto_latest'),
            'migration_version' => $this->config->item('migration_version'),
        ));
    }

    private function jsonOk($payload, $status = 200)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function jsonError($status, $payload)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function ensure_allowed()
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            show_404();
            return false;
        }

        if (is_cli()) {
            return true;
        }

        if ($this->config->item('migration_allow_remote') === TRUE) {
            return true;
        }

        $ip = $this->input->ip_address();
        if (!in_array($ip, $this->allowed_ips, true)) {
            show_404();
            return false;
        }

        return true;
    }
}
