<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\RequestTrait;
use craft\config\GeneralConfig;
use craft\errors\SiteNotFoundException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Session as SessionHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\services\Sites;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;
use yii\di\Instance;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\CookieCollection;
use yii\web\NotFoundHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * @inheritdoc
 * @property string $fullPath The full requested path, including the control panel trigger and pagination info.
 * @property string $path The requested path, sans control panel trigger and pagination info.
 * @property array $segments The segments of the requested path.
 * @property int $pageNum The requested page number.
 * @property string $token The token submitted with the request, if there is one.
 * @property bool $isCpRequest Whether the control panel was requested.
 * @property bool $isSiteRequest Whether the front end site was requested.
 * @property bool $isActionRequest Whether a specific controller action was requested.
 * @property array $actionSegments The segments of the requested controller action path, if this is an [[getIsActionRequest()|action request]].
 * @property bool $isLivePreview Whether this is a Live Preview request.
 * @property string $queryStringWithoutPath The request’s query string, without the path parameter.
 * @property-read bool $isPreview Whether this is an element preview request.
 * @property-read string|null $mimeType The MIME type of the request, extracted from the request’s content type
 * @property-read bool $isGraphql Whether the request’s MIME type is `application/graphql`
 * @property-read bool $isJson Whether the request’s MIME type is `application/json`
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Request extends \yii\web\Request
{
    use RequestTrait;

    public const CP_PATH_LOGIN = 'login';
    public const CP_PATH_LOGOUT = 'logout';
    public const CP_PATH_SET_PASSWORD = 'set-password';
    public const CP_PATH_VERIFY_EMAIL = 'verify-email';
    public const CP_PATH_UPDATE = 'update';

    /**
     * @inheritdoc
     */
    public $ipHeaders = [
        'Client-IP',
        'X-Forwarded-For',
        'X-Forwarded',
        'X-Cluster-Client-IP',
        'Forwarded-For',
        'Forwarded',
    ];

    /**
     * @var int The highest page number that Craft should accept.
     * @since 3.1.14
     */
    public int $maxPageNum = 100000;

    /**
     * @var GeneralConfig|array|string
     * @since 3.5.10
     */
    public GeneralConfig|string|array $generalConfig;

    /**
     * @var Sites|array|string|null
     * @since 3.5.10
     */
    public string|array|null|Sites $sites = 'sites';

    /**
     * @var string
     * @see getFullPath()
     */
    private string $_fullPath;

    /**
     * @var string
     * @see getPathInfo()
     */
    private string $_path;

    /**
     * @var string
     * @see getFullUri()
     */
    private string $_fullUri;

    /**
     * @var string[]
     */
    private array $_segments;

    /**
     * @var int
     */
    private int $_pageNum = 1;

    /**
     * @var bool|null
     */
    private ?bool $_isCpRequest = null;

    /**
     * @var bool
     * @see checkIfActionRequest()
     */
    private bool $_isActionRequest = false;

    /**
     * @var bool
     * @see checkIfActionRequest()
     */
    private bool $_isLoginRequest = false;

    /**
     * @var bool
     * @see checkIfActionRequest()
     */
    private bool $_checkedRequestType = false;

    /**
     * @var string[]|null
     * @see checkIfActionRequest()
     */
    private ?array $_actionSegments = null;

    /**
     * @var bool
     */
    private bool $_isLivePreview = false;

    /**
     * @var bool|null
     */
    private ?bool $_isMobileBrowser = null;

    /**
     * @var bool|null
     */
    private ?bool $_isMobileOrTabletBrowser = null;

    /**
     * @var string|null
     */
    private ?string $_ipAddress = null;

    /**
     * @var CookieCollection Collection of raw cookies
     * @see getRawCookies()
     */
    private CookieCollection $_rawCookies;

    /**
     * @var string|null
     */
    private ?string $_craftCsrfToken = null;

    /**
     * @var bool
     */
    private bool $_encodedQueryParams = false;

    /**
     * @var bool
     */
    private bool $_setBodyParams = false;

    /**
     * @var bool|null Whether the request initially had a token
     * @see getHadToken()
     */
    private ?bool $_hadToken = null;

    /**
     * @var string|null
     * @see getToken()
     */
    public ?string $_token = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->generalConfig)) {
            $this->generalConfig = Craft::$app->getConfig()->getGeneral();
        }
        $this->generalConfig = Instance::ensure($this->generalConfig, GeneralConfig::class);

        // Set the @webroot and @web aliases now (instead of from yii\web\Application::bootstrap())
        // in case a site's base URL requires @web, and so we can include the host info in @web
        if (Craft::getRootAlias('@webroot') === false) {
            Craft::setAlias('@webroot', dirname($this->getScriptFile()));
            $this->isWebrootAliasSetDynamically = true;
        }
        if (Craft::getRootAlias('@web') === false) {
            Craft::setAlias('@web', $this->getHostInfo() . $this->getBaseUrl());
            $this->isWebAliasSetDynamically = true;
        }

        // Determine the request path
        $this->_path = $this->getFullPath();

        // Figure out whether a site or the control panel were requested
        // ---------------------------------------------------------------------

        try {
            $this->sites = Instance::ensure($this->sites, Sites::class);

            // Only check if a site was requested if don’t know for sure that it’s a control panel request
            if ($this->_isCpRequest !== true) {
                if ($this->sites->getHasCurrentSite()) {
                    $site = $this->sites->getCurrentSite();
                } else {
                    $site = $this->_requestedSite($siteScore);
                }

                if ($siteBaseUrl = $site->getBaseUrl()) {
                    $baseUrl = rtrim($siteBaseUrl, '/');
                }
            }
        } catch (SiteNotFoundException $e) {
            // Fail silently if Craft isn’t installed yet or is in the middle of updating
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw $e;
            }
        }

        // Is the jury still out on whether this is a control panel request?
        if (!isset($this->_isCpRequest)) {
            $this->_isCpRequest = false;
            // Is it a possibility?
            if ($this->generalConfig->cpTrigger || $this->generalConfig->baseCpUrl) {
                // Figure out the base URL the request must have if this is a control panel request
                $testBaseCpUrls = [];
                if ($this->generalConfig->baseCpUrl) {
                    $testBaseCpUrls[] = implode('/', array_filter([rtrim($this->generalConfig->baseCpUrl, '/'), $this->generalConfig->cpTrigger]));
                } else {
                    if (isset($baseUrl)) {
                        $testBaseCpUrls[] = "$baseUrl/{$this->generalConfig->cpTrigger}";
                    }
                    $testBaseCpUrls[] = $this->getBaseUrl() . "/{$this->generalConfig->cpTrigger}";
                }
                $siteScore = $siteScore ?? (isset($site) ? $this->_scoreSite($site) : 0);
                foreach ($testBaseCpUrls as $testUrl) {
                    $cpScore = $this->_scoreUrl($testUrl);
                    if ($cpScore > $siteScore) {
                        $this->_isCpRequest = true;
                        $baseUrl = $testUrl;
                        $site = null;
                        break;
                    }
                }
            }
        }

        // Set the current site for the request
        if ($this->sites instanceof Sites) {
            $this->sites->setCurrentSite($site ?? null);
        }

        // If this is a control panel request and the path begins with the control panel trigger, remove it
        if ($this->_isCpRequest && $this->generalConfig->cpTrigger && str_starts_with($this->_path . '/', $this->generalConfig->cpTrigger . '/')) {
            $this->_path = ltrim(substr($this->_path, strlen($this->generalConfig->cpTrigger)), '/');
        }

        // Trim off any leading path segments that are part of the base URL
        if ($this->_path !== '' && isset($baseUrl) && ($basePath = parse_url($baseUrl, PHP_URL_PATH)) !== null) {
            $basePath = $this->_normalizePath($basePath);

            // If Craft is running from a subfolder, chop the subfolder path off of the base path first
            if (
                ($requestBaseUrl = $this->_normalizePath($this->getBaseUrl())) &&
                str_starts_with($basePath . '/', $requestBaseUrl . '/')
            ) {
                $basePath = ltrim(substr($basePath, strlen($requestBaseUrl)), '/');
            }

            if (str_starts_with($this->_path . '/', $basePath . '/')) {
                $this->_path = ltrim(substr($this->_path, strlen($basePath)), '/');
            }
        }

        if ($this->_isCpRequest) {
            // Force 'p' pageTrigger
            // (all that really matters is that it doesn't have a trailing slash, but whatever.)
            $this->generalConfig->pageTrigger = 'p';
        }

        // Is this a paginated request?
        $pageTrigger = $this->generalConfig->getPageTrigger();

        // Is this query string-based pagination?
        if (str_starts_with($pageTrigger, '?')) {
            $this->_pageNum = (int)$this->getQueryParam(trim($pageTrigger, '?='), '1');
        } elseif ($this->_path !== '') {
            // Match against the entire path string as opposed to just the last segment so that we can support
            // "/page/2"-style pagination URLs
            $pageTrigger = preg_quote($pageTrigger, '/');

            if (preg_match("/^(?:(.*)\/)?$pageTrigger(\d+)$/", $this->_path, $match)) {
                // Capture the page num
                $this->_pageNum = (int)$match[2];

                // Sanitize
                $this->_path = $match[1];
            }
        }

        $this->_pageNum = min($this->_pageNum, $this->maxPageNum);
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
        if (isset($this->_fullPath)) {
            return $this->_fullPath;
        }

        try {
            if ($this->generalConfig->usePathInfo) {
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
        } catch (InvalidConfigException) {
            $this->_fullPath = $this->_getQueryStringPath();
        }

        return $this->_fullPath = $this->_normalizePath($this->_fullPath);
    }

    /**
     * Returns the requested path, sans control panel trigger and pagination info.
     *
     * If $returnRealPathInfo is returned, then [[\yii\web\Request::getPathInfo()]] will be returned.
     *
     * @param bool $returnRealPathInfo Whether the real path info should be returned instead.
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
     * Returns the full requested URI.
     *
     * @return string
     * @since 3.5.0
     */
    public function getFullUri(): string
    {
        if (isset($this->_fullUri)) {
            return $this->_fullUri;
        }

        $baseUrl = $this->_normalizePath($this->getBaseUrl());
        $path = $this->getFullPath();
        return $this->_fullUri = $baseUrl . ($baseUrl && $path ? '/' : '') . $path;
    }

    /**
     * @inheritdoc
     *
     * ::: warning
     * Don’t include the results of this method in places that will be cached, to avoid a cache poisoning attack.
     * :::
     */
    public function getAbsoluteUrl(): string
    {
        return parent::getAbsoluteUrl();
    }

    /**
     * Returns the segments of the requested path.
     *
     * ::: tip
     * Note that the segments will not include the [control panel trigger](config4:cpTrigger)
     * if it’s a control panel request, or the [page trigger](config4:pageTrigger)
     * or page number if it’s a paginated request.
     * :::
     *
     * ---
     *
     * ```php
     * $segments = Craft::$app->request->segments;
     * ```
     * ```twig
     * {% set segments = craft.app.request.segments %}
     * ```
     *
     * @return array The Craft path’s segments.
     */
    public function getSegments(): array
    {
        if (isset($this->_segments)) {
            return $this->_segments;
        }

        return $this->_segments = $this->_segments($this->_path);
    }

    /**
     * Returns a specific segment from the Craft path.
     *
     * ---
     *
     * ```php
     * $firstSegment = Craft::$app->request->getSegment(1);
     * ```
     * ```twig
     * {% set firstSegment = craft.app.request.getSegment(1) %}
     * ```
     *
     * @param int $num Which segment to return (1-indexed).
     * @return string|null The matching segment, or `null` if there wasn’t one.
     */
    public function getSegment(int $num): ?string
    {
        $segments = $this->getSegments();

        if ($num > 0 && isset($segments[$num - 1])) {
            return $segments[$num - 1];
        }

        if ($num < 0) {
            $totalSegs = count($segments);

            if (isset($segments[$totalSegs + $num])) {
                return $segments[$totalSegs + $num];
            }
        }

        return null;
    }

    /**
     * Returns the requested page number.
     *
     * ---
     *
     * ```php
     * $page = Craft::$app->request->pageNum;
     * ```
     * ```twig
     * {% set page = craft.app.request.pageNum %}
     * ```
     *
     * @return int The requested page number.
     */
    public function getPageNum(): int
    {
        return $this->_pageNum;
    }

    /**
     * Returns whether the request initially had a token.
     *
     * @return bool
     * @throws BadRequestHttpException
     * @since 3.6.0
     */
    public function getHadToken(): bool
    {
        $this->_findToken();
        return $this->_hadToken;
    }

    /**
     * Returns the token submitted with the request, if there is one.
     *
     * Tokens must be sent either as a query string param named after the <config4:tokenParam> config setting (`token` by
     * default), or an `X-Craft-Token` HTTP header on the request.
     *
     * @return string|null The token, or `null` if there isn’t one.
     * @throws BadRequestHttpException if an invalid token is supplied
     * @see \craft\services\Tokens::createToken()
     * @see Controller::requireToken()
     */
    public function getToken(): ?string
    {
        $this->_findToken();
        return $this->_token;
    }

    /**
     * Sets the token value.
     *
     * @param ?string $token
     * @since 3.6.0
     */
    public function setToken(?string $token): void
    {
        // Make sure $this->_hadToken has been set
        try {
            $this->_findToken();
        } catch (BadRequestHttpException) {
        }

        $this->_token = $token;
    }

    /**
     * Looks for a token on the request.
     *
     * @throws BadRequestHttpException
     */
    private function _findToken(): void
    {
        if (isset($this->_hadToken)) {
            return;
        }

        $this->_token = ($this->getQueryParam($this->generalConfig->tokenParam) ?? $this->getHeaders()->get('X-Craft-Token')) ?: null;

        if ($this->_token && !preg_match('/^[A-Za-z0-9_-]+$/', $this->_token)) {
            $this->_token = null;
            $this->_hadToken = false;
            throw new BadRequestHttpException('Invalid token');
        }

        $this->_hadToken = isset($this->_token);
    }

    /**
     * Returns the site token submitted with the request, if there is one.
     *
     * Tokens must be sent either as a query string param named after the <config4:siteToken> config setting
     * (`siteToken` by default), or an `X-Craft-Site-Token` HTTP header on the request.
     *
     * @return string|null The token, or `null` if there isn’t one.
     * @since 3.6.0
     */
    public function getSiteToken(): ?string
    {
        return $this->getQueryParam($this->generalConfig->siteToken) ?? $this->getHeaders()->get('X-Craft-Site-Token');
    }

    /**
     * Returns whether the control panel was requested.
     *
     * The result depends on whether the first segment in the URI matches the
     * [control panel trigger](config4:cpTrigger).
     *
     * @return bool Whether the current request should be routed to the control panel.
     */
    public function getIsCpRequest(): bool
    {
        return $this->_isCpRequest;
    }

    /**
     * Sets whether the control panel was requested.
     *
     * @param bool|null $isCpRequest
     * @since 3.5.0
     */
    public function setIsCpRequest(?bool $isCpRequest = null): void
    {
        $this->_isCpRequest = $isCpRequest;
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
     * - If the first segment in the Craft path matches the [action trigger](config4:actionTrigger)
     * - If there is an `action` param in either the POST data or query string
     * - If the Craft path matches the Login path, the Logout path, or the Set Password path
     *
     * @return bool Whether the current request should be routed to a controller action.
     */
    public function getIsActionRequest(): bool
    {
        $this->checkIfActionRequest();
        return $this->_isActionRequest;
    }

    /**
     * Overrides whether this request should be treated as an action request.
     *
     * @param bool $isActionRequest
     * @see checkIfActionRequest()
     * @since 3.7.8
     */
    public function setIsActionRequest(bool $isActionRequest): void
    {
        $this->_isActionRequest = $isActionRequest;
    }

    /**
     * Returns whether this was a Login request.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsLoginRequest(): bool
    {
        $this->checkIfActionRequest();
        return $this->_isLoginRequest;
    }

    /**
     * Returns the segments of the requested controller action path, if this is an [[getIsActionRequest()|action request]].
     *
     * @return array|null The action path segments, or `null` if this isn’t an action request.
     */
    public function getActionSegments(): ?array
    {
        $this->checkIfActionRequest();
        return $this->_isActionRequest ? $this->_actionSegments : null;
    }

    /**
     * Returns whether this is an element preview request.
     *
     * ::: tip
     * This will only return `true` when previewing entries at the moment. For all other element types, check
     * [[getIsLivePreview()]].
     * :::
     *
     * ---
     * ```php
     * $isPreviewRequest = Craft::$app->request->isPreview;
     * ```
     * ```twig
     * {% set isPreviewRequest = craft.app.request.isPreview %}
     * ```
     *
     * @return bool
     * @since 3.2.1
     */
    public function getIsPreview(): bool
    {
        return $this->getQueryParam('x-craft-preview') !== null || $this->getQueryParam('x-craft-live-preview') !== null;
    }

    /**
     * Returns whether this is a Live Preview request.
     *
     * ::: tip
     * As of Craft 3.2, entries use a new previewing system, so this won’t return `true` for them. Check
     * [[getIsPreview()]] instead for entries.
     * :::
     *
     * ---
     * ```php
     * $isLivePreview = Craft::$app->request->isLivePreview;
     * ```
     * ```twig
     * {% set isLivePreview = craft.app.request.isLivePreview %}
     * ```
     *
     * @return bool Whether this is a Live Preview request.
     */
    public function getIsLivePreview(): bool
    {
        return $this->_isLivePreview;
    }

    /**
     * Sets whether this is a Live Preview request.
     *
     * @param bool $isLivePreview
     */
    public function setIsLivePreview(bool $isLivePreview): void
    {
        $this->_isLivePreview = $isLivePreview;
    }

    /**
     * Returns the MIME type of the request, extracted from the request’s content type.
     *
     * @return string|null
     * @since 3.5.0
     */
    public function getMimeType(): ?string
    {
        $contentType = parent::getContentType();

        if (!$contentType) {
            return null;
        }

        // Strip out the charset & boundary, if present
        if (($pos = strpos($contentType, ';')) !== false) {
            $contentType = substr($contentType, 0, $pos);
        }

        return strtolower(trim($contentType));
    }

    /**
     * Returns whether the request’s MIME type is `application/graphql`.
     *
     * @return bool
     * @since 3.5.0
     */
    public function getIsGraphql(): bool
    {
        return $this->getMimeType() === 'application/graphql';
    }

    /**
     * Returns whether the request’s MIME type is `application/json`.
     *
     * @return bool
     * @since 3.5.0
     */
    public function getIsJson(): bool
    {
        return $this->getMimeType() === 'application/json';
    }

    /**
     * Returns whether the request is coming from a mobile browser.
     *
     * The detection script is provided by http://detectmobilebrowsers.com. It was last updated on 2014-11-24.
     *
     * ---
     *
     * ```php
     * $isMobileBrowser = Craft::$app->request->isMobileBrowser();
     * ```
     * ```twig
     * {% set isMobileBrowser = craft.app.request.isMobileBrowser() %}
     * ```
     *
     * @param bool $detectTablets Whether tablets should be considered “mobile”.
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
                        . ($detectTablets ? '|android|ipad|playbook|silk' : '') . '/i',
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
     * @inheritdoc
     */
    public function getBodyParams(): array
    {
        if ($this->_setBodyParams === false) {
            $params = parent::getBodyParams();

            // Was a namespace passed?
            $namespace = $this->getHeaders()->get('X-Craft-Namespace');
            if ($namespace) {
                $params = $params[$namespace] ?? [];
            }

            $this->setBodyParams($this->_utf8AllTheThings($params));
            $this->_setBodyParams = true;
        }

        return parent::getBodyParams();
    }

    /**
     * Returns the named request body parameter value.
     *
     * If the parameter does not exist, the second argument passed to this method will be returned.
     *
     * ---
     *
     * ```php
     * // get $_POST['foo'], if it exists
     * $foo = Craft::$app->request->getBodyParam('foo');
     *
     * // get $_POST['foo']['bar'], if it exists
     * $bar = Craft::$app->request->getBodyParam('foo.bar');
     * ```
     * ```twig
     * {# get $_POST['foo'], if it exists #}
     * {% set foo = craft.app.request.getBodyParam('foo') %}
     *
     * {# get $_POST['foo']['bar'], if it exists #}
     * {% set bar = craft.app.request.getBodyParam('foo.bar') %}
     * ```
     *
     * @param string $name The parameter name.
     * @param mixed $defaultValue The default parameter value if the parameter does not exist.
     * @return mixed The parameter value
     * @see getBodyParams()
     * @see setBodyParams()
     */
    public function getBodyParam($name, $defaultValue = null): mixed
    {
        return $this->_getParam($name, $defaultValue, $this->getBodyParams());
    }

    /**
     * Returns the named request body parameter value, or bails on the request with a 400 error if that parameter doesn’t exist.
     *
     * ---
     *
     * ```php
     * // get required $_POST['foo']
     * $foo = Craft::$app->request->getRequiredBodyParam('foo');
     *
     * // get required $_POST['foo']['bar']
     * $bar = Craft::$app->request->getRequiredBodyParam('foo.bar');
     * ```
     * ```twig
     * {# get required $_POST['foo'] #}
     * {% set foo = craft.app.request.getRequiredBodyParam('foo') %}
     *
     * {# get required $_POST['foo']['bar'] #}
     * {% set bar = craft.app.request.getRequiredBodyParam('foo.bar') %}
     * ```
     *
     * @param string $name The parameter name.
     * @return mixed The parameter value
     * @throws BadRequestHttpException if the request does not have the body param
     * @see getBodyParam()
     */
    public function getRequiredBodyParam(string $name): mixed
    {
        $value = $this->getBodyParam($name);

        if ($value !== null) {
            return $value;
        }

        throw new BadRequestHttpException("Request missing required body param");
    }

    /**
     * Validates and returns the named request body parameter value, or bails on the request with a 400 error if that parameter doesn’t pass validation.
     *
     * ---
     *
     * ```php
     * // get validated $_POST['foo']
     * $foo = Craft::$app->request->getValidatedBodyParam('foo');
     *
     * // get validated $_POST['foo']['bar']
     * $bar = Craft::$app->request->getValidatedBodyParam('foo.bar');
     * ```
     * ```twig
     * {# get validated $_POST['foo'] #}
     * {% set foo = craft.app.request.getValidatedBodyParam('foo') %}
     *
     * {# get validated $_POST['foo']['bar'] #}
     * {% set bar = craft.app.request.getValidatedBodyParam('foo.bar') %}
     * ```
     *
     * @param string $name The parameter name.
     * @return string|null The parameter value
     * @throws BadRequestHttpException if the param value doesn’t pass validation
     * @see getBodyParam()
     */
    public function getValidatedBodyParam(string $name): ?string
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
    public function getQueryParams(): array
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
     * If the GET parameter does not exist, the second argument passed to this method will be returned.
     *
     * ---
     *
     * ```php
     * // get $_GET['foo'], if it exists
     * $foo = Craft::$app->request->getQueryParam('foo');
     *
     * // get $_GET['foo']['bar'], if it exists
     * $bar = Craft::$app->request->getQueryParam('foo.bar');
     * ```
     * ```twig
     * {# get $_GET['foo'], if it exists #}
     * {% set foo = craft.app.request.getQueryParam('foo') %}
     *
     * {# get $_GET['foo']['bar'], if it exists #}
     * {% set bar = craft.app.request.getQueryParam('foo.bar') %}
     * ```
     *
     * @param string $name The GET parameter name.
     * @param mixed $defaultValue The default parameter value if the GET parameter does not exist.
     * @return mixed The GET parameter value.
     * @see getBodyParam()
     */
    public function getQueryParam($name, $defaultValue = null): mixed
    {
        return $this->_getParam($name, $defaultValue, $this->getQueryParams());
    }

    /**
     * Returns the named GET parameter value, or bails on the request with a 400 error if that parameter doesn’t exist.
     *
     * ---
     *
     * ```php
     * // get required $_GET['foo']
     * $foo = Craft::$app->request->getRequiredQueryParam('foo');
     *
     * // get required $_GET['foo']['bar']
     * $bar = Craft::$app->request->getRequiredQueryParam('foo.bar');
     * ```
     * ```twig
     * {# get required$_GET['foo'] #}
     * {% set foo = craft.app.request.getRequiredQueryParam('foo') %}
     *
     * {# get required $_GET['foo']['bar'] #}
     * {% set bar = craft.app.request.getRequiredQueryParam('foo.bar') %}
     * ```
     *
     * @param string $name The GET parameter name.
     * @return mixed The GET parameter value.
     * @throws BadRequestHttpException if the request does not have the query param
     * @see getQueryParam()
     */
    public function getRequiredQueryParam(string $name): mixed
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
     * @param string $name The parameter name.
     * @param mixed $defaultValue The default parameter value if the parameter does not exist.
     * @return mixed The parameter value.
     * @see getQueryParam()
     * @see getBodyParam()
     */
    public function getParam(string $name, mixed $defaultValue = null): mixed
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
     * @return mixed The parameter value.
     * @throws BadRequestHttpException if the request does not have the param
     * @see getQueryParam()
     * @see getBodyParam()
     */
    public function getRequiredParam(string $name): mixed
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
     * ---
     *
     * ```php
     * $queryString = Craft::$app->request->queryStringWithoutPath;
     * ```
     * ```twig
     * {% set queryString = craft.app.request.queryStringWithoutPath %}
     * ```
     *
     * @return string The query string.
     */
    public function getQueryStringWithoutPath(): string
    {
        // Get the full query string
        $queryString = $this->getQueryString();

        // If there's no path param, just return the full query string
        if (!$this->generalConfig->pathParam) {
            return $queryString;
        }

        // Tear it down and rebuild it without the path param
        $parts = explode('&', $queryString);
        foreach ($parts as $key => $part) {
            if (str_starts_with($part, $this->generalConfig->pathParam . '=')) {
                unset($parts[$key]);
                break;
            }
        }
        return implode('&', $parts);
    }

    /**
     * @inheritdoc
     * @param int $filterOptions bitwise disjunction of flags that should be
     * passed to [filter_var()](https://php.net/manual/en/function.filter-var.php)
     * when validating the IP address. Options include `FILTER_FLAG_IPV4`,
     * `FILTER_FLAG_IPV6`, `FILTER_FLAG_NO_PRIV_RANGE`, and `FILTER_FLAG_NO_RES_RANGE`.
     */
    public function getUserIP(int $filterOptions = 0): ?string
    {
        if (!isset($this->_ipAddress)) {
            foreach ($this->ipHeaders as $ipHeader) {
                if ($this->headers->has($ipHeader)) {
                    foreach (explode(',', $this->headers->get($ipHeader)) as $ip) {
                        if ($ip = $this->_validateIp($ip, $filterOptions)) {
                            return $this->_ipAddress = $ip;
                        }
                    }
                }
            }

            $this->_ipAddress = $this->getRemoteIP($filterOptions) ?? false;
        }

        return $this->_ipAddress ?: null;
    }

    /**
     * @inheritdoc
     * @param int $filterOptions bitwise disjunction of flags that should be
     * passed to [filter_var()](https://php.net/manual/en/function.filter-var.php)
     * when validating the IP address. Options include `FILTER_FLAG_IPV4`,
     * `FILTER_FLAG_IPV6`, `FILTER_FLAG_NO_PRIV_RANGE`, and `FILTER_FLAG_NO_RES_RANGE`.
     */
    public function getRemoteIP(int $filterOptions = 0): ?string
    {
        $ip = parent::getRemoteIP();
        return $ip ? $this->_validateIp($ip, $filterOptions) : null;
    }

    /**
     * Returns whether the client is running "Windows", "Mac", "Linux" or "Other", based on the
     * browser's UserAgent string.
     *
     * ---
     *
     * ```php
     * $clientOs = Craft::$app->request->clientOs;
     * ```
     * ```twig
     * {% set clientOs = craft.app.request.clientOs %}
     * ```
     *
     * @return string The OS the client is running.
     */
    public function getClientOs(): string
    {
        $userAgent = $this->getUserAgent();

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        if (str_contains($userAgent, 'Win')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac')) {
            return 'Mac';
        }

        return 'Other';
    }

    /**
     * Returns the “raw” cookie collection.
     *
     * Works similar to [[getCookies()]], but these cookies won’t go through validation, and their values won’t
     * be hashed.
     *
     * @return CookieCollection the cookie collection.
     * @since 3.5.0
     */
    public function getRawCookies(): CookieCollection
    {
        if (!isset($this->_rawCookies)) {
            $this->_rawCookies = new CookieCollection($this->loadRawCookies(), [
                'readOnly' => true,
            ]);
        }

        return $this->_rawCookies;
    }

    /**
     * Converts any invalid cookies in `$_COOKIE` into an array of [[Cookie]] objects.
     *
     * @return array the cookies obtained from request
     * @return Cookie[]
     * @since 3.5.0
     */
    protected function loadRawCookies(): array
    {
        $cookies = [];

        // If cookie validation is enabled, then we don't need the concept of "raw" cookies to begin with
        if ($this->enableCookieValidation) {
            $security = Craft::$app->getSecurity();
            foreach ($_COOKIE as $name => $value) {
                // Ignore if this is a hashed cookie
                if (is_string($value) && $security->validateData($value, $this->cookieValidationKey) !== false) {
                    continue;
                }
                $cookies[$name] = Craft::createObject([
                    'class' => Cookie::class,
                    'name' => $name,
                    'value' => $value,
                    'expire' => null,
                ]);
            }
        }

        return $cookies;
    }

    /**
     * Returns the token used to perform CSRF validation.
     *
     * This token is a masked version of [[rawCsrfToken]] to prevent [BREACH attacks](http://breachattack.com/).
     * This token may be passed along via a hidden field of an HTML form or an HTTP header value
     * to support CSRF validation.
     *
     * @param bool $regenerate whether to regenerate CSRF token. When this parameter is true, each time
     * this method is called, a new CSRF token will be generated and persisted (in session or cookie).
     * @return string the token used to perform CSRF validation.
     */
    public function getCsrfToken($regenerate = false): string
    {
        if (!isset($this->_craftCsrfToken) || $regenerate) {
            $token = $this->loadCsrfToken();

            if (
                $regenerate ||
                $token === null ||
                !$this->csrfTokenValidForCurrentUser($token)
            ) {
                $token = $this->generateCsrfToken();
            }

            $this->_craftCsrfToken = Craft::$app->getSecurity()->maskToken($token);
        }

        return $this->_craftCsrfToken;
    }

    /**
     * Regenerates a CSRF token.
     */
    public function regenCsrfToken(): void
    {
        $this->_craftCsrfToken = $this->getCsrfToken(true);
    }

    /**
     * Returns whether the request will accept a given content type3
     *
     * @param string $contentType
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

    /**
     * Returns whether the request will accept an image response.
     *
     * @return bool
     * @since 3.5.0
     */
    public function getAcceptsImage(): bool
    {
        return $this->accepts('image/*');
    }

    /**
     * Returns the normalized content type.
     *
     * @return string|null
     * @since 3.3.8
     */
    public function getNormalizedContentType(): ?string
    {
        $rawContentType = $this->getContentType();
        if (($pos = strpos($rawContentType, ';')) !== false) {
            // e.g. text/html; charset=UTF-8
            return substr($rawContentType, 0, $pos);
        }
        return $rawContentType;
    }

    /**
     * @inheritdoc
     * @internal Based on \yii\web\Request::resolve(), but we don't modify $_GET/$this->_queryParams in the process.
     */
    public function resolve(): array
    {
        if (($result = Craft::$app->getUrlManager()->parseRequest($this)) === false) {
            throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'));
        }

        [$route, $params] = $result;

        /** @noinspection AdditionOperationOnArraysInspection */
        return [$route, $params + $this->getQueryParams()];
    }

    /**
     * Generates an unmasked random token used to perform CSRF validation.
     *
     * @return string the random token for CSRF validation.
     */
    protected function generateCsrfToken(): string
    {
        $existingToken = $this->loadCsrfToken();

        // They have an existing CSRF token.
        if ($existingToken) {
            // It's a CSRF token that came from an authenticated request.
            if (str_contains($existingToken, '|')) {
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
            $userId = $currentUser->id;
            $hashable = implode('|', [$nonce, $userId]);
            $token = $nonce . '|' . Craft::$app->getSecurity()->hashData($hashable, $this->cookieValidationKey);
        } else {
            // Unauthenticated users.
            $token = $nonce;
        }

        if ($this->enableCsrfCookie) {
            $cookie = $this->createCsrfCookie($token);
            Craft::$app->getResponse()->getCookies()->add($cookie);
        } else {
            SessionHelper::set($this->csrfParam, $token);
        }

        return $token;
    }

    /**
     * Gets whether the CSRF token is valid for the current user or not
     *
     * @param string $token
     * @return bool
     */
    protected function csrfTokenValidForCurrentUser(string $token): bool
    {
        if (!Craft::$app->getIsInstalled()) {
            return true;
        }

        try {
            if (($currentUser = Craft::$app->getUser()->getIdentity()) === null) {
                return true;
            }
        } catch (DbException) {
            // Craft is probably not installed or updating
            Craft::$app->getUser()->switchIdentity(null);
            return true;
        }

        $splitToken = explode('|', $token, 2);

        if (count($splitToken) !== 2) {
            return false;
        }

        [$nonce,] = $splitToken;

        // Check that this token is for the current user
        $userId = $currentUser->id;
        $hashable = implode('|', [$nonce, $userId]);
        $expectedToken = $nonce . '|' . Craft::$app->getSecurity()->hashData($hashable, $this->cookieValidationKey);

        return Craft::$app->getSecurity()->compareString($expectedToken, $token);
    }

    /**
     * Returns the segments of a given path.
     *
     * @param string $path
     * @return string[]
     */
    private function _segments(string $path): array
    {
        return array_values(ArrayHelper::filterEmptyStringsFromArray(explode('/', $path)));
    }

    /**
     * Normalizes a URI path by trimming leading/trailing slashes and removing double slashes.
     *
     * @param string $path
     * @return string
     */
    private function _normalizePath(string $path): string
    {
        return preg_replace('/\/\/+/', '/', trim($path, '/'));
    }

    /**
     * Returns the site that most closely matches the requested URL.
     *
     * @param int|null $siteScore
     * @return Site
     * @throws BadRequestHttpException if a site token was sent, but the site doesn’t exist
     * @throws SiteNotFoundException if no sites exist
     */
    private function _requestedSite(?int &$siteScore = null): Site
    {
        // Was a site token provided?
        $siteId = $this->getQueryParam($this->generalConfig->siteToken)
            ?? $this->getHeaders()->get('X-Craft-Site-Token')
            ?? false;
        if ($siteId) {
            $siteId = Craft::$app->getSecurity()->validateData($siteId);
            if (!is_numeric($siteId)) {
                throw new BadRequestHttpException('Invalid site token');
            }
            $site = $this->sites->getSiteById((int)$siteId, true);
            if (!$site) {
                throw new BadRequestHttpException('Invalid site ID: ' . $siteId);
            }
            return $site;
        }

        $sites = $this->sites->getAllSites(false);

        if (empty($sites)) {
            throw new SiteNotFoundException('No sites exist');
        }

        $scores = [];
        foreach ($sites as $i => $site) {
            $scores[$i] = $this->_scoreSite($site);
        }

        // Sort by scores descending
        arsort($scores, SORT_NUMERIC);
        $first = ArrayHelper::firstKey($scores);
        $siteScore = reset($scores);
        return $sites[$first];
    }

    /**
     * Scores a site to determine how close of a match it is for the current request.
     *
     * @param Site $site
     * @return int
     */
    private function _scoreSite(Site $site): int
    {
        if ($baseUrl = $site->getBaseUrl()) {
            $score = $this->_scoreUrl($baseUrl);
        } else {
            $score = 0;
        }

        if ($site->primary) {
            // One more point in case we need a tiebreaker
            $score++;
        }

        return $score;
    }

    /**
     * Scores a URL to determine how close of a match it is for the current request.
     *
     * @param string $url
     * @return int
     */
    private function _scoreUrl(string $url): int
    {
        if (($parsed = parse_url($url)) === false) {
            Craft::warning("Unable to parse the URL: $url");
            return 0;
        }

        $hostName = $this->getHostName();

        // Does the site URL specify a host name?
        if (
            !empty($parsed['host']) &&
            $hostName &&
            $parsed['host'] !== $hostName &&
            (
                !App::supportsIdn() ||
                !defined('IDNA_NONTRANSITIONAL_TO_ASCII') ||
                idn_to_ascii($parsed['host'], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46) !== $hostName
            )
        ) {
            return 0;
        }

        // Does the site URL specify a base path?
        $parsedPath = !empty($parsed['path']) ? $this->_normalizePath($parsed['path']) : '';
        if ($parsedPath && !str_starts_with($this->getFullUri() . '/', $parsedPath . '/')) {
            return 0;
        }

        // It's a possible match!
        $score = 1000 + strlen($parsedPath) * 100;

        if ($this->getIsSecureConnection()) {
            $scheme = 'https';
            $port = $this->getSecurePort();
        } else {
            $scheme = 'http';
            $port = $this->getPort();
        }

        $parsedScheme = !empty($parsed['scheme']) ? strtolower($parsed['scheme']) : $scheme;
        $parsedPort = $parsed['port'] ?? ($parsedScheme === 'https' ? 443 : 80);

        // Do the ports match?
        if ($parsedPort == $port) {
            $score += 100;
        }

        // Do the schemes match?
        if ($parsedScheme === $scheme) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Returns the query string path.
     *
     * @return string
     */
    private function _getQueryStringPath(): string
    {
        $value = $this->getQueryParam($this->generalConfig->pathParam, '');
        if (!is_string($value)) {
            return '';
        }
        return $value;
    }

    /**
     * Checks to see if this is an action request.
     *
     * @param bool $force Whether to recheck even if we already know
     * @param bool $checkToken Whether to check if there’s a token on the request and use that.
     * @param bool $checkSpecialPaths Whether to check for special URIs that should route to controller actions
     * @since 3.7.0
     */
    public function checkIfActionRequest(bool $force = false, bool $checkToken = true, bool $checkSpecialPaths = true): void
    {
        if ($this->_checkedRequestType) {
            if (!$force) {
                return;
            }

            // Reset
            $this->_isActionRequest = false;
            $this->_actionSegments = null;
            $this->_isLoginRequest = false;
        }

        // If there's a token on the request, then that should take precedence over everything else
        if (!$checkToken || $this->getToken() === null) {
            $this->_isActionRequest = $this->_checkIfActionRequestInternal($checkSpecialPaths);
        }

        $this->_checkedRequestType = true;
    }

    private function _checkIfActionRequestInternal(bool $checkSpecialPaths): bool
    {
        // Important we check in this specific order:
        // 1) /actions/some/action
        // 2) any/uri?action=some/action
        // 3) special/uri

        // Trigger match?
        if (
            $this->getSegment(1) === $this->generalConfig->actionTrigger &&
            count($this->getSegments()) > 1
        ) {
            $this->_actionSegments = array_slice($this->getSegments(), 1);
            return true;
        }

        // Action param?
        if ($this->getNormalizedContentType() !== 'application/json') {
            $actionParam = $this->getParam('action');
        } else {
            $actionParam = $this->getQueryParam('action');
        }

        if ($actionParam !== null) {
            if (!is_string($actionParam)) {
                throw new BadRequestHttpException('Invalid action param');
            }

            $this->_actionSegments = array_values(array_filter(explode('/', $actionParam)));
            return true;
        }

        // Special path?
        if (
            $checkSpecialPaths &&
            ($this->_isCpRequest || !$this->generalConfig->headlessMode)
        ) {
            $specialPaths = [
                [
                    $this->_isCpRequest ? self::CP_PATH_LOGIN : $this->generalConfig->getLoginPath(),
                    function() {
                        $this->_isLoginRequest = true;
                        return ['users', 'login'];
                    },
                ],
                [
                    $this->_isCpRequest ? self::CP_PATH_LOGOUT : $this->generalConfig->getLogoutPath(),
                    fn() => ['users', 'logout'],
                ],
                [
                    $this->_isCpRequest ? self::CP_PATH_SET_PASSWORD : $this->generalConfig->getSetPasswordPath(),
                    fn() => ['users', 'set-password'],
                ],
                [
                    $this->_isCpRequest ? self::CP_PATH_VERIFY_EMAIL : $this->generalConfig->getVerifyEmailPath(),
                    fn() => ['users', 'verify-email'],
                ],
                [
                    $this->_isCpRequest ? self::CP_PATH_UPDATE : null,
                    fn() => ['updater', 'index'],
                ],
            ];

            foreach ($specialPaths as [$path, $actionSegments]) {
                if ($path === $this->_path) {
                    $this->_actionSegments = $actionSegments();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $things
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
     * @param mixed $value
     * @return mixed
     */
    private function _utf8Value(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->_utf8AllTheThings($value);
        }

        if (is_string($value)) {
            return StringHelper::convertToUtf8($value);
        }

        return $value;
    }

    /**
     * Returns the named parameter value.
     *
     * The name may include dots, to specify the path to a nested param.
     *
     * @param string|null $name
     * @param mixed $defaultValue
     * @param array $params
     * @return mixed
     */
    private function _getParam(?string $name, mixed $defaultValue, array $params): mixed
    {
        // Do they just want the whole array?
        if ($name === null) {
            return $this->_utf8AllTheThings($params);
        }

        return ArrayHelper::getValue($params, $name, $defaultValue);
    }

    /**
     * @param string $ip
     * @param int $filterOptions
     * @return string|null
     */
    private function _validateIp(string $ip, int $filterOptions): ?string
    {
        $ip = trim($ip);
        return filter_var($ip, FILTER_VALIDATE_IP, $filterOptions) !== false ? $ip : null;
    }
}
