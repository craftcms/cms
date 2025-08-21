<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        $newRequest = $request->duplicate(server: array_merge($request->server->all(), [
            'REQUEST_URI' => $route,
        ]));

        /** @var Response $response */
        $response = $next($newRequest);

        /**
         * If Yii returned with a Page not found. It needs to handle the
         * original request with an action body parameter.
         *
         * @todo Remove when cms is fully ported.
         */
        if ($response->getStatusCode() === 500 && str_contains($response->getContent(), 'Page not found.')) {
            return $next($request);
        }

        return $response;
    }
}
