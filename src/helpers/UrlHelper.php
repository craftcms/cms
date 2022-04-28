<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\SiteNotFoundException;
use yii\base\Exception;

/**
 * Class Url
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UrlHelper
{
    /**
     * Returns whether a given string appears to be an absolute URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isAbsoluteUrl(string $url): bool
    {
        return (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
    }

    /**
     * Returns whether a given string appears to be a protocol-relative URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isProtocolRelativeUrl(string $url): bool
    {
        return (str_starts_with($url, '//'));
    }

    /**
     * Returns whether a given string appears to be a root-relative URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isRootRelativeUrl(string $url): bool
    {
        return (str_starts_with($url, '/') && !static::isProtocolRelativeUrl($url));
    }

    /**
     * Returns whether a given string appears to be a "full" URL (absolute, root-relative or protocol-relative).
     *
     * @param string $url
     * @return bool
     */
    public static function isFullUrl(string $url): bool
    {
        return (static::isAbsoluteUrl($url) || static::isRootRelativeUrl($url) || static::isProtocolRelativeUrl($url));
    }

    /**
     * Returns a query string based on the given params.
     *
     * @param array $params
     * @return string
     * @since 3.3.0
     */
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
        // Decode the param names and a few select chars in param values
        $params = [];
        foreach (explode('&', $query) as $param) {
            [$n, $v] = array_pad(explode('=', $param, 2), 2, '');
            $n = urldecode($n);
            $v = str_replace(['%2F', '%7B', '%7D'], ['/', '{', '}'], $v);
            $params[] = $v !== '' ? "$n=$v" : $n;
        }
        return implode('&', $params);
    }

    /**
     * Returns a URL with additional query string parameters.
     *
     * @param string $url
     * @param array|string $params
     * @return string
     */
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
        $fragment = $fragment ?? $baseFragment;

        // Append to the base URL and return
        if (($query = static::buildQuery($params)) !== '') {
            $url .= '?' . $query;
        }
        if ($fragment !== null) {
            $url .= '#' . $fragment;
        }
        return $url;
    }

    /**
     * Removes a query string param from a URL.
     *
     * @param string $url
     * @param string $param
     * @return string
     * @since 3.2.2
     */
    public static function removeParam(string $url, string $param): string
    {
        // Extract any params/fragment from the base URL
        [$url, $params, $fragment] = self::_extractParams($url);

        // Remove the param
        unset($params[$param]);

        // Rebuild
        if (($query = static::buildQuery($params)) !== '') {
            $url .= '?' . $query;
        }
        if ($fragment !== null) {
            $url .= '#' . $fragment;
        }
        return $url;
    }

    /**
     * Returns a URL with a 'token' query string param set to a given token.
     *
     * @param string $url
     * @param string $token
     * @param bool $cp Whether this is for a control panel URL
     * @return string
     */
    public static function urlWithToken(string $url, string $token, bool $cp = false): string
    {
        $scheme = static::getSchemeForTokenizedUrl($cp);
        $url = static::urlWithScheme($url, $scheme);

        return static::urlWithParams($url, [
            Craft::$app->getConfig()->getGeneral()->tokenParam => $token,
        ]);
    }

    /**
     * Returns a URL with a specific scheme.
     *
     * @param string $url the URL
     * @param string $scheme the scheme ('http' or 'https')
     * @return string
     * @throws SiteNotFoundException
     */
    public static function urlWithScheme(string $url, string $scheme): string
    {
        if (!$url || !$scheme) {
            return $url;
        }

        if (static::isProtocolRelativeUrl($url)) {
            return $scheme . ':' . $url;
        }

        if (static::isRootRelativeUrl($url)) {
            // Prepend the current request’s scheme and hostname
            $url = static::siteHost() . $url;
        }

        return preg_replace('/^https?:/', $scheme . ':', $url);
    }

    /**
     * Encodes a URL’s query string params.
     *
     * @param string $url
     * @return string
     * @since 3.7.24
     */
    public static function encodeParams(string $url): string
    {
        [$url, $params, $fragment] = self::_extractParams($url);
        return self::_buildUrl($url, $params, $fragment);
    }

    /**
     * Returns a root-relative URL based on the given URL.
     *
     * @param string $url
     * @return string
     * @since 3.1.11
     */
    public static function rootRelativeUrl(string $url): string
    {
        $url = static::urlWithScheme($url, 'http');
        if (strlen($url) > 7 && ($slash = strpos($url, '/', 7)) !== false) {
            return substr($url, $slash);
        }
        // Is this a host without a URI?
        if (str_contains($url, '//')) {
            return '/';
        }
        // Must just be a URI, then
        return '/' . $url;
    }

    /**
     * Returns either a control panel or a site URL, depending on the request type.
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @param bool|null $showScriptName Whether the script name (index.php) should be included in the URL.
     * By default (null) it will defer to the `omitScriptNameInUrls` config setting.
     * @return string
     */
    public static function url(string $path = '', array|string|null $params = null, ?string $scheme = null, ?bool $showScriptName = null): string
    {
        // Return $path if it appears to be an absolute URL.
        if (static::isFullUrl($path)) {
            if ($params) {
                $path = static::urlWithParams($path, $params);
            }

            if ($scheme !== null) {
                $path = static::urlWithScheme($path, $scheme);
            }

            return $path;
        }

        $path = trim($path, '/');

        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest()) {
            $path = static::prependCpTrigger($path);
            $cpUrl = true;
        } else {
            $cpUrl = false;
        }

        // Stick with SSL if the current request is over SSL and a scheme wasn't defined
        if ($scheme === null && !$request->getIsConsoleRequest() && $request->getIsSecureConnection()) {
            $scheme = 'https';
        }

        return self::_createUrl($path, $params, $scheme, $cpUrl, $showScriptName);
    }

    /**
     * Returns a control panel URL.
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @return string
     */
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
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @param int|null $siteId
     * @return string
     * @throws Exception if|null $siteId is invalid
     */
    public static function siteUrl(string $path = '', array|string|null $params = null, ?string $scheme = null, ?int $siteId = null): string
    {
        // Return $path if it appears to be an absolute URL.
        if (static::isAbsoluteUrl($path) || static::isProtocolRelativeUrl($path)) {
            if ($params) {
                $path = static::urlWithParams($path, $params);
            }

            if ($scheme !== null) {
                $path = static::urlWithScheme($path, $scheme);
            }

            return $path;
        }

        // Does this URL point to a different site?
        $sites = Craft::$app->getSites();

        if ($siteId !== null && $siteId != $sites->getCurrentSite()->id) {
            // Get the site
            $site = $sites->getSiteById($siteId, true);

            if (!$site) {
                throw new Exception('Invalid site ID: ' . $siteId);
            }

            // Swap the current site
            $currentSite = $sites->getCurrentSite();
            $sites->setCurrentSite($site);
        }

        $path = trim($path, '/');
        $url = self::_createUrl($path, $params, $scheme, false);

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (isset($currentSite)) {
            // Restore the original current site
            $sites->setCurrentSite($currentSite);
        }

        return $url;
    }

    /**
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme The scheme to use ('http' or 'https'). If empty, the scheme used for the current
     * request will be used.
     * @param bool $showScriptName Whether the script name (index.php) should be included in the URL. Note that
     * it’s only safe to set this to `false` for URLs that will be used for GET requests.
     * @return string
     */
    public static function actionUrl(string $path = '', array|string|null $params = null, ?string $scheme = null, ?bool $showScriptName = null): string
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $path = $generalConfig->actionTrigger . '/' . trim($path, '/');

        $request = Craft::$app->getRequest();

        if ($generalConfig->headlessMode || $request->getIsCpRequest()) {
            $path = static::prependCpTrigger($path);
            $cpUrl = true;
        } else {
            $cpUrl = false;
        }

        // Stick with SSL if the current request is over SSL and a scheme wasn't defined
        if ($scheme === null && !$request->getIsConsoleRequest() && $request->getIsSecureConnection()) {
            $scheme = 'https';
        }

        // Default to showing index.php if there's a pathParam
        if ($showScriptName === null) {
            $showScriptName = (bool)$generalConfig->pathParam;
        }

        return self::_createUrl($path, $params, $scheme, $cpUrl, $showScriptName, false);
    }

    /**
     * Removes the query string from a given URL.
     *
     * @param string $url The URL to check.
     * @return string The URL without a query string.
     */
    public static function stripQueryString(string $url): string
    {
        if (($qIndex = mb_strpos($url, '?')) !== false) {
            $url = mb_substr($url, 0, $qIndex);
        }

        // Just in case the URL had an invalid query string
        if (($qIndex = mb_strpos($url, '&')) !== false) {
            $url = mb_substr($url, 0, $qIndex);
        }

        return $url;
    }

    /**
     * Returns what the scheme part of the URL should be (http/https)
     * for any tokenized URLs in Craft (email verification links, password reset
     * urls, share entry URLs, etc.
     *
     * @param bool $cp Whether this is for a control panel URL
     * @return string
     */
    public static function getSchemeForTokenizedUrl(bool $cp = false): string
    {
        $useSslOnTokenizedUrls = Craft::$app->getConfig()->getGeneral()->useSslOnTokenizedUrls;

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
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        if ($scheme !== false && strtolower($scheme) === 'https') {
            return 'https';
        }

        // Is the current request over SSL?
        $request = Craft::$app->getRequest();
        if (!$request->getIsConsoleRequest() && $request->getIsSecureConnection()) {
            return 'https';
        }

        // Lame ole' http.
        return 'http';
    }

    /**
     * Returns either the current site’s base URL or the control panel’s base URL, depending on the type of request this is.
     *
     * @return string
     * @throws SiteNotFoundException if this is a site request and yet there's no current site for some reason
     */
    public static function baseUrl(): string
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return static::baseCpUrl();
        }
        return static::baseSiteUrl();
    }

    /**
     * Returns the current site’s base URL (with a trailing slash).
     *
     * @return string
     * @throws SiteNotFoundException if there's no current site for some reason
     */
    public static function baseSiteUrl(): string
    {
        try {
            $currentSite = Craft::$app->getSites()->getCurrentSite();
            if (($baseUrl = $currentSite->getBaseUrl()) !== null) {
                return $baseUrl;
            }
        } catch (SiteNotFoundException $e) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
                throw $e;
            }
        }

        // Use @web as a fallback
        return Craft::getAlias('@web');
    }

    /**
     * Returns the control panel’s base URL (with a trailing slash) (sans control panel trigger).
     *
     * @return string
     */
    public static function baseCpUrl(): string
    {
        // Is a custom base control panel URL being defined in the config?
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->baseCpUrl) {
            return rtrim($generalConfig->baseCpUrl, '/') . '/';
        }

        // Use @web as a fallback
        return Craft::getAlias('@web');
    }

    /**
     * Returns the host info for the control panel or the current site, depending on the request type.
     *
     * @return string
     * @throws SiteNotFoundException
     */
    public static function host(): string
    {
        return static::hostInfo(static::baseUrl());
    }

    /**
     * Returns the current site’s host.
     *
     * @return string
     * @throws SiteNotFoundException
     */
    public static function siteHost(): string
    {
        return static::hostInfo(static::baseSiteUrl());
    }

    /**
     * Returns the control panel's host.
     *
     * @return string
     */
    public static function cpHost(): string
    {
        return static::hostInfo(static::baseCpUrl());
    }

    /**
     * Parses a URL for the host info.
     *
     * @param string $url
     * @return string
     */
    public static function hostInfo(string $url): string
    {
        // If there's no host info in the base URL, default to the request's host info
        if (($slashes = strpos($url, '//')) === false) {
            $request = Craft::$app->getRequest();
            if ($request->getIsConsoleRequest()) {
                return '';
            }
            return $request->getHostInfo();
        }

        $host = $url;

        // Trim off the URI
        $uriPos = strpos($host, '/', $slashes + 2);
        if ($uriPos !== false) {
            $host = substr($host, 0, $uriPos);
        }

        return $host;
    }

    /**
     * Prepends the control panel trigger onto the given path.
     *
     * @param string $path
     * @return string
     * @since 3.5.0
     */
    public static function prependCpTrigger(string $path): string
    {
        return implode('/', array_filter([Craft::$app->getConfig()->getGeneral()->cpTrigger, $path]));
    }

    /**
     * Returns a URL.
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @param bool $cpUrl
     * @param bool|null $showScriptName
     * @param bool $addToken
     * @return string
     */
    private static function _createUrl(string $path, array|string|null $params, ?string $scheme, bool $cpUrl, ?bool $showScriptName = null, bool $addToken = true): string
    {
        // Extract any params/fragment from the path
        [$path, $baseParams, $baseFragment] = self::_extractParams($path);

        // Normalize the passed-in params/fragment
        [$params, $fragment] = self::_normalizeParams($params);

        // Combine them
        $params = array_merge($baseParams, $params);
        $fragment = $fragment ?? $baseFragment;

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $request = Craft::$app->getRequest();

        if ($cpUrl) {
            // site param
            if (!isset($params['site']) && Craft::$app->getIsInitialized() && Craft::$app->getIsMultiSite() && Cp::requestedSite() !== null) {
                $params['site'] = Cp::requestedSite()->handle;
            }
        } else {
            // token/siteToken params
            if ($addToken && !isset($params[$generalConfig->tokenParam]) && ($token = $request->getToken()) !== null) {
                $params[$generalConfig->tokenParam] = $token;
            }
            if (!isset($params[$generalConfig->siteToken]) && ($siteToken = $request->getSiteToken()) !== null) {
                $params[$generalConfig->siteToken] = $siteToken;
            }
        }

        if ($showScriptName === null) {
            $showScriptName = !$generalConfig->omitScriptNameInUrls;
        }

        // If we must show the script name, then just start with the script URL,
        // regardless of whether this is a control panel or site request, as we can't assume
        // that index.php lives within the base URL anymore.
        if ($showScriptName) {
            if ($request->getIsConsoleRequest()) {
                // No way to know for sure, so just guess
                $baseUrl = '/index.php';
            } else {
                $baseUrl = static::host() . $request->getScriptUrl();
            }
        } elseif ($cpUrl) {
            $baseUrl = static::baseCpUrl();
        } else {
            $baseUrl = static::baseSiteUrl();
        }

        if ($scheme === null && !static::isAbsoluteUrl($baseUrl)) {
            $scheme = !$request->getIsConsoleRequest() && $request->getIsSecureConnection() ? 'https' : 'http';
        }

        if ($scheme !== null) {
            // Make sure we're using the right scheme
            $baseUrl = static::urlWithScheme($baseUrl, $scheme);
        }

        // Put it all together
        if (!$showScriptName || $generalConfig->usePathInfo || !$generalConfig->pathParam) {
            if ($path) {
                $url = rtrim($baseUrl, '/') . '/' . trim($path, '/');

                if (!$cpUrl && $generalConfig->addTrailingSlashesToUrls && !preg_match('/\.[^\/]+$/', $url)) {
                    $url .= '/';
                }
            } else {
                $url = $baseUrl;
            }
        } else {
            $url = $baseUrl;

            if ($path) {
                // Prepend it to the params array
                ArrayHelper::remove($params, $generalConfig->pathParam);
                $params = array_merge([$generalConfig->pathParam => $path], $params);
            }
        }

        return self::_buildUrl($url, $params, $fragment);
    }

    /**
     * Rebuilds a URL with params and a fragment.
     *
     * @param string $url
     * @param array $params
     * @param string|null $fragment
     * @return string
     */
    private static function _buildUrl(string $url, array $params, ?string $fragment): string
    {
        if (($query = static::buildQuery($params)) !== '') {
            $url .= '?' . $query;
        }

        if ($fragment !== null) {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    /**
     * Normalizes query string params.
     *
     * @param string|array|null $params
     * @return array
     */
    private static function _normalizeParams(array|string|null $params): array
    {
        // If it's already an array, just split out the fragment and return
        if (is_array($params)) {
            $fragment = ArrayHelper::remove($params, '#');
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
     *
     * @param string $url
     * @return array
     */
    private static function _extractParams(string $url): array
    {
        if (($queryPos = strpos($url, '?')) === false && ($queryPos = strpos($url, '#')) === false) {
            return [$url, [], null];
        }

        [$params, $fragment] = self::_normalizeParams(substr($url, $queryPos));
        return [substr($url, 0, $queryPos), $params, $fragment];
    }
}
