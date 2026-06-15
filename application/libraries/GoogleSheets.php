<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * GoogleSheets — thin service-account wrapper around the Google Sheets API.
 *
 * Auth is headless via a service-account JSON key (GOOGLE_SHEETS_CREDENTIALS_PATH), so it
 * works from both web requests and cron with no browser/OAuth session. Reused by the
 * on-demand sync button and the cron. The target spreadsheet must be shared with the
 * service account's client_email as Editor.
 */
class GoogleSheets
{
    /** @var \Google\Client */
    protected $client;

    /** @var \Google\Service\Sheets */
    protected $service;

    public function __construct()
    {
        require_once FCPATH . 'vendor/autoload.php';

        $CI = get_instance();
        $CI->load->helper('env');

        $this->client = new \Google\Client();
        $this->client->setApplicationName('Forbes Endorse Optimization');

        // Credentials resolve in priority order so secrets stay out of git:
        //  1) GOOGLE_SHEETS_CREDENTIALS_B64 — base64 of the SA JSON, inlined in .env
        //     (best for the Docker/.env-only deploy: no extra file to mount).
        //  2) GOOGLE_SHEETS_CREDENTIALS_PATH — path to the SA JSON key file.
        $b64 = env('GOOGLE_SHEETS_CREDENTIALS_B64', '');
        if ($b64 !== '') {
            // Strip whitespace and any chars outside the base64 alphabet — handles stray
            // shell artifacts like a trailing '%' (zsh no-newline marker) or wrapped lines.
            $b64 = preg_replace('#[^A-Za-z0-9+/=]#', '', trim($b64));
            $json = base64_decode($b64, true);
            if ($json === false) {
                throw new \RuntimeException('GOOGLE_SHEETS_CREDENTIALS_B64 is not valid base64.');
            }
            $config = json_decode($json, true);
            if (!is_array($config)) {
                throw new \RuntimeException('GOOGLE_SHEETS_CREDENTIALS_B64 did not decode to valid JSON.');
            }
            $this->client->setAuthConfig($config);
        } else {
            $credPath = env('GOOGLE_SHEETS_CREDENTIALS_PATH', 'application/config/google-sheets-sa.json');
            if ($credPath !== '' && $credPath[0] !== '/') {
                $credPath = FCPATH . $credPath;
            }
            if (!file_exists($credPath)) {
                throw new \RuntimeException('Google Sheets credentials not found. Set GOOGLE_SHEETS_CREDENTIALS_B64 in .env or place the key at: ' . $credPath);
            }
            $this->client->setAuthConfig($credPath);
        }

        $this->client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);

        $this->service = new \Google\Service\Sheets($this->client);
    }

    /** @return \Google\Service\Sheets */
    public function service()
    {
        return $this->service;
    }

    /**
     * Ensure a tab exists in the spreadsheet; create it via batchUpdate if missing.
     */
    public function ensureTab(string $spreadsheetId, string $tabName): void
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $tabName) {
                return;
            }
        }

        $props = new \Google\Service\Sheets\SheetProperties();
        $props->setTitle($tabName);

        $addSheet = new \Google\Service\Sheets\AddSheetRequest();
        $addSheet->setProperties($props);

        $request = new \Google\Service\Sheets\Request();
        $request->setAddSheet($addSheet);

        $batch = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest();
        $batch->setRequests([$request]);

        $this->service->spreadsheets->batchUpdate($spreadsheetId, $batch);
    }

    /**
     * Full-replace a tab: clear it, then write header + rows from A1.
     * Idempotent and drift-free for an app-owned tab.
     *
     * @param array  $header One header row.
     * @param array  $rows   List of rows (each a flat array of cell values).
     * @return int Number of data rows written.
     */
    public function replaceTab(string $spreadsheetId, string $tabName, array $header, array $rows): int
    {
        $this->ensureTab($spreadsheetId, $tabName);

        $quoted = "'" . str_replace("'", "''", $tabName) . "'";

        // Clear everything in the tab first (A:Z covers the 26-column layout).
        $clear = new \Google\Service\Sheets\ClearValuesRequest();
        $this->service->spreadsheets_values->clear($spreadsheetId, $quoted . '!A:Z', $clear);

        $values = array_merge([$header], $rows);
        $body = new \Google\Service\Sheets\ValueRange();
        $body->setValues($values);

        $this->service->spreadsheets_values->update(
            $spreadsheetId,
            $quoted . '!A1',
            $body,
            ['valueInputOption' => 'RAW']
        );

        return count($rows);
    }
}
