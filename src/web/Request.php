<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web;

use Craft;
use craft\base\RequestTrait;
use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * @inheritdoc
 *
 * @property string $fullPath               The full requested path, including the CP trigger and pagination info.
 * @property string $path                   The requested path, sans CP trigger and pagination info.
 * @property array  $segments               The segments of the requested path.
 * @property int    $pageNum                The requested page number.
 * @property string $token                  The token submitted with the request, if there is one.
 * @property bool   $isCpRequest            Whether the Control Panel was requested.
 * @property bool   $isSiteRequest          Whether the front end site was requested.
 * @property bool   $isActionRequest        Whether a specific controller action was requested.
 * @property array  $actionSegments         The segments of the requested controller action path, if this is an [[getIsActionRequest() action request]].
 * @property bool   $isLivePreview          Whether this is a Live Preview request.
 * @property string $hostName               The host name from the current request URL.
 * @property string $queryStringWithoutPath The request’s query string, without the path parameter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Request extends \yii\web\Request
{
    // Traits
    // =========================================================================

    use RequestTrait;

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_fullPath;

    /**
     * @var
     */
    private $_path;

    /**
     * @var
     */
    private $_segments;

    /**
     * @var int
     */
    private $_pageNum = 1;

    /**
     * @var bool
     */
    private $_isCpRequest = false;

    /**
     * @var bool
     */
    private $_isActionRequest = false;

    /**
     * @var bool
     */
    private $_isSingleActionRequest = false;

    /**
     * @var bool
     */
    private $_checkedRequestType = false;

    /**
     * @var string[]|null
     */
    private $_actionSegments;

    /**
     * @var bool|null
     */
    private $_isMobileBrowser;

    /**
     * @var bool|null
     */
    private $_isMobileOrTabletBrowser;

    /**
     * @var string|null
     */
    private $_ipAddress;

    /**
     * @var string|null
     */
    private $_craftCsrfToken;

    /**
     * @var bool
     */
    private $_encodedQueryParams = false;

    /**
     * @var bool
     */
    private $_encodedBodyParams = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        // Sanitize
        $path = $this->getFullPath();

        // Get the path segments
        $this->_segments = array_values(array_filter(explode('/', $path), function($value) {
            // Explicitly check in case there is a 0 in a segment (i.e. foo/0 or foo/0/bar)
            return $value !== '';
        }));

        // Is this a CP request?
        $this->_isCpRequest = ($this->getSegment(1) == $generalConfig->cpTrigger);

        if ($this->_isCpRequest) {
            // Chop the CP trigger segment off of the path & segments array
            array_shift($this->_segments);
        }

        // Is this a paginated request?
        $pageTrigger = Craft::$app->getConfig()->getGeneral()->pageTrigger;

        if (!is_string($pageTrigger) || $pageTrigger === '') {
            $pageTrigger = 'p';
        }

        // Is this query string-based pagination?
        if ($pageTrigger[0] === '?') {
            $pageTrigger = trim($pageTrigger, '?=');

            // Avoid conflict with the path param
            $pathParam = Craft::$app->getConfig()->getGeneral()->pathParam;
            if ($pageTrigger === $pathParam) {
                $pageTrigger = $pathParam === 'p' ? 'pg' : 'p';
            }

            $this->_pageNum = (int)$this->getQueryParam($pageTrigger, '1');
        } else if (!empty($this->_segments)) {
            // Match against the entire path string as opposed to just the last segment so that we can support
            // "/page/2"-style pagination URLs
            $path = implode('/', $this->_segments);
            $pageTrigger = preg_quote($generalConfig->pageTrigger, '/');

            if (preg_match("/^(?:(.*)\/)?{$pageTrigger}(\d+)$/", $path, $match)) {
                // Capture the page num
                $this->_pageNum = (int)$match[2];

                // Sanitize
                $newPath = $match[1];

                // Reset the segments without the pagination stuff
                $this->_segments = array_values(array_filter(explode('/', $newPath)));
            }
        }

        // Now that we've chopped off the admin/page segments, set the path
        $this->_path = implode('/', $this->_segments);
    }

    /**
     * Returns the full request path, whether that came from the path info or the path query parameter.
     *
     * Leading and trailing slashes will be removed.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        if ($this->_fullPath === null) {
            try {
                if (Craft::$app->getConfig()->getGeneral()->usePathInfo) {
                    $this->_fullPath = $this->getPathInfo(true);

                    if (!$this->_fullPath) {
                        $this->_fullPath = $this->_getQueryStringPath();
                    }
                } else {
                    $this->_fullPath = $this->_getQueryStringPath();

                    if (!$this->_fullPath) {
                        $this->_fullPath = $this->getPathInfo(true);
                    }
                }
            } catch (InvalidConfigException $e) {
                $this->_fullPath = $this->_getQueryStringPath();
            }

            $this->_fullPath = trim((string)$this->_fullPath, '/');
        }

        return $this->_fullPath;
    }

    /**
     * Returns the requested path, sans CP trigger and pagination info.
     *
     * If $returnRealPathInfo is returned, then [[parent::getPathInfo()]] will be returned.
     *
     * @param bool $returnRealPathInfo Whether the real path info should be returned instead.
     *
     * @see \yii\web\UrlManager::processRequest()
     * @see \yii\web\UrlRule::processRequest()
     * @return string The requested path, or the path info.
     * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
     */
    public function getPathInfo(bool $returnRealPathInfo = false): string
    {
        if ($returnRealPathInfo) {
            return parent::getPathInfo();
        }

        return $this->_path;
    }

    /**
     * Returns the segments of the requested path.
     *
     * Note that the segments will not include the [CP trigger](http://craftcms.com/docs/config-settings#cpTrigger)
     * if it’s a CP request, or the [page trigger](http://craftcms.com/docs/config-settings#pageTrigger) or page
     * number if it’s a paginated request.
     *
     * @return array The Craft path’s segments.
     */
    public function getSegments(): array
    {
        return $this->_segments;
    }

    /**
     * Returns a specific segment from the Craft path.
     *
     * @param int $num Which segment to return (1-indexed).
     *
     * @return string|null The matching segment, or `null` if there wasn’t one.
     */
    public function getSegment(int $num)
    {
        if ($num > 0 && isset($this->_segments[$num - 1])) {
            return $this->_segments[$num - 1];
        }

        if ($num < 0) {
            $totalSegs = count($this->_segments);

            if (isset($this->_segments[$totalSegs + $num])) {
                return $this->_segments[$totalSegs + $num];
            }
        }

        return null;
    }

    /**
     * Returns the requested page number.
     *
     * @return int The requested page number.
     */
    public function getPageNum(): int
    {
        return $this->_pageNum;
    }

    /**
     * Returns the token submitted with the request, if there is one.
     *
     * @return string|null The token, or `null` if there isn’t one.
     */
    public function getToken()
    {
        return $this->getQueryParam(Craft::$app->getConfig()->getGeneral()->tokenParam);
    }

    /**
     * Returns whether the Control Panel was requested.
     *
     * The result depends on whether the first segment in the URI matches the
     * [CP trigger](http://craftcms.com/docs/config-settings#cpTrigger).
     *
     * Note that even if this function returns `true`, the request will not necessarily route to the Control Panel.
     *
     * @return bool Whether the current request should be routed to the Control Panel.
     */
    public function getIsCpRequest(): bool
    {
        return $this->_isCpRequest;
    }

    /**
     * Returns whether the front end site was requested.
     *
     * The result will always just be the opposite of whatever [[getIsCpRequest()]] returns.
     *
     * @return bool Whether the current request should be routed to the front-end site.
     */
    public function getIsSiteRequest(): bool
    {
        return !$this->_isCpRequest;
    }

    /**
     * Returns whether a specific controller action was requested.
     *
     * There are several ways that this method could return `true`:
     *
     * - If the first segment in the Craft path matches the
     *   [action trigger](http://craftcms.com/docs/config-settings#actionTrigger)
     * - If there is an 'action' param in either the POST data or query string
     * - If the Craft path matches the Login path, the Logout path, or the Set Password path
     *
     * @return bool Whether the current request should be routed to a controller action.
     */
    public function getIsActionRequest(): bool
    {
        $this->_checkRequestType();
        return $this->_isActionRequest;
    }

    /**
     * Returns whether the current request is solely an action request.
     */
    public function getIsSingleActionRequest()
    {
        $this->_checkRequestType();
        return $this->_isSingleActionRequest;
    }

    /**
     * Returns the segments of the requested controller action path, if this is an [[getIsActionRequest() action request]].
     *
     * @return array|null The action path segments, or `null` if this isn’t an action request.
     */
    public function getActionSegments()
    {
        $this->_checkRequestType();

        return $this->_actionSegments;
    }

    /**
     * Returns whether this is a Live Preview request.
     *
     * @return bool Whether this is a Live Preview request.
     */
    public function getIsLivePreview(): bool
    {
        return (
            $this->getIsSiteRequest() &&
            $this->getIsActionRequest() &&
            $this->getBodyParam('livePreview')
        );
    }

    /**
     * Returns whether the request is coming from a mobile browser.
     *
     * The detection script is provided by http://detectmobilebrowsers.com. It was last updated on 2014-11-24.
     *
     * @param bool $detectTablets Whether tablets should be considered “mobile”.
     *
     * @return bool Whether the request is coming from a mobile browser.
     */
    public function isMobileBrowser(bool $detectTablets = false): bool
    {
        if ($detectTablets) {
            $property = &$this->_isMobileOrTabletBrowser;
        } else {
            $property = &$this->_isMobileBrowser;
        }

        if ($property === null) {
            if ($this->getUserAgent() !== null) {
                $property = (
                    preg_match(
                        '/(android|bb\\d+|meego).+mobile|avantgo|bada\\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\\.(browser|link)|vodafone|wap|windows ce|xda|xiino'
                        .($detectTablets ? '|android|ipad|playbook|silk' : '').'/i',
                        $this->getUserAgent()
                    ) ||
                    preg_match(
                        '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',
                        mb_substr($this->getUserAgent(), 0, 4)
                    )
                );
            } else {
                $property = false;
            }
        }

        return $property;
    }

    /**
     * Returns the host name from the current request URL.
     *
     * Internally, this method will first check the Host header that should have accompanied the request, which browsers
     * will set depending on the host name they are requesting. If that header does not exist, the method will fall back
     * on the SERVER_NAME server environment variable.
     *
     * @return string The host name.
     */
    public function getHostName(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        return $_SERVER['SERVER_NAME'];
    }

    /**
     * @inheritdoc
     */
    public function getBodyParams()
    {
        if ($this->_encodedBodyParams === false) {
            $this->setBodyParams($this->_utf8AllTheThings(parent::getBodyParams()));
            $this->_encodedBodyParams = true;
        }

        return parent::getBodyParams();
    }

    /**
     * Returns the named request body parameter value.
     *
     * If the parameter does not exist, the second parameter passed to this method will be returned.
     *
     * ```php
     * $foo = Craft::$app->getRequest()->getBodyParam('foo'); // Returns $_POST['foo'], if it exists
     * ```
     *
     * You can also specify a nested parameter using a dot-delimited string.
     *
     * ```php
     * $bar = Craft::$app->getRequest()->getBodyParam('foo.bar'); // Returns $_POST['foo']['bar'], if it exists
     * ```
     *
     * @param string $name         The parameter name.
     * @param mixed  $defaultValue The default parameter value if the parameter does not exist.
     *
     * @return mixed The parameter value
     * @see getBodyParams()
     * @see setBodyParams()
     */
    public function getBodyParam($name, $defaultValue = null)
    {
        return $this->_getParam($name, $defaultValue, $this->getBodyParams());
    }

    /**
     * Returns the named request body parameter value, or bails on the request with a 400 error if that parameter doesn’t exist.
     *
     * @param string $name The parameter name.
     *
     * @return mixed The parameter value
     * @throws BadRequestHttpException if the request does not have the body param
     * @see getBodyParam()
     */
    public function getRequiredBodyParam(string $name)
    {
        $value = $this->getBodyParam($name);

        if ($value !== null) {
            return $value;
        }

        throw new BadRequestHttpException('Request missing required body param');
    }

    /**
     * Validates and returns the named request body parameter value, or bails on the request with a 400 error if that parameter doesn’t pass validation.
     *
     * @param string $name The parameter name.
     *
     * @return mixed|null The parameter value
     * @throws BadRequestHttpException if the param value doesn’t pass validation
     * @see getBodyParam()
     */
    public function getValidatedBodyParam(string $name)
    {
        $value = $this->getBodyParam($name);

        if ($value === null) {
            return null;
        }

        $value = Craft::$app->getSecurity()->validateData($value);

        if ($value === false) {
            throw new BadRequestHttpException('Request contained an invalid body param');
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getQueryParams()
    {
        if ($this->_encodedQueryParams === false) {
            $this->setQueryParams($this->_utf8AllTheThings(parent::getQueryParams()));
            $this->_encodedQueryParams = true;
        }

        return parent::getQueryParams();
    }

    /**
     * Returns the named GET parameter value.
     *
     * If the GET parameter does not exist, the second parameter to this method will be returned.
     *
     * ```php
     * $foo = Craft::$app->getRequest()->getQueryParam('foo'); // Returns $_GET['foo'], if it exists
     * ```
     *
     * You can also specify a nested parameter using a dot-delimited string.
     *
     * ```php
     * $bar = Craft::$app->getRequest()->getQueryParam('foo.bar'); // Returns $_GET['foo']['bar'], if it exists
     * ```
     *
     * @param string     $name         The GET parameter name.
     * @param mixed|null $defaultValue The default parameter value if the GET parameter does not exist.
     *
     * @return mixed The GET parameter value.
     * @see getBodyParam()
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        return $this->_getParam($name, $defaultValue, $this->getQueryParams());
    }

    /**
     * Returns the named GET parameter value, or bails on the request with a 400 error if that parameter doesn’t exist.
     *
     * @param string $name The GET parameter name.
     *
     * @return mixed The GET parameter value.
     * @throws BadRequestHttpException if the request does not have the query param
     * @see getQueryParam()
     */
    public function getRequiredQueryParam(string $name)
    {
        $value = $this->getQueryParam($name);

        if ($value !== null) {
            return $value;
        }

        throw new BadRequestHttpException('Request missing required query param');
    }

    /**
     * Returns the named parameter value from either GET or the request body.
     *
     * If the parameter does not exist, the second parameter to this method will be returned.
     *
     * @param string $name         The parameter name.
     * @param mixed  $defaultValue The default parameter value if the parameter does not exist.
     *
     * @return mixed The parameter value.
     * @see getQueryParam()
     * @see getBodyParam()
     */
    public function getParam(string $name, $defaultValue = null)
    {
        if (($value = $this->getQueryParam($name)) !== null) {
            return $value;
        }

        if (($value = $this->getBodyParam($name)) !== null) {
            return $value;
        }

        return $defaultValue;
    }

    /**
     * Returns the named parameter value from either GET or the request body, or bails on the request with a 400 error
     * if that parameter doesn’t exist anywhere.
     *
     * @param string $name The parameter name.
     *
     * @return mixed The parameter value.
     * @throws BadRequestHttpException if the request does not have the param
     * @see getQueryParam()
     * @see getBodyParam()
     */
    public function getRequiredParam(string $name)
    {
        $value = $this->getParam($name);

        if ($value !== null) {
            return $value;
        }

        throw new BadRequestHttpException('Request missing required param');
    }

    /**
     * Returns the request’s query string, without the path parameter.
     *
     * @return string The query string.
     */
    public function getQueryStringWithoutPath(): string
    {
        // Get the full query string
        $queryString = $this->getQueryString();
        $parts = explode('&', $queryString);
        $pathParam = Craft::$app->getConfig()->getGeneral()->pathParam;

        foreach ($parts as $key => $part) {
            if (strpos($part, $pathParam.'=') === 0) {
                unset($parts[$key]);
                break;
            }
        }

        return implode('&', $parts);
    }

    /**
     * Retrieves the best guess of the client’s actual IP address taking into account numerous HTTP proxy headers due to
     * variations in how different ISPs handle IP addresses in headers between hops.
     *
     * Considering any of these server vars besides REMOTE_ADDR can be spoofed, this method should not be used when you
     * need a trusted source for the IP address. Use `$_SERVER['REMOTE_ADDR']` instead.
     *
     * @return string The IP address.
     */
    public function getUserIP(): string
    {
        if ($this->_ipAddress === null) {
            $ipMatch = false;

            // Check for shared internet/ISP IP
            if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->_validateIp($_SERVER['HTTP_CLIENT_IP'])) {
                $ipMatch = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                // Check for IPs passing through proxies
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    // Check if multiple IPs exist in var
                    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                    foreach ($ipList as $ip) {
                        if ($this->_validateIp($ip)) {
                            $ipMatch = $ip;
                        }
                    }
                }
            }

            if (!$ipMatch) {
                if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->_validateIp($_SERVER['HTTP_X_FORWARDED'])) {
                    $ipMatch = $_SERVER['HTTP_X_FORWARDED'];
                } else {
                    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->_validateIp($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
                        $ipMatch = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
                    } else {
                        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->_validateIp($_SERVER['HTTP_FORWARDED_FOR'])) {
                            $ipMatch = $_SERVER['HTTP_FORWARDED_FOR'];
                        } else {
                            if (!empty($_SERVER['HTTP_FORWARDED']) && $this->_validateIp($_SERVER['HTTP_FORWARDED'])) {
                                $ipMatch = $_SERVER['HTTP_FORWARDED'];
                            }
                        }
                    }
                }

                // The only one we're guaranteed to be accurate.
                if (!$ipMatch) {
                    $ipMatch = $_SERVER['REMOTE_ADDR'];
                }
            }

            $this->_ipAddress = $ipMatch;
        }

        return $this->_ipAddress;
    }

    /**
     * Returns whether the client is running "Windows", "Mac", "Linux" or "Other", based on the
     * browser's UserAgent string.
     *
     * @return string The OS the client is running.
     */
    public function getClientOs(): string
    {
        $userAgent = $this->getUserAgent();

        if (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }

        if (strpos($userAgent, 'Win') !== false) {
            return 'Windows';
        }

        if (strpos($userAgent, 'Mac') !== false) {
            return 'Mac';
        }

        return 'Other';
    }

    /**
     * Returns the token used to perform CSRF validation.
     *
     * This token is a masked version of [[rawCsrfToken]] to prevent [BREACH attacks](http://breachattack.com/).
     * This token may be passed along via a hidden field of an HTML form or an HTTP header value
     * to support CSRF validation.
     *
     * @param bool $regenerate    whether to regenerate CSRF token. When this parameter is true, each time
     *                            this method is called, a new CSRF token will be generated and persisted (in session or cookie).
     *
     * @return string the token used to perform CSRF validation.
     */
    public function getCsrfToken($regenerate = false): string
    {
        if ($this->_craftCsrfToken === null || $regenerate) {
            $token = $this->loadCsrfToken();

            if ($regenerate || $token === null || ($this->_craftCsrfToken = $token) === null || !$this->csrfTokenValidForCurrentUser($token)) {
                $token = $this->generateCsrfToken();
            }

            $this->_craftCsrfToken = Craft::$app->getSecurity()->maskToken($token);
        }

        return $this->_craftCsrfToken;
    }

    /**
     * Regenerates a CSRF token.
     */
    public function regenCsrfToken()
    {
        $this->_craftCsrfToken = $this->getCsrfToken(true);
    }

    /**
     * Returns whether the request will accept a given content type3
     *
     * @param string $contentType
     *
     * @return bool
     */
    public function accepts(string $contentType): bool
    {
        return array_key_exists($contentType, $this->getAcceptableContentTypes());
    }

    /**
     * Returns whether the request will accept a JSON response.
     *
     * @return bool
     */
    public function getAcceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    // Protected Methods
    // =========================================================================

    /**
     * Generates  an unmasked random token used to perform CSRF validation.
     *
     * @return string the random token for CSRF validation.
     */
    protected function generateCsrfToken(): string
    {
        $existingToken = $this->loadCsrfToken();

        // They have an existing CSRF token.
        if ($existingToken) {
            // It's a CSRF token that came from an authenticated request.
            if (strpos($existingToken, '|') !== false) {
                // Grab the existing nonce.
                $parts = explode('|', $existingToken);
                $nonce = $parts[0];
            } else {
                // It's a CSRF token from an unauthenticated request.
                $nonce = $existingToken;
            }
        } else {
            // No previous CSRF token, generate a new nonce.
            $nonce = Craft::$app->getSecurity()->generateRandomString(40);
        }

        // Authenticated users
        if (Craft::$app->get('user', false) && ($currentUser = Craft::$app->getUser()->getIdentity())) {
            // We mix the password into the token so that it will become invalid when the user changes their password.
            // The salt on the blowfish hash will be different even if they change their password to the same thing.
            // Normally using the session ID would be a better choice, but PHP's bananas session handling makes that difficult.
            $passwordHash = $currentUser->password;
            $userId = $currentUser->id;
            $hashable = implode('|', [$nonce, $userId, $passwordHash]);
            $token = $nonce.'|'.Craft::$app->getSecurity()->hashData($hashable, $this->cookieValidationKey);
        } else {
            // Unauthenticated users.
            $token = $nonce;
        }

        if ($this->enableCsrfCookie) {
            $cookie = $this->createCsrfCookie($token);
            Craft::$app->getResponse()->getCookies()->add($cookie);
        } else {
            Craft::$app->getSession()->set($this->csrfParam, $token);
        }

        return $token;
    }

    /**
     * Gets whether the CSRF token is valid for the current user or not
     *
     * @param string $token
     *
     * @return bool
     */
    protected function csrfTokenValidForCurrentUser(string $token): bool
    {
        $currentUser = false;

        if (Craft::$app->getIsInstalled() && Craft::$app->get('user', false)) {
            $currentUser = Craft::$app->getUser()->getIdentity();
        }

        if ($currentUser) {
            $splitToken = explode('|', $token, 2);

            if (count($splitToken) !== 2) {
                return false;
            }

            list($nonce,) = $splitToken;

            // Check that this token is for the current user
            $passwordHash = $currentUser->password;
            $userId = $currentUser->id;
            $hashable = implode('|', [$nonce, $userId, $passwordHash]);
            $expectedToken = $nonce.'|'.Craft::$app->getSecurity()->hashData($hashable, $this->cookieValidationKey);

            return Craft::$app->getSecurity()->compareString($expectedToken, $token);
        }

        // If they're logged out, any token is fine
        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the query string path.
     *
     * @return string
     */
    private function _getQueryStringPath(): string
    {
        $pathParam = Craft::$app->getConfig()->getGeneral()->pathParam;

        return $this->getQueryParam($pathParam, '');
    }

    /**
     * Checks to see if this is an action request.
     *
     * @return void
     */
    private function _checkRequestType()
    {
        if ($this->_checkedRequestType) {
            return;
        }

        $configService = Craft::$app->getConfig();
        $generalConfig = $configService->getGeneral();

        // If there's a token in the query string, then that should take precedence over everything else
        if (!$this->getQueryParam($generalConfig->tokenParam)) {
            $firstSegment = $this->getSegment(1);

            // Is this an action request?
            if ($this->_isCpRequest) {
                $loginPath = 'login';
                $logoutPath = 'logout';
                $setPasswordPath = 'setpassword';
                $updatePath = 'update';
            } else {
                $loginPath = trim($generalConfig->getLoginPath(), '/');
                $logoutPath = trim($generalConfig->getLogoutPath(), '/');
                $setPasswordPath = trim($generalConfig->getSetPasswordPath(), '/');
                $updatePath = null;
            }

            if (
                ($specialPath = in_array($this->_path, [$loginPath, $logoutPath, $setPasswordPath, $updatePath], true)) ||
                ($triggerMatch = ($firstSegment === $generalConfig->actionTrigger && count($this->_segments) > 1)) ||
                ($actionParam = $this->getParam('action')) !== null
            ) {
                $this->_isActionRequest = true;

                /** @noinspection PhpUndefinedVariableInspection */
                if ($specialPath) {
                    switch ($this->_path) {
                        case $loginPath:
                            $this->_actionSegments = ['users', 'login'];
                            break;
                        case $logoutPath:
                            $this->_actionSegments = ['users', 'logout'];
                            break;
                        case $setPasswordPath:
                            $this->_actionSegments = ['users', 'set-password'];
                            break;
                        case $updatePath:
                            $this->_actionSegments = ['updater', 'index'];
                            break;
                    }
                    $this->_isSingleActionRequest = true;
                } else if ($triggerMatch) {
                    $this->_actionSegments = array_slice($this->_segments, 1);
                    $this->_isSingleActionRequest = true;
                } else {
                    $this->_actionSegments = array_values(array_filter(explode('/', $actionParam)));
                    $this->_isSingleActionRequest = empty($this->_path);
                }
            }
        }

        $this->_checkedRequestType = true;
    }

    /**
     * @param array $things
     *
     * @return array
     */
    private function _utf8AllTheThings(array $things): array
    {
        foreach ($things as $key => $value) {
            $things[$key] = $this->_utf8Value($value);
        }

        return $things;
    }

    /**
     * @param array|string $value
     *
     * @return array|string
     */
    private function _utf8Value($value)
    {
        if (is_array($value)) {
            return $this->_utf8AllTheThings($value);
        }

        return StringHelper::convertToUtf8($value);
    }

    /**
     * Returns the named parameter value.
     *
     * The name may include dots, to specify the path to a nested param.
     *
     * @param string|null $name
     * @param mixed       $defaultValue
     * @param array       $params
     *
     * @return mixed
     */
    private function _getParam(string $name = null, $defaultValue, array $params)
    {
        // Do they just want the whole array?
        if ($name === null) {
            return $this->_utf8AllTheThings($params);
        }

        // Looking for a specific value?
        if (isset($params[$name])) {
            return $this->_utf8Value($params[$name]);
        }

        // Maybe they're looking for a nested param?
        if (StringHelper::contains($name, '.')) {
            $path = explode('.', $name);
            $param = $params;

            foreach ($path as $step) {
                if (is_array($param) && isset($param[$step])) {
                    $param = $param[$step];
                } else {
                    return $defaultValue;
                }
            }

            return $this->_utf8Value($param);
        }

        return $defaultValue;
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    private function _validateIp(string $ip): bool
    {
        return !(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false &&
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false);
    }
}
