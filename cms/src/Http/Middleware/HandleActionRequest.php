<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;

final readonly class HandleActionRequest
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->has('action')) {
            return $next($request);
        }

        $action = $request->string('action');
        $route = implode('/', [
            '',
            $this->generalConfig->cpTrigger,
            $this->generalConfig->actionTrigger,
            $action,
        ]);

        $request = $request->duplicate(server: array_merge($request->server->all(), [
            'REQUEST_URI' => $route,
        ]));

        return $next($request);
    }
}
