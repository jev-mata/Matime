<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\Weekday;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                UniqueEloquent::make(User::class, 'email')->ignore($user->id)->query(function (Builder $query) {
                    /** @var Builder<User> $query */
                    return $query->where('is_placeholder', '=', false);
                }),
            ],
            'photo' => [
                'nullable',
                'mimes:jpg,jpeg,png',
                'max:1024',
            ],
            'timezone' => [
                'required',
                'timezone:all',
            ],
            'week_start' => [
                'required',
                Rule::enum(Weekday::class),
            ],
        ])->validateWithBag('updateProfileInformation');
        if (isset($input['photo']) && $input['photo'] instanceof \Illuminate\Http\UploadedFile && $input['photo']->isValid()) {

            $disk = 'public';
            $directory = 'app/profile-photos';
            $filename = Str::uuid()->toString() . '.' . $input['photo']->getClientOriginalExtension(); 
            Log::info($input['photo']->getPath());
            try {

                $path = Storage::disk($disk)->putFileAs(
                    $directory,
                    $input['photo'],
                    $filename
                );

                $user->profile_photo_path = $path;
                $user->save();
            } catch (\Throwable $exception) {
                Log::error('Unhandled error during photo upload', [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }

 
        } else {
            Log::warning('Invalid or missing photo input', [
                'type' => gettype($input['photo'] ?? null),
                'value' => $input['photo'] ?? null
            ]);
        }

        if (
            $input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail
        ) {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'email_verified_at' => null,
                'timezone' => $input['timezone'],
                'week_start' => $input['week_start'],
            ])->save();

            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'timezone' => $input['timezone'],
                'week_start' => $input['week_start'],
            ])->save();
        }
    }
}
