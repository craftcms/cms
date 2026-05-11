<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\DynamicRoute;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use function CraftCms\Cms\t;

readonly class HandleMatchedElementRoute
{
    public function __construct(
        private Elements $elements,
        private Sites $sites,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! Cms::isInstalled() || ! $request->isSiteRequest() || $request->isActionRequest() || Cms::config()->headlessMode) {
            return $next($request);
        }

        $path = $this->sites->getRequestPath($request);

        if ($path === Element::HOMEPAGE_URI) {
            return $next($request);
        }

        $element = $this->elements->getElementByUri($path, $this->sites->getCurrentSite()->id, true);

        if (! $element) {
            return $next($request);
        }

        $route = $element->getRoute();

        if (! $route) {
            return $next($request);
        }

        if (is_string($route)) {
            $route = [$route, []];
        }

        $routeParams = is_array($route[1] ?? null) ? $route[1] : [];

        MatchedElement::set($element, $route);

        $this->enforceOfflineAccess($request);

        return new DynamicRoute($route[0], $routeParams + ['publicOnly' => false])->handle($request);
    }

    private function enforceOfflineAccess(Request $request): void
    {
        if (app()->isLive() || $request->getHadToken() || $request->siteToken()) {
            return;
        }

        if (Auth::guest()) {
            throw new ServiceUnavailableHttpException(
                retryAfter: app(ProjectConfig::class)->get('system.retryDuration'),
            );
        }

        if (! Gate::check('accessSiteWhenSystemIsOff')) {
            throw new ServiceUnavailableHttpException(t('Your account doesn’t have permission to access the site when the system is offline.'));
        }
    }
}
