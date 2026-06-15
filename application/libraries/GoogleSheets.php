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

        // Clear everything in the tab first (A:AZ covers the extended ~32-column layout).
        $clear = new \Google\Service\Sheets\ClearValuesRequest();
        $this->service->spreadsheets_values->clear($spreadsheetId, $quoted . '!A:AZ', $clear);

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

    /** Resolve a tab's numeric sheetId (needed for batchUpdate grid requests). Null if absent. */
    public function getSheetId(string $spreadsheetId, string $tabName): ?int
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $tabName) {
                return intval($sheet->getProperties()->getSheetId());
            }
        }
        return null;
    }

    /**
     * Read the header-row (row 1) cell formats of a source tab, per column. Returns a list of
     * \Google\Service\Sheets\CellFormat indexed by column (0-based). Empty if tab/data absent.
     */
    public function readHeaderFormats(string $spreadsheetId, string $sourceTab): array
    {
        $quoted = "'" . str_replace("'", "''", $sourceTab) . "'";
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId, [
            'ranges'          => [$quoted . '!1:1'],
            'includeGridData' => true,
        ]);

        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() !== $sourceTab) {
                continue;
            }
            $grid = $sheet->getData();
            if (empty($grid) || empty($grid[0]->getRowData())) {
                return [];
            }
            $rowData = $grid[0]->getRowData();
            $values = $rowData[0]->getValues();
            if (empty($values)) {
                return [];
            }
            $formats = [];
            foreach ($values as $idx => $cell) {
                $fmt = $cell->getUserEnteredFormat();
                if ($fmt !== null) {
                    $formats[$idx] = $fmt;
                }
            }
            return $formats;
        }
        return [];
    }

    /**
     * Apply formatting to an app-owned tab: paint the header row to mirror $headerFormats
     * (column-aligned; columns beyond the source reuse the last source format as a default),
     * enable text wrap, and auto-size every column to full width.
     *
     * @param array $headerFormats list of CellFormat indexed by column (from readHeaderFormats).
     * @param int   $numCols       total column count of the target layout.
     */
    public function applyTabFormatting(string $spreadsheetId, string $tabName, array $headerFormats, int $numCols): void
    {
        $sheetId = $this->getSheetId($spreadsheetId, $tabName);
        if ($sheetId === null || $numCols <= 0) {
            return;
        }

        $requests = [];
        $headerMask = 'userEnteredFormat(backgroundColor,textFormat,horizontalAlignment,verticalAlignment,wrapStrategy)';
        $defaultFmt = !empty($headerFormats) ? end($headerFormats) : null;

        for ($col = 0; $col < $numCols; $col++) {
            $fmt = $headerFormats[$col] ?? $defaultFmt;
            if ($fmt === null) {
                continue; // no source formatting available — leave as-is
            }
            $requests[] = new \Google\Service\Sheets\Request([
                'repeatCell' => [
                    'range' => [
                        'sheetId'          => $sheetId,
                        'startRowIndex'    => 0,
                        'endRowIndex'      => 1,
                        'startColumnIndex' => $col,
                        'endColumnIndex'   => $col + 1,
                    ],
                    'cell'   => ['userEnteredFormat' => $fmt],
                    'fields' => $headerMask,
                ],
            ]);
        }

        // Wrap text across the whole used range so long links/keywords stay readable.
        $requests[] = new \Google\Service\Sheets\Request([
            'repeatCell' => [
                'range'  => ['sheetId' => $sheetId, 'startColumnIndex' => 0, 'endColumnIndex' => $numCols],
                'cell'   => ['userEnteredFormat' => ['wrapStrategy' => 'WRAP']],
                'fields' => 'userEnteredFormat.wrapStrategy',
            ],
        ]);

        // Auto-size every column to its content (full width per request).
        $requests[] = new \Google\Service\Sheets\Request([
            'autoResizeDimensions' => [
                'dimensions' => [
                    'sheetId'    => $sheetId,
                    'dimension'  => 'COLUMNS',
                    'startIndex' => 0,
                    'endIndex'   => $numCols,
                ],
            ],
        ]);

        $batch = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest();
        $batch->setRequests($requests);
        $this->service->spreadsheets->batchUpdate($spreadsheetId, $batch);
    }
}
