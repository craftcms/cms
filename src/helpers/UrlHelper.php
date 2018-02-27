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
     */
    public static function urlWithScheme(string $url, string $scheme): string
    {
        if (!$url || !$scheme) {
            return $url;
        }

        if (static::isProtocolRelativeUrl($url)) {
            return $scheme.':'.$url;
        }

        if (static::isRootRelativeUrl($url)) {
            // Prepend the current request's scheme and host name
            $url = Craft::$app->getRequest()->getHostInfo().$url;
        }

        return preg_replace('/^https?:/', $scheme.':', $url);
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
            $path = Craft::$app->getConfig()->getGeneral()->cpTrigger.($path ? '/'.$path : '');
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
        $path = Craft::$app->getConfig()->getGeneral()->cpTrigger.($path ? '/'.$path : '');

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
                throw new Exception('Invalid site ID: '.$siteId);
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
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/'.trim($path, '/');

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
        $scheme = parse_url(static::baseUrl(), PHP_URL_SCHEME);

        $request = Craft::$app->getRequest();
        if (($scheme !== false && strtolower($scheme) === 'https') || (!$request->getIsConsoleRequest() && $request->getIsSecureConnection())) {
            return 'https';
        }

        // Lame ole' http.
        return 'http';
    }

    /**
     * Returns the current site’s base URL (with a trailing slash).
     *
     * @return string
     */
    public static function baseUrl(): string
    {
        try {
            $currentSite = Craft::$app->getSites()->getCurrentSite();
        } catch (SiteNotFoundException $e) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw $e;
            }
            $currentSite = null;
        }

        if ($currentSite && $currentSite->baseUrl) {
            $baseUrl = Craft::getAlias($currentSite->baseUrl);
        } else {
            // Figure it out for ourselves, then
            $request = Craft::$app->getRequest();
            if ($request->getIsConsoleRequest()) {
                $baseUrl = '';
            } else {
                $baseUrl = $request->getHostInfo().$request->getBaseUrl();
            }
        }

        return rtrim($baseUrl, '/').'/';
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
            $params = substr($path, $qpos + 1).($params ? '&'.$params : '');
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
                $baseUrl = '/'.$request->getScriptFilename();
            } else {
                $baseUrl = $request->getHostInfo().$request->getScriptUrl();
            }
        } else if ($cpUrl) {
            // Did they set the base URL manually?
            $baseUrl = $generalConfig->baseCpUrl;

            if ($baseUrl) {
                // Make sure it ends in a slash
                $baseUrl = StringHelper::ensureRight($baseUrl, '/');

                if ($scheme !== null) {
                    // Make sure we're using the right scheme
                    $baseUrl = static::urlWithScheme($baseUrl, $scheme);
                }
            } else if ($request->getIsConsoleRequest()) {
                // No way to know for sure, so just guess
                $baseUrl = '/';
            } else {
                // Figure it out for ourselves, then
                $baseUrl = $request->getHostInfo().$request->getBaseUrl();

                if ($scheme !== null) {
                    $baseUrl = static::urlWithScheme($baseUrl, $scheme);
                }
            }
        } else {
            $baseUrl = static::baseUrl();
        }

        // Put it all together
        if (!$showScriptName || $generalConfig->usePathInfo) {
            if ($path) {
                $url = rtrim($baseUrl, '/').'/'.trim($path, '/');

                if (!$cpUrl && $generalConfig->addTrailingSlashesToUrls) {
                    $url .= '/';
                }
            } else {
                $url = $baseUrl;
            }
        } else {
            $url = $baseUrl;

            if ($path) {
                $pathParam = $generalConfig->pathParam;
                $params = $pathParam.'='.$path.($params ? '&'.$params : '');
            }
        }

        if ($params) {
            $url .= '?'.$params;
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
                $fragment = '#'.$params['#'];
                unset($params['#']);
            }

            $params = http_build_query($params);
        } else {
            $params = trim($params, '&?');
        }

        return $params;
    }
}
