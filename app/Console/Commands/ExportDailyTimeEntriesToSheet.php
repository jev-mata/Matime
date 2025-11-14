<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Exception as GoogleException;
use Illuminate\Console\Command;
use Revolution\Google\Sheets\Facades\Sheets;

class ExportDailyTimeEntriesToSheet extends Command
{
    protected $signature = 'sheets:export-time-entries';
    protected $description = 'Export daily time entries to Google Sheets at midnight';

    public function handle(): void
    {
        $today = Carbon::yesterday(); // yesterday's date for midnight run
        $entries = TimeEntry::whereDate('created_at', $today)->get();

        if ($entries->isEmpty()) {
            $this->info("No entries for {$today->toDateString()}");
            return;
        }

        $spreadsheetId = env('GOOGLE_SHEETS_ID');
        $sheetName = $today->format('F Y'); // e.g., "November 2025"

        $client = Sheets::getService(); // raw Google API client

        // --- Ensure the sheet exists ---
        $spreadsheet = $client->spreadsheets->get($spreadsheetId);
        $sheet = collect($spreadsheet->getSheets())
            ->first(fn($s) => $s->getProperties()->getTitle() === $sheetName);

        if (!$sheet) {
            // Create new sheet
            Sheets::spreadsheet($spreadsheetId)->addSheet($sheetName);

            // Re-fetch spreadsheet metadata
            $spreadsheet = $client->spreadsheets->get($spreadsheetId);
            $sheet = collect($spreadsheet->getSheets())
                ->first(fn($s) => $s->getProperties()->getTitle() === $sheetName);

            // Add header row
            Sheets::spreadsheet($spreadsheetId)
                ->sheet($sheetName)
                ->append([[
                    'Project', 'Client', 'Description', 'Task', 'User', 'Organization', 'Email', 'Tags',
                    'Billable', 'Invoiced', 'Invoice ID', 'Start Date', 'Start Time', 'End Date', 'End Time',
                    'Duration (H:i)', 'Duration (Decimal)', 'Approval',
                ]]);
        }

        $sheetId = $sheet->getProperties()->getSheetId(); // numeric ID for filter

        // --- Prepare entries ---
        $data = [];

        // If Monday, add a week marker
        if ($today->isMonday()) {
            $data[] = ["=== Week of {$today->toFormattedDateString()} ==="];
        }

        foreach ($entries as $entry) {
            $duration = $entry->getDuration();
            $decimalHours = $duration
                ? $duration->h + ($duration->i / 60) + ($duration->s / 3600)
                : 0;

            $data[] = [
                $entry->project?->name ?? '',
                $entry->client?->name ?? '',
                $entry->description,
                $entry->task?->name ?? '',
                $entry->user?->name ?? '',
                $entry->organization?->name ?? '',
                $entry->user?->email ?? '',
                implode(', ', $entry->tags ?? []),
                $entry->billable ? 'Yes' : 'No',
                $entry->invoiced ?? '',
                $entry->invoice_id ?? '',
                $entry->start?->format('Y-m-d') ?? '',
                $entry->start?->format('H:i') ?? '',
                $entry->end?->format('Y-m-d') ?? '',
                $entry->end?->format('H:i') ?? '',
                $duration?->format('%h:%i') ?? '',
                round($decimalHours, 2),
                $entry->approval ?? 'N/A',
            ];
        }

        // --- Append entries ---
        Sheets::spreadsheet($spreadsheetId)->sheet($sheetName)->append($data);

        // --- Apply filter to header row ---
        $requests = [
            new GoogleSheets\Request([
                'setBasicFilter' => [
                    'filter' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 0,
                            'endRowIndex' => 1, // only header row
                            'startColumnIndex' => 0,
                            'endColumnIndex' => count($data[0]),
                        ],
                    ],
                ],
            ]),
        ];

        $client->spreadsheets->batchUpdate(
            $spreadsheetId,
            new GoogleSheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
        );

        $this->info('Exported '.count($entries)." entries for {$today->toDateString()} into {$sheetName} tab.");
    }
}
