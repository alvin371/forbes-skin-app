<?php
/**
 * Google Sheets connection + template inspector.
 *
 * Standalone CLI (no CodeIgniter bootstrap) — parses .env directly like migrations/run.php.
 * Proves the app can reach the configured spreadsheet via a service account and DUMPS the
 * real template: every tab, its grid size, and its header row + first data rows. The output
 * is the basis for designing the sync column mapping.
 *
 * Usage:
 *   php tools/gsheet_test.php
 *
 * Prerequisites:
 *   - composer require google/apiclient (already installed)
 *   - A GCP service-account JSON key at GOOGLE_SHEETS_CREDENTIALS_PATH
 *   - The target spreadsheet shared with the service account's client_email (Viewer/Editor)
 */

if (PHP_SAPI !== 'cli') {
    exit("Run from command line only.\n");
}

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

// ---- Load .env manually (avoids BASEPATH/FCPATH dependency) ----
$envFile = $root . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $env[trim($name)] = trim(trim($value), "\"'");
    }
} else {
    fwrite(STDERR, "ERROR: .env not found at $envFile\n");
    exit(1);
}

function gs_env(array $env, string $key, string $default = ''): string
{
    return $env[$key] ?? (getenv($key) ?: $default);
}

function gs_fail(string $msg): void
{
    fwrite(STDERR, "\n[FAIL] $msg\n");
    exit(1);
}

$spreadsheetId = gs_env($env, 'GOOGLE_SHEETS_SPREADSHEET_ID');

echo "=== Google Sheets connection test ===\n";
echo "Spreadsheet : $spreadsheetId\n";

// Resolve credentials: base64 env var first (Docker/.env-only deploy), then key file.
$b64 = gs_env($env, 'GOOGLE_SHEETS_CREDENTIALS_B64', '');
$authConfig = null;
if ($b64 !== '') {
    // Strip whitespace and any chars outside the base64 alphabet (e.g. a trailing '%').
    $b64 = preg_replace('#[^A-Za-z0-9+/=]#', '', trim($b64));
    $json = base64_decode($b64, true);
    if ($json === false) {
        gs_fail('GOOGLE_SHEETS_CREDENTIALS_B64 is not valid base64.');
    }
    $authConfig = json_decode((string) $json, true);
    if (!is_array($authConfig)) {
        gs_fail('GOOGLE_SHEETS_CREDENTIALS_B64 did not decode to valid JSON.');
    }
    echo "Credentials : GOOGLE_SHEETS_CREDENTIALS_B64 (inline)\n";
} else {
    $credPathRaw = gs_env($env, 'GOOGLE_SHEETS_CREDENTIALS_PATH', 'application/config/google-sheets-sa.json');
    $credPath = ($credPathRaw !== '' && $credPathRaw[0] === '/') ? $credPathRaw : $root . '/' . $credPathRaw;
    echo "Credentials : $credPath\n";
    if (!file_exists($credPath)) {
        gs_fail("No credentials. Set GOOGLE_SHEETS_CREDENTIALS_B64 in .env, or place the SA key at: $credPath");
    }
    $authConfig = json_decode((string) file_get_contents($credPath), true);
    if (!is_array($authConfig)) {
        gs_fail("Key file is not valid JSON: $credPath");
    }
}
echo "\n";

if ($spreadsheetId === '') {
    gs_fail("GOOGLE_SHEETS_SPREADSHEET_ID is not set in .env");
}

// Surface the service-account email so the user knows whom to share the sheet with.
$saEmail = $authConfig['client_email'] ?? '(unknown — invalid key?)';
echo "Service account email (share the sheet with this as Editor):\n  $saEmail\n\n";

try {
    $client = new \Google\Client();
    $client->setApplicationName('Forbes Endorse Optimization');
    $client->setAuthConfig($authConfig);
    $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);

    $service = new \Google\Service\Sheets($client);

    // 1) Spreadsheet metadata: title + every tab.
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    $title = $spreadsheet->getProperties()->getTitle();
    echo "[OK] Connected. Spreadsheet title: \"$title\"\n";

    $sheetsList = $spreadsheet->getSheets();
    echo "Tabs (" . count($sheetsList) . "):\n";
    $tabNames = [];
    foreach ($sheetsList as $sheet) {
        $props = $sheet->getProperties();
        $grid = $props->getGridProperties();
        $tabNames[] = $props->getTitle();
        printf(
            "  - %-28s gid=%-12s %dx%d (rows x cols)\n",
            $props->getTitle(),
            $props->getSheetId(),
            $grid ? $grid->getRowCount() : 0,
            $grid ? $grid->getColumnCount() : 0
        );
    }
    echo "\n";

    // 2) For each tab, dump the header row + first ~5 data rows (real template).
    foreach ($tabNames as $tab) {
        echo "================ TAB: $tab ================\n";
        $range = "'" . str_replace("'", "''", $tab) . "'!1:6";
        try {
            $resp = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $resp->getValues() ?: [];
            if (empty($values)) {
                echo "  (empty)\n\n";
                continue;
            }
            foreach ($values as $r => $row) {
                $label = $r === 0 ? 'HEADER' : ('row ' . $r);
                $cells = array_map(function ($c) {
                    $c = (string) $c;
                    return $c === '' ? '·' : $c;
                }, $row);
                echo sprintf("  [%-6s] %s\n", $label, implode(' | ', $cells));
            }
            echo "\n";
        } catch (\Throwable $e) {
            echo "  (could not read values: " . $e->getMessage() . ")\n\n";
        }
    }

    echo "[PASS] Connection successful — real template printed above.\n";
} catch (\Google\Service\Exception $e) {
    $code = $e->getCode();
    if ($code === 403) {
        gs_fail("403 Permission denied. Share the spreadsheet with the service account email:\n"
            . "       $saEmail   (give it Editor access), and ensure the Google Sheets API is enabled.");
    }
    if ($code === 404) {
        gs_fail("404 Not found. Check GOOGLE_SHEETS_SPREADSHEET_ID — got: $spreadsheetId");
    }
    gs_fail("Google API error ($code): " . $e->getMessage());
} catch (\Throwable $e) {
    gs_fail($e->getMessage());
}
