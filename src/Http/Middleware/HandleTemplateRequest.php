<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Route\TemplateRoute;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
readonly class HandleTemplateRequest
{
    public function __construct(
        private TemplateResolver $templateResolver,
        private Sites $sites,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $this->handleTemplateRequest($request, $next($request));

        /**
         * The site fallback route returns an empty 404 when nothing matched, so
         * template routing can run before an error page is rendered.
         */
        if (
            $request->routeIs('craft.siteFallback') &&
            $response instanceof Response &&
            $response->getStatusCode() === 404 &&
            $response->getContent() === ''
        ) {
            abort(404);
        }

        return $response;
    }

    private function handleTemplateRequest(Request $request, mixed $response): mixed
    {
        if ($request->isSiteRequest() && Cms::config()->headlessMode) {
            return $response;
        }

        if ($request->isActionRequest()) {
            return $response;
        }

        if ($request->isSiteRequest() && ! $request->routeIs('craft.siteFallback')) {
            return $response;
        }

        /**
         * Template routing is considered a fallback, so we should
         * only try this once the original response is a 404.
         */
        if ($response instanceof Response && $response->getStatusCode() !== 404) {
            return $response;
        }

        if (! Cms::isInstalled()) {
            return redirect()->action([InstallController::class, 'index']);
        }

        if ($request->isCpRequest() && ! $request->routeIs('craft.cp.*')) {
            return $response;
        }

        $path = $request->isSiteRequest()
            ? $this->sites->getRequestPath($request)
            : $request->craftPath();

        if (! $this->isPublicTemplatePath($request, $path)) {
            return $response;
        }

        return new TemplateRoute($path)->handle($request);
    }

    private function isPublicTemplatePath(Request $request, string $path): bool
    {
        // If privateTemplateTrigger is set to an empty value, disable all public template routing
        if ($request->isSiteRequest() && ! Cms::config()->privateTemplateTrigger) {
            return false;
        }

        if (str_starts_with($path, Cms::config()->privateTemplateTrigger)) {
            return false;
        }

        return $this->templateResolver->exists($path, publicOnly: true);
    }
}
