<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Route\DynamicRoute;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\View\Factory as ViewFactory;
use Symfony\Component\HttpFoundation\Response;

readonly class HandleTemplateRequest
{
    public function __construct(
        private TemplateResolver $templateResolver,
        private Sites $sites,
        private ViewFactory $views,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isCpRequest()) {
            TemplateMode::set(TemplateMode::Cp);

            $this->prependViewLocation(dirname(__DIR__, 3).'/resources/templates');
            $this->prependViewLocation(dirname(__DIR__, 3).'/resources/views');
        }

        $response = $next($request);

        if ($request->isSiteRequest() && Cms::config()->headlessMode) {
            return $response;
        }

        if ($request->isActionRequest()) {
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

        return new DynamicRoute('templates/render', ['template' => $path])->handle($request);
    }

    private function prependViewLocation(string $path): void
    {
        if (in_array($path, $this->views->getFinder()->getPaths(), true)) {
            return;
        }

        $this->views->prependLocation($path);
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
