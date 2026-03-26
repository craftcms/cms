<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Route\DynamicRoute;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Http\Request;

readonly class HandleMatchedElementRoute
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Cms::isInstalled() || ! $request->isSiteRequest() || $request->isActionRequest() || Cms::config()->headlessMode) {
            return $next($request);
        }

        $path = trim($request->decodedPath(), '/');

        if ($path === Element::HOMEPAGE_URI) {
            return $next($request);
        }

        $element = Craft::$app->getElements()->getElementByUri($path, Sites::getCurrentSite()->id, true);

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

        MatchedElement::set($element);

        return new DynamicRoute($route[0], $routeParams)->handle($request);
    }
}
