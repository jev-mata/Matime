@component('mail::message')
# Reminder

Hello,

This is a reminder to submit your timesheet.

@component('mail::button', ['url' => $acceptUrl])
Submit
@endcomponent

Thanks,  
{{ config('app.name') }}
@endcomponent
