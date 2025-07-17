@component('mail::message')
    ## Timesheet {{ $type }},




    <table width="100%" cellpadding="10" cellspacing="0" style="border: 1px solid #ccc; margin: 20px 0;">
        <tr>
            <td>
                <strong>{{ __('Period:') }}</strong> {{ $period }}<br>
                <strong>{{ __('Name:') }}</strong>
                @if (is_array($nameTarg))
                    {{ implode(', ', $nameTarg) }}
                @else
                    {{ $nameTarg }}
                @endif

            </td>
        </tr>
    </table>


    @component('mail::button', ['url' => $acceptUrl])
        Review
    @endcomponent

    {{ $type }} by: {{ $nameCurrent }}

    Thanks,
    {{ config('app.name') }}
@endcomponent
