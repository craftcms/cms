<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Http;

use Illuminate\Session\Middleware\StartSession;
use Override;

class HostStartSession extends StartSession
{
    #[Override]
    public function handle($request, \Closure $next)
    {
        return parent::handle($request, $next)->header('X-Host-Start-Session', 'yes');
    }
}
