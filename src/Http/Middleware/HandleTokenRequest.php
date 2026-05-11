<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\RouteToken\RouteTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

readonly class HandleTokenRequest
{
    public const string TOKEN_KEY = 'craft.token';

    public const string ORIGINAL_URI_KEY = 'craft.token.originalUri';

    public const string HAD_TOKEN_KEY = 'craft.token.hadToken';

    public const string TOKEN_HEADER = 'X-Craft-Token';

    public function __construct(
        private GeneralConfig $generalConfig,
        private RouteTokens $tokens,
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

        Context::addHidden(self::ORIGINAL_URI_KEY, $request->uri()->withoutQuery([
            'token',
            'x-craft-preview',
            'x-craft-live-preview',
        ]));

        $newRequest = $request->duplicateWithUri((string) $tokenRoute[0], $tokenRoute[1] ?? []);

        return $next($newRequest);
    }
}
