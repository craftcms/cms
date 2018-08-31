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
 * @since 3.0
 */
class UrlHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether a given string appears to be an absolute URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isAbsoluteUrl(string $url): bool
    {
        return (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
    }

    /**
     * Returns whether a given string appears to be a protocol-relative URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isProtocolRelativeUrl(string $url): bool
    {
        return (strpos($url, '//') === 0);
    }

    /**
     * Returns whether a given string appears to be a root-relative URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isRootRelativeUrl(string $url): bool
    {
        return (strpos($url, '/') === 0 && !static::isProtocolRelativeUrl($url));
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
     * Returns a URL with additional query string parameters.
     *
     * @param string $url
     * @param array|string $params
     * @return string
     */
    public static function urlWithParams(string $url, $params): string
    {
        $params = self::_normalizeParams($params, $fragment);

        if ($params) {
            if (StringHelper::contains($url, '?')) {
                $url .= '&';
            } else {
                $url .= '?';
            }

            $url .= $params;
        }

        if ($fragment) {
            $url .= $fragment;
        }

        return $url;
    }

    /**
     * Returns a URL with a 'token' query string param set to a given token.
     *
     * @param string $url
     * @param string $token
     * @return string
     */
    public static function urlWithToken(string $url, string $token): string
    {
        $scheme = static::getSchemeForTokenizedUrl();
        $url = static::urlWithScheme($url, $scheme);

        return static::urlWithParams($url, [
            Craft::$app->getConfig()->getGeneral()->tokenParam => $token
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
            // Prepend the current request's scheme and host name
            $url = static::siteHost() . $url;
        }

        return preg_replace('/^https?:/', $scheme . ':', $url);
    }

    /**
     * Returns either a CP or a site URL, depending on the request type.
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @param bool|null $showScriptName Whether the script name (index.php) should be included in the URL.
     * By default (null) it will defer to the `omitScriptNameInUrls` config setting.
     * @return string
     */
    public static function url(string $path = '', $params = null, string $scheme = null, bool $showScriptName = null): string
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
            $path = Craft::$app->getConfig()->getGeneral()->cpTrigger . ($path ? '/' . $path : '');
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
     * Returns a CP URL.
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @return string
     */
    public static function cpUrl(string $path = '', $params = null, string $scheme = null): string
    {
        $path = trim($path, '/');
        $path = Craft::$app->getConfig()->getGeneral()->cpTrigger . ($path ? '/' . $path : '');

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
    public static function siteUrl(string $path = '', $params = null, string $scheme = null, int $siteId = null): string
    {
        // Does this URL point to a different site?
        $sites = Craft::$app->getSites();

        if ($siteId !== null && $siteId != $sites->getCurrentSite()->id) {
            // Get the site
            $site = $sites->getSiteById($siteId);

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
     * @return string
     */
    public static function actionUrl(string $path = '', $params = null, string $scheme = null): string
    {
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/' . trim($path, '/');

        return static::url($path, $params, $scheme, true);
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
     * @return string
     */
    public static function getSchemeForTokenizedUrl(): string
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

        // Let's auto-detect.

        // If the siteUrl is https or the current request is https, use it.
        $scheme = parse_url(static::baseSiteUrl(), PHP_URL_SCHEME);

        $request = Craft::$app->getRequest();
        if (($scheme !== false && strtolower($scheme) === 'https') || (!$request->getIsConsoleRequest() && $request->getIsSecureConnection())) {
            return 'https';
        }

        // Lame ole' http.
        return 'http';
    }

    /**
     * Returns either the current site’s base URL or the CP base URL, depending on the type of request this is.
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
            if ($currentSite->baseUrl) {
                return rtrim(Craft::getAlias($currentSite->baseUrl), '/') . '/';
            }
        } catch (SiteNotFoundException $e) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                throw $e;
            }
        }

        // Use the request's base URL as a fallback
        return static::baseRequestUrl();
    }

    /**
     * Returns the Control Panel’s base URL (with a trailing slash) (sans-CP trigger).
     *
     * @return string
     */
    public static function baseCpUrl(): string
    {
        // Is a custom base CP URL being defined in the config?
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->baseCpUrl) {
            return rtrim($generalConfig->baseCpUrl, '/') . '/';
        }

        // Use the request's base URL as a fallback
        return static::baseRequestUrl();
    }

    /**
     * Returns the base URL (with a trailing slash) for the current request.
     *
     * @return string
     */
    public static function baseRequestUrl(): string
    {
        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            return '/';
        }

        return rtrim($request->getHostInfo() . $request->getBaseUrl(), '/') . '/';
    }

    /**
     * Returns the host info for the CP or the current site, depending on the request type.
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
     * Returns the Control Panel's host.
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

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Returns a URL with a specific scheme.
     *
     * @param string $url the URL
     * @param string $scheme the scheme ('http' or 'https')
     * @return string
     * @deprecated in 3.0. Use [[urlWithScheme()]] instead.
     */
    public static function urlWithProtocol(string $url, string $scheme): string
    {
        Craft::$app->getDeprecator()->log('UrlHelper::urlWithProtocol()', 'UrlHelper::urlWithProtocol() is deprecated. Use urlWithScheme() instead.');
        return static::urlWithScheme($url, $scheme);
    }

    /**
     * Returns what the scheme part of the URL should be (http/https)
     * for any tokenized URLs in Craft (email verification links, password reset
     * urls, share entry URLs, etc.
     *
     * @return string
     * @deprecated in 3.0. Use [[getSchemeForTokenizedUrl()]] instead.
     */
    public static function getProtocolForTokenizedUrl(): string
    {
        Craft::$app->getDeprecator()->log('UrlHelper::getProtocolForTokenizedUrl()', 'UrlHelper::getProtocolForTokenizedUrl() is deprecated. Use getSchemeForTokenizedUrl() instead.');
        return static::getSchemeForTokenizedUrl();
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a URL.
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @param bool $cpUrl
     * @param bool|null $showScriptName
     * @return string
     */
    private static function _createUrl(string $path, $params, string $scheme = null, bool $cpUrl, bool $showScriptName = null): string
    {
        // Normalize the params
        $params = self::_normalizeParams($params, $fragment);

        // Were there already any query string params in the path?
        if (($qpos = mb_strpos($path, '?')) !== false) {
            $params = substr($path, $qpos + 1) . ($params ? '&' . $params : '');
            $path = substr($path, 0, $qpos);
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $request = Craft::$app->getRequest();

        if ($showScriptName === null) {
            $showScriptName = !$generalConfig->omitScriptNameInUrls;
        }

        // If we must show the script name, then just start with the script URL,
        // regardless of whether this is a CP or site request, as we can't assume
        // that index.php lives within the base URL anymore.
        if ($showScriptName) {
            if ($request->getIsConsoleRequest()) {
                // No way to know for sure, so just guess
                $baseUrl = '/' . $request->getScriptFilename();
            } else {
                $baseUrl = static::host() . $request->getScriptUrl();
            }
        } else if ($cpUrl) {
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
        if (!$showScriptName || $generalConfig->usePathInfo) {
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
                $pathParam = $generalConfig->pathParam;
                $params = $pathParam . '=' . $path . ($params ? '&' . $params : '');
            }
        }

        if ($params) {
            $url .= '?' . $params;
        }

        if ($fragment) {
            $url .= $fragment;
        }

        return $url;
    }

    /**
     * Normalizes query string params.
     *
     * @param string|array|null $params
     * @param string|null &$fragment
     * @return string
     */
    private static function _normalizeParams($params, &$fragment = null): string
    {
        if (is_array($params)) {
            // See if there's an anchor
            if (isset($params['#'])) {
                $fragment = '#' . $params['#'];
                unset($params['#']);
            }

            $params = http_build_query($params);
        } else {
            $params = trim($params, '&?');
        }

        return $params;
    }
}
