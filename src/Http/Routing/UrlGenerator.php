<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Routing;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\StripSiteBasePath;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;
use Override;

/**
 * Generates absolute URLs for site routes under a site’s base URL, so `route('news')` returns
 * `https://example.com/fr/news` on a site whose base URL is `https://example.com/fr/`.
 *
 * URLs use the current site, or the site passed as the `site` parameter, as a `Site`, handle, or ID. Relative URLs
 * stay relative to the site, like `request()->path()`. Routes registered with `rootOnly()` keep the
 * app’s root URL.
 *
 * @since 6.0.0
 */
class UrlGenerator extends BaseUrlGenerator
{
    public const string SITE_PARAMETER = 'site';

    private const string ROOT_ONLY_METADATA = 'craft.rootOnly';

    /**
     * Registers the `rootOnly()` route macro, for routes that keep Craft’s middleware but only live at
     * the app’s root URL, such as `.well-known` URLs or callbacks registered with third parties.
     */
    public static function registerRouteMacro(): void
    {
        $metadata = [self::ROOT_ONLY_METADATA => true];

        Router::macro('rootOnly', function () use ($metadata): RouteRegistrar {
            /** @var Router $this */
            return new RouteRegistrar($this)->metadata($metadata);
        });

        RouteRegistrar::macro('rootOnly', function () use ($metadata): RouteRegistrar {
            /** @var RouteRegistrar $this */
            return $this->metadata($metadata);
        });

        Route::macro('rootOnly', function () use ($metadata): Route {
            /** @var Route $this */
            return $this->metadata($metadata);
        });
    }

    /**
     * Returns whether a route belongs to the sites: it’s in the `craft` middleware group, isn’t a control panel
     * route or under the CP trigger, and wasn’t registered with `rootOnly()`. Site routes match under a site’s
     * base path, and their URLs include it. Routes with a domain keep it.
     */
    public static function isSiteRoute(Route $route): bool
    {
        $middleware = (array) $route->middleware();
        $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');

        return in_array('craft', $middleware, true) &&
            ! in_array('craft.cp', $middleware, true) &&
            ($cpTrigger === '' || ! Str::is([$cpTrigger, "$cpTrigger/*"], $route->uri())) &&
            $route->getMetadata(self::ROOT_ONLY_METADATA) !== true;
    }

    #[Override]
    public function toRoute($route, $parameters, $absolute)
    {
        if (! self::isSiteRoute($route)) {
            return parent::toRoute($route, $parameters, $absolute);
        }

        $site = null;

        if (
            is_array($parameters) &&
            array_key_exists(self::SITE_PARAMETER, $parameters) &&
            ! in_array(self::SITE_PARAMETER, $route->parameterNames(), true) &&
            ($site = $this->site($parameters[self::SITE_PARAMETER])) !== null
        ) {
            unset($parameters[self::SITE_PARAMETER]);
        }

        $root = $absolute ? $this->siteRoot($site) : null;

        if ($root === null) {
            return parent::toRoute($route, $parameters, $absolute);
        }

        $forcedRoot = $this->forcedRoot;
        $this->useOrigin($root);

        try {
            return parent::toRoute($route, $parameters, $absolute);
        } finally {
            $this->useOrigin($forcedRoot);
        }
    }

    /**
     * Leaves the site’s base path that {@see StripSiteBasePath} moved into the request’s base URL out of the root URL.
     */
    #[Override]
    public function formatRoot($scheme, $root = null)
    {
        if ($root === null && $this->forcedRoot === null) {
            $root = $this->request->attributes->get(StripSiteBasePath::ROOT_ATTRIBUTE);
        }

        return parent::formatRoot($scheme, $root);
    }

    private function site(mixed $value): ?Site
    {
        if ($value instanceof Site) {
            return $value;
        }

        if (! Cms::isInstalled()) {
            return null;
        }

        $sites = app(Sites::class);

        return match (true) {
            is_int($value), is_string($value) && ctype_digit($value) => $sites->getSiteById((int) $value, true),
            is_string($value) => $sites->getSiteByHandle($value, true),
            default => null,
        };
    }

    /**
     * Returns the root URL for a site’s routes, or `null` to keep the app’s. The current site keeps the request’s
     * origin and adds its base path, while another site gets its full base URL, which may be on another host.
     */
    private function siteRoot(?Site $site): ?string
    {
        $currentSite = $this->currentSite();
        $site ??= $currentSite;
        $baseUrl = rtrim((string) $site?->getBaseUrl(), '/');

        if ($site?->id !== $currentSite?->id && parse_url($baseUrl, PHP_URL_HOST) !== null) {
            return $baseUrl;
        }

        $basePath = trim((string) parse_url($baseUrl, PHP_URL_PATH), '/');

        if ($basePath === '') {
            return null;
        }

        $origin = preg_replace('#^(\w+://[^/]+).*$#', '$1', $this->formatRoot($this->formatScheme()));

        return "$origin/$basePath";
    }

    private function currentSite(): ?Site
    {
        if (! Cms::isInstalled()) {
            return null;
        }

        try {
            return app(Sites::class)->getCurrentSite();
        } catch (SiteNotFoundException) {
            return null;
        }
    }
}
