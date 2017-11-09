<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use yii\base\Exception;

/**
 * Class Url
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UrlHelper
{
    // Properties
    // =========================================================================

    private static $_x;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether a given string appears to be an absolute URL.
     *
     * @param string $url
     *
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
     *
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
     *
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
     *
     * @return bool
     */
    public static function isFullUrl(string $url): bool
    {
        return (static::isAbsoluteUrl($url) || static::isRootRelativeUrl($url) || static::isProtocolRelativeUrl($url));
    }

    /**
     * Returns a URL with additional query string parameters.
     *
     * @param string       $url
     * @param array|string $params
     *
     * @return string
     */
    public static function urlWithParams(string $url, $params): string
    {
        $params = self::_normalizeParams($params, $anchor);

        if ($params) {
            if (StringHelper::contains($url, '?')) {
                $url .= '&';
            } else {
                $url .= '?';
            }

            $url .= $params;
        }

        if ($anchor) {
            $url .= $anchor;
        }

        return $url;
    }

    /**
     * Returns a URL with a 'token' query string param set to a given token.
     *
     * @param string $url
     * @param string $token
     *
     * @return string
     */
    public static function urlWithToken(string $url, string $token): string
    {
        $protocol = static::getProtocolForTokenizedUrl();
        $url = static::urlWithProtocol($url, $protocol);

        return static::urlWithParams($url, [
            Craft::$app->getConfig()->getGeneral()->tokenParam => $token
        ]);
    }

    /**
     * Returns a URL with a specific protocol.
     *
     * @param string $url
     * @param string $protocol
     *
     * @return string
     */
    public static function urlWithProtocol(string $url, string $protocol): string
    {
        if (!$url || !$protocol) {
            return $url;
        }

        if (static::isProtocolRelativeUrl($url)) {
            return $protocol.':'.$url;
        }

        if (static::isRootRelativeUrl($url)) {
            // Prepend the current request's protocol and host name
            $url = Craft::$app->getRequest()->getHostInfo().$url;
        }

        return preg_replace('/^https?:/', $protocol.':', $url);
    }

    /**
     * Returns either a CP or a site URL, depending on the request type.
     *
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol
     * @param bool              $mustShowScriptName
     *
     * @return string
     */
    public static function url(string $path = '', $params = null, string $protocol = null, bool $mustShowScriptName = false): string
    {
        // Return $path if it appears to be an absolute URL.
        if (static::isFullUrl($path)) {
            if ($params) {
                $path = static::urlWithParams($path, $params);
            }

            if ($protocol !== null) {
                $path = static::urlWithProtocol($path, $protocol);
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

        // Stick with SSL if the current request is over SSL and a protocol wasn't defined
        if ($protocol === null && !$request->getIsConsoleRequest() && $request->getIsSecureConnection()) {
            $protocol = 'https';
        }

        return self::_createUrl($path, $params, $protocol, $cpUrl, $mustShowScriptName);
    }

    /**
     * Returns a CP URL.
     *
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol
     *
     * @return string
     */
    public static function cpUrl(string $path = '', $params = null, string $protocol = null): string
    {
        $path = trim($path, '/');
        $path = Craft::$app->getConfig()->getGeneral()->cpTrigger.($path ? '/'.$path : '');

        return self::_createUrl($path, $params, $protocol, true, false);
    }

    /**
     * Returns a site URL.
     *
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol
     * @param int|null          $siteId
     *
     * @return string
     * @throws Exception if|null $siteId is invalid
     */
    public static function siteUrl(string $path = '', $params = null, string $protocol = null, int $siteId = null): string
    {
        // Does this URL point to a different site?
        $sites = Craft::$app->getSites();

        if ($siteId !== null && $siteId != $sites->currentSite->id) {
            // Get the site
            $site = $sites->getSiteById($siteId);

            if (!$site) {
                throw new Exception('Invalid site ID: '.$siteId);
            }

            // Swap the current site
            $currentSite = $sites->currentSite;
            $sites->currentSite = $site;
        }

        $path = trim($path, '/');
        $url = self::_createUrl($path, $params, $protocol, false, false);

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (isset($currentSite)) {
            // Restore the original current site
            $sites->currentSite = $currentSite;
        }

        return $url;
    }

    /**
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol The protocol to use (e.g. http, https). If empty, the protocol used for the current
     *                                    request will be used.
     *
     * @return string
     */
    public static function actionUrl(string $path = '', $params = null, string $protocol = null): string
    {
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/'.trim($path, '/');

        return static::url($path, $params, $protocol, true);
    }

    /**
     * Removes the query string from a given URL.
     *
     * @param string $url The URL to check.
     *
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
     * Returns what the protocol/schema part of the URL should be (http/https)
     * for any tokenized URLs in Craft (email verification links, password reset
     * urls, share entry URLs, etc.
     *
     * @return string
     */
    public static function getProtocolForTokenizedUrl(): string
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
        $currentSite = false;

        if (Craft::$app->getIsInstalled()) {
            // Is there a current site, and does it have a base URL?
            $currentSite = Craft::$app->getSites()->currentSite;
        }

        if ($currentSite && $currentSite->baseUrl) {
            $baseUrl = $currentSite->baseUrl;
        } else {
            // Figure it out for ourselves, then
            $request = Craft::$app->getRequest();
            $baseUrl = $request->getHostInfo().$request->getBaseUrl();
        }

        return rtrim($baseUrl, '/').'/';
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a URL.
     *
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol
     * @param bool              $cpUrl
     * @param bool              $mustShowScriptName
     *
     * @return string
     */
    private static function _createUrl(string $path, $params, string $protocol = null, bool $cpUrl, bool $mustShowScriptName): string
    {
        // Normalize the params
        $params = self::_normalizeParams($params, $anchor);

        // Were there already any query string params in the path?
        if (($qpos = mb_strpos($path, '?')) !== false) {
            $params = substr($path, $qpos + 1).($params ? '&'.$params : '');
            $path = substr($path, 0, $qpos);
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $showScriptName = ($mustShowScriptName || !$generalConfig->omitScriptNameInUrls);
        $request = Craft::$app->getRequest();

        if ($cpUrl) {
            // Did they set the base URL manually?
            $baseUrl = $generalConfig->baseCpUrl;

            if ($baseUrl) {
                // Make sure it ends in a slash
                $baseUrl = StringHelper::ensureRight($baseUrl, '/');

                if ($protocol !== null) {
                    // Make sure we're using the right protocol
                    $baseUrl = static::urlWithProtocol($baseUrl, $protocol);
                }

                // Should we be adding that script name in?
                if ($showScriptName) {
                    $baseUrl .= $request->getScriptFilename();
                }
            } else {
                // Figure it out for ourselves, then
                $baseUrl = $request->getHostInfo();

                if ($showScriptName) {
                    $baseUrl .= $request->getScriptUrl();
                } else {
                    $baseUrl .= $request->getBaseUrl();
                }

                if ($protocol !== null) {
                    $baseUrl = static::urlWithProtocol($baseUrl, $protocol);
                }
            }
        } else {
            $baseUrl = static::baseUrl();

            // Should we be adding that script name in?
            if ($showScriptName) {
                $baseUrl .= $request->getScriptFilename();
            }
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

        if ($anchor) {
            $url .= $anchor;
        }

        return $url;
    }

    /**
     * Normalizes query string params.
     *
     * @param string|array|null $params
     * @param string|null       &$anchor
     *
     * @return string
     */
    private static function _normalizeParams($params, &$anchor = null): string
    {
        if (is_array($params)) {
            // See if there's an anchor
            if (isset($params['#'])) {
                $anchor = '#'.$params['#'];
                unset($params['#']);
            }

            $params = http_build_query($params);
        } else {
            $params = trim($params, '&?');
        }

        return $params;
    }
}
