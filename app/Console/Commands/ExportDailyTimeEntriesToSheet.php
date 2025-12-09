<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TimeEntry; 
use Google\Service\Sheets as GoogleSheets;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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

        /**
         * ---- NEW WEEK LOGIC ----
         * Week count starting from the first Monday of January 2025
         */
        // Always align today to the Monday of its week
        $currentWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        // First Monday of 2025
        $yearStart = Carbon::create(2025, 1, 1)->startOfWeek(Carbon::MONDAY);

        // Whole-week difference (never decimals)
        $weekNumber = $yearStart->diffInWeeks($currentWeekStart) + 1;

        // Final sheet name
        $sheetName = ''.$weekNumber;

        // -------------------------

        $client = Sheets::getService(); // raw Google API client

        // --- Ensure the sheet exists ---
        $spreadsheet = $client->spreadsheets->get($spreadsheetId);
        $sheet = collect($spreadsheet->getSheets())
            ->first(fn ($s) => $s->getProperties()->getTitle() === $sheetName);

        if (! $sheet) {

            Sheets::spreadsheet($spreadsheetId)->addSheet($sheetName);

            $spreadsheet = $client->spreadsheets->get($spreadsheetId);
            $sheet = collect($spreadsheet->getSheets())
                ->first(fn ($s) => $s->getProperties()->getTitle() === $sheetName);

            // Add header
            Sheets::spreadsheet($spreadsheetId)
                ->sheet($sheetName)
                ->append([[
                    'Project', 'Client', 'Description', 'Task', 'User', 'Organization', 'Email', 'Tags',
                    'Billable', 'Invoiced', 'Invoice ID', 'Start Date', 'Start Time', 'End Date', 'End Time',
                    'Duration (H:i)', 'Duration (Decimal)', 'Approval',
                ]]);
        }

        $sheetId = $sheet->getProperties()->getSheetId();

        // --- Prepare entries ---
        $data = [];

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
                $entry->start?->format('m/d/Y') ?? '',
                $entry->start?->format('H:i') ?? '',
                $entry->end?->format('m/d/Y') ?? '',
                $entry->end?->format('H:i') ?? '',
                $duration?->format('%h:%i') ?? '',
                round($decimalHours, 2),
                $entry->approval ?? 'N/A',
            ];
        }

        Sheets::spreadsheet($spreadsheetId)->sheet($sheetName)->append($data);

        // --- Apply filter ---
        $requests = [
            new GoogleSheets\Request([
                'setBasicFilter' => [
                    'filter' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 0,
                            'endRowIndex' => 1,
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

        $this->info('Exported '.count($entries)." entries into {$sheetName}");
    }
}
