<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Enums\ExportFormat;
use App\Models\TimeEntry;
use App\Service\LocalizationService;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * @implements WithMapping<TimeEntry>
 */
class TimeEntriesDetailedExport implements
    FromQuery,
    ShouldAutoSize,
    WithColumnFormatting,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithEvents
{
    use Exportable;

    /**
     * @var Builder<TimeEntry>
     */
    private Builder $builder;

    private ExportFormat $exportFormat;

    private string $timezone;

    private LocalizationService $localizationService;
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Freeze top row
                $sheet->freezePane('A2');

                $highestColumn = $sheet->getHighestColumn(); // e.g., 'K'
                $highestRow = $sheet->getHighestRow();       // e.g., '50'
                $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");
            },
        ];
    }

    /**
     * @param  Builder<TimeEntry>  $builder
     */
    public function __construct(Builder $builder, ExportFormat $exportFormat, string $timezone, LocalizationService $localizationService)
    {
        $this->builder = $builder;
        $this->exportFormat = $exportFormat;
        $this->timezone = $timezone;
        $this->localizationService = $localizationService;
    }

    /**
     * @return Builder<TimeEntry>
     */
    public function query(): Builder
    {
        return $this->builder;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        if ($this->exportFormat === ExportFormat::XLSX) {
            return [
                'I' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Start Date
                'J' => 'hh:mm:ss AM/PM',                   // Start Time
                'K' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // End Date
                'L' => 'hh:mm:ss AM/PM',                   // End Time
                'N' => NumberFormat::FORMAT_NUMBER_00,     // Duration (decimal)
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                'N' => NumberFormat::FORMAT_NUMBER_00,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }


    /**
     * @return array<int|string, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Project',
            'Client',
            'Description',
            'Task',
            'User',
            'Group',
            'Email',
            'Tags',
            'Start Date',
            'Start Time',
            'End  Date',
            'End  Time',
            'Duration (h)',
            'Duration (decimal)',
            'Approval',
        ];
    }

    /**
     * @param  TimeEntry  $model
     * @return array<int, string|float|null>
     */
    public function map($model): array
    {
        $duration = $model->getDuration();

        if ($this->exportFormat === ExportFormat::XLSX) {
            return [
                $model->project?->name,
                $model->client?->name,
                $model->description,
                $model->task?->name,
                $model->user->name,
                $model->project?->groups()?->first()?->name,
                $model->user?->email,
                $model->tagsRelation->pluck('name')->implode(', '),
                Date::dateTimeToExcel($model->start->timezone($this->timezone)),
                Date::dateTimeToExcel($model->start->timezone($this->timezone)),
                $model->end !== null ? Date::dateTimeToExcel($model->end->timezone($this->timezone)) : null,
                $model->end !== null ? Date::dateTimeToExcel($model->end->timezone($this->timezone)) : null,
                $duration !== null ? $this->localizationService->formatInterval($duration) : null,
                $duration?->totalHours,
                $model->approval,
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                $model->project?->name,
                $model->client?->name,
                $model->description,
                $model->task?->name,
                $model->user->name,
                $model->project?->groups()?->first()?->name,
                $model->user?->email,
                $model->tagsRelation->pluck('name')->implode(', '),
                $model->start->timezone($this->timezone)->format('Y-m-d'),
                $model->start->timezone($this->timezone)->format('h:i:s A'),
                $model->end?->timezone($this->timezone)?->format('Y-m-d'),
                $model->end?->timezone($this->timezone)?->format('h:i:s A'),

                $duration !== null ? $this->localizationService->formatInterval($duration) : null,
                $duration?->totalHours,
                $model->approval,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }
}
