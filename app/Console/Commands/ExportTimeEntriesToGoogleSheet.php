<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use App\Models\TimeEntry;
use App\Models\Organization;
use App\Service\LocalizationService;
use App\Service\ReportExport\TimeEntriesDetailedExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Enums\ExportFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;

use Illuminate\Support\Facades\Storage;

class ExportTimeEntriesToGoogleSheet extends Command
{
    protected $signature = 'timeentry:export';
    protected $description = 'Export TimeEntry records to Excel and upload to Google Drive';

    public function handle()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        if ($now->day <= 15) {
            $start = $startOfMonth->copy();
            $end = $startOfMonth->copy()->day(15)->endOfDay();
            $label = $start->format('Y');
        } else {
            $start = $startOfMonth->copy()->day(16)->startOfDay();
            $end = $startOfMonth->copy()->endOfMonth()->endOfDay();
            $label = $start->format('Y');
        }



        $organization = Organization::whereHas('owner', function ($query) {
            $query->where('email', 'hello@mata.ph');
        })->with('owner')->first();

        $entries = TimeEntry::with(['user', 'project', 'task', 'client', 'tagsRelation'])
            ->whereBetween('start', [$start, $end])->where('organization_id', '=', $organization->id);

        if ($entries->get()->isEmpty()) {
            $this->info('No time entries found for export.');
            return 0;
        }
        $localizationService = LocalizationService::forOrganization($organization);

        $format = ExportFormat::XLSX;

        $filename = 'time-entries-export-' . $label . '.' . $format->getFileExtension();

        $folderPath = 'exports';
        $path = $folderPath . '/' . $filename;

        // Save Excel to local private storage
        Excel::store(
            new TimeEntriesDetailedExport($entries, $format, 'UTC', $localizationService),
            $path,
            config('filesystems.private'),
            $format->getExportPackageType(),
            ['visibility' => 'private']
        );

        $this->uploadToGoogleDrive(storage_path("app/{$path}"), $filename,$start->format('d-')."-".$end->format('d, F Y'));
        return 0;
    }

    protected function uploadToGoogleDrive(string $filePath, string $fileName,string $periods)
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
        $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));
        $client->addScope(Google_Service_Drive::DRIVE);

        $accessToken = $client->getAccessToken();
        $client->setAccessToken($accessToken);
        $drive = new Google_Service_Drive($client);
        $folderId = env('GOOGLE_DRIVE_FOLDER_ID');

        // Check if file already exists
        $files = $drive->files->listFiles([
            'q' => sprintf("name='%s' and trashed = false", addslashes($fileName)),
            'fields' => 'files(id, name, parents)',
            'spaces' => 'drive',
        ]);

        $spreadsheet = IOFactory::load($filePath); // Your newly generated Excel export

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->freezePane('A1');
        if (count($files->getFiles()) > 0) {
            // ✅ Download existing file
            $existingFile = $files->getFiles()[0];
            $existingFileId = $existingFile->getId();

            $response = $drive->files->get($existingFileId, ['alt' => 'media']);
            $existingTemp = storage_path('app/temp_existing_' . $fileName);
            file_put_contents($existingTemp, $response->getBody()->getContents());

            // ✅ Load existing spreadsheet
            $existingSpreadsheet = IOFactory::load($existingTemp);
            $existingSheet = $existingSpreadsheet->getActiveSheet();

            $highestRow = $existingSheet->getHighestRow() + 1;

            $existingSheet->mergeCells("A{$highestRow}:K{$highestRow}");

            // Apply background color (e.g., light gray)
            $existingSheet->getStyle("A{$highestRow}:K{$highestRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF424343'], // light gray
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                ],
            ]);
            // Add divider row
            $existingSheet->setCellValue('A' . ($highestRow), "{$periods} -------------------- Time Entries EXPORT ");

            // ✅ Append new rows
            $newSheet = $sheet;
            $newData = $newSheet->toArray(null, true, true, true);

            foreach ($newData as $rowIndex => $row) {
                $targetRow = $highestRow + $rowIndex;
                $colIndex = 'A';
                foreach ($row as $cell) {
                    $existingSheet->setCellValue($colIndex . $targetRow, $cell);
                    $colIndex++;
                }
            }

            // ✅ Save to temp file
            $finalTempFile = storage_path("app/final_" . $fileName);
            IOFactory::createWriter($existingSpreadsheet, 'Xlsx')->save($finalTempFile);

            // ✅ Upload final file, replacing the original
            $fileMetadata = new Google_Service_Drive_DriveFile(['name' => $fileName]);
            $updated = $drive->files->update($existingFileId, $fileMetadata, [
                'data' => file_get_contents($finalTempFile),
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink',
            ]);

            $this->info("🔁 File updated with appended data: " . $updated->webViewLink);

            unlink($existingTemp);
            unlink($finalTempFile);
        } else {
            // First-time upload
            $newFile = $drive->files->create(new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$folderId],
            ]), [
                'data' => file_get_contents($filePath),
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink',
            ]);

            $this->info("✅ File uploaded to Google Drive: " . $newFile->webViewLink);
        }

        unlink($filePath);
    }

}
