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
 * action, preview, and site token requests.
 *
 * It runs before `StartSession` in the middleware priority list, outside the session middleware, so the no-cache
 * headers {@see StartLazySession} queues for a saved session are applied after everything the page queued.
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
            Context::getHidden(ResolveSite::HAD_SITE_TOKEN_KEY) === true
        ) {
            $this->setNoCacheHeaders($response);
        }

        $this->setPoweredByHeader($response);

        return $response;
    }

    private function setNoCacheHeaders(SymfonyResponse $response): void
    {
        $response->headers->set('Expires', '0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
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
