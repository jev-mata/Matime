<?php

declare(strict_types=1);

namespace App\Models\Passport;

use Database\Factories\Passport\TokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Passport\Token as PassportToken;

/**
 * @property string $id
 * @property null|string $user_id
 * @property string $client_id
 * @property null|string $name
 * @property array<string> $scopes
 * @property bool $revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $expires_at
 */
class Token extends PassportToken
{
    /** @use HasFactory<TokenFactory> */
    use HasFactory;

    /**
     * Get the client that the token belongs to.
     *
     * @return BelongsTo<Client, Token>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}
