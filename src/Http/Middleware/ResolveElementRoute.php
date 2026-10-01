<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Route\ElementRoute;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

readonly class ResolveElementRoute
{
    public function __construct(
        private Elements $elements,
        private Sites $sites,
        private ProjectConfig $projectConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! Cms::isInstalled() || ! $request->isSiteRequest() || $request->isActionRequest() || Cms::config()->headlessMode) {
            return $next($request);
        }

        $route = $request->route();

        if ($route->isFallback) {
            return $next($request);
        }

        $site = $this->sites->getCurrentSite();

        if (! $this->hasConfiguredDestination($route, $site->uid)) {
            return $next($request);
        }

        $element = $this->elements->getElementByUri($this->sites->getRequestPath($request), $site->id, true);

        if ($element instanceof Entry && ($destination = $element->getRouteDestination()) && $destination->matches($route)) {
            MatchedElement::set($element, $destination);
        }

        return $next($request);
    }

    /**
     * Checks cached project config first so ordinary site routes avoid an element URI lookup.
     */
    private function hasConfiguredDestination(Route $route, string $siteUid): bool
    {
        foreach ($this->projectConfig->get(ProjectConfig::PATH_SECTIONS) ?? [] as $section) {
            $settings = $section['siteSettings'][$siteUid] ?? [];

            if (! empty($settings['hasUrls']) && ! empty($settings['route']) && new ElementRoute($settings['route'])->matches($route)) {
                return true;
            }
        }

        foreach ($this->projectConfig->get(ProjectConfig::PATH_FIELDS) ?? [] as $field) {
            if (! is_a($field['type'], Matrix::class, true)) {
                continue;
            }

            $settings = ProjectConfigHelper::unpackAssociativeArrays($field['settings'] ?? []);
            $destination = $settings['siteSettings'][$siteUid]['route'] ?? null;

            if ($destination && new ElementRoute($destination)->matches($route)) {
                return true;
            }
        }

        return false;
    }
}
