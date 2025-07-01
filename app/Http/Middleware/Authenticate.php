<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {

       
        return $request->expectsJson() ? null : route('login');
    }
}
