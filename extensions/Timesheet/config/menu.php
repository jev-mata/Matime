<?php

use Illuminate\Support\Facades\Gate;

return [
    'title' => 'Timesheet',
    'icon' => 'ClockIcon',
    'href' => '#',
    'subItems' => [
        [
            'title' => 'Submit Entry',
            'route' => 'timesheet.index',
            'href' => '/timesheet',
            'show' => true,
        ],
        [
            'title' => 'Approvals',
            'route' => 'timesheet.approve',
            'href' => '/timesheet/approvals',
            'show' => auth()->user()?->is_manager,
        ],
    ],
];
