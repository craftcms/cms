<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Routes site requests relative to their site’s base URL, so a request for `https://example.com/fr/news`
 * on a site whose base URL is `https://example.com/fr/` matches the `news` route.
 *
 * Only routes in the `craft` middleware group are matched this way, such as plugins’ web routes and
 * Settings → Routes. Craft’s fallback route for element URIs and templates is in that group too, but a
 * request that matches another route with its full path, such as an app route for `fr/news`, keeps that path.
 *
 * The request is served as if the app were installed in the `fr` folder: `path()` and `is()` leave the
 * base path out, while `url()` and `fullUrl()` keep it. {@see UrlGenerator} keeps generating URLs from the
 * app’s root URL, and adds the site’s base path to URLs for site routes.
 *
 * @since 6.0.0
 */
readonly class StripSiteBasePath
{
    /** The app’s root URL, without the site’s base path. */
    public const string ROOT_ATTRIBUTE = 'craft.stripSiteBasePath.root';

    public function __construct(
        private ResolveSite $resolveSite,
        private Router $router,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        // Requests that another middleware rewrote to a route path don't have the base path
        if ($request->attributes->has(ResolveSite::REQUEST_PATH_ATTRIBUTE)) {
            return $next($request);
        }

        $site = $this->resolveSite->requestedSite($request);
        $basePath = trim((string) parse_url((string) $site?->getBaseUrl(), PHP_URL_PATH), '/');
        $appPath = trim(rawurldecode($request->getBasePath()), '/');
        $path = trim($request->decodedPath(), '/');

        // For an app in a folder, such as `craft`, the base path is within it: `fr` for `/craft/fr/`
        if ($appPath !== '') {
            $basePath = str_starts_with("$basePath/", "$appPath/") ? ltrim(substr($basePath, strlen($appPath)), '/') : '';
        }

        if ($basePath === '' || ! str_starts_with("$path/", "$basePath/")) {
            return $next($request);
        }

        $sitePath = ltrim(substr($path, strlen($basePath)), '/');
        $newRequest = $this->withBasePath($request, $basePath, $sitePath === '');

        // The CP trigger under the base path stays a site path
        if ($newRequest->isCpRequest()) {
            return $next($request);
        }

        $siteRoute = $this->matchingRoute($newRequest);

        if ($siteRoute === null || ! UrlGenerator::isSiteRoute($siteRoute)) {
            return $next($request);
        }

        // Another route for the full path wins over Craft’s fallback route
        if ($siteRoute->isFallback) {
            $route = $this->matchingRoute($request);

            if ($route !== null && ! $route->isFallback) {
                return $next($request);
            }
        }

        $newRequest->attributes->set(ResolveSite::REQUEST_PATH_ATTRIBUTE, ResolveSite::requestPath($request));
        $newRequest->attributes->set(self::ROOT_ATTRIBUTE, $request->root());
        app()->instance('request', $newRequest);

        return $next($newRequest);
    }

    private function matchingRoute(Request $request): ?Route
    {
        try {
            return $this->router->getRoutes()->match($request);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return null;
        }
    }

    /**
     * Duplicates the request with a `SCRIPT_NAME` in the base path’s folder, which Symfony derives the base URL
     * from. The base path survives later duplicates, such as when cached routes are matched. Symfony compares
     * `SCRIPT_NAME` with the decoded request URI, so it’s decoded too.
     *
     * Symfony only derives the folder from a URI inside it, so a request for the base path itself, such as `/fr`,
     * gets a trailing slash.
     */
    private function withBasePath(Request $request, string $basePath, bool $isBasePath): Request
    {
        $baseUrl = Closure::bind(fn (): string => $this->getBaseUrlReal(), $request, SymfonyRequest::class)();
        $baseSegments = array_slice(explode('/', ltrim($request->getPathInfo(), '/')), 0, substr_count($basePath, '/') + 1);
        $scriptFilename = (string) $request->server->get('SCRIPT_FILENAME') ?: 'index.php';
        $server = [
            ...$request->server->all(),
            'SCRIPT_FILENAME' => $scriptFilename,
            'SCRIPT_NAME' => rawurldecode("$baseUrl/".implode('/', $baseSegments)).'/'.basename($scriptFilename),
        ];

        if ($isBasePath) {
            $parts = explode('?', (string) $request->server->get('REQUEST_URI'), 2);
            $server['REQUEST_URI'] = rtrim($parts[0], '/').'/'.(isset($parts[1]) ? "?$parts[1]" : '');
        }

        return $request->duplicate(server: $server);
    }
}
