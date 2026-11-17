<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\ResponseHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
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

        if ($request->isCpRequest() || $request->isActionRequest() || $hasPreviewParam || $this->responseHeaders->noCache) {
            $this->setNoCacheHeaders($response);
        } elseif ($this->responseHeaders->duration !== null) {
            if ($this->responseHeaders->duration <= 0) {
                $this->setNoCacheHeaders($response);
            } else {
                $response->headers->set('Pragma', 'cache', $this->responseHeaders->replace);
                $response
                    ->setExpires(now()->addSeconds($this->responseHeaders->duration))
                    ->setPublic()
                    ->setMaxAge($this->responseHeaders->duration);
            }
        }

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

        foreach ($this->responseHeaders->headers as $header) {
            $response->headers->set($header['header'], $header['value'], $header['replace']);
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
