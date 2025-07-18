<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_Sheet;
use Google_Service_Sheets_SheetProperties;
use Google_Service_Drive;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Exception;

class ExportTimeEntriesToGoogleSheet extends Command
{
    protected $signature = 'timeentry:export';
    protected $description = 'Export TimeEntry records to Google Sheets with summaries and charts';

    public function handle()
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->day <= 15 ? $start->copy()->day(15)->endOfDay() : $start->copy()->endOfMonth();

        $entries = TimeEntry::with(['user', 'project'])
            ->whereBetween('start', [$start, $end])
            ->get();

        if ($entries->isEmpty()) {
            $this->info('No time entries found for export.');
            return 0;
        }

        $sheetTitle = 'Detailed '.$now->format('M d, Y');
        $values = [['User', 'Project', 'Date', 'Hours', 'Description']];
        $summaryByUserDate = [];
        $summaryByProject = [];

        foreach ($entries as $entry) {
            $duration = $entry->start->diffInMinutes($entry->end);
            $hours = round($duration / 60, 2);

            $values[] = [
                $entry->user->name ?? 'N/A',
                $entry->project->name ?? 'N/A',
                $entry->start->format('Y-m-d'),
                $hours,
                $entry->description,
            ];

            // Summary by user + date
            $key = $entry->user->name . '|' . $entry->start->format('Y-m-d');
            $summaryByUserDate[$key] = ($summaryByUserDate[$key] ?? 0) + $hours;

            // Summary by project
            $project = $entry->project->name ?? 'N/A';
            $summaryByProject[$project] = ($summaryByProject[$project] ?? 0) + $hours;
        }

