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
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;

/**
 * @since 6.0.0
 */
readonly class ResolveElementRoute
{
    private const string DESTINATIONS_CACHE_KEY = 'elementRouteDestinations';

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
     * Checks the configured route destinations first so ordinary site routes avoid an element URI lookup.
     */
    private function hasConfiguredDestination(Route $route, string $siteUid): bool
    {
        foreach ($this->destinations()[$siteUid] ?? [] as $destination) {
            if (new ElementRoute($destination)->matches($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the route destinations that sections and Matrix fields configure, by site UID.
     *
     * They’re cached on their own, so site requests don’t load the sections and fields config to find them.
     *
     * @return array<string, list<string>>
     */
    private function destinations(): array
    {
        $version = Info::fetch()->configVersion;
        $cached = Cache::get(self::DESTINATIONS_CACHE_KEY);

        if (($cached['version'] ?? null) === $version) {
            return $cached['destinations'];
        }

        $destinations = [];

        foreach ($this->projectConfig->get(ProjectConfig::PATH_SECTIONS) ?? [] as $section) {
            foreach ($section['siteSettings'] ?? [] as $siteUid => $settings) {
                if (! empty($settings['hasUrls']) && ! empty($settings['route'])) {
                    $destinations[$siteUid][] = $settings['route'];
                }
            }
        }

        foreach ($this->projectConfig->get(ProjectConfig::PATH_FIELDS) ?? [] as $field) {
            if (! is_a($field['type'], Matrix::class, true)) {
                continue;
            }

            $settings = ProjectConfigHelper::unpackAssociativeArrays($field['settings'] ?? []);

            foreach ($settings['siteSettings'] ?? [] as $siteUid => $siteSettings) {
                if (! empty($siteSettings['route'])) {
                    $destinations[$siteUid][] = $siteSettings['route'];
                }
            }
        }

        $destinations = array_map(fn (array $routes): array => array_values(array_unique($routes)), $destinations);
        Cache::put(self::DESTINATIONS_CACHE_KEY, ['version' => $version, 'destinations' => $destinations], ProjectConfig::CACHE_DURATION);

        return $destinations;
    }
}
