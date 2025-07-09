@component('mail::message')
{{ __('You have been invited to join the :organization organization!', ['organization' => $invitation->organization->name]) }}

 
{{ __('You may accept this invitation by clicking the button below:') }} 

@component('mail::button', ['url' => $acceptUrl])
{{ __('Accept Invitation') }}
@endcomponent
  

{{ __('If the button above does not work, you may copy and paste the following URL into your browser:') }}

[{{ $acceptUrl }}]({{ $acceptUrl }})

{{ __('If you did not expect to receive an invitation to this organization, you may discard this email.') }}
@endcomponent
