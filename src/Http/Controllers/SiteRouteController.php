<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Route\ControllerRoute;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\Route\TemplateRoute;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class SiteRouteController
{
    public function __construct(
        private Elements $elements,
        private Sites $sites,
    ) {}

    public function __invoke(Request $request): Response
    {
        $request->route()?->forgetParameter('fallbackPlaceholder');

        if (! Cms::isInstalled() || ! $request->isSiteRequest() || $request->isActionRequest() || Cms::config()->headlessMode) {
            return response(status: 404);
        }

        $path = $this->sites->getRequestPath($request);

        return $this->matchElement($request, $path) ?? response(status: 404);
    }

    private function matchElement(Request $request, string $path): ?Response
    {
        if ($path === Element::HOMEPAGE_URI) {
            return null;
        }

        $element = $this->elements->getElementByUri($path, $this->sites->getCurrentSite()->id, true);

        if (! $element || ! $route = $element->getRoute()) {
            return null;
        }

        MatchedElement::set($element, $route);

        if ($route instanceof ControllerRoute) {
            return $route->handle($request, $element);
        }

        if (is_string($route)) {
            $route = [$route, []];
        }

        $params = Arr::get($route, 1, []);
        $params = is_array($params) ? $params : [];

        if (Arr::get($route, 0) === 'templates/render') {
            $variables = Arr::get($params, 'variables', []);
            $template = Arr::get($params, 'template');

            if (! is_string($template) || $template === '') {
                return null;
            }

            return new TemplateRoute(
                $template,
                is_array($variables) ? $variables : [],
                publicOnly: false,
            )->handle($request);
        }

        return response(status: 404);
    }
}