        // Google Sheets setup
        $client = new Google_Client();
        $client->setAuthConfig(config('google.credentials_path') ?? env('GOOGLE_APPLICATION_CREDENTIALS'));
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);
        $client->addScope(Google_Service_Drive::DRIVE);

        $sheetsService = new Google_Service_Sheets($client);
        $spreadsheetId = env('GOOGLE_SHEET_ID');

        if (!$spreadsheetId) {
            $spreadsheet = new Google_Service_Sheets_Spreadsheet([
                'properties' => ['title' => 'TimeEntries ' . now()->format('Y-m-d H:i:s')],
                'sheets' => [['properties' => ['title' => $sheetTitle]]]
            ]);
            $createdSpreadsheet = $sheetsService->spreadsheets->create($spreadsheet);
            $spreadsheetId = $createdSpreadsheet->spreadsheetId;
            $this->info("Created new spreadsheet: $spreadsheetId");
        } else {
            try {
                $sheetsService->spreadsheets->batchUpdate($spreadsheetId, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                    'requests' => [[ 'addSheet' => ['properties' => ['title' => $sheetTitle]] ]]
                ]));
            } catch (Exception $e) {
                $this->warn("Sheet already exists or cannot be created: {$e->getMessage()}");
            }
        }

        // Insert main data
        $sheetsService->spreadsheets_values->update(
            $spreadsheetId,
            "$sheetTitle!A1",
            new Google_Service_Sheets_ValueRange(['values' => $values]),
            ['valueInputOption' => 'RAW']
        );

        // === Summary Sheet ===
        $summaryTitle = 'Summary '.$now->format('M d, Y');
        try {
            $sheetsService->spreadsheets->batchUpdate($spreadsheetId, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => [[ 'addSheet' => ['properties' => ['title' => $summaryTitle]] ]]
            ]));
        } catch (Exception $e) {
            $this->warn("Summary sheet exists or cannot be created: {$e->getMessage()}");
        }

        // Prepare summary rows
        $userDayHeaders = ['User', 'Date', 'Total Hours'];
        $userDayRows = [$userDayHeaders];

        $userDayGroup = [];
        foreach ($summaryByUserDate as $key => $hours) {
            [$user, $date] = explode('|', $key);
            $userDayRows[] = [$user, $date, $hours];
            $userDayGroup[$user][] = $hours;
        }

        $avgUserHeaders = ['User', 'Average Hours per Day'];
        $avgUserRows = [$avgUserHeaders];
        foreach ($userDayGroup as $user => $hoursArr) {
            $avg = round(array_sum($hoursArr) / count($hoursArr), 2);
            $avgUserRows[] = [$user, $avg];
        }

        $projectHeaders = ['Project', 'Total Hours'];
        $projectRows = [$projectHeaders];
        foreach ($summaryByProject as $project => $hours) {
            $projectRows[] = [$project, $hours];
        }

        // Write to Summary Sheet
        $sheetsService->spreadsheets_values->update(
            $spreadsheetId,
            "$summaryTitle!A1",
            new Google_Service_Sheets_ValueRange(['values' => array_merge($userDayRows, [['']], $avgUserRows, [['']], $projectRows)]),
            ['valueInputOption' => 'RAW']
        );

        // Get Sheet ID for chart injection
        $spreadsheet = $sheetsService->spreadsheets->get($spreadsheetId);
        $sheetId = null;
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $summaryTitle) {
                $sheetId = $sheet->getProperties()->getSheetId();
                break;
            }
        }

        if ($sheetId) {
            $rowCount = count($avgUserRows);
            $projectCount = count($projectRows);

            $requests = [
                [ // Chart 1: Avg hours per user
                    'addChart' => [
                        'chart' => [
                            'spec' => [
                                'title' => 'Avg Hours/Day per User',
                                'basicChart' => [
                                    'chartType' => 'COLUMN',
                                    'legendPosition' => 'BOTTOM_LEGEND',
                                    'axis' => [
                                        ['position' => 'BOTTOM_AXIS', 'title' => 'User'],
                                        ['position' => 'LEFT_AXIS', 'title' => 'Hours']
                                    ],
                                    'domains' => [[
                                        'domain' => [
                                            'sourceRange' => ['sources' => [[
                                                'sheetId' => $sheetId,
                                                'startRowIndex' => count($userDayRows)+1,
                                                'endRowIndex' => count($userDayRows)+$rowCount,
                                                'startColumnIndex' => 0,
                                                'endColumnIndex' => 1,
                                            ]]]
                                        ]
                                    ]],
                                    'series' => [[
                                        'series' => [
                                            'sourceRange' => ['sources' => [[
                                                'sheetId' => $sheetId,
                                                'startRowIndex' => count($userDayRows)+1,
                                                'endRowIndex' => count($userDayRows)+$rowCount,
                                                'startColumnIndex' => 1,
                                                'endColumnIndex' => 2,
                                            ]]]
                                        ],
                                        'targetAxis' => 'LEFT_AXIS'
                                    ]]
                                ]
                            ],
                            'position' => [
                                'overlayPosition' => [
                                    'anchorCell' => [
                                        'sheetId' => $sheetId,
                                        'rowIndex' => 1,
                                        'columnIndex' => 5
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [ // Chart 2: Total hours per project
                    'addChart' => [
                        'chart' => [
                            'spec' => [
                                'title' => 'Total Hours per Project',
                                'basicChart' => [
                                    'chartType' => 'COLUMN',
                                    'legendPosition' => 'BOTTOM_LEGEND',
                                    'axis' => [
                                        ['position' => 'BOTTOM_AXIS', 'title' => 'Project'],
                                        ['position' => 'LEFT_AXIS', 'title' => 'Hours']
                                    ],
                                    'domains' => [[
                                        'domain' => [
                                            'sourceRange' => ['sources' => [[
                                                'sheetId' => $sheetId,
                                                'startRowIndex' => count($userDayRows)+count($avgUserRows)+3,
                                                'endRowIndex' => count($userDayRows)+count($avgUserRows)+3+$projectCount,
                                                'startColumnIndex' => 0,
                                                'endColumnIndex' => 1
                                            ]]]
                                        ]
                                    ]],
                                    'series' => [[
                                        'series' => [
                                            'sourceRange' => ['sources' => [[
                                                'sheetId' => $sheetId,
                                                'startRowIndex' => count($userDayRows)+count($avgUserRows)+3,
                                                'endRowIndex' => count($userDayRows)+count($avgUserRows)+3+$projectCount,
                                                'startColumnIndex' => 1,
                                                'endColumnIndex' => 2
                                            ]]]
                                        ],
                                        'targetAxis' => 'LEFT_AXIS'
                                    ]]
                                ]
                            ],
                            'position' => [
                                'overlayPosition' => [
                                    'anchorCell' => [
                                        'sheetId' => $sheetId,
                                        'rowIndex' => 15,
                                        'columnIndex' => 5
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $sheetsService->spreadsheets->batchUpdate($spreadsheetId, new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]));
        }

        $this->info("Exported to tab '{$sheetTitle}' with summary and charts in '{$summaryTitle}'");
        return 0;
    }
}