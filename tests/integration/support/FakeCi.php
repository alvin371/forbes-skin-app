<?php
/**
 * Minimal mysqli-backed CodeIgniter stand-in so integration tests can exercise the REAL
 * Endorse_sync::apply() against the REAL endorse/endorse_logs schema — not a reimplementation.
 * Implements exactly the CI surface apply()/load_prev_stats use: db->{field_exists,update,
 * insert}, mymodel->selectWithQuery(), template->{extract_tiktok_content_id,detect...}.
 */

final class FakeDb
{
    public mysqli $m;
    private array $cols = [];
    private array $whereStack = [];
    private int $affected = 0;
    private bool $transStatus = true;
    private ?string $crashBeforeTable = null;

    public function __construct(mysqli $m)
    {
        $this->m = $m;
    }

    /** Test seam: throw the first time a write targets $table, simulating a mid-apply crash. */
    public function crashBeforeWriteTo(?string $table): void
    {
        $this->crashBeforeTable = $table;
    }

    private function maybeCrash(string $table): void
    {
        if ($this->crashBeforeTable !== null && $table === $this->crashBeforeTable) {
            $this->crashBeforeTable = null; // one-shot
            throw new RuntimeException("simulated crash before writing $table");
        }
    }

    public function trans_begin(): bool
    {
        $this->transStatus = true;
        return $this->m->begin_transaction();
    }

    public function trans_commit(): bool
    {
        return $this->m->commit();
    }

    public function trans_rollback(): bool
    {
        return $this->m->rollback();
    }

    public function trans_status(): bool
    {
        return $this->transStatus;
    }

    public function delete(string $table, array $where): bool
    {
        $this->maybeCrash($table);
        $w = [];
        foreach ($where as $k => $v) {
            $w[] = "`$k`=" . $this->val($v);
        }
        $sql = "DELETE FROM `$table`" . (empty($w) ? '' : ' WHERE ' . implode(' AND ', $w));
        $ok = $this->m->query($sql) !== false;
        $this->affected = $ok ? $this->m->affected_rows : 0;
        $this->transStatus = $this->transStatus && $ok;
        return $ok;
    }

    public function field_exists(string $col, string $table): bool
    {
        if (!isset($this->cols[$table])) {
            $this->cols[$table] = [];
            $r = $this->m->query("SHOW COLUMNS FROM `$table`");
            while ($r && $row = $r->fetch_assoc()) {
                $this->cols[$table][$row['Field']] = true;
            }
        }
        return isset($this->cols[$table][$col]);
    }

    private function val($v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        return "'" . $this->m->real_escape_string((string) $v) . "'";
    }

    /** CI-compatible escape (adds quotes). */
    public function escape($v): string
    {
        return $this->val($v);
    }

    public function affected_rows(): int
    {
        return $this->affected;
    }

    /** Raw query passthrough (used by resetStuck / reservation store). */
    public function query(string $sql)
    {
        $r = $this->m->query($sql);
        $this->affected = $this->m->affected_rows;
        $this->transStatus = $this->transStatus && ($r !== false);
        return new FakeResult($r);
    }

    /**
     * CI query-builder where(). $value === null with a raw condition string ($escape=false)
     * pushes the condition verbatim; otherwise it's a `col`=escaped(value) equality.
     */
    public function where($key, $value = null, $escape = true)
    {
        if ($value === null && $escape === false) {
            $this->whereStack[] = '(' . $key . ')';
        } else {
            $this->whereStack[] = "`$key`=" . $this->val($value);
        }
        return $this;
    }

    public function update(string $table, array $row, ?array $where = null): bool
    {
        $this->maybeCrash($table);
        $set = [];
        foreach ($row as $k => $v) {
            $set[] = "`$k`=" . $this->val($v);
        }
        $w = $this->whereStack;
        $this->whereStack = [];
        if (is_array($where)) {
            foreach ($where as $k => $v) {
                $w[] = "`$k`=" . $this->val($v);
            }
        }
        $sql = "UPDATE `$table` SET " . implode(',', $set) . " WHERE " . implode(' AND ', $w);
        $ok = $this->m->query($sql) !== false;
        $this->affected = $ok ? $this->m->affected_rows : 0;
        $this->transStatus = $this->transStatus && $ok;
        return $ok;
    }

    public function insert(string $table, array $row): bool
    {
        $this->maybeCrash($table);
        $cols = array_map(fn ($k) => "`$k`", array_keys($row));
        $vals = array_map(fn ($v) => $this->val($v), array_values($row));
        $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $ok = $this->m->query($sql) !== false;
        $this->affected = $ok ? $this->m->affected_rows : 0;
        $this->transStatus = $this->transStatus && $ok;
        return $ok;
    }
}

final class FakeResult
{
    private $r;

    public function __construct($r)
    {
        $this->r = $r;
    }

    public function row()
    {
        return ($this->r && $this->r !== true) ? $this->r->fetch_object() : null;
    }

    public function result_array(): array
    {
        $out = [];
        if ($this->r && $this->r !== true) {
            while ($x = $this->r->fetch_assoc()) {
                $out[] = $x;
            }
        }
        return $out;
    }

    public function num_rows(): int
    {
        return ($this->r && $this->r !== true) ? $this->r->num_rows : 0;
    }
}

final class FakeMyModel
{
    private mysqli $m;

    public function __construct(mysqli $m)
    {
        $this->m = $m;
    }

    public function selectWithQuery(string $sql): array
    {
        $r = $this->m->query($sql);
        if ($r === false) {
            throw new RuntimeException('selectWithQuery failed: ' . $this->m->error . ' :: ' . $sql);
        }
        $out = [];
        while ($row = $r->fetch_assoc()) {
            $out[] = $row;
        }
        return $out;
    }
}

final class FakeTemplate
{
    public function extract_tiktok_content_id($url)
    {
        if (preg_match('/\/(?:video|photo)\/(\d+)/', (string) $url, $m)) {
            return $m[1];
        }
        return '';
    }

    public function detect_tiktok_media_type_from_url($url)
    {
        if (stripos((string) $url, '/photo/') !== false) {
            return 'photo';
        }
        if (stripos((string) $url, '/video/') !== false) {
            return 'video';
        }
        return '';
    }
}

final class FakeLoader
{
    public function database()
    {
    }

    public function model($n)
    {
    }

    public function library($n)
    {
    }
}

final class FakeCi
{
    public FakeDb $db;
    public FakeMyModel $mymodel;
    public FakeTemplate $template;
    public FakeLoader $load;
    /** @var mixed set to a real Endorse_sync after construction (avoids ctor recursion). */
    public $endorse_sync = null;

    public function __construct(mysqli $m)
    {
        $this->db = new FakeDb($m);
        $this->mymodel = new FakeMyModel($m);
        $this->template = new FakeTemplate();
        $this->load = new FakeLoader();
    }
}

// Endorse_sync's constructor calls get_instance(); provide the fake in test context.
if (! function_exists('env')) {
    function env($key, $default = null)
    {
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }
}

if (! function_exists('get_instance')) {
    $GLOBALS['__fake_ci'] = null;
    function &get_instance()
    {
        return $GLOBALS['__fake_ci'];
    }
}
