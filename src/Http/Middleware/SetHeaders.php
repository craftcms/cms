<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\ResponseHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Applies the headers queued with {@see ResponseHeaders}, followed by the headers Craft sends for control panel,
 * action, preview, and site token requests, and for responses that contain the session’s CSRF token.
 *
 * @since 6.0.0
 */
class SetHeaders
{
    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly ResponseHeaders $responseHeaders,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $response instanceof SymfonyResponse) {
            return $response;
        }

        $hasPreviewParam = $request->previewParam() !== null;

        // Tell bots not to index/follow control panel and tokenized pages
        if (
            $this->generalConfig->disallowRobots ||
            $request->isCpRequest() ||
            $request->has(HandleTokenRequest::TOKEN_KEY) ||
            $request->hasHeader(HandleTokenRequest::TOKEN_HEADER) ||
            $hasPreviewParam ||
            ($request->isActionRequest() && ! ($request->route()?->getActionName() === LoginController::class && $request->isMethod('GET')))
        ) {
            $response->headers->set('X-Robots-Tag', 'none');
        }

        // Prevent some possible XSS attack vectors
        if ($request->isCpRequest()) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", replace: false);

            if (! $response->headers->has('X-Frame-Options')) {
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            }

            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        $this->responseHeaders->applyTo($response);

        if (
            $request->isCpRequest() ||
            $request->isActionRequest() ||
            $hasPreviewParam ||
            Context::getHidden(ResolveSite::HAD_SITE_TOKEN_KEY) === true ||
            $this->containsCsrfToken($request, $response)
        ) {
            ResponseHeaders::setNoCacheHeaders($response);
        }

        $this->setPoweredByHeader($response);

        return $response;
    }

    /**
     * Whether the response contains the session’s CSRF token, which no cache may store, as it’s only valid for
     * this visitor’s session.
     *
     * The body and headers are checked, but not cookies, which already keep shared caches from storing the response.
     * Streamed bodies aren’t checked, as they’re only sent after this middleware, so streamed responses keep the
     * cache headers they were given. Neither are the bodies of HEAD responses, which are emptied before this
     * middleware runs, so a HEAD response can have cache headers that the GET response for the same page doesn’t.
     */
    private function containsCsrfToken(Request $request, SymfonyResponse $response): bool
    {
        $token = $request->hasSession() ? $request->session()->token() : null;

        if (! is_string($token) || $token === '') {
            return false;
        }

        $content = $response->getContent();

        if (is_string($content) && str_contains($content, $token)) {
            return true;
        }

        return array_any(
            $response->headers->allPreserveCaseWithoutCookies(),
            fn (array $values) => array_any($values, fn ($value) => is_string($value) && str_contains($value, $token)),
        );
    }

    private function setPoweredByHeader(SymfonyResponse $response): void
    {
        if (! $this->generalConfig->sendPoweredByHeader) {
            $response->headers->remove('X-Powered-By');

            return;
        }

        $header = str($response->headers->get('X-Powered-By', ''))
            ->explode(',')
            ->add('Craft CMS')
            ->unique()
            ->filter()
            ->join(',');

        $response->headers->set('X-Powered-By', $header);
    }
}
