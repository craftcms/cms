<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use yii\base\Exception;

/**
 * Class Url
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Url
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
     * @return boolean
     */
    public static function isAbsoluteUrl($url)
    {
        return (strncmp('http://', $url, 7) === 0 || strncmp('https://', $url,
                8) === 0);
    }

    /**
     * Returns whether a given string appears to be a protocol-relative URL.
     *
     * @param string $url
     *
     * @return boolean
     */
    public static function isProtocolRelativeUrl($url)
    {
        return (strncmp('//', $url, 2) === 0);
    }

    /**
     * Returns whether a given string appears to be a root-relative URL.
     *
     * @param string $url
     *
     * @return boolean
     */
    public static function isRootRelativeUrl($url)
    {
        return (strncmp('/', $url,
                1) === 0 && !static::isProtocolRelativeUrl($url));
    }

    /**
     * Returns whether a given string appears to be a "full" URL (absolute, root-relative or protocol-relative).
     *
     * @param string $url
     *
     * @return boolean
     */
    public static function isFullUrl($url)
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
    public static function getUrlWithParams($url, $params)
    {
        $params = static::_normalizeParams($params, $anchor);

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
    public static function getUrlWithToken($url, $token)
    {
        $protocol = static::getProtocolForTokenizedUrl();
        $url = static::getUrlWithProtocol($url, $protocol);

        return static::getUrlWithParams($url, [
            Craft::$app->getConfig()->get('tokenParam') => $token
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
    public static function getUrlWithProtocol($url, $protocol)
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
     * @param boolean           $mustShowScriptName
     *
     * @return string
     */
    public static function getUrl($path = '', $params = null, $protocol = null, $mustShowScriptName = false)
    {
        // Return $path if it appears to be an absolute URL.
        if (static::isFullUrl($path)) {
            if ($params) {
                $path = static::getUrlWithParams($path, $params);
            }

            if ($protocol) {
                $path = static::getUrlWithProtocol($path, $protocol);
            }

            return $path;
        }

        $path = trim($path, '/');

        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
            $path = Craft::$app->getConfig()->get('cpTrigger').($path ? '/'.$path : '');
            $cpUrl = true;
        } else {
            $cpUrl = false;
        }

        // Send all resources over SSL if this request is loaded over SSL.
        if (!$protocol && !$request->getIsConsoleRequest() && $request->getIsSecureConnection()) {
            $protocol = 'https';
        }

        return static::_getUrl($path, $params, $protocol, $cpUrl, $mustShowScriptName);
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
    public static function getCpUrl($path = '', $params = null, $protocol = null)
    {
        $path = trim($path, '/');
        $path = Craft::$app->getConfig()->get('cpTrigger').($path ? '/'.$path : '');

        return static::_getUrl($path, $params, $protocol, true, false);
    }

    /**
     * Returns a site URL.
     *
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol
     * @param integer|null      $siteId
     *
     * @return string
     * @throws Exception if $siteId is invalid
     */
    public static function getSiteUrl($path = '', $params = null, $protocol = null, $siteId = null)
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
        $url = static::_getUrl($path, $params, $protocol, false, false);

        if (isset($currentSite)) {
            // Restore the original current site
            $sites->currentSite = $currentSite;
        }

        return $url;
    }

    /**
     * Returns a resource URL.
     *
     * @param string            $path
     * @param array|string|null $params
     * @param string|null       $protocol The protocol to use (e.g. http, https). If empty, the protocol used for the
     *                                    current request will be used.
     *
     * @return string
     */
    public static function getResourceUrl($path = '', $params = null, $protocol = null)
    {
        $path = trim($path, '/');

        if ($path) {
            // If we've served this resource before, we should have a cached copy of the server path already. Use that
            // to get its timestamp, and add timestamp to the resource URL so the Resources service sends it with
            // a Pragma: Cache header.
            $dateParam = Craft::$app->getResources()->dateParam;

            if (!isset($params[$dateParam])) {
                $realPath = Craft::$app->getResources()->getCachedResourcePath($path);

                if ($realPath) {
                    if (!is_array($params)) {
                        $params = [$params];
                    }

                    $timeModified = Io::getLastTimeModified($realPath);
                    $params[$dateParam] = $timeModified->getTimestamp();
                } else {
                    // Just set a random query string param on there, so even if the browser decides to cache it,
                    // the next time this happens, the cache won't be used.

                    // Use a consistent param for all resource requests with uncached paths, in case the same resource
                    // URL is requested multiple times in the same request
                    if (!isset(static::$_x)) {
                        static::$_x = StringHelper::randomString(9);
                    }

                    $params['x'] = static::$_x;
                }
            }
        }

        return static::getUrl(Craft::$app->getConfig()->getResourceTrigger().'/'.$path, $params, $protocol);
    }

    /**
     * @param string $path
     * @param null   $params
     * @param string $protocol The protocol to use (e.g. http, https). If empty, the protocol used for the current
     *                         request will be used.
     *
     * @return array|string
     */
    public static function getActionUrl($path = '', $params = null, $protocol = null)
    {
        $path = Craft::$app->getConfig()->get('actionTrigger').'/'.trim($path,
                '/');

        return static::getUrl($path, $params, $protocol, true);
    }

    /**
     * Removes the query string from a given URL.
     *
     * @param string $url The URL to check.
     *
     * @return string The URL without a query string.
     */
    public static function stripQueryString($url)
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
    public static function getProtocolForTokenizedUrl()
    {
        $useSslOnTokenizedUrls = Craft::$app->getConfig()->get('useSslOnTokenizedUrls');

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
        if (($scheme && strtolower($scheme) == 'https') || (!$request->getIsConsoleRequest() && $request->getIsSecureConnection())) {
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
    public static function baseUrl()
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
            return $request->getHostInfo().$request->getBaseUrl();
        }

        return rtrim($baseUrl, '/').'/';
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a URL.
     *
     * @param string       $path
     * @param array|string $params
     * @param              $protocol
     * @param              $cpUrl
     * @param              $mustShowScriptName
     *
     * @return string
     */
    private static function _getUrl($path, $params, $protocol, $cpUrl, $mustShowScriptName)
    {
        // Normalize the params
        $params = static::_normalizeParams($params, $anchor);

        // Were there already any query string params in the path?
        if (($qpos = mb_strpos($path, '?')) !== false) {
            $params = substr($path, $qpos + 1).($params ? '&'.$params : '');
            $path = substr($path, 0, $qpos);
        }

        $showScriptName = ($mustShowScriptName || !Craft::$app->getConfig()->omitScriptNameInUrls());
        $request = Craft::$app->getRequest();

        if ($cpUrl) {
            // Did they set the base URL manually?
            $baseUrl = Craft::$app->getConfig()->get('baseCpUrl');

            if ($baseUrl) {
                // Make sure it ends in a slash
                $baseUrl = StringHelper::ensureRight($baseUrl, '/');

                if ($protocol) {
                    // Make sure we're using the right protocol
                    $baseUrl = static::getUrlWithProtocol($baseUrl, $protocol);
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

                if ($protocol) {
                    $baseUrl = static::getUrlWithProtocol($baseUrl, $protocol);
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
        if (!$showScriptName || Craft::$app->getConfig()->usePathInfo()) {
            if ($path) {
                $url = rtrim($baseUrl, '/').'/'.trim($path, '/');

                if (($request->getIsConsoleRequest() || $request->getIsSiteRequest()) && Craft::$app->getConfig()->get('addTrailingSlashesToUrls')) {
                    $url .= '/';
                }
            } else {
                $url = $baseUrl;
            }
        } else {
            $url = $baseUrl;

            if ($path) {
                $pathParam = Craft::$app->getConfig()->get('pathParam');
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
    private static function _normalizeParams($params, &$anchor = '')
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
