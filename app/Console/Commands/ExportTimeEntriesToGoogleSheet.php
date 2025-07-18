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
use Illuminate\Support\Facades\Storage;

class ExportTimeEntriesToGoogleSheet extends Command
{
    protected $signature = 'timeentry:export';
    protected $description = 'Export TimeEntry records to Excel and upload to Google Drive';

    public function handle()
    {
        $now = Carbon::now();
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        if ($now->day <= 15) {
            $start = $startOfMonth->copy();
            $end = $startOfMonth->copy()->day(15)->endOfDay();
            $label = $start->format('Y-F') . '-1-15';
        } else {
            $start = $startOfMonth->copy()->day(16)->startOfDay();
            $end = $startOfMonth->copy()->endOfMonth()->endOfDay();
            $label = $start->format('Y-F') . '-16-' . $end->format('d');
        }


        $entries = TimeEntry::with(['user', 'project', 'task', 'client', 'tagsRelation'])
            ->whereBetween('start', [$start, $end]);

        if ($entries->get()->isEmpty()) {
            $this->info('No time entries found for export.');
            return 0;
        }
        $organization = Organization::whereHas('owner', function ($query) {
            $query->where('email', 'hello@mata.ph');
        })->with('owner')->first();

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

        $this->uploadToGoogleDrive(storage_path("app/{$path}"), $filename);
        return 0;
    }

    protected function uploadToGoogleDrive(string $filePath, string $fileName)
    {
        $client = new \Google_Client();
        $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));

        // Set the refresh token
        $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));

        // ✅ IMPORTANT: You must set the access token after refreshing
        $accessToken = env('GOOGLE_ACCESS_TOKEN');
        $client->setAccessToken($accessToken);

        $client->addScope(Google_Service_Drive::DRIVE_FILE);
        $drive = new \Google_Service_Drive($client);

        $fileMetadata = new \Google_Service_Drive_DriveFile([
            'name' => $fileName,
            'parents' => [env('GOOGLE_DRIVE_FOLDER_ID')],
        ]);

        $content = file_get_contents($filePath);

        $file = $drive->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink'
        ]);

        $this->info("✅ Excel uploaded to Google Drive: " . $file->webViewLink);
        unlink($filePath);
    }

}
