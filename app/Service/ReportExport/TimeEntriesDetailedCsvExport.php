<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Models\TimeEntry;
use App\Service\IntervalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends CsvExport<TimeEntry>
 */
class TimeEntriesDetailedCsvExport extends CsvExport
{
    public const array HEADER = [
        'Project',
        'Client',
        'Description',
        'Task',
        'User',
        'Group',
        'Email',
        'Tags',
        'Billable',
        'Invoiced',
        'Invoice ID',
        'Start Date',
        'Start Time',
        'End Date',
        'End Time',
        'Duration (h)',
        'Duration (decimal)',
        'Approval',
    ];

    protected const string CARBON_FORMAT = 'Y-m-d H:i:s';

    private string $timezone;

    public function __construct(string $disk, string $folderPath, string $filename, Builder $builder, int $chunk, string $timezone)
    {
        parent::__construct($disk, $folderPath, $filename, $builder, $chunk);

        $this->timezone = $timezone;
    }

    /**
     * @param  TimeEntry  $model
     */
    public function mapRow(Model $model): array
    {
        $interval = app(IntervalService::class);
        $duration = $model->getDuration();

        return [
            'Project' => $model->project?->name,
            'Client' => $model->client?->name,
            'Description' => $model->description,
            'Task' => $model->task?->name,
            'User' => $model->user->name,
            'Group' => $model->project?->groups()?->first()?->name,
            'Email' => $model->user?->email,
            'Tags' => $model->tagsRelation->pluck('name')->implode(', '),
            'Billable' =>  $model->billable?"Yes":"No",
            'Invoiced' => '',
            'Invoice ID' => '',
            'Start Date' => $model->start->timezone($this->timezone)->format('Y-m-d'),
            'Start Time' => $model->start->timezone($this->timezone)->format('h:i:s A'),
            'End Date' => $model->end?->timezone($this->timezone)?->format('Y-m-d'),
            'End Time' => $model->end?->timezone($this->timezone)?->format('h:i:s A'),
            'Duration (h)' => $duration !== null ? $interval->format($model->getDuration()) : null,
            'Duration (decimal)' => $duration !== null
                ? number_format($duration->totalHours, 2, '.', '')
                : null,
            'Approval' => $model->approval,
        ];
    }
}
