<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SetHeaders
{
    private static bool $noCache = false;

    private static ?int $duration = null;

    private static bool $replace = true;

    private static array $headers = [];

    public function __construct(
        private readonly GeneralConfig $generalConfig,
    ) {}

    public static function noCache(): void
    {
        self::$noCache = true;
    }

    public static function setCache(int $duration = 31536000, bool $replace = true): void
    {
        self::$duration = $duration;
        self::$replace = $replace;
    }

    public static function add(string $header, string $value, bool $replace = true): void
    {
        self::$headers[] = [
            'header' => $header,
            'value' => $value,
            'replace' => $replace,
        ];
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        if ($request->isCpRequest() || $request->isActionRequest() || self::$noCache) {
            $response->setNoCacheHeaders();
        } elseif (! is_null(self::$duration)) {
            if (self::$duration <= 0) {
                $response->setNoCacheHeaders();
            } else {
                $response
                    ->setExpires(now()->addSeconds(self::$duration))
                    ->header('Pragma', 'cache', self::$replace)
                    ->setPublic()
                    ->setMaxAge(self::$duration);
            }
        }

        // Tell bots not to index/follow control panel and tokenized pages
        if (
            $this->generalConfig->disallowRobots ||
            $request->isCpRequest() ||
            $request->has(HandleTokenRequest::TOKEN_KEY) ||
            $request->hasHeader(HandleTokenRequest::TOKEN_HEADER) ||
            $request->isPreview()
            || ($request->isActionRequest() && ! ($request->route()?->getActionName() === LoginController::class && $request->isMethod('GET')))
        ) {
            $response->headers->set('X-Robots-Tag', 'none');
        }

        // Prevent some possible XSS attack vectors
        if ($request->isCpRequest()) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", replace: false);
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        foreach (self::$headers as $header) {
            $response->header($header['header'], $header['value'], $header['replace'] ?? true);
        }

        self::reset();

        return $response;
    }

    public static function reset(): void
    {
        self::$noCache = false;
        self::$duration = null;
        self::$replace = true;
        self::$headers = [];
    }
}
