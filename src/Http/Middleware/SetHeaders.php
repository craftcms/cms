<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SetHeaders
{
    private static bool $noCache = false;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
    ) {}

    public static function noCache(): void
    {
        self::$noCache = true;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        if ($request->isCpRequest() || $request->isActionRequest()) {
            $response->setNoCacheHeaders();
        }

        // Tell bots not to index/follow control panel and tokenized pages
        if (
            $this->generalConfig->disallowRobots ||
            $request->isCpRequest() ||
            $request->has(HandleTokenRequest::TOKEN_KEY) ||
            $request->hasHeader(HandleTokenRequest::TOKEN_HEADER) ||
            $request->isPreview()
            // @TODO: || ($request->isActionRequest() && !($request->route()?->getActionName() === \CraftCms\Cms\Http\Controllers\Auth\LoginController::class && $request->isMethod('GET')))
        ) {
            $response->headers->set('X-Robots-Tag', 'none');
        }

        // Prevent some possible XSS attack vectors
        if ($request->isCpRequest()) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", replace: false);
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
