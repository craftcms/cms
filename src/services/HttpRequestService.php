<?php
namespace Craft;

/**
 * HttpRequestService provides APIs for getting information about the current HTTP request.
 *
 * An instance of HttpRequestService is globally accessible in Craft via {@link WebApp::request `craft()->request`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class HttpRequestService extends \CHttpRequest
{
	// Properties
	// =========================================================================

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
	private $_mimeType;

	/**
	 * @var
	 */
	private $_browserLanguages;

	/**
	 * @var
	 */
	private $_ipAddress;

	/**
	 * @var
	 */
	private $_cookies;

	/**
	 * @var
	 */
	private $_csrfToken;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		// Is CSRF protection enabled?
		if (craft()->config->get('enableCsrfProtection') === true)
		{
			$this->enableCsrfValidation = true;

			// Grab the token name.
			$this->csrfTokenName = craft()->config->get('csrfTokenName');
		}

		// Now initialize Yii's CHttpRequest.
		parent::init();

		// There is no path.
		if (craft()->isConsole())
		{
			$path = '';
		}
		else
		{
			// Get the normalized path.
			$path = $this->getNormalizedPath();
		}

		// Get the path segments
		$this->_segments = array_filter(explode('/', $path), function($value)
		{
			// Explicitly check in case there is a 0 in a segment (i.e. foo/0 or foo/0/bar)
			return $value !== '';
		});

		// Is this a CP request?
		$this->_isCpRequest = ($this->getSegment(1) == craft()->config->get('cpTrigger'));

		if ($this->_isCpRequest)
		{
			// Chop the CP trigger segment off of the path & segments array
			array_shift($this->_segments);
		}

		// Is this a paginated request?
		$pageTrigger = craft()->config->get('pageTrigger');

		if (!is_string($pageTrigger) || !strlen($pageTrigger))
		{
			$pageTrigger = 'p';
		}

		// Is this query string-based pagination?
		if ($pageTrigger[0] === '?')
		{
			$pageTrigger = trim($pageTrigger, '?=');

			if ($pageTrigger === 'p')
			{
				// Avoid conflict with the main 'p' param
				$pageTrigger = 'pg';
			}

			$this->_pageNum = (int) $this->getQuery($pageTrigger, '1');
		}
		else if ($this->_segments)
		{
			// Match against the entire path string as opposed to just the last segment so that we can support
			// "/page/2"-style pagination URLs
			$path = implode('/', $this->_segments);
			$pageTrigger = preg_quote(craft()->config->get('pageTrigger'), '/');

			if (preg_match("/^(?:(.*)\/)?{$pageTrigger}(\d+)$/", $path, $match))
			{
				// Capture the page num
				$this->_pageNum = (int) $match[2];

				// Sanitize
				$newPath = $this->decodePathInfo($match[1]);

				// Reset the segments without the pagination stuff
				$this->_segments = array_filter(explode('/', $newPath));
			}
		}

		// Now that we've chopped off the admin/page segments, set the path
		$this->_path = implode('/', $this->_segments);
	}

	/**
	 * Returns the script name used to access Craft (e.g. “index.php”).
	 *
	 * @return string
	 */
	public function getScriptName()
	{
		$scriptUrl = $this->getScriptUrl();
		return mb_substr($scriptUrl, mb_strrpos($scriptUrl, '/')+1);
	}

	/**
	 * Returns the request’s Craft path.
	 *
	 * Note that the path will not include the [CP trigger](http://craftcms.com/docs/config-settings#cpTrigger)
	 * if it’s a CP request, or the [page trigger](http://craftcms.com/docs/config-settings#pageTrigger) or page
	 * number if it’s a paginated request.
	 *
	 * @return string The Craft path.
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Returns an array of the Craft path’s segments.
	 *
	 * Note that the segments will not include the [CP trigger](http://craftcms.com/docs/config-settings#cpTrigger)
	 * if it’s a CP request, or the [page trigger](http://craftcms.com/docs/config-settings#pageTrigger) or page
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
		else if ($num < 0)
		{
			$totalSegs = count($this->_segments);

			if (isset($this->_segments[$totalSegs + $num]))
			{
				return $this->_segments[$totalSegs + $num];
			}
		}
	}

	/**
	 * Returns the current page number.
	 *
	 * @return int The page number.
	 */
	public function getPageNum()
	{
		return $this->_pageNum;
	}

	/**
	 * Returns the request’s token, if there is one.
	 *
	 * @return string|null The request’s token, or `null` if there isn’t one.
	 */
	public function getToken()
	{
		return $this->getQuery(craft()->config->get('tokenParam'));
	}

	/**
	 * Returns whether the current request should be routed to the Control Panel.
	 *
	 * The result depends on whether the first segment in the URI matches the
	 * [CP trigger](http://craftcms.com/docs/config-settings#cpTrigger).
	 *
	 * Note that even if this function returns `true`, the request will not necessarily route to the Control Panel.
	 * It could instead route to a resource, for example.
	 *
	 * @return bool Whether the current request should be routed to the Control Panel.
	 */
	public function isCpRequest()
	{
		return $this->_isCpRequest;
	}

	/**
	 * Returns whether the current request should be routed to the front-end site.
	 *
	 * The result will always just be the opposite of whatever {@link isCpRequest()} returns.
	 *
	 * @return bool Whether the current request should be routed to the front-end site.
	 */
	public function isSiteRequest()
	{
		return !$this->_isCpRequest;
	}

	/**
	 * Returns whether the current request should be routed to a resource.
	 *
	 * The result depends on whether the first segment in the Craft path matches the
	 * [resource trigger](http://craftcms.com/docs/config-settings#resourceTrigger).
	 *
	 * @return bool Whether the current request should be routed to a resource.
	 */
	public function isResourceRequest()
	{
		$this->_checkRequestType();
		return $this->_isResourceRequest;
	}

	/**
	 * Returns whether the current request should be routed to a specific controller action before normal request
	 * routing takes over.
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
	public function isActionRequest()
	{
		$this->_checkRequestType();
		return $this->_isActionRequest;
	}

	/**
	 * Returns an array of the action path segments, if this is an {@link isActionRequest() action request}.
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
	public function isLivePreview()
	{
		return (
			$this->isSiteRequest() &&
			$this->isActionRequest() &&
			craft()->request->getPost('livePreview')
		);
	}

	/**
	 * Returns the MIME type that is going to be included in the response via the Content-Type header.
	 *
	 * @return string
	 * @deprecated Deprecated in 2.2. Use {@link HeaderHelper::getMimeType()} instead.
	 */
	public function getMimeType()
	{
		// TODO: Call the deprecator here in Craft 3.0
		return HeaderHelper::getMimeType();
	}

	/**
	 * Returns a query string parameter, or all of them.
	 *
	 * If $name is specified, then the corresponding query string parameter will be returned if it exists, or
	 * $defaultValue will be returned if it doesn’t.
	 *
	 * ```php
	 * $foo = craft()->request->getQuery('foo'); // Returns $_GET['foo'], if it exists
	 * ```
	 *
	 * $name can also represent a nested parameter using a dot-delimited string.
	 *
	 * ```php
	 * $bar = craft()->request->getQuery('foo.bar'); // Returns $_GET['foo']['bar'], if it exists
	 * ```
	 *
	 * If $name is omitted, the entire $_GET array will be returned instead:
	 *
	 * ```php
	 * $allTheQueryParams = craft()->request->getQuery(); // Returns $_GET
	 * ```
	 *
	 * All values will be converted to UTF-8, regardless of the original character encoding.
	 *
	 * @param string|null $name         The dot-delimited name of the query string param to be fetched, if any.
	 * @param mixed       $defaultValue The fallback value to be returned if no param exists by the given $name.
	 *                                  Defaults to `null`.
	 *
	 * @return mixed The value of the corresponding query string param if a single param was requested, or $defaultValue
	 *               if that value didn’t exist, or the entire $_GET array if no single param was requested.
	 */
	public function getQuery($name = null, $defaultValue = null)
	{
		return $this->_getParam($name, $defaultValue, $_GET);
	}

	/**
	 * Returns a query string parameter, or bails on the request with a 400 error if that parameter doesn’t exist.
	 *
	 * ```php
	 * $foo = craft()->request->getRequiredQuery('foo'); // Returns $_GET['foo']
	 * ```
	 *
	 * $name can also represent a nested parameter using a dot-delimited string.
	 *
	 * ```php
	 * $bar = craft()->request->getRequiredQuery('foo.bar'); // Returns $_GET['foo']['bar']
	 * ```
	 *
	 * The returned value will be converted to UTF-8, regardless of the original character encoding.
	 *
	 * @param string $name The dot-delimited name of the query string param to be fetched.
	 *
	 * @throws HttpException
	 *
	 * @return mixed The value of the corresponding query string param.
	 */
	public function getRequiredQuery($name)
	{
		$value = $this->getQuery($name);

		if ($value !== null)
		{
			return $value;
		}
		else
		{
			throw new HttpException(400, Craft::t('GET param “{name}” doesn’t exist.', array('name' => $name)));
		}
	}

	/**
	 * Returns a POST parameter, or all of them.
	 *
	 * If $name is specified, then the corresponding POST parameter will be returned if it exists, or
	 * $defaultValue will be returned if it doesn’t.
	 *
	 * ```php
	 * $foo = craft()->request->getPost('foo'); // Returns $_POST['foo'], if it exists
	 * ```
	 *
	 * $name can also represent a nested parameter using a dot-delimited string.
	 *
	 * ```php
	 * $bar = craft()->request->getPost('foo.bar'); // Returns $_POST['foo']['bar'], if it exists
	 * ```
	 *
	 * If $name is omitted, the entire $_POST array will be returned instead:
	 *
	 * ```php
	 * $allThePostParams = craft()->request->getPost(); // Returns $_POST
	 * ```
	 *
	 * All values will be converted to UTF-8, regardless of the original character encoding.
	 *
	 * @param string|null $name         The dot-delimited name of the POST param to be fetched, if any.
	 * @param mixed       $defaultValue The fallback value to be returned if no param exists by the given $name.
	 *                                  Defaults to `null`.
	 *
	 * @return mixed The value of the corresponding POST param if a single param was requested, or $defaultValue
	 *               if that value didn’t exist, or the entire $_POST array if no single param was requested.
	 */
	public function getPost($name = null, $defaultValue = null)
	{
		return $this->_getParam($name, $defaultValue, $_POST);
	}

	/**
	 * Returns a POST parameter, or bails on the request with a 400 error if that parameter doesn’t exist.
	 *
	 * ```php
	 * $foo = craft()->request->getRequiredPost('foo'); // Returns $_POST['foo']
	 * ```
	 *
	 * $name can also represent a nested parameter using a dot-delimited string.
	 *
	 * ```php
	 * $bar = craft()->request->getRequiredPost('foo.bar'); // Returns $_POST['foo']['bar']
	 * ```
	 *
	 * The returned value will be converted to UTF-8, regardless of the original character encoding.
	 *
	 * @param string $name The dot-delimited name of the POST param to be fetched.
	 *
	 * @throws HttpException
	 *
	 * @return mixed The value of the corresponding POST param.
	 */
	public function getRequiredPost($name)
	{
		$value = $this->getPost($name);

		if ($value !== null)
		{
			return $value;
		}
		else
		{
			throw new HttpException(400, Craft::t('POST param “{name}” doesn’t exist.', array('name' => $name)));
		}
	}

	/**
	 * Returns a POST parameter. If the validateUnsafeRequestParams config setting has been set to `true`,
	 * and this is a front-end request, then the POST parameter’s value will be validated with
	 * {@link SecurityService::validateData()} before being returned, ensuring that the value had not
	 * been tampered with by the user.
	 *
	 * @param string $name The dot-delimited name of the POST param to be fetched.
	 *
	 * @return mixed The value of the corresponding POST param
	 * @thorws HttpException if the param did not validate
	 */
	public function getValidatedPost($name)
	{
		$value = $this->getPost($name);

		if ($value !== null && $this->isSiteRequest() && craft()->config->get('validateUnsafeRequestParams'))
		{
			$value = craft()->security->validateData($value);

			if ($value === false)
			{
				throw new HttpException(400, Craft::t('POST param “{name}” was invalid.', array('name' => $name)));
			}
		}

		return $value;
	}

	/**
	 * Returns a parameter from either the query string or POST data.
	 *
	 * This method will first search for the given paramater in the query string, calling {@link getQuery()} internally,
	 * and if that doesn’t come back with a value, it will call {@link getPost()}. If that doesn’t come back with a
	 * value either, $defaultValue will be returned.
	 *
	 * ```php
	 * $foo = craft()->request->getParam('foo'); // Returns $_GET['foo'] or $_POST['foo'], if either exist
	 * ```
	 *
	 * $name can also represent a nested parameter using a dot-delimited string.
	 *
	 * ```php
	 * $bar = craft()->request->getParam('foo.bar'); // Returns $_GET['foo']['bar'] or $_POST['foo']['bar'], if either exist
	 * ```
	 *
	 * All values will be converted to UTF-8, regardless of the original character encoding.
	 *
	 * @param string $name         The dot-delimited name of the param to be fetched.
	 * @param mixed  $defaultValue The fallback value to be returned if no param exists by the given $name.
	 *                             Defaults to `null`.
	 *
	 * @return mixed The value of the corresponding param, or $defaultValue if that value didn’t exist.
	 */
	public function getParam($name, $defaultValue = null)
	{
		if (($value = $this->getQuery($name)) !== null)
		{
			return $value;
		}
		else if (($value = $this->getPost($name)) !== null)
		{
			return $value;
		}

		return $defaultValue;
	}

	/**
	 * Returns a parameter from either the query string or POST data, or bails on the request with a 400 error if that
	 * parameter doesn’t exist anywhere.
	 *
	 * This method will first search for the given paramater in the query string, calling {@link getQuery()} internally,
	 * and if that doesn’t come back with a value, it will call {@link getPost()}.
	 *
	 * ```php
	 * $foo = craft()->request->getRequiredParam('foo'); // Returns $_GET['foo'] or $_POST['foo']
	 * ```
	 *
	 * $name can also represent a nested parameter using a dot-delimited string.
	 *
	 * ```php
	 * $bar = craft()->request->getParam('foo.bar'); // Returns $_GET['foo']['bar'] or $_POST['foo']['bar'], if either exist
	 * ```
	 *
	 * All values will be converted to UTF-8, regardless of the original character encoding.
	 *
	 * @param string $name The dot-delimited name of the param to be fetched.
	 *
	 * @throws HttpException
	 * @return mixed The value of the corresponding param, or $defaultValue if that value didn’t exist.
	 */
	public function getRequiredParam($name)
	{
		$value = $this->getParam($name);

		if ($value !== null)
		{
			return $value;
		}
		else
		{
			throw new HttpException(400, Craft::t('Param “{name}” doesn’t exist.', array('name' => $name)));
		}
	}

	/**
	 * Returns whether the request is coming from a mobile browser.
	 *
	 * The detection script is provided by http://detectmobilebrowsers.com. It was last updated on 2014-11-24.
	 *
	 * @param bool $detectTablets Whether tablets should be considered “modile”.
	 *
	 * @return bool Whether the request is coming from a mobile browser.
	 */
	public function isMobileBrowser($detectTablets = false)
	{
		$key = ($detectTablets ? '_isMobileOrTabletBrowser' : '_isMobileBrowser');

		if (!isset($this->$key))
		{
			if ($this->userAgent)
			{
				$this->$key = (
					preg_match(
						'/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino'.($detectTablets ? '|android|ipad|playbook|silk' : '').'/i',
						$this->userAgent
					) ||
					preg_match(
						'/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',
						mb_substr($this->userAgent, 0, 4)
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
	 * Returns a list of languages the user has selected in their browser’s settings, canonicalized using
	 * {@link LocaleData::getCanonicalID}.
	 *
	 * Internally, this method checks the Accept-Language header that should have accompanied the request.
	 * If that header was not present, the method will return `false`.
	 *
	 * @return array|false The preferred languages, or `false` if Craft is unable to determine them.
	 */
	public function getBrowserLanguages()
	{
		if (!isset($this->_browserLanguages))
		{
			$this->_browserLanguages = array();

			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && preg_match_all('/([\w\-_]+)\s*(?:;\s*q\s*=\s*(\d*\.\d*))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches, PREG_SET_ORDER))
			{
				$weights = array();

				foreach ($matches as $match)
				{
					$this->_browserLanguages[] = LocaleData::getCanonicalID($match[1]);
					$weights[] = !empty($match[2]) ? floatval($match[2]) : 1;
				}

				// Sort the languages by their weight
				array_multisort($weights, SORT_NUMERIC, SORT_DESC, $this->_browserLanguages);
			}
		}

		if ($this->_browserLanguages)
		{
			return $this->_browserLanguages;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the host name, without “http://” or “https://”.
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
		else
		{
			return $_SERVER['SERVER_NAME'];
		}
	}

	/**
	 * Sends a file to the user.
	 *
	 * We’re overriding this from {@link \CHttpRequest::sendFile()} so we can have more control over the headers.
	 *
	 * @param string     $path      The path to the file on the server.
	 * @param string     $content   The contents of the file.
	 * @param array|null $options   An array of optional options. Possible keys include 'forceDownload', 'mimeType',
	 *                              and 'cache'.
	 * @param bool|null  $terminate Whether the request should be terminated after the file has been sent.
	 *                              Defaults to `true`.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function sendFile($path, $content, $options = array(), $terminate = true)
	{
		$fileName = empty($options['filename']) ? IOHelper::getFileName($path, true) : $options['filename'];

		// Clear the output buffer to prevent corrupt downloads. Need to check the OB status first, or else some PHP
		// versions will throw an E_NOTICE since we have a custom error handler
		// (http://pear.php.net/bugs/bug.php?id=9670)
		if (ob_get_length() !== false)
		{
			// If zlib.output_compression is enabled, then ob_clean() will corrupt the results of output buffering.
			// ob_end_clean is what we want.
			ob_end_clean();
		}

		// Default to disposition to 'download'
		$forceDownload = !isset($options['forceDownload']) || $options['forceDownload'];

		if ($forceDownload)
		{
			HeaderHelper::setDownload($fileName);
		}

		if (empty($options['mimeType']))
		{
			if (($options['mimeType'] = FileHelper::getMimeTypeByExtension($fileName)) === null)
			{
				$options['mimeType'] = 'text/plain';
			}
		}

		HeaderHelper::setHeader(array('Content-Type' => $options['mimeType'].'; charset=utf-8'));

		$fileSize = mb_strlen($content, '8bit');
		$contentStart = 0;
		$contentEnd = $fileSize - 1;

		$httpVersion = $this->getHttpVersion();

		if (isset($_SERVER['HTTP_RANGE']))
		{
			HeaderHelper::setHeader(array('Accept-Ranges' => 'bytes'));

			// Client sent us a multibyte range, can not hold this one for now
			if (mb_strpos($_SERVER['HTTP_RANGE'], ',') !== false)
			{
				HeaderHelper::setHeader(array('Content-Range' => 'bytes '.$contentStart - $contentEnd / $fileSize));
				throw new HttpException(416, 'Requested Range Not Satisfiable');
			}

			$range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);

			// range requests starts from "-", so it means that data must be dumped the end point.
			if ($range[0] === '-')
			{
				$contentStart = $fileSize - mb_substr($range, 1);
			}
			else
			{
				$range = explode('-', $range);
				$contentStart = $range[0];

				// check if the last-byte-pos presents in header
				if ((isset($range[1]) && is_numeric($range[1])))
				{
					$contentEnd = $range[1];
				}
			}

			// Check the range and make sure it's treated according to the specs.
			// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html

			// End bytes can not be larger than $end.
			$contentEnd = ($contentEnd > $fileSize) ? $fileSize - 1 : $contentEnd;

			// Validate the requested range and return an error if it's not correct.
			$wrongContentStart = ($contentStart > $contentEnd || $contentStart > $fileSize - 1 || $contentStart < 0);

			if ($wrongContentStart)
			{
				HeaderHelper::setHeader(array('Content-Range' => 'bytes '.$contentStart - $contentEnd / $fileSize));
				throw new HttpException(416, 'Requested Range Not Satisfiable');
			}

			HeaderHelper::setHeader("HTTP/$httpVersion 206 Partial Content");
			HeaderHelper::setHeader(array('Content-Range' => 'bytes '.$contentStart - $contentEnd / $fileSize));
		}
		else
		{
			HeaderHelper::setHeader("HTTP/$httpVersion 200 OK");
		}

		// Calculate new content length
		$length = $contentEnd - $contentStart + 1;

		if (!empty($options['cache']))
		{
			$cacheTime = 31536000; // 1 year
			HeaderHelper::setHeader(array('Expires' => gmdate('D, d M Y H:i:s', time() + $cacheTime).' GMT'));
			HeaderHelper::setHeader(array('Pragma' => 'cache'));
			HeaderHelper::setHeader(array('Cache-Control' => 'max-age='.$cacheTime));
			$modifiedTime = IOHelper::getLastTimeModified($path);
			HeaderHelper::setHeader(array('Last-Modified' => gmdate("D, d M Y H:i:s", $modifiedTime->getTimestamp()).' GMT'));
		}
		else
		{
			if (!$forceDownload)
			{
				HeaderHelper::setNoCache();
			}
			else
			{
				// Fixes a bug in IE 6, 7 and 8 when trying to force download a file over SSL:
				// https://stackoverflow.com/questions/1218925/php-script-to-download-file-not-working-in-ie
				HeaderHelper::setHeader(array(
					'Pragma' => '',
					'Cache-Control' => ''
				));
			}
		}

		if ($options['mimeType'] == 'application/x-javascript' || $options['mimeType'] == 'text/css')
		{
			HeaderHelper::setHeader(array('Vary' => 'Accept-Encoding'));
		}

		$content = mb_substr($content, $contentStart, $length, '8bit');

		if ($terminate)
		{
			// Clean up the application first because the file downloading could take long time which may cause timeout
			// of some resources (such as DB connection)
			ob_start();
			Craft::app()->end(0, false);
			ob_end_clean();

			echo $content;
			exit(0);
		}
		else
		{
			echo $content;
		}
	}

	/**
	 * Returns a cookie by its name.
	 *
	 * @param string $name The cookie name.
	 *
	 * @return HttpCookie|null The cookie, or `null` if it didn’t exist.
	 */
	public function getCookie($name)
	{
		if (isset($this->cookies[$name]))
		{
			return $this->cookies[$name];
		}
	}

	/**
	 * Returns the cookie collection. The result can be used like an associative array. Adding {@link HttpCookie} objects
	 * to the collection will send the cookies to the client; and removing the objects from the collection will delete
	 * those cookies on the client.
	 *
	 * @return CookieCollection The cookie collection.
	 */
	public function getCookies()
	{
		if ($this->_cookies !== null)
		{
			return $this->_cookies;
		}
		else
		{
			return $this->_cookies = new CookieCollection($this);
		}
	}

	/**
	 * Deletes a cookie by its name.
	 *
	 * @param $name The cookie name.
	 *
	 * @return null
	 */
	public function deleteCookie($name)
	{
		if (isset($this->cookies[$name]))
		{
			unset($this->cookies[$name]);
		}
	}

	/**
	 * Returns whether this is a GET request.
	 *
	 * @return bool Whether this is a GET request.
	 */
	public function getIsGetRequest()
	{
		return ($this->getRequestType() == 'GET');
	}

	// Rename getIsX() => isX() functions for consistency
	//  - We realize that these methods could be called as if they're properties (using CComponent's magic getter) but
	//    we're trying to resist the temptation of magic methods for the sake of code obviousness.

	/**
	 * Alias of {@link getIsSecureConnection()}.
	 *
	 * @return bool
	 */
	public function isSecureConnection()
	{
		return $this->getIsSecureConnection();
	}

	/**
	 * Alias of {@link getIsPostRequest()}.
	 *
	 * @return bool
	 */
	public function isPostRequest()
	{
		return $this->getIsPostRequest();
	}

	/**
	 * Alias of {@link getIsDeleteRequest()}.
	 *
	 * @return bool
	 */
	public function isDeleteRequest()
	{
		return $this->getIsDeleteRequest();
	}

	/**
	 * Alias of {@link getIsDeleteViaPostRequest()}.
	 *
	 * @return bool
	 */
	public function isDeleteViaPostRequest()
	{
		return $this->getIsDeleteViaPostRequest();
	}

	/**
	 * Alias of {@link getIsGetRequest()}.
	 */
	public function isGetRequest()
	{
		return $this->getIsGetRequest();
	}

	/**
	 * Alias of {@link getIsPutRequest()}.
	 *
	 * @return bool
	 */
	public function isPutRequest()
	{
		return $this->getIsPutRequest();
	}

	/**
	 * Alias of {@link getIsPutViaPostRequest()}.
	 *
	 * @return bool
	 */
	public function isPutViaPostRequest()
	{
		return $this->getIsPutViaPostRequest();
	}

	/**
	 * Alias of {@link getIsAjaxRequest()}.
	 *
	 * @return bool
	 */
	public function isAjaxRequest()
	{
		return $this->getIsAjaxRequest();
	}

	/**
	 * Alias of {@link getIsFlashRequest()}.
	 *
	 * @return bool
	 */
	public function isFlashRequest()
	{
		return $this->getIsFlashRequest();
	}

	/**
	 * Alias of {@link getIpAddress()}.
	 *
	 * @return string
	 */
	public function getUserHostAddress()
	{
		return $this->getIpAddress();
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
	public function getIpAddress()
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

	/**
	 * Decodes the path info.
	 *
	 * Replacement for Yii's {@link \CHttpRequest::decodePathInfo()}.
	 *
	 * @param string $pathInfo Encoded path info.
	 *
	 * @return string Decoded path info.
	 */
	public function decodePathInfo($pathInfo)
	{
		$pathInfo = urldecode($pathInfo);

		if (!StringHelper::isUTF8($pathInfo))
		{
			$pathInfo = StringHelper::convertToUTF8($pathInfo);
		}

		return IOHelper::normalizePathSeparators($pathInfo);
	}

	/**
	 * Returns the request’s query string, without the p= parameter.
	 *
	 * @return string The query string.
	 */
	public function getQueryStringWithoutPath()
	{
		$queryData = $this->getQuery();

		unset($queryData[craft()->urlManager->pathParam]);

		return http_build_query($queryData);
	}

	/**
	 * Returns the path Craft should use to route this request, including the [CP trigger](http://craftcms.com/docs/config-settings#cpTrigger) if it is in there.
	 *
	 * @return string The path.
	 */
	public function getNormalizedPath()
	{
		// Get the path
		if (craft()->config->usePathInfo())
		{
			$pathInfo = $this->getPathInfo();
			$path = $pathInfo ? $pathInfo : $this->_getQueryStringPath();
		}
		else
		{
			$queryString = $this->_getQueryStringPath();
			$path = $queryString ? $queryString : $this->getPathInfo();
		}

		// Sanitize
		return $this->decodePathInfo($path);
	}

	/**
	 * Attempts to closes the connection with the HTTP client, without ending PHP script execution.
	 *
	 * This method relies on [flush()](http://php.net/manual/en/function.flush.php), which may not actually work if
	 * mod_deflate or mod_gzip is installed, or if this is a Win32 server.
	 *
	 * @param string|null $content Any content that should be included in the response body.
	 *
	 * @see http://stackoverflow.com/a/141026
	 * @throws Exception An exception will be thrown if content has already been output.
	 * @return null
	 */
	public function close($content = '')
	{
		// Make sure nothing has been output yet
		if (headers_sent())
		{
			throw new Exception(Craft::t('HttpRequestService::close() cannot be called after content has been output.'));
		}

		// Prevent the script from ending when the browser closes the connection
		ignore_user_abort(true);

		// Prepend any current OB content
		while (ob_get_length() !== false)
		{
			// If ob_start() didn't have the PHP_OUTPUT_HANDLER_CLEANABLE flag, ob_get_clean() will cause a PHP notice
			// and return false.
			$obContent = @ob_get_clean();

			if ($obContent !== false)
			{
				$content = $obContent . $content;
			}
			else
			{
				break;
			}
		}

		// Send the content
		ob_start();
		echo $content;
		$size = ob_get_length();

		// Tell the browser to close the connection
		HeaderHelper::setHeader(array(
			'Connection'     => 'close',
			'Content-Length' => $size
		));

		// Output the content, flush it to the browser, and close out the session
		ob_end_flush();
		flush();

		// Close the session.
		craft()->session->close();

		// In case we're running on php-fpm (https://secure.php.net/manual/en/book.fpm.php)
		if (function_exists("fastcgi_finish_request"))
		{
			fastcgi_finish_request();
		}
	}

	/**
	 * Returns whether the client is running "Windows", "Mac", "Linux" or "Other", based on the
	 * browser's UserAgent string.
	 *
	 * @return string The OS the client is running.
	 */
	public function getClientOs()
	{
		$userAgent = $this->getUserAgent();

		if (preg_match('/Linux/', $userAgent))
		{
			return 'Linux';
		}
		elseif (preg_match('/Win/', $userAgent))
		{
			return 'Windows';
		}
		elseif (preg_match('/Mac/', $userAgent))
		{
			return 'Mac';
		}
		else
		{
			return 'Other';
		}
	}

	/**
	 * Performs the CSRF validation. This is the event handler responding to {@link CApplication::onBeginRequest}.
	 * The default implementation will compare the CSRF token obtained from session and from a POST field. If they
	 * are different, a CSRF attack is detected.
	 *
	 * @param Event $event event parameter
	 *
	 * @throws HttpException If the validation fails
	 */
	public function validateCsrfToken($event)
	{
		if ($this->getIsPostRequest() || $this->getIsPutRequest() || $this->getIsPatchRequest() || $this->getIsDeleteRequest())
		{
			$method = $this->getRequestType();

			switch($method)
			{
				case 'POST':
				{
					$tokenFromPost = $this->getPost($this->csrfTokenName);
					break;
				}

				case 'PUT':
				{
					$tokenFromPost = $this->getPut($this->csrfTokenName);
					break;
				}

				case 'PATCH':
				{
					$tokenFromPost = $this->getPatch($this->csrfTokenName);
					break;
				}

				case 'DELETE':
				{
					$tokenFromPost = $this->getDelete($this->csrfTokenName);
				}
			}

			$csrfCookie = $this->getCookies()->itemAt($this->csrfTokenName);

			if (!empty($tokenFromPost) && $csrfCookie && $csrfCookie->value)
			{
				// Must at least match the cookie so that tokens from previous sessions won't work
				if (\CPasswordHelper::same($csrfCookie->value, $tokenFromPost))
				{
					// TODO: Remove this nested condition after the next breakpoint and call csrfTokenValidForCurrentUser() directly.
					// Is this an update request?
					if ($this->isActionRequest() && isset($this->_actionSegments[0]) && $this->_actionSegments[0] == 'update')
					{
						return true;
					}
					else
					{
						$valid = $this->csrfTokenValidForCurrentUser($tokenFromPost);
					}
				}
				else
				{
					$valid = false;
				}
			}
			else
			{
				$valid = false;
			}

			if (!$valid)
			{
				throw new HttpException(400, Craft::t('The CSRF token could not be verified.'));
			}
		}
	}

	/**
	 * Gets the current CSRF token from the CSRF token cookie, (re)creating the cookie if it is missing or invalid.
	 *
	 * @return string
	 * @throws \CException
	 */
	public function getCsrfToken()
	{
		if ($this->_csrfToken === null)
		{
			$cookie = $this->getCookies()->itemAt($this->csrfTokenName);

			// Reset the CSRF token cookie if it's not set, or for another user.
			if (!$cookie || ($this->_csrfToken = $cookie->value) == null || !$this->csrfTokenValidForCurrentUser($cookie->value))
			{
				$cookie = $this->createCsrfCookie();
				$this->_csrfToken = $cookie->value;
				$this->getCookies()->add($cookie->name, $cookie);
			}
		}

		return $this->_csrfToken;
	}

	/**
	 *
	 *
	 * @throws \CException
	 */
	public function regenCsrfCookie()
	{
		$cookie = $this->createCsrfCookie();
		$this->_csrfToken = $cookie->value;
		$this->getCookies()->add($cookie->name, $cookie);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Creates a cookie with a randomly generated CSRF token. Initial values specified in {@link csrfCookie} will be
	 * applied to the generated cookie.
	 *
	 * @return HttpCookie The generated cookie
	 */
	protected function createCsrfCookie()
	{
		$cookie = $this->getCookies()->itemAt($this->csrfTokenName);

		if ($cookie)
		{
			// They have an existing CSRF cookie.
			$value = $cookie->value;

			// It's a CSRF cookie that came from an authenticated request.
			if (strpos($value, '|') !== false)
			{
				// Grab the existing nonce.
				$parts = explode('|', $value);
				$nonce = $parts[0];
			}
			else
			{
				// It's a CSRF cookie from an unauthenticated request.
				$nonce = $value;
			}
		}
		else
		{
			// No previous CSRF cookie, generate a new nonce.
			$nonce = craft()->security->generateRandomString(40);
		}

		// Authenticated users
		if (craft()->getComponent('userSession', false) && ($currentUser = craft()->userSession->getUser()))
		{
			// We mix the password into the token so that it will become invalid when the user changes their password.
			// The salt on the blowfish hash will be different even if they change their password to the same thing.
			// Normally using the session ID would be a better choice, but PHP's bananas session handling makes that difficult.
			$passwordHash = $currentUser->password;
			$userId = $currentUser->id;
			$hashable = implode('|', array($nonce, $userId, $passwordHash));
			$token = $nonce.'|'.craft()->security->computeHMAC($hashable);
		}
		else
		{
			// Unauthenticated users.
			$token = $nonce;
		}

		$cookie = new HttpCookie($this->csrfTokenName, $token);

		if (is_array($this->csrfCookie))
		{
			foreach ($this->csrfCookie as $name => $value)
			{
				$cookie->$name = $value;
			}
		}

		return $cookie;
	}

	/**
	 * Gets whether the CSRF token is valid for the current user or not
	 *
	 * @param $token
	 *
	 * @return bool
	 * @throws \CException
	 */
	protected function csrfTokenValidForCurrentUser($token)
	{
		$currentUser = false;

		if (craft()->isInstalled() && craft()->getComponent('userSession', false))
		{
			$currentUser = craft()->userSession->getUser();
		}

		if ($currentUser)
		{
			$splitToken = explode('|', $token, 2);

			if (count($splitToken) !== 2)
			{
				return false;
			}

			list($nonce, $hashFromToken) = $splitToken;

			// Check that this token is for the current user
			$passwordHash = $currentUser->password;
			$userId = $currentUser->id;
			$hashable = implode('|', array($nonce, $userId, $passwordHash));
			$expectedToken = $nonce.'|'.craft()->security->computeHMAC($hashable);

			return \CPasswordHelper::same($token, $expectedToken);
		}
		else
		{
			// If they're logged out, any token is fine
			return true;
		}
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
		$pathParam = craft()->urlManager->pathParam;
		return trim($this->getQuery($pathParam, ''), '/');
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

		// If there's a token in the query string, then that should take precedence over everything else
		if (!$this->getQuery(craft()->config->get('tokenParam')))
		{
			$firstSegment = $this->getSegment(1);

			// Is this a resource request?
			if ($firstSegment == craft()->config->getResourceTrigger())
			{
				$this->_isResourceRequest = true;
			}
			else
			{
				// Is this an action request?
				if ($this->_isCpRequest)
				{
					$loginPath       = craft()->config->getCpLoginPath();
					$logoutPath      = craft()->config->getCpLogoutPath();
					$setPasswordPath = craft()->config->getCpSetPasswordPath();
				}
				else
				{
					$loginPath       = trim(craft()->config->getLocalized('loginPath'), '/');
					$logoutPath      = trim(craft()->config->getLocalized('logoutPath'), '/');
					$setPasswordPath = trim(craft()->config->getLocalized('setPasswordPath'), '/');
				}

				$verifyEmailPath = 'verifyemail';

				if (
					($triggerMatch = ($firstSegment == craft()->config->get('actionTrigger') && count($this->_segments) > 1)) ||
					($actionParam = $this->getParam('action')) !== null ||
					($specialPath = in_array($this->_path, array($loginPath, $logoutPath, $setPasswordPath, $verifyEmailPath)))
				)
				{
					$this->_isActionRequest = true;

					if ($triggerMatch)
					{
						$this->_actionSegments = array_slice($this->_segments, 1);
					}
					else if ($actionParam)
					{
						$actionParam = $this->decodePathInfo($actionParam);
						$this->_actionSegments = array_filter(explode('/', $actionParam));
					}
					else
					{
						if ($this->_path == $loginPath)
						{
							$this->_actionSegments = array('users', 'login');
						}
						else if ($this->_path == $logoutPath)
						{
							$this->_actionSegments = array('users', 'logout');
						}
						else if ($this->_path == $verifyEmailPath)
						{
							$this->_actionSegments = array('users', 'verifyemail');
						}
						else
						{
							$this->_actionSegments = array('users', 'setpassword');
						}
					}
				}
			}
		}

		$this->_checkedRequestType = true;
	}

	/**
	 * Returns a param value from GET or POST data.
	 *
	 * @param string|null $name
	 * @param mixed       $defaultValue
	 * @param array       $data
	 *
	 * @return mixed
	 */
	private function _getParam($name, $defaultValue, $data)
	{
		// Do they just want the whole array?
		if (!$name)
		{
			return $this->_utf8AllTheThings($data);
		}

		// Looking for a specific value?
		if (isset($data[$name]))
		{
			return $this->_utf8AllTheThings($data[$name]);
		}

		// Maybe they're looking for a nested param?
		if (strpos($name, '.') !== false)
		{
			$path = explode('.', $name);
			$param = $data;

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
					$things[$key] = StringHelper::convertToUTF8($value);
				}
			}
		}
		else
		{
			$things = StringHelper::convertToUTF8($things);
		}

		return $things;
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
