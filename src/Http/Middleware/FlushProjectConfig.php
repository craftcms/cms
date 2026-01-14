<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Http\Request;

final readonly class FlushProjectConfig
{
    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! Cms::isInstalled()) {
            return $next($request);
        }

        $response = $next($request);

        $this->projectConfig->flush();

        if ($this->projectConfig->waitingToUpdateParsedConfigTimes) {
            $this->projectConfig->updateParsedConfigTimes();
        }

        return $response;
    }
}
