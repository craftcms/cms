<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\UrlHelper;
use yii\web\Cookie;

/**
 * Request functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @deprecated in 3.0
 */
class Request
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether this is a GET request.
     *
     * @return bool Whether this is a GET request
     */
    public function isGet(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isGet()', 'craft.request.isGet() has been deprecated. Use craft.app.request.isGet instead.');

        return Craft::$app->getRequest()->getIsGet();
    }

    /**
     * Returns whether this is a POST request.
     *
     * @return bool Whether this is a POST request
     */
    public function isPost(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isPost()', 'craft.request.isPost() has been deprecated. Use craft.app.request.isPost instead.');

        return Craft::$app->getRequest()->getIsPost();
    }

    /**
     * Returns whether this is a DELETE request.
     *
     * @return bool Whether this is a DELETE request
     */
    public function isDelete(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isDelete()', 'craft.request.isDelete() has been deprecated. Use craft.app.request.isDelete instead.');

        return Craft::$app->getRequest()->getIsDelete();
    }

    /**
     * Returns whether this is a PUT request.
     *
     * @return bool Whether this is a PUT request
     */
    public function isPut(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isPut()', 'craft.request.isPut() has been deprecated. Use craft.app.request.isPut instead.');

        return Craft::$app->getRequest()->getIsPut();
    }

    /**
     * Returns whether this is an Ajax request.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isAjax()', 'craft.request.isAjax() has been deprecated. Use craft.app.request.isAjax instead.');

        return Craft::$app->getRequest()->getIsAjax();
    }

    /**
     * Returns whether this is a secure connection.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isSecure()', 'craft.request.isSecure() has been deprecated. Use craft.app.request.isSecureConnection instead.');

        return Craft::$app->getRequest()->getIsSecureConnection();
    }

    /**
     * Returns whether this is a Live Preview request.
     *
     * @return bool
     */
    public function isLivePreview(): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isLivePreview()', 'craft.request.isLivePreview() has been deprecated. Use craft.app.request.isLivePreview instead.');

        return Craft::$app->getRequest()->getIsLivePreview();
    }

    /**
     * Returns the script name used to access Craft.
     *
     * @return string
     */
    public function getScriptName(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getScriptName()', 'craft.request.getScriptName() has been deprecated. Use craft.app.request.scriptFilename instead.');

        return Craft::$app->getRequest()->getScriptFilename();
    }

    /**
     * Returns the request's URI.
     *
     * @return mixed
     */
    public function getPath()
    {
        Craft::$app->getDeprecator()->log('craft.request.getPath()', 'craft.request.getPath() has been deprecated. Use craft.app.request.pathInfo instead.');

        return Craft::$app->getRequest()->getPathInfo();
    }

    /**
     * Returns the request's full URL.
     *
     * @return mixed
     */
    public function getUrl()
    {
        Craft::$app->getDeprecator()->log('craft.request.getUrl()', 'craft.request.getUrl() has been deprecated. Use craft.app.request.absoluteUrl instead.');

        return UrlHelper::url(Craft::$app->getRequest()->getPathInfo());
    }

    /**
     * Returns all URI segments.
     *
     * @return array
     */
    public function getSegments(): array
    {
        Craft::$app->getDeprecator()->log('craft.request.getSegments()', 'craft.request.getSegments() has been deprecated. Use craft.app.request.segments instead.');

        return Craft::$app->getRequest()->getSegments();
    }

    /**
     * Returns a specific URI segment, or null if the segment doesn't exist.
     *
     * @param int $num
     * @return string|null
     */
    public function getSegment(int $num)
    {
        Craft::$app->getDeprecator()->log('craft.request.getSegment()', 'craft.request.getSegment() has been deprecated. Use craft.app.request.getSegment() instead.');

        return Craft::$app->getRequest()->getSegment($num);
    }

    /**
     * Returns the first URI segment.
     *
     * @return string|null
     */
    public function getFirstSegment()
    {
        Craft::$app->getDeprecator()->log('craft.request.getFirstSegment()', 'craft.request.getFirstSegment() has been deprecated. Use craft.app.request.segments|first instead.');

        return Craft::$app->getRequest()->getSegment(1);
    }

    /**
     * Returns the last URL segment.
     *
     * @return string|null
     */
    public function getLastSegment()
    {
        Craft::$app->getDeprecator()->log('craft.request.getLastSegment()', 'craft.request.getLastSegment() has been deprecated. Use craft.app.request.segments|last instead.');

        return Craft::$app->getRequest()->getSegment(-1);
    }

    /**
     * Returns a variable from either the query string or the post data.
     *
     * @param string $name
     * @param string|null $default
     * @return mixed
     */
    public function getParam(string $name, string $default = null)
    {
        Craft::$app->getDeprecator()->log('craft.request.getParam()', 'craft.request.getParam() has been deprecated. Use craft.app.request.getParam() instead.');

        return Craft::$app->getRequest()->getParam($name, $default);
    }

    /**
     * Returns a [[Cookie]] if it exists, otherwise, null.
     *
     * @param string $name
     * @return Cookie|null
     */
    public function getCookie(string $name)
    {
        Craft::$app->getDeprecator()->log('craft.request.getCookie()', 'craft.request.getCookie() has been deprecated. Use craft.app.request.cookies.get() instead.');

        return Craft::$app->getRequest()->getCookies()->get($name);
    }

    /**
     * Returns the server name.
     *
     * @return string
     */
    public function getServerName(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getServerName()', 'craft.request.getServerName() has been deprecated. Use craft.app.request.serverName instead.');

        return Craft::$app->getRequest()->getServerName();
    }

    /**
     * Returns which URL format we're using (PATH_INFO or the query string)
     *
     * @return string
     */
    public function getUrlFormat(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getUrlFormat()', 'craft.request.getUrlFormat() has been deprecated. Use craft.app.config.general.usePathInfo instead.');

        return Craft::$app->getConfig()->getGeneral()->usePathInfo ? 'pathinfo' : 'querystring';
    }

    /**
     * Returns whether the request is coming from a mobile browser.
     *
     * @param bool $detectTablets
     * @return bool
     */
    public function isMobileBrowser(bool $detectTablets = false): bool
    {
        Craft::$app->getDeprecator()->log('craft.request.isMobileBrowser()', 'craft.request.isMobileBrowser() has been deprecated. Use craft.app.request.isMobileBrowser() instead.');

        return Craft::$app->getRequest()->isMobileBrowser($detectTablets);
    }

    /**
     * Returns the page number if this is a paginated request.
     *
     * @return int
     */
    public function getPageNum(): int
    {
        Craft::$app->getDeprecator()->log('craft.request.getPageNum()', 'craft.request.getPageNum() has been deprecated. Use craft.app.request.pageNum instead.');

        return Craft::$app->getRequest()->getPageNum();
    }

    /**
     * Returns the schema and host part of the application URL. The returned URL does not have an ending slash. By
     * default this is determined based on the user request information.
     *
     * @return string
     */
    public function getHostInfo(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getHostInfo()', 'craft.request.getHostInfo() has been deprecated. Use craft.app.request.hostInfo instead.');

        return Craft::$app->getRequest()->getHostInfo();
    }

    /**
     * Returns the relative URL of the entry script.
     *
     * @return string
     */
    public function getScriptUrl(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getScriptUrl()', 'craft.request.getScriptUrl() has been deprecated. Use craft.app.request.scriptUrl instead.');

        return Craft::$app->getRequest()->getScriptUrl();
    }

    /**
     * Returns the path info of the currently requested URL. This refers to the part that is after the entry script and
     * before the question mark. The starting and ending slashes are stripped off.
     *
     * @return string
     */
    public function getPathInfo(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getPathInfo()', 'craft.request.getPathInfo() has been deprecated. Use craft.app.request.getPathInfo(true) instead.');

        return Craft::$app->getRequest()->getPathInfo(true);
    }

    /**
     * Returns the request URI portion for the currently requested URL. This refers to the portion that is after the
     * host info part. It includes the query string part if any.
     *
     * @return string
     */
    public function getRequestUri(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getRequestUri()', 'craft.request.getRequestUri() has been deprecated. Use craft.app.request.url instead.');

        return Craft::$app->getRequest()->getUrl();
    }

    /**
     * Returns the server port number.
     *
     * @return int|null
     */
    public function getServerPort()
    {
        Craft::$app->getDeprecator()->log('craft.request.getServerPort()', 'craft.request.getServerPort() has been deprecated. Use craft.app.request.serverPort instead.');

        return Craft::$app->getRequest()->getServerPort();
    }

    /**
     * Returns the URL referrer or null if not present.
     *
     * @return string
     */
    public function getUrlReferrer(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getUrlReferrer()', 'craft.request.getUrlReferrer() has been deprecated. Use craft.app.request.referrer instead.');

        return Craft::$app->getRequest()->getReferrer();
    }

    /**
     * Returns the user agent or null if not present.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getUserAgent()', 'craft.request.getUserAgent() has been deprecated. Use craft.app.request.userAgent instead.');

        return Craft::$app->getRequest()->getUserAgent();
    }

    /**
     * Returns the user host name or null if it cannot be determined.
     *
     * @return string|null
     */
    public function getUserHost()
    {
        Craft::$app->getDeprecator()->log('craft.request.getUserHost()', 'craft.request.getUserHost() has been deprecated. Use craft.app.request.userHost instead.');

        return Craft::$app->getRequest()->getUserHost();
    }

    /**
     * Returns the port to use for insecure requests. Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     *
     * @return int
     */
    public function getPort(): int
    {
        Craft::$app->getDeprecator()->log('craft.request.getPort()', 'craft.request.getPort() has been deprecated. Use craft.app.request.port instead.');

        return Craft::$app->getRequest()->getPort();
    }

    /**
     * Returns the random token used to perform CSRF validation.
     *
     * The token will be read from cookie first. If not found, a new token will be generated.
     *
     * @return string The random token for CSRF validation.
     */
    public function getCsrfToken(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getCsrfToken()', 'craft.request.getCsrfToken() has been deprecated. Use craft.app.request.csrfToken instead.');

        return Craft::$app->getRequest()->getCsrfToken();
    }

    /**
     * Returns part of the request URL that is after the question mark.
     *
     * @return string The part of the request URL that is after the question mark.
     */
    public function getQueryString(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getQueryString()', 'craft.request.getQueryString() has been deprecated. Use craft.app.request.queryString instead.');

        return Craft::$app->getRequest()->getQueryString();
    }

    /**
     * Returns the request’s query string, without the p= parameter.
     *
     * @return string The query string.
     */
    public function getQueryStringWithoutPath(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getQueryStringWithoutPath()', 'craft.request.getQueryStringWithoutPath() has been deprecated. Use craft.app.request.queryStringWithoutPath instead.');

        return Craft::$app->getRequest()->getQueryStringWithoutPath();
    }

    /**
     * Returns a variable from the query string.
     *
     * @param string|null $name
     * @param string|null $default
     * @return mixed
     */
    public function getQuery(string $name = null, string $default = null)
    {
        Craft::$app->getDeprecator()->log('craft.request.getQuery()', 'craft.request.getQuery() has been deprecated. Use craft.app.request.getQueryParam() instead.');

        return Craft::$app->getRequest()->getQueryParam($name, $default);
    }

    /**
     * Returns a value from post data.
     *
     * @param string|null $name
     * @param string|null $default
     * @return mixed
     */
    public function getPost(string $name = null, string $default = null)
    {
        Craft::$app->getDeprecator()->log('craft.request.getPost()', 'craft.request.getPost() has been deprecated. Use craft.app.request.getBodyParam() instead.');

        return Craft::$app->getRequest()->getBodyParam($name, $default);
    }

    /**
     * Returns the user IP address.
     *
     * @return string
     */
    public function getUserHostAddress(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getUserHostAddress()', 'craft.request.getUserHostAddress() has been deprecated. Use craft.app.request.userIP instead.');

        return Craft::$app->getRequest()->getUserIP();
    }

    /**
     * Retrieves the best guess of the client’s actual IP address taking into account numerous HTTP proxy headers due to
     * variations in how different ISPs handle IP addresses in headers between hops.
     * Considering any of these server vars besides REMOTE_ADDR can be spoofed, this method should not be used when you
     * need a trusted source for the IP address. Use `$_SERVER['REMOTE_ADDR']` instead.
     *
     * @return string The IP address.
     */
    public function getIpAddress(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getIpAddress()', 'craft.request.getIpAddress() has been deprecated. Use craft.app.request.userIP instead.');

        return Craft::$app->getRequest()->getUserIP();
    }

    /**
     * Returns whether the client is running "Windows", "Mac", "Linux" or "Other", based on the
     * browser's UserAgent string.
     *
     * @return string The OS the client is running.
     */
    public function getClientOs(): string
    {
        Craft::$app->getDeprecator()->log('craft.request.getClientOs()', 'craft.request.getClientOs() has been deprecated. Use craft.app.request.clientOs instead.');

        return Craft::$app->getRequest()->getClientOs();
    }
}
