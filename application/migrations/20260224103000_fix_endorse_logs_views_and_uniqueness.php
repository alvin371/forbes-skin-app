<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Fix_endorse_logs_views_and_uniqueness extends CI_Migration
{
    public function up()
    {
        if (!$this->table_exists('endorse_logs')) {
            return;
        }

        if (!$this->index_exists('endorse_logs', 'idx_endorse_logs_endorse_date')) {
            $this->db->query("ALTER TABLE endorse_logs ADD INDEX idx_endorse_logs_endorse_date (`id_endorse`, `date`)");
        }

        $this->repair_declining_views_in_chunks(1000);
        $this->dedupe_logs_in_chunks(500);

        // Keep endorse.views aligned to latest non-decreasing snapshot.
        if ($this->table_exists('endorse')) {
            $this->db->query("
                UPDATE endorse e
                INNER JOIN (
                    SELECT l.id_endorse, l.views_after
                    FROM endorse_logs l
                    INNER JOIN (
                        SELECT id_endorse, MAX(id) AS max_id
                        FROM endorse_logs
                        GROUP BY id_endorse
                    ) latest
                        ON latest.id_endorse = l.id_endorse
                        AND latest.max_id = l.id
                ) v ON v.id_endorse = e.id
                SET e.views = GREATEST(COALESCE(e.views, 0), COALESCE(v.views_after, 0))
            ");
        }

        if (!$this->index_exists('endorse_logs', 'uniq_endorse_logs_endorse_date')) {
            $this->db->query("ALTER TABLE endorse_logs ADD UNIQUE INDEX uniq_endorse_logs_endorse_date (`id_endorse`, `date`)");
        }
    }

    public function down()
    {
        if ($this->table_exists('endorse_logs') && $this->index_exists('endorse_logs', 'uniq_endorse_logs_endorse_date')) {
            $this->db->query("ALTER TABLE endorse_logs DROP INDEX uniq_endorse_logs_endorse_date");
        }
    }

    private function table_exists($table)
    {
        return $this->db->table_exists($table);
    }

    private function index_exists($table, $index_name)
    {
        $table = $this->db->escape($table);
        $index_name = $this->db->escape($index_name);
        $row = $this->db->query("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = $table
              AND INDEX_NAME = $index_name
            LIMIT 1
        ")->row_array();
        return !empty($row);
    }

    private function repair_declining_views_in_chunks($chunk_size)
    {
        while (true) {
            $rows = $this->db->query("
                SELECT id, COALESCE(total_cost, 0) AS total_cost, COALESCE(views_before, 0) AS views_before, COALESCE(views_after, 0) AS views_after
                FROM endorse_logs
                WHERE COALESCE(views_after, 0) < COALESCE(views_before, 0) OR COALESCE(views, 0) < 0
                LIMIT " . intval($chunk_size)
            )->result_array();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $views_before = (int) $row['views_before'];
                $views_after = max($views_before, (int) $row['views_after']);
                $views_delta = max(0, $views_after - $views_before);
                $total_cost = (float) $row['total_cost'];

                $cpm_after = ($total_cost > 0 && $views_after > 0) ? (($total_cost / $views_after) * 1000) : 0;
                $cpm = ($total_cost > 0 && $views_delta > 0) ? (($total_cost / $views_delta) * 1000) : 0;

                $this->db->update('endorse_logs', array(
                    'views_after' => $views_after,
                    'views' => $views_delta,
                    'cpm_after' => $cpm_after,
                    'cpm' => $cpm,
                ), array('id' => (int) $row['id']));
            }
        }
    }

    private function dedupe_logs_in_chunks($chunk_size)
    {
        while (true) {
            $groups = $this->db->query("
                SELECT id_endorse, date, MAX(id) AS keep_id
                FROM endorse_logs
                GROUP BY id_endorse, date
                HAVING COUNT(*) > 1
                LIMIT " . intval($chunk_size)
            )->result_array();

            if (empty($groups)) {
                break;
            }

            foreach ($groups as $group) {
                $this->db->query(
                    "DELETE FROM endorse_logs WHERE id_endorse = ? AND date = ? AND id <> ?",
                    array($group['id_endorse'], $group['date'], $group['keep_id'])
                );
            }
        }
    }
}
