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

    public function __construct(mysqli $m)
    {
        $this->m = $m;
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

    public function update(string $table, array $row, array $where): bool
    {
        $set = [];
        foreach ($row as $k => $v) {
            $set[] = "`$k`=" . $this->val($v);
        }
        $w = [];
        foreach ($where as $k => $v) {
            $w[] = "`$k`=" . $this->val($v);
        }
        $sql = "UPDATE `$table` SET " . implode(',', $set) . " WHERE " . implode(' AND ', $w);
        return $this->m->query($sql) !== false;
    }

    public function insert(string $table, array $row): bool
    {
        $cols = array_map(fn ($k) => "`$k`", array_keys($row));
        $vals = array_map(fn ($v) => $this->val($v), array_values($row));
        $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        return $this->m->query($sql) !== false;
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
