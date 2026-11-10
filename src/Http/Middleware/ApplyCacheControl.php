<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\CacheControl;
use CraftCms\Cms\Http\Events\CacheControlApplied;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects response dependencies and resolves cache-control policy for Craft web requests.
 * This is a middleware checkpoint, not a universal final-response or storage guarantee.
 *
 * @since 6.0.0
 */
class ApplyCacheControl
{
    public function __construct(
        private readonly CacheControl $cacheControl,
        private readonly GeneralConfig $generalConfig,
        private readonly ElementCaches $elementCaches,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->elementCaches->startCollectingCacheInfo();

        try {
            $response = $next($request);
        } finally {
            [$dependency, $duration] = $this->elementCaches->stopCollectingCacheInfo();
        }

        if (! $response instanceof Response) {
            return $response;
        }

        $reasons = $this->reasons($request, $response);
        $this->cacheControl->applyTo($response);
        $reasons = array_values(array_unique([...$reasons, ...$this->reasons($request, $response)]));
        $this->applyPrivacy($response, array_diff($reasons, ['no-store', 'no-cache']) === []);

        $explanations = $this->cacheControl->reasons();

        foreach ($reasons as $reason) {
            $explanations[] = [
                'code' => $reason,
                'target' => in_array($reason, ['no-store', 'no-cache'], true) ? 'both' : 'shared',
                'message' => match ($reason) {
                    'session-cookie' => 'The response sets a session cookie.',
                    'authorization' => 'The request includes authorization credentials.',
                    'control-panel' => 'The request is for the control panel.',
                    'action' => 'The request is an action request.',
                    'preview' => 'The request is a preview.',
                    'token' => 'The request uses a Craft token.',
                    'site-token' => 'The request uses a site-access token.',
                    default => "Observed $reason cache-control restriction.",
                },
                'source' => match ($reason) {
                    'private', 'no-store', 'no-cache' => 'response.header:Cache-Control',
                    default => 'request/session',
                },
            ];
        }

        if ($duration !== null) {
            $changed = false;
            $maxAge = $response->getAge() + $duration;

            foreach (['max-age', 's-maxage'] as $directive) {
                $value = $response->headers->getCacheControlDirective($directive);

                if ($value !== null && (int) $value > $maxAge) {
                    $response->headers->addCacheControlDirective($directive, (string) $maxAge);
                    $changed = true;
                }
            }

            $expires = $response->getExpires();
            $deadline = $response->getDate()->modify("+$maxAge seconds");

            if ($expires !== null && $expires > $deadline) {
                $response->setExpires($deadline);
                $changed = true;
            }

            if ($changed) {
                $explanations[] = [
                    'code' => 'dependency-expiry',
                    'target' => 'both',
                    'message' => "Freshness limited to $duration remaining seconds by collected dependencies.",
                    'source' => ElementCaches::class,
                ];
            }
        }

        if ($explanations !== []) {
            Log::debug('Cache-control policy applied.', ['reasons' => $explanations]);
        }

        event(new CacheControlApplied($request, $response, clone $response, $dependency->tags ?? [], $explanations));

        return $response;
    }

    private function applyPrivacy(Response $response, bool $shared): void
    {
        if (! $shared) {
            $response->setPrivate();

            foreach (['s-maxage', 'stale-while-revalidate', 'stale-if-error'] as $directive) {
                $response->headers->removeCacheControlDirective($directive);
            }

            if ($response->getMaxAge() === null) {
                $response->headers->addCacheControlDirective('no-cache');
            }
        }

        if ($response->headers->hasCacheControlDirective('no-store')) {
            $response->headers->removeCacheControlDirective('max-age');
        }
    }

    /** @return list<string> */
    private function reasons(Request $request, Response $response): array
    {
        // Symfony synthesizes private/no-cache defaults. Only explicit directives constrain an opt-in.
        $headers = clone $response->headers;
        $headers->addCacheControlDirective('public');

        return array_keys(array_filter([
            'session-cookie' => array_any($response->headers->getCookies(), fn ($cookie) => $cookie->getName() === config('session.cookie')),
            'authorization' => $request->headers->has('Authorization'),
            'control-panel' => $request->isCpRequest(),
            'action' => $request->isActionRequest(),
            'preview' => $request->previewParam() !== null,
            'token' => $request->has($this->generalConfig->tokenParam)
                || $request->headers->has(HandleTokenRequest::TOKEN_HEADER)
                || Context::hasHidden(HandleTokenRequest::HAD_TOKEN_KEY),
            'site-token' => $request->siteToken() !== null
                || Context::getHidden(ResolveSite::HAD_SITE_TOKEN_KEY) === true,
            'private' => $headers->hasCacheControlDirective('private'),
            'no-store' => $headers->hasCacheControlDirective('no-store'),
            'no-cache' => $headers->hasCacheControlDirective('no-cache'),
        ]));
    }
}
