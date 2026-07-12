<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\PreviewRequestPreparer;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\RouteToken\RouteTokens;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Context;

readonly class HandleTokenRequest
{
    public const string TOKEN_KEY = 'craft.token';

    public const string HAD_TOKEN_KEY = 'craft.token.hadToken';

    public const string TOKEN_HEADER = 'X-Craft-Token';

    public const string ROUTE_RESOLVED_ATTRIBUTE = 'craft.token.routeResolved';

    public function __construct(
        private GeneralConfig $generalConfig,
        private RouteTokens $tokens,
        private PreviewRequestPreparer $previews,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->input($this->generalConfig->tokenParam, $request->header(self::TOKEN_HEADER));

        if (! $token) {
            return $next($request);
        }

        abort_unless(preg_match('/^[A-Za-z0-9_-]+$/', (string) $token), 400, 'Invalid token');

        $tokenRoute = $this->tokens->getTokenRoute($token);

        if ($tokenRoute === false) {
            return $next($request);
        }

        Context::addHidden(self::TOKEN_KEY, $token);
        Context::addHidden(self::HAD_TOKEN_KEY, true);

        $originalUri = $request->uri()->withoutQuery([
            $this->generalConfig->tokenParam,
            'x-craft-preview',
            'x-craft-live-preview',
        ]);

        if ($this->isPreviewRoute((string) $tokenRoute[0])) {
            $this->previews->prepare((array) ($tokenRoute[1] ?? []));

            $previewRequest = $request->duplicateWithUri(
                $originalUri->value(),
                $originalUri->query()->all(),
            );
            app()->instance('request', $previewRequest);

            $response = $next($previewRequest);

            return $response instanceof Response
                ? $response->setNoCacheHeaders()
                : $response;
        }

        $newRequest = $request->duplicateWithUri((string) $tokenRoute[0], $tokenRoute[1] ?? []);
        $newRequest->attributes->set(self::ROUTE_RESOLVED_ATTRIBUTE, true);
        app()->instance('request', $newRequest);

        return $next($newRequest);
    }

    private function isPreviewRoute(string $route): bool
    {
        return '/'.trim($route, '/') === ActionRoute::uriForSegments(['preview', 'preview'], false);
    }
}
