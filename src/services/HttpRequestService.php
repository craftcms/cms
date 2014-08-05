<?php
namespace Craft;

/**
 * Class HttpRequestService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class HttpRequestService extends \CHttpRequest
{
	////////////////////
	// PROPERTIES
	////////////////////

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

	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Init
	 *
	 * @return null
	 */
	public function init()
	{
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
		$this->_segments = array_filter(explode('/', $path));

		// Is this a CP request?
		$this->_isCpRequest = ($this->getSegment(1) == craft()->config->get('cpTrigger'));

		if ($this->_isCpRequest)
		{
			// Chop the CP trigger segment off of the path & segments array
			array_shift($this->_segments);
		}

		// Is this a paginated request?
		if ($this->_segments)
		{
			// Match against the entire path string as opposed to just the last segment
			// so that we can support "/page/2"-style pagination URLs
			$path = implode('/', $this->_segments);
			$pageTrigger = str_replace('/', '\/', craft()->config->get('pageTrigger'));

			if (preg_match("/(.*)\b{$pageTrigger}(\d+)$/", $path, $match))
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
	 * Returns the script name used to access Craft.
	 *
	 * @return string
	 */
	public function getScriptName()
	{
		$scriptUrl = $this->getScriptUrl();
		return mb_substr($scriptUrl, mb_strrpos($scriptUrl, '/')+1);
	}

	/**
	 * Returns the request's path, without the CP trigger segment if there is one.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Returns an array of the path segments, without the CP trigger segment if there is one.
	 *
	 * @return array
	 */
	public function getSegments()
	{
		return $this->_segments;
	}

	/**
	 * Returns a specific URI segment, or null if the segment doesn't exist.
	 *
	 * @param int $num
	 *
	 * @return string|null
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
	 * Returns the page number if this is a paginated request.
	 *
	 * @return int
	 */
	public function getPageNum()
	{
		return $this->_pageNum;
	}

	/**
	 * Returns the request's token, if there is one.
	 *
	 * @return string|null
	 */
	public function getToken()
	{
		return $this->getQuery(craft()->config->get('tokenParam'));
	}

	/**
	 * Returns whether this is a CP request.
	 *
	 * @return bool
	 */
	public function isCpRequest()
	{
		return $this->_isCpRequest;
	}

	/**
	 * Returns whether this is a site request.
	 *
	 * @return bool
	 */
	public function isSiteRequest()
	{
		return !$this->_isCpRequest;
	}

	/**
	 * Returns whether this is a resource request.
	 *
	 * @return bool
	 */
	public function isResourceRequest()
	{
		$this->_checkRequestType();
		return $this->_isResourceRequest;
	}

	/**
	 * Returns whether this is an action request.
	 *
	 * @return bool
	 */
	public function isActionRequest()
	{
		$this->_checkRequestType();
		return $this->_isActionRequest;
	}

	/**
	 * Returns an array of the action path segments for action requests.
	 *
	 * @return array|null
	 */
	public function getActionSegments()
	{
		$this->_checkRequestType();
		return $this->_actionSegments;
	}

	/**
	 * Returns whether this is a Live Preview request.
	 *
	 * @return bool
	 */
	public function isLivePreview()
	{
		return ($this->isSiteRequest() &&
			($actionSegments = $this->getActionSegments()) &&
			count($actionSegments) == 2 &&
			$actionSegments[0] == 'entries' &&
			$actionSegments[1] == 'previewEntry'
		);
	}

	/**
	 * @return mixed
	 */
	public function getMimeType()
	{
		if (!$this->_mimeType)
		{
			$extension = IOHelper::getExtension($this->getPath(), 'html');
			$this->_mimeType = IOHelper::getMimeTypeByExtension('.'.$extension);
		}

		return $this->_mimeType;
	}

	/**
	 * Returns the named GET parameter value, or the entire GET array if no name is specified.
	 * If $name is specified and the GET parameter does not exist, $defaultValue will be returned.
	 * $name can also represent a nested param using dot syntax, e.g. getQuery('fields.body')
	 *
	 * @param string|null $name
	 * @param string|null $defaultValue
	 *
	 * @return mixed
	 */
	public function getQuery($name = null, $defaultValue = null)
	{
		return $this->_getParam($name, $defaultValue, $_GET);
	}

	/**
	 * Returns the named GET parameter value, or throws an exception if it's not set
	 *
	 * @param string $name
	 *
	 * @throws HttpException
	 * @return mixed
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
	 * Returns the named POST parameter value, or the entire POST array if no name is specified.
	 * If $name is specified and the POST parameter does not exist, $defaultValue will be returned.
	 * $name can also represent a nested param using dot syntax, e.g. getPost('fields.body')
	 *
	 * @param string|null $name
	 * @param string|null $defaultValue
	 *
	 * @return mixed
	 */
	public function getPost($name = null, $defaultValue = null)
	{
		return $this->_getParam($name, $defaultValue, $_POST);
	}

	/**
	 * Returns the named GET or POST parameter value, or throws an exception if it's not set
	 *
	 * @param string $name
	 *
	 * @throws HttpException
	 * @return mixed
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
	 * Checks for a value in GET and POST.
	 *
	 * @param string $name
	 * @param null   $defaultValue
	 *
	 * @return mixed|null
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
	 * Returns the named GET or POST parameter value, or throws an exception if it's not set
	 *
	 * @param string $name
	 *
	 * @throws HttpException
	 * @return mixed
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
	 * Returns whether the request is coming from a mobile browser. Detection script courtesy of http://detectmobilebrowsers.com
	 *
	 * Last updated: 2013-02-04
	 *
	 * @param bool $detectTablets
	 *
	 * @return bool
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
						'/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino'.($detectTablets ? '|android|ipad|playbook|silk' : '').'/i',$this->userAgent
					) ||
					preg_match(
						'/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', mb_substr($this->userAgent, 0, 4))
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
	 * Returns the user preferred languages sorted by preference. The returned language IDs will be canonicalized using
	 * {@link LocaleData::getCanonicalID}. This method returns false if the user does not have language preferences.
	 *
	 * @return array The user preferred languages.
	 */
	public function getBrowserLanguages()
	{
		if ($this->_browserLanguages === null)
		{
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ($n = preg_match_all('/([\w\-_]+)\s*(;\s*q\s*=\s*(\d*\.\d*))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) > 0)
			{
				$languages = array();

				for ($i = 0; $i < $n; ++$i)
				{
					$languages[$matches[1][$i]] = empty($matches[3][$i]) ? 1.0 : floatval($matches[3][$i]);
				}

				// Sort by it's weight.
				arsort($languages);

				foreach ($languages as $language => $pref)
				{
					$this->_browserLanguages[] = LocaleData::getCanonicalID($language);
				}
			}

			if ($this->_browserLanguages === null)
			{
				return false;
			}
		}

		return $this->_browserLanguages;
	}

	/**
	 * Returns the host name, without http(s)://
	 *
	 * @return string
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
	 * We're overriding this from \CHttpRequest so we can have more control over the headers.
	 *
	 * @param string     $path
	 * @param string     $content
	 * @param array|null $options
	 * @param bool|null  $terminate
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function sendFile($path, $content, $options = array(), $terminate = true)
	{
		$fileName = IOHelper::getFileName($path, true);

		// Clear the output buffer to prevent corrupt downloads.
		// Need to check the OB status first, or else some PHP versions will throw an E_NOTICE since we have a custom error handler
		// (http://pear.php.net/bugs/bug.php?id=9670)
		if (ob_get_length() !== false)
		{
			ob_clean();
		}

		// Default to disposition to 'download'
		$forceDownload = !isset($options['forceDownload']) || $options['forceDownload'];

		if ($forceDownload)
		{
			HeaderHelper::setDownload($fileName);
		}

		if (empty($options['mimeType']))
		{
			if (($options['mimeType'] = \CFileHelper::getMimeTypeByExtension($fileName)) === null)
			{
				$options['mimeType'] = 'text/plain';
			}
		}

		HeaderHelper::setHeader(array('Content-Type' => $options['mimeType'].'; charset=utf-8'));

		$fileSize = mb_strlen($content, '8bit');
		$contentStart = 0;
		$contentEnd = $fileSize - 1;

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

			/* Check the range and make sure it's treated according to the specs.
			 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
			 */
			// End bytes can not be larger than $end.
			$contentEnd = ($contentEnd > $fileSize) ? $fileSize - 1 : $contentEnd;

			// Validate the requested range and return an error if it's not correct.
			$wrongContentStart = ($contentStart > $contentEnd || $contentStart > $fileSize - 1 || $contentStart < 0);

			if ($wrongContentStart)
			{
				HeaderHelper::setHeader(array('Content-Range' => 'bytes '.$contentStart - $contentEnd / $fileSize));
				throw new HttpException(416, 'Requested Range Not Satisfiable');
			}

			HeaderHelper::setHeader('HTTP/1.1 206 Partial Content');
			HeaderHelper::setHeader(array('Content-Range' => 'bytes '.$contentStart - $contentEnd / $fileSize));
		}
		else
		{
			HeaderHelper::setHeader('HTTP/1.1 200 OK');
		}

		$length = $contentEnd - $contentStart + 1; // Calculate new content length

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

		if (!ob_get_length())
		{
			HeaderHelper::setLength($length);
		}

		if ($options['mimeType'] == 'application/x-javascript' || $options['mimeType'] == 'text/css')
		{
			HeaderHelper::setHeader(array('Vary' => 'Accept-Encoding'));
		}

		$content = mb_substr($content, $contentStart, $length);

		if ($terminate)
		{
			// clean up the application first because the file downloading could take long time
			// which may cause timeout of some resources (such as DB connection)
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
	 * Returns a cookie, if it's set.
	 *
	 * @param string $name
	 *
	 * @return \CHttpCookie|null
	 */
	public function getCookie($name)
	{
		if (isset($this->cookies[$name]))
		{
			return $this->cookies[$name];
		}
	}

	/**
	 * Deletes a cookie.
	 *
	 * @param $name
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

	// Rename getIsX() => isX() functions for consistency
	//  - We realize that these methods could be called as if they're properties (using CComponent's magic getter)
	//    but we're trying to resist the temptation of magic methods for the sake of code obviousness.

	/**
	 * @return bool
	 */
	public function isSecureConnection()
	{
		return $this->getIsSecureConnection();
	}

	/**
	 * @return bool
	 */
	public function isPostRequest()
	{
		return $this->getIsPostRequest();
	}

	/**
	 * @return bool
	 */
	public function isDeleteRequest()
	{
		return $this->getIsDeleteRequest();
	}

	/**
	 * @return bool
	 */
	public function isDeleteViaPostRequest()
	{
		return $this->getIsDeleteViaPostRequest();
	}

	/**
	 * @return bool
	 */
	public function isPutRequest()
	{
		return $this->getIsPutRequest();
	}

	/**
	 * @return bool
	 */
	public function isPutViaPostRequest()
	{
		return $this->getIsPutViaPostRequest();
	}

	/**
	 * @return bool
	 */
	public function isAjaxRequest()
	{
		return $this->getIsAjaxRequest();
	}

	/**
	 * @return bool
	 */
	public function isFlashRequest()
	{
		return $this->getIsFlashRequest();
	}

	/**
	 * @return string
	 */
	public function getUserHostAddress()
	{
		return $this->getIpAddress();
	}

	/**
	 * Retrieves the best guess of the client's actual IP address taking into account numerous HTTP proxy headers due
	 * to variations in how different ISPs handle IP addresses in headers between hops.
	 *
	 * Considering any of these server vars besides REMOTE_ADDR can be spoofed, this method should not be used when you
	 * need a trusted source of information for you IP address... use $_SERVER['REMOTE_ADDR'] instead.
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
	 * Wrapper for Yii's decodePathInfo, plus we clean up path separators.
	 *
	 * @param string $pathInfo
	 *
	 * @return string
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
	 * Returns the part of the querystring minus any p= parameter regardless of whether PATH_INFO is enabled or not.
	 *
	 * @return string
	 */
	public function getQueryStringWithoutPath()
	{
		// Get the full querystring.
		$queryString = $this->getQueryString();

		$parts = explode('&', $queryString);

		if (count($parts) == 1)
		{
			return '';
		}

		foreach ($parts as $key => $part)
		{
			if (mb_strpos($part, 'p=') !== false)
			{
				unset($parts[$key]);
				break;
			}
		}

		return implode('&', $parts);
	}

	/**
	 * @return string
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
	 * Ends the current HTTP request, without ending script execution.
	 *
	 * @param string|null $content
	 *
	 * @see http://stackoverflow.com/a/141026
	 * @return null
	 */
	public function close($content = '')
	{
		// Prevent the script from ending when the browser closes the connection
		ignore_user_abort(true);

		// Discard any current OB content
		if (ob_get_length() !== false)
		{
			ob_end_clean();
		}

		// Send the content
		ob_start();
		echo $content;
		$size = ob_get_length();

		// Tell the browser to close the connection
		header('Connection: close');
		header('Content-Length: '.$size);

		// Output the content, flush it to the browser, and close out the session
		ob_end_flush();
		flush();
		session_write_close();
	}

	////////////////////
	// PRIVATE METHODS
	////////////////////

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

		$resourceTrigger = craft()->config->getResourceTrigger();
		$actionTrigger = craft()->config->get('actionTrigger');
		$frontEndLoginPath = trim(craft()->config->getLocalized('loginPath'), '/');
		$frontEndLogoutPath = trim(craft()->config->getLocalized('logoutPath'), '/');
		$frontEndSetPasswordPath = trim(craft()->config->getLocalized('setPasswordPath'), '/');
		$cpLoginPath = craft()->config->getCpLoginPath();
		$cpLogoutPath = craft()->config->getCpLogoutPath();
		$cpSetPasswordPath = craft()->config->getCpSetPasswordPath();

		$firstSegment = $this->getSegment(1);

		// If there's a token in the query string, then that should take precedence over everything else
		if (!$this->getQuery(craft()->config->get('tokenParam')))
		{
			// If the first path segment is the resource trigger word, it's a resource request.
			if ($firstSegment === $resourceTrigger)
			{
				$this->_isResourceRequest = true;
			}

			// If the first path segment is the action trigger word, or the logout trigger word (special case), it's an action request
			else if ($firstSegment === $actionTrigger || (in_array($this->_path, array($frontEndLoginPath, $cpLoginPath, $frontEndSetPasswordPath, $cpSetPasswordPath, $frontEndLogoutPath, $cpLogoutPath)) && !$this->getParam('action')))
			{
				$this->_isActionRequest = true;

				if (in_array($this->_path, array($cpLoginPath, $frontEndLoginPath)))
				{
					$this->_actionSegments = array('users', 'login');
				}
				else if (in_array($this->_path, array($frontEndSetPasswordPath, $cpSetPasswordPath)))
				{
					$this->_actionSegments = array('users', 'setpassword');
				}
				else if (in_array($this->_path, array($frontEndLogoutPath, $cpLogoutPath)))
				{
					$this->_actionSegments = array('users', 'logout');
				}
				else
				{
					$this->_actionSegments = array_slice($this->_segments, 1);
				}
			}

			// If there's a non-empty 'action' param (either in the query string or post data), it's an action request
			else if (($action = $this->getParam('action')) !== null)
			{
				$this->_isActionRequest = true;

				// Sanitize
				$action = $this->decodePathInfo($action);
				$this->_actionSegments = array_filter(explode('/', $action));
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
			$path = array_filter(explode('.', $name));
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
