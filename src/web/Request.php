<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\base\RequestTrait;
use craft\app\errors\HttpException;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * @inheritdoc
 *
 * @property string $fullPath The full requested path, including the CP trigger and pagination info.
 * @property string $path The requested path, sans CP trigger and pagination info.
 * @property array $segments The segments of the requested path.
 * @property int $pageNum The requested page number.
 * @property string $token The token submitted with the request, if there is one.
 * @property boolean $isCpRequest Whether the Control Panel was requested.
 * @property boolean $isSiteRequest Whether the front end site was requested.
 * @property boolean $isResourceRequest Whether a resource was requested.
 * @property boolean $isActionRequest Whether a specific controller action was requested.
 * @property array $actionSegments The segments of the requested controller action path, if this is an [[getIsActionRequest() action request]].
 * @property boolean $isLivePreview Whether this is a Live Preview request.
 * @property boolean $isMobileBrowser Whether the request is coming from a mobile browser.
 * @property string $hostName The host name from the current request URL.
 * @property string $queryStringWithoutPath The request’s query string, without the path parameter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	private $_isResourceRequest = false;

	/**
	 * @var bool
	 */
	private $_isActionRequest = false;

	/**
	 * @var bool
	 */
	private $_checkedRequestType = false;

	/**
	 * @var
	 */
	private $_actionSegments;

	/**
	 * @var
	 */
	private $_isMobileBrowser;

	/**
	 * @var
	 */
	private $_isMobileOrTabletBrowser;

	/**
	 * @var
	 */
	private $_ipAddress;

	/**
	 * @var array
	 */
	private $_bodyParams;

	/**
	 * @var array
	 */
	private $_queryParams;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		$configService = Craft::$app->getConfig();

		// Is CSRF protection enabled?
		if ($configService->get('enableCsrfProtection') === true)
		{
			$config['enableCsrfValidation'] = true;
			$config['csrfParam'] = $configService->get('csrfTokenName');
			$config['csrfCookie'] = Craft::getCookieConfig([], $this);
		}

		$this->cookieValidationKey = Craft::$app->getSecurity()->getValidationKey();

		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$configService = Craft::$app->getConfig();

		// Sanitize
		$path = $this->getFullPath();

		// Get the path segments
		$this->_segments = array_filter(explode('/', $path));

		// Is this a CP request?
		$this->_isCpRequest = ($this->getSegment(1) == $configService->get('cpTrigger'));

		if ($this->_isCpRequest)
		{
			// Chop the CP trigger segment off of the path & segments array
			array_shift($this->_segments);
		}

		// Is this a paginated request?
		if ($this->_segments)
		{
			// Match against the entire path string as opposed to just the last segment so that we can support
			// "/page/2"-style pagination URLs
			$path = implode('/', $this->_segments);
			$pageTrigger = preg_quote($configService->get('pageTrigger'), '/');

			if (preg_match("/^(?:(.*)\/)?{$pageTrigger}(\d+)$/", $path, $match))
			{
				// Capture the page num
				$this->_pageNum = (int) $match[2];

				// Sanitize
				$newPath = $match[1];

				// Reset the segments without the pagination stuff
				$this->_segments = array_filter(explode('/', $newPath));
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
	public function getFullPath()
	{
		if ($this->_fullPath === null)
		{
			try
			{
				if (Craft::$app->getConfig()->usePathInfo())
				{
					$this->_fullPath = $this->getPathInfo(true);

					if (!$this->_fullPath)
					{
						$this->_fullPath = $this->_getQueryStringPath();
					}
				}
				else
				{
					$this->_fullPath = $this->_getQueryStringPath();

					if (!$this->_fullPath)
					{
						$this->_fullPath = $this->getPathInfo(true);
					}
				}
			}
			catch (InvalidConfigException $e)
			{
				$this->_fullPath = $this->_getQueryStringPath();
			}

			$this->_fullPath = trim($this->_fullPath, '/');
		}

		return $this->_fullPath;
	}

	/**
	 * @inheritdoc
	 */
	public function resolve()
	{
		$result = parent::resolve();

		// Merge in any additional parameters stored on UrlManager
		$params = Craft::$app->getUrlManager()->getRouteParams();

		if ($params)
		{
			$result[1] = ArrayHelper::merge($result[1], $params);
		}

		return $result;
	}

	/**
	 * Returns the requested path, sans CP trigger and pagination info.
	 *
	 * If $returnRealPathInfo is returned, then [[parent::getPathInfo()]] will be returned.
	 *
	 * @param boolean $returnRealPathInfo Whether the real path info should be returned instead.
	 * @see \yii\web\UrlManager::processRequest()
	 * @see \yii\web\UrlRule::processRequest()
	 * @return string The requested path, or the path info.
	 * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
	 */
	public function getPathInfo($returnRealPathInfo = false)
	{
		if ($returnRealPathInfo)
		{
			return parent::getPathInfo();
		}
		else
		{
			return $this->_path;
		}
	}

	/**
	 * Returns the segments of the requested path.
	 *
	 * Note that the segments will not include the [CP trigger](http://buildwithcraft.com/docs/config-settings#cpTrigger)
	 * if it’s a CP request, or the [page trigger](http://buildwithcraft.com/docs/config-settings#pageTrigger) or page
	 * number if it’s a paginated request.
	 *
	 * @return array The Craft path’s segments.
	 */
	public function getSegments()
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
	public function getSegment($num)
	{
		if ($num > 0 && isset($this->_segments[$num-1]))
		{
			return $this->_segments[$num-1];
		}

		if ($num < 0)
		{
			$totalSegs = count($this->_segments);

			if (isset($this->_segments[$totalSegs + $num]))
			{
				return $this->_segments[$totalSegs + $num];
			}
		}
	}

	/**
	 * Returns the requested page number.
	 *
	 * @return int The requested page number.
	 */
	public function getPageNum()
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
		return $this->getQueryParam(Craft::$app->getConfig()->get('tokenParam'));
	}

	/**
	 * Returns whether the Control Panel was requested.
	 *
	 * The result depends on whether the first segment in the URI matches the
	 * [CP trigger](http://buildwithcraft.com/docs/config-settings#cpTrigger).
	 *
	 * Note that even if this function returns `true`, the request will not necessarily route to the Control Panel.
	 * It could instead route to a resource, for example.
	 *
	 * @return bool Whether the current request should be routed to the Control Panel.
	 */
	public function getIsCpRequest()
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
	public function getIsSiteRequest()
	{
		return !$this->_isCpRequest;
	}

	/**
	 * Returns whether a resource was requested.
	 *
	 * The result depends on whether the first segment in the Craft path matches the
	 * [resource trigger](http://buildwithcraft.com/docs/config-settings#resourceTrigger).
	 *
	 * @return bool Whether the current request should be routed to a resource.
	 */
	public function getIsResourceRequest()
	{
		$this->_checkRequestType();
		return $this->_isResourceRequest;
	}

	/**
	 * Returns whether a specific controller action was requested.
	 *
	 * There are several ways that this method could return `true`:
	 *
	 * - If the first segment in the Craft path matches the
	 *   [action trigger](http://buildwithcraft.com/docs/config-settings#actionTrigger)
	 * - If there is an 'action' param in either the POST data or query string
	 * - If the Craft path matches the Login path, the Logout path, or the Set Password path
	 *
	 * @return bool Whether the current request should be routed to a controller action.
	 */
	public function getIsActionRequest()
	{
		$this->_checkRequestType();
		return $this->_isActionRequest;
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
	public function getIsLivePreview()
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
	public function getIsMobileBrowser($detectTablets = false)
	{
		$key = ($detectTablets ? '_isMobileOrTabletBrowser' : '_isMobileBrowser');

		if (!isset($this->$key))
		{
			if ($this->getUserAgent())
			{
				$this->$key = (
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
			}
			else
			{
				$this->$key = false;
			}
		}

		return $this->$key;
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
	public function getHostName()
	{
		if (isset($_SERVER['HTTP_HOST']))
		{
			return $_SERVER['HTTP_HOST'];
		}

		return $_SERVER['SERVER_NAME'];
	}

	/**
	 * @inheritdoc
	 */
	public function getBodyParams()
	{
		if (!isset($this->_bodyParams))
		{
			$this->_bodyParams = $this->_utf8AllTheThings(parent::getBodyParams());
		}

		return $this->_bodyParams;
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
	 * @param string $name The parameter name.
	 * @param mixed $defaultValue The default parameter value if the parameter does not exist.
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
	 * @return mixed The parameter value
	 * @throws HttpException
	 * @see getBodyParam()
	 */
	public function getRequiredBodyParam($name)
	{
		$value = $this->getBodyParam($name);

		if ($value !== null)
		{
			return $value;
		}

		throw new HttpException(400, Craft::t('app', 'Body param “{name}” doesn’t exist.', ['name' => $name]));
	}

	/**
	 * @inheritdoc
	 */
	public function setBodyParams($values)
	{
		$this->_bodyParams = $values;
	}

	/**
	 * @inheritdoc
	 */
	public function getQueryParams()
	{
		if (!isset($this->_queryParams))
		{
			$this->_queryParams = $this->_utf8AllTheThings(parent::getQueryParams());
		}

		return $this->_queryParams;
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
	 * @param string $name         The GET parameter name.
	 * @param mixed  $defaultValue The default parameter value if the GET parameter does not exist.
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
	 * @return mixed The GET parameter value.
	 * @throws HttpException
	 * @see getQueryParam()
	 */
	public function getRequiredQueryParam($name)
	{
		$value = $this->getQueryParam($name);

		if ($value !== null)
		{
			return $value;
		}

		throw new HttpException(400, Craft::t('app', 'GET param “{name}” doesn’t exist.', ['name' => $name]));
	}

	/**
	 * @inheritdoc
	 */
	public function setQueryParams($values)
	{
		$this->_queryParams = $values;
	}

	/**
	 * Returns the named parameter value from either GET or the request body.
	 *
	 * If the parameter does not exist, the second parameter to this method will be returned.
	 *
	 * @param string $name         The parameter name.
	 * @param mixed  $defaultValue The default parameter value if the parameter does not exist.
	 * @return mixed The parameter value.
	 * @see getQueryParam()
	 * @see getBodyParam()
	 */
	public function getParam($name, $defaultValue = null)
	{
		if (($value = $this->getQueryParam($name)) !== null)
		{
			return $value;
		}

		if (($value = $this->getBodyParam($name)) !== null)
		{
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
	 * @throws HttpException
	 * @see getQueryParam()
	 * @see getBodyParam()
	 */
	public function getRequiredParam($name)
	{
		$value = $this->getParam($name);

		if ($value !== null)
		{
			return $value;
		}

		throw new HttpException(400, Craft::t('app', 'Param “{name}” doesn’t exist.', ['name' => $name]));
	}

	/**
	 * Returns the request’s query string, without the path parameter.
	 *
	 * @return string The query string.
	 */
	public function getQueryStringWithoutPath()
	{
		// Get the full query string.
		$queryString = $this->getQueryString();

		$parts = explode('&', $queryString);

		if (count($parts) == 1)
		{
			return '';
		}

		$pathSubstr = Craft::$app->getConfig()->get('pathParam').'=';

		foreach ($parts as $key => $part)
		{
			if (StringHelper::startsWith($part, $pathSubstr))
			{
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
	public function getUserIP()
	{
		if ($this->_ipAddress === null)
		{
			$ipMatch = false;

			// Check for shared internet/ISP IP
			if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->_validateIp($_SERVER['HTTP_CLIENT_IP']))
			{
				$ipMatch = $_SERVER['HTTP_CLIENT_IP'];
			}
			else
			{
				// Check for IPs passing through proxies
				if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
				{
					// Check if multiple IPs exist in var
					$ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

					foreach ($ipList as $ip)
					{
						if ($this->_validateIp($ip))
						{
							$ipMatch = $ip;
						}
					}
				}
			}

			if (!$ipMatch)
			{
				if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->_validateIp($_SERVER['HTTP_X_FORWARDED']))
				{
					$ipMatch = $_SERVER['HTTP_X_FORWARDED'];
				}
				else
				{
					if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->_validateIp($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
					{
						$ipMatch = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
					}
					else
					{
						if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->_validateIp($_SERVER['HTTP_FORWARDED_FOR']))
						{
							$ipMatch = $_SERVER['HTTP_FORWARDED_FOR'];
						}
						else
						{
							if (!empty($_SERVER['HTTP_FORWARDED']) && $this->_validateIp($_SERVER['HTTP_FORWARDED']))
							{
								$ipMatch = $_SERVER['HTTP_FORWARDED'];
							}
						}
					}
				}

				// The only one we're guaranteed to be accurate.
				if (!$ipMatch)
				{
					$ipMatch = $_SERVER['REMOTE_ADDR'];
				}
			}

			$this->_ipAddress = $ipMatch;
		}

		return $this->_ipAddress;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the query string path.
	 *
	 * @return string
	 */
	private function _getQueryStringPath()
	{
		$pathParam = Craft::$app->getConfig()->get('pathParam');
		return $this->getQueryParam($pathParam, '');
	}

	/**
	 * Checks to see if this is an action or resource request.
	 *
	 * @return null
	 */
	private function _checkRequestType()
	{
		if ($this->_checkedRequestType)
		{
			return;
		}

		$configService = Craft::$app->getConfig();

		// If there's a token in the query string, then that should take precedence over everything else
		if (!$this->getQueryParam($configService->get('tokenParam')))
		{
			$firstSegment = $this->getSegment(1);

			// Is this a resource request?
			if ($firstSegment == $configService->getResourceTrigger())
			{
				$this->_isResourceRequest = true;
			}
			else
			{
				// Is this an action request?
				if ($this->_isCpRequest)
				{
					$loginPath       = $configService->getCpLoginPath();
					$logoutPath      = $configService->getCpLogoutPath();
					$setPasswordPath = $configService->getCpSetPasswordPath();
				}
				else
				{
					$loginPath       = trim($configService->getLocalized('loginPath'), '/');
					$logoutPath      = trim($configService->getLocalized('logoutPath'), '/');
					$setPasswordPath = trim($configService->getLocalized('setPasswordPath'), '/');
				}

				$verifyEmailPath = 'verifyemail';

				if (
					($triggerMatch = ($firstSegment == $configService->get('actionTrigger') && count($this->_segments) > 1)) ||
					($actionParam = $this->getParam('action')) !== null ||
					($specialPath = in_array($this->_path, [$loginPath, $logoutPath, $setPasswordPath, $verifyEmailPath]))
				)
				{
					$this->_isActionRequest = true;

					if ($triggerMatch)
					{
						$this->_actionSegments = array_slice($this->_segments, 1);
					}
					else if ($actionParam)
					{
						$this->_actionSegments = array_filter(explode('/', $actionParam));
					}
					else
					{
						if ($this->_path == $loginPath)
						{
							$this->_actionSegments = ['users', 'login'];
						}
						else if ($this->_path == $logoutPath)
						{
							$this->_actionSegments = ['users', 'logout'];
						}
						else if ($this->_path == $verifyEmailPath)
						{
							$this->_actionSegments = ['users', 'verify-email'];
						}
						else
						{
							$this->_actionSegments = ['users', 'set-password'];
						}
					}

					// TODO: Remove this in Craft 4
					// Make sure it's a Yii 2-styled route
					$invalid = false;
					$requestedRoute = implode('/', $this->_actionSegments);

					foreach ($this->_actionSegments as $k => $v)
					{
						if (StringHelper::hasUpperCase($v))
						{
							$parts = preg_split('/(?=[\p{Lu}])+/u', $v);
							$this->_actionSegments[$k] = StringHelper::toLowerCase(implode('-', $parts));
							$invalid = true;
						}
					}

					if ($invalid === true)
					{
						Craft::$app->getDeprecator()->log('yii1-route', 'A Yii 1-styled route was requested: "'.$requestedRoute.'". It should be changed to: "'.implode('/', $this->_actionSegments).'".');
					}
				}
			}
		}

		$this->_checkedRequestType = true;
	}

	/**
	 * @param array|string $things
	 *
	 * @return mixed
	 */
	private function _utf8AllTheThings($things)
	{
		if (is_array($things))
		{
			foreach ($things as $key => $value)
			{
				if (is_array($value))
				{
					$things[$key] = $this->_utf8AllTheThings($value);
				}
				else
				{
					$things[$key] = StringHelper::convertToUtf8($value);
				}
			}
		}
		else
		{
			$things = StringHelper::convertToUtf8($things);
		}

		return $things;
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
	private function _getParam($name, $defaultValue, $params)
	{
		// Do they just want the whole array?
		if (!$name)
		{
			return $this->_utf8AllTheThings($params);
		}

		// Looking for a specific value?
		if (isset($params[$name]))
		{
			return $this->_utf8AllTheThings($params[$name]);
		}

		// Maybe they're looking for a nested param?
		if (StringHelper::contains($name, '.'))
		{
			$path = explode('.', $name);
			$param = $params;

			foreach ($path as $step)
			{
				if (is_array($param) && isset($param[$step]))
				{
					$param = $param[$step];
				}
				else
				{
					return $defaultValue;
				}
			}

			return $this->_utf8AllTheThings($param);
		}

		return $defaultValue;
	}

	/**
	 * @param string $ip
	 *
	 * @return bool
	 */
	private function _validateIp($ip)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false)
		{
			return false;
		}

		return true;
	}
}
