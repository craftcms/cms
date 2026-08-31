<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Update\Updates;
use Exception;
use Illuminate\Support\Uri;

class Url extends \Illuminate\Support\Facades\URL
{
    /**
     * Returns whether a given string appears to be an absolute URL.
     */
    public static function isAbsoluteUrl(string $url): bool
    {
        // From https://developer.apple.com/documentation/webkit/wkwebviewconfiguration/2875766-seturlschemehandler:
        // > Scheme names are case sensitive, must start with an ASCII letter, and may contain only ASCII letters,
        // > numbers, the “+” character, the “-” character, and the “.” character.
        if (! preg_match('/^[a-z][a-z0-9+-.]*:/i', $url)) {
            return false;
        }

        // Make sure it's not a Windows file path
        if (preg_match('/^[a-z]:(?:$|\\\)/i', $url)) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether a given string appears to be a protocol-relative URL.
     */
    public static function isProtocolRelativeUrl(string $url): bool
    {
        return str_starts_with($url, '//');
    }

    /**
     * Returns whether a given string appears to be a root-relative URL.
     */
    public static function isRootRelativeUrl(string $url): bool
    {
        return str_starts_with($url, '/') && ! static::isProtocolRelativeUrl($url);
    }

    /**
     * Returns whether a given string appears to be a "full" URL (absolute, root-relative or protocol-relative).
     */
    public static function isFullUrl(string $url): bool
    {
        if (static::isAbsoluteUrl($url)) {
            return true;
        }
        if (static::isRootRelativeUrl($url)) {
            return true;
        }

        return static::isProtocolRelativeUrl($url);
    }

    /**
     * Returns a query string based on the given params.
     *
     * Param names and values will be encoded, except for `{` and `}` characters.
     */
    /** @param array<string, mixed> $params */
    public static function buildQuery(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        // build the query string
        $query = http_build_query($params);
        if ($query === '') {
            return '';
        }

        // Decode curly braces
        $params = [];

        foreach (explode('&', $query) as $param) {
            [$n, $v] = array_pad(explode('=', $param, 2), 2, '');
            $n = str_replace(['%7B', '%7D'], ['{', '}'], $n);
            $v = str_replace(['%7B', '%7D'], ['{', '}'], $v);
            $params[] = $v !== '' ? "$n=$v" : $n;
        }

        return implode('&', $params);
    }

    /**
     * Returns a URL with additional query string parameters.
     */
    /** @param array<string, mixed>|string $params */
    public static function urlWithParams(string $url, array|string $params): string
    {
        if (empty($params)) {
            return $url;
        }

        // Extract any params/fragment from the base URL
        [$url, $baseParams, $baseFragment] = self::_extractParams($url);

        // Normalize the passed-in params/fragment
        [$params, $fragment] = self::_normalizeParams($params);

        // Combine them
        $params = array_merge($baseParams, $params);
        $fragment ??= $baseFragment;

        // Append to the base URL and return
        if (($query = static::buildQuery($params)) !== '') {
            $url .= '?'.$query;
        }
        if ($fragment !== null) {
            $url .= '#'.$fragment;
        }

        return $url;
    }

    /**
     * Removes a query string param from a URL.
     */
    public static function removeParam(string $url, string $param): string
    {
        // Extract any params/fragment from the base URL
        [$url, $params, $fragment] = self::_extractParams($url);

        // Remove the param
        unset($params[$param]);

        // Rebuild
        if (($query = static::buildQuery($params)) !== '') {
            $url .= '?'.$query;
        }
        if ($fragment !== null) {
            $url .= '#'.$fragment;
        }

        return $url;
    }

    /**
     * Returns a URL with a 'token' query string param set to a given token.
     *
     * @param  bool  $cp  Whether this is for a control panel URL
     */
    public static function urlWithToken(string $url, string $token, bool $cp = false): string
    {
        $scheme = static::getSchemeForTokenizedUrl($cp);
        $url = static::urlWithScheme($url, $scheme);

        return static::urlWithParams($url, [
            Cms::config()->tokenParam => $token,
        ]);
    }

    /**
     * Returns a URL with a specific scheme.
     *
     * @param  string  $url  the URL
     * @param  string  $scheme  the scheme ('http' or 'https')
     *
     * @throws SiteNotFoundException
     */
    public static function urlWithScheme(string $url, string $scheme): string
    {
        if (! $url || ! $scheme) {
            return $url;
        }

        if (static::isProtocolRelativeUrl($url)) {
            return Uri::of($url)
                ->withScheme($scheme)
                ->value();
        }

        if (static::isRootRelativeUrl($url)) {
            // Prepend the current request’s scheme and hostname
            $url = static::siteHost().$url;
        }

        if (! preg_match('/^https?:/', $url)) {
            return $url;
        }

        return Uri::of($url)
            ->withScheme($scheme)
            ->value();
    }

    /**
     * Encodes a URL’s query string param values, except for `/`, `{`, and `}` characters.
     */
    public static function encodeParams(string $url): string
    {
        [$url, $params, $fragment] = self::_extractParams($url);

        return self::_buildUrl($url, $params, $fragment);
    }

    /**
     * Encodes non-alphanumeric characters in a URL, except reserved characters and already-encoded characters.
     */
    public static function encodeUrl(string $url): string
    {
        $parts = preg_split('/([:\/?#\[\]@!$&\'()*+,;=%])/', $url, flags: PREG_SPLIT_DELIM_CAPTURE);
        $url = '';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $url .= urlencode($part);
            } else {
                $url .= $part;
            }
        }

