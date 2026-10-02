<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use Illuminate\Http\Request;

/**
 * @since 6.0.0
 */
class RequireConfirmedPassword
{
    use ConfirmsPasswords;

    public function handle(Request $request, Closure $next): mixed
    {
        $this->requireConfirmedPassword();

        return $next($request);
    }
}
