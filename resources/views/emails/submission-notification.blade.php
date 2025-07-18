@component('mail::message')
## Timesheet {{ $type }}

<table width="100%" cellpadding="10" cellspacing="0" style="border: 1px solid #ccc; margin: 20px 0; font-family: Poppins;">
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

<p><strong>{{ $type }} by:</strong> {{ $nameCurrent }}</p>

Thanks,  
{{ config('app.name') }}
@endcomponent
