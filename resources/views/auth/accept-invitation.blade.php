<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <!-- Logo here -->
        </x-slot>

        <div class="mb-4 text-sm text-gray-600">
            You've been invited to join <strong>{{ $invitation->team->name }}</strong> as <strong>{{ $invitation->role }}</strong>.
        </div>

        <form method="POST" action="{{ route('invitations.accept', $invitation->id) }}">
            @csrf

            <div class="mt-4">
                <x-input-label for="name" value="Your Name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" value="Confirm Password" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
            </div>

            <div class="mt-6">
                <x-primary-button>
                    Accept Invitation
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
