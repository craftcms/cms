<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use craft\web\Application;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\Request;

final readonly class FlushProjectConfig
{
    public function __construct(
        #[Give('Craft')] private Application $craft,
        private ProjectConfig $projectConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->craft->getIsInstalled()) {
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