        return $url;
    }

    /**
     * Returns a root-relative URL based on the given URL.
     */
    public static function rootRelativeUrl(string $url): string
    {
        if (static::isAbsoluteUrl($url) || static::isProtocolRelativeUrl($url) || static::isRootRelativeUrl($url)) {
            $path = Uri::of($url)->path();

            return $path === '/' ? '/' : '/'.ltrim($path, '/');
        }

        return '/'.ltrim($url, '/');
    }

    /**
     * Returns either a control panel or a site URL, depending on the request type.
     */
    /** @param array<string, mixed>|string|null $params */
    public static function url(string $path = '', array|string|null $params = null, ?string $scheme = null): string
    {
        // Return $path if it appears to be an absolute URL.
        if (static::isFullUrl($path)) {
            if ($params) {
                $path = static::urlWithParams($path, $params);
            }

            if ($scheme !== null) {
                return static::urlWithScheme($path, $scheme);
            }

            return $path;
        }

        $path = trim($path, '/');
        $request = request();

        if ($request->isCpRequest()) {
            $path = static::prependCpTrigger($path);
            $cpUrl = true;
        } else {
            $cpUrl = false;
        }

        // Stick with SSL if the current request is over SSL and a scheme wasn't defined
        if ($scheme === null && ! app()->runningInConsole() && $request->secure()) {
            $scheme = 'https';
        }

        return self::_createUrl($path, $params, $scheme, $cpUrl);
    }

    /**
     * Returns a control panel URL.
     */
    /** @param array<string, mixed>|string|null $params */
    public static function cpUrl(string $path = '', array|string|null $params = null, ?string $scheme = null): string
    {
        // If this is already an absolute or root-relative URL, don't change it
        if (static::isAbsoluteUrl($path) || static::isRootRelativeUrl($path)) {
            return static::url($path, $params, $scheme);
        }

        $path = trim($path, '/');
        $path = static::prependCpTrigger($path);

        return self::_createUrl($path, $params, $scheme, true);
    }

    /**
     * Returns a site URL.
     *
     * @throws Exception if|null $siteId is invalid
     */
    /** @param array<string, mixed>|string|null $params */
    public static function siteUrl(string $path = '', array|string|null $params = null, ?string $scheme = null, ?int $siteId = null): string
    {
        // Return $path if it appears to be an absolute URL.
        if (static::isAbsoluteUrl($path) || static::isProtocolRelativeUrl($path)) {
            if ($params) {
                $path = static::urlWithParams($path, $params);
            }

            if ($scheme !== null) {
                return static::urlWithScheme($path, $scheme);
            }

            return $path;
        }

        // Does this URL point to a different site?
        if ($siteId !== null && $siteId != Sites::getCurrentSite()->id) {
            // Get the site
            $site = Sites::getSiteById($siteId, true);

            if (! $site) {
                throw new Exception('Invalid site ID: '.$siteId);
            }

            // Swap the current site
            $currentSite = Sites::getCurrentSite();
            Sites::setCurrentSite($site);
        }

        $path = trim($path, '/');
        $url = self::_createUrl($path, $params, $scheme, false);

        if (isset($currentSite)) {
            // Restore the original current site
            Sites::setCurrentSite($currentSite);
        }

        return $url;
    }

    /**
     * @param  string|null  $scheme  The scheme to use ('http' or 'https'). If empty, the scheme used for the current
     *                               request will be used.
     */
    /** @param array<string, mixed>|string|null $params */
    public static function actionUrl(string $path = '', array|string|null $params = null, ?string $scheme = null): string
    {
        $generalConfig = Cms::config();
        $path = $generalConfig->actionTrigger.'/'.trim($path, '/');

        $request = request();

        if ($generalConfig->headlessMode || $request->isCpRequest()) {
            $path = static::prependCpTrigger($path);
            $cpUrl = true;
        } else {
            $cpUrl = false;
        }

        // Stick with SSL if the current request is over SSL and a scheme wasn't defined
        if ($scheme === null && ! app()->runningInConsole() && $request->secure()) {
            $scheme = 'https';
        }

        return self::_createUrl($path, $params, $scheme, $cpUrl, true, false);
    }

    /**
     * Removes the query string from a given URL.
     *
     * @param  string  $url  The URL to check.
     * @return string The URL without a query string.
     */
    public static function stripQueryString(string $url): string
    {
        return str($url)
            ->before('?')
            ->before('&')
            ->value();
    }

    /**
     * Returns what the scheme part of the URL should be (http/https)
     * for any tokenized URLs in Craft (email verification links, password reset
     * urls, share entry URLs, etc.
     *
     * @param  bool  $cp  Whether this is for a control panel URL
     */
    public static function getSchemeForTokenizedUrl(bool $cp = false): string
    {
        $useSslOnTokenizedUrls = Cms::config()->useSslOnTokenizedUrls;

        // If they've explicitly set `useSslOnTokenizedUrls` to true, use https.
        if ($useSslOnTokenizedUrls === true) {
            return 'https';
        }

        // If they've explicitly set `useSslOnTokenizedUrls` to false, use http.
        if ($useSslOnTokenizedUrls === false) {
            return 'http';
        }

        // Is the base URL set to https?
        $baseUrl = $cp ? static::baseCpUrl() : static::baseSiteUrl();
        if (strtolower((string) Uri::of($baseUrl)->scheme()) === 'https') {
            return 'https';
        }

        // Is the current request over SSL?
        if (! app()->runningInConsole() && request()->secure()) {
            return 'https';
        }

        // Lame ole' http.
        return 'http';
    }

    /**
     * Returns either the current site’s base URL or the control panel’s base URL, depending on the type of request this is.
     *
     * @throws SiteNotFoundException if this is a site request and yet there's no current site for some reason
     */
    public static function baseUrl(): string
    {
        if (request()->isCpRequest()) {
            return static::baseCpUrl();
        }

        return static::baseSiteUrl();
    }

    /**
     * Returns the current site’s base URL (with a trailing slash).
     *
     * @throws SiteNotFoundException if there's no current site for some reason
     */
    public static function baseSiteUrl(): string
    {
        try {
            $currentSite = Sites::getCurrentSite();
            if (($baseUrl = $currentSite->getBaseUrl()) !== null) {
                return $baseUrl;
            }
        } catch (SiteNotFoundException $e) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Cms::isInstalled() && ! app(Updates::class)->isCraftUpdatePending()) {
                throw $e;
            }
        }

        return url('/');
    }

    /**
     * Returns the control panel’s base URL (with a trailing slash) (sans control panel trigger).
     */
    public static function baseCpUrl(): string
    {
        // Is a custom base control panel URL being defined in the config?
        $generalConfig = Cms::config();
        if ($generalConfig->baseCpUrl) {
            return rtrim($generalConfig->baseCpUrl, '/').'/';
        }

        return self::fallbackBaseUrl();
    }

    private static function fallbackBaseUrl(): string
    {
        return app()->runningInConsole()
            ? static::baseSiteUrl()
            : url('/');
    }

    /**
     * Returns the host info for the control panel or the current site, depending on the request type.
     *
     * @throws SiteNotFoundException
     */
    public static function host(): string
    {
        return static::hostInfo(static::baseUrl());
    }

    /**
     * Returns the current site’s host.
     *
     * @throws SiteNotFoundException
     */
    public static function siteHost(): string
    {
        return static::hostInfo(static::baseSiteUrl());
    }

    /**
     * Returns the control panel's host.
     */
    public static function cpHost(): string
    {
        return static::hostInfo(static::baseCpUrl());
    }

    /**
     * Parses a URL for the host info.
     */
    public static function hostInfo(string $url): string
    {
        $uri = Uri::of($url);

        if (($authority = $uri->authority()) !== null) {
            if (($scheme = $uri->scheme()) !== null) {
                return "$scheme://$authority";
            }

            return "//$authority";
        }

        if (app()->runningInConsole()) {
            return '';
        }

        return request()->schemeAndHttpHost();
    }

    /**
     * Prepends the control panel trigger onto the given path.
     */
    public static function prependCpTrigger(string $path): string
    {
        $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
        $path = trim($path, '/');

        if ($cpTrigger !== '' && ($path === $cpTrigger || str_starts_with($path, "$cpTrigger/"))) {
            return $path;
        }

        return implode('/', array_filter([$cpTrigger, $path]));
    }

    /**
     * Returns a URL.
     */
    /** @param array<string, mixed>|string|null $params */
    private static function _createUrl(
        string $path,
        array|string|null $params,
        ?string $scheme,
        bool $cpUrl,
        bool $useRequestHostInfo = false,
        bool $addToken = true,
    ): string {
        // Extract any params/fragment from the path
        [$path, $baseParams, $baseFragment] = self::_extractParams($path);

        // Normalize the passed-in params/fragment
        [$params, $fragment] = self::_normalizeParams($params);

        // Combine them
        $params = array_merge($baseParams, $params);
        $fragment ??= $baseFragment;

        $generalConfig = Cms::config();
        $request = request();

        if ($cpUrl) {
            // site param
            if (! isset($params['site']) && Sites::isMultiSite() && app(RequestedSite::class)->get() !== null) {
                $params['site'] = app(RequestedSite::class)->get()->handle;
            }
        } else {
            // token/siteToken/preview params
            if (! isset($params[$generalConfig->siteToken]) && ($siteToken = $request->siteToken()) !== null) {
                $params[$generalConfig->siteToken] = $siteToken;
            }
            if ($request->isSiteRequest()) {
                if (
                    $addToken &&
                    ! isset($params[$generalConfig->tokenParam]) &&
                    ($token = $request->getToken()) !== null &&
                    app(RouteTokens::class)->getRemainingTokenUsages($token) !== 0
                ) {
                    $params[$generalConfig->tokenParam] = $token;
                }

                if (
                    ! isset($params['x-craft-preview']) &&
                    ! isset($params['x-craft-live-preview']) &&
                    $request->isPreview()
                ) {
                    if (($previewToken = $request->query('x-craft-preview')) !== null) {
                        $params['x-craft-preview'] = $previewToken;
                    } elseif (($previewToken = $request->query('x-craft-live-preview')) !== null) {
                        $params['x-craft-live-preview'] = $previewToken;
                    }
                }
            }
        }

        if ($useRequestHostInfo) {
            $baseUrl = self::fallbackBaseUrl();
        } elseif ($cpUrl) {
            $baseUrl = static::baseCpUrl();
        } else {
            $baseUrl = static::baseSiteUrl();
        }

        if ($scheme === null && ! static::isAbsoluteUrl($baseUrl)) {
            $scheme = ! app()->runningInConsole() && $request->secure() ? 'https' : 'http';
        }

        if ($scheme !== null) {
            // Make sure we're using the right scheme
            $baseUrl = static::urlWithScheme($baseUrl, $scheme);
        }

        // Put it all together
        if ($path) {
            $url = rtrim($baseUrl, '/').'/'.trim($path, '/');

            if (! $cpUrl && $generalConfig->addTrailingSlashesToUrls && ! preg_match('/\.[^\/]+$/', $url)) {
                $url .= '/';
            }
        } else {
            $url = $baseUrl;
        }

        return self::_buildUrl($url, $params, $fragment);
    }

    /**
     * Rebuilds a URL with params and a fragment.
     */
    /** @param array<string, mixed> $params */
    private static function _buildUrl(string $url, array $params, ?string $fragment): string
    {
        if (($query = static::buildQuery($params)) !== '') {
            $url .= '?'.$query;
        }

        if ($fragment !== null) {
            $url .= '#'.$fragment;
        }

        return $url;
    }

    /**
     * Normalizes query string params.
     */
    /**
     * @param  array<string, mixed>|string|null  $params
     * @return array{array<string, mixed>, ?string}
     */
    private static function _normalizeParams(array|string|null $params): array
    {
        // If it's already an array, just split out the fragment and return
        if (is_array($params)) {
            $fragment = Arr::pull($params, '#');

            return [$params, $fragment];
        }

        $fragment = null;

        if (is_string($params)) {
            $params = ltrim($params, '?&');

            if (($fragmentPos = strpos($params, '#')) !== false) {
                $fragment = substr($params, $fragmentPos + 1);
                $params = substr($params, 0, $fragmentPos);
            }

            parse_str($params, $arr);
        } else {
            $arr = [];
        }

        return [$arr, $fragment];
    }

    /**
     * Extracts the params and fragment from a given URL, and merges those with another set of params.
     */
    /** @return array{string, array<string, mixed>, ?string} */
    private static function _extractParams(string $url): array
    {
        if (($queryPos = strpos($url, '?')) === false && ($queryPos = strpos($url, '#')) === false) {
            return [$url, [], null];
        }

        [$params, $fragment] = self::_normalizeParams(substr($url, $queryPos));

        return [substr($url, 0, $queryPos), $params, $fragment];
    }
}
