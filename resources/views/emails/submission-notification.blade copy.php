@component('mail::message') 
## Timesheet {{ __('type', ['type' => $type]) }},




<table width="100%" cellpadding="10" cellspacing="0" style="border: 1px solid #ccc; margin: 20px 0;">
    <tr>
        <td>
            <strong>{{ __('Period:') }}</strong> {{ $period }}<br>
            <strong>{{ __('Name:') }}</strong> {{ $nameTarg }}
        </td>
    </tr>
</table>


@component('mail::button', ['url' => $acceptUrl])
Review
@endcomponent


{{ __('type by: name', ['name' => $nameCurrent,'type' => $type]) }}

Thanks,  
{{ config('app.name') }}
@endcomponent
