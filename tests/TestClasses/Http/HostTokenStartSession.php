<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Http;

use Closure;
use Illuminate\Session\Middleware\StartSession;
use Override;

class HostTokenStartSession extends StartSession
{
    #[Override]
    public function handle($request, Closure $next)
    {
        $response = parent::handle($request, $next);

        return $response->header('X-CSRF-Token', $request->session()->token());
    }
}
