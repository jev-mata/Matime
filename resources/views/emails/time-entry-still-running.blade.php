@component('mail::message')

@if(empty($timeEntry->description))
{{ __('Your currently running time entry is now running for more than 8 hours!') }}
@else
{{ __('Your currently running time entry ":description" is now running for more than 8 hours!', ['description' => $timeEntry->description]) }}
@endif

<br><br>

{{ __('If you forgot to stop the Time Tracker, you can do that in appName.', ['appName' => config('app.name')]) }}

@component('mail::button', ['url' => $dashboardUrl])
{{ __('Go to :appName', ['appName' => config('app.name')]) }}
@endcomponent

@endcomponent
