<?php
namespace Blocks;

/**
 *
 */
class HttpRequest extends \CHttpRequest
{
	private $_actionPath;
	private $_urlFormat;
	private $_path;
	private $_queryStringPath;
	private $_pathSegments;
	private $_pathExtension;
	private $_mode;
	private $_isMobileBrowser;
	private $_mimeType;
	private $_browserLanguages;

	/**
	 * @return mixed
	 */
	public function getPath()
	{
		if (!isset($this->_path))
		{
			// urlFormat determines where to look for a path first
			if ($this->getUrlFormat() == UrlFormat::PathInfo)
				$this->_path = $this->getPathInfo() ? $this->getPathInfo() : $this->getQueryStringPath();
			else
				$this->_path = $this->getQueryStringPath() ? $this->getQueryStringPath() : $this->getPathInfo();
		}

		return $this->_path;
	}

	/**
	 * @param $path
	 * @return void
	 */
	public function setPath($path)
	{
		$this->_path = $path;
	}

	/**
	 * @param $mimeType
	 * @return void
	 */
	public function setMimeType($mimeType)
	{
		$this->_mimeType = $mimeType;
	}

	/**
	 * @return mixed
	 */
	public function getMimeType()
	{
		if (!$this->_mimeType)
		{
			$extension = FileHelper::getExtension($this->getPath(), 'html');
			$this->_mimeType = FileHelper::getMimeTypeByExtension('.'.$extension);
		}

		return $this->_mimeType;
	}

	/**
	 * @return mixed
	 */
	public function getActionPath()
	{
		if (!$this->_actionPath)
		{
			// Ugly.
			$this->getMode();
		}

		return $this->_actionPath;
	}

	/**
	 * @return mixed
	 */
	public function getQueryStringPath()
	{
		if (!isset($this->_queryStringPath))
		{
			$pathVar = blx()->urlManager->routeVar;
			$this->_queryStringPath = trim($this->getQuery($pathVar, ''), '/');
		}

		return $this->_queryStringPath;
	}

	/**
	 * @param $path
	 */
	public function setQueryStringPath($path)
	{
		$this->_queryStringPath = $path;
	}

	/**
	 * @return mixed
	 */
	public function getPathSegments()
	{
		if (!isset($this->_pathSegments))
		{
			$this->_pathSegments = array_filter(explode('/', $this->getPath()));
		}

		return $this->_pathSegments;
	}

	/**
	 * Returns a specific path segment
	 *
	 * @param      $num
	 * @param null $default
	 * @return mixed The requested path segment, or null
	 */
	public function getPathSegment($num, $default = null)
	{
		$pathSegments = $this->getPathSegments();
		if (isset($pathSegments[$num - 1]))
			return $pathSegments[$num - 1];

		return $default;
	}

	/**
	 * @return mixed
	 */
	public function getPathExtension()
	{
		if (!isset($this->_pathExtension))
		{
			$ext = pathinfo($this->getPath(), PATHINFO_EXTENSION);
			$this->_pathExtension = strtolower($ext);
		}

		return $this->_pathExtension;
	}

	/**
	 * @return Returns which URL format we're using (PATH_INFO or the query string)
	 */
	public function getUrlFormat()
	{
		if (!isset($this->_urlFormat))
		{
			// If config[urlFormat] is set to either PathInfo or QueryString, take their word for it.
			if (blx()->config->urlFormat == UrlFormat::PathInfo)
			{
				$this->_urlFormat = UrlFormat::PathInfo;
			}
			else if (blx()->config->urlFormat == UrlFormat::QueryString)
			{
				$this->_urlFormat = UrlFormat::QueryString;
			}
			// Check if it's cached
			else if (($cachedUrlFormat = blx()->fileCache->get('urlFormat')) !== false)
			{
				$this->_urlFormat = $cachedUrlFormat;
			}
			else
			{
				// If there is already a PATH_INFO var available, we know it supports it.
				if (isset($_SERVER['PATH_INFO']))
				{
					$this->_urlFormat = UrlFormat::PathInfo;
				}
				else
				{
					$this->_urlFormat = UrlFormat::QueryString;

					// Last ditch, let's try to determine if PATH_INFO is enabled on the server.
					try
					{
						$context = stream_context_create(array('http' => array('header' => 'Connection: close')));
						$url = blx()->request->getHostInfo().blx()->request->getScriptUrl().'/testpathinfo';
						if (($result = @file_get_contents($url, 0, $context)) !== false)
						{
							if ($result === 'success')
							{
								$this->_urlFormat = UrlFormat::PathInfo;
							}
						}
					}
					catch (\Exception $e)
					{
						Blocks::log('Unable to determine if server PATH_INFO is enabled: '.$e->getMessage());
					}
				}

				// cache it and set it to expire according to config
				blx()->fileCache->set('urlFormat', $this->_urlFormat, blx()->config->cacheTimeSeconds);
			}
		}

		return $this->_urlFormat;
	}

	/**
	 * @param $urlFormat
	 */
	public function setUrlFormat($urlFormat)
	{
		$this->_urlFormat = $urlFormat;
	}

	/**
	 * @return string The app mode (Action, Resource, CP, or Site)
	 */
	public function getMode()
	{
		if (!isset($this->_mode))
		{
			$resourceTriggerWord = blx()->config->resourceTriggerWord;
			$actionTriggerWord = blx()->config->actionTriggerWord;
			$logoutTriggerWord = blx()->config->logoutTriggerWord;

			$firstPathSegment = $this->getPathSegment(1);

			// If the first path segment is the resource trigger word, it's a resource request.
			if ($firstPathSegment === $resourceTriggerWord)
				$this->_mode = RequestMode::Resource;

			// If the first path segment is the action trigger word, or the logout trigger word (special case), it's an action request.
			else if ($firstPathSegment === $actionTriggerWord || $firstPathSegment === $logoutTriggerWord)
			{
				$this->_mode = RequestMode::Action;

				// If it's an action request, we set the actionPath for the given request.

				// Special case for logging out.
				if ($firstPathSegment === $logoutTriggerWord)
				{
					$segs[0] = 'session';
					$segs[1] = 'logout';
				}
				else
				{
					$segs = array_slice(array_merge($this->getPathSegments()), 1);
				}

				$this->_actionPath = $segs;
			}
			// Check post for action request.  If so, it's an action request and set action path.
			else if ((($action = $this->getParam($actionTriggerWord)) !== null) && ($segs = array_filter(explode('/', $action))))
			{
				$this->_mode = RequestMode::Action;
				$this->_actionPath = $segs;
			}

			// If we made it here and BLOCKS_CP_REQUEST is set, it's a CP request.
			else if (BLOCKS_CP_REQUEST === true)
				$this->_mode = RequestMode::CP;

			// If we made it here, it's a front-end site request.
			else
				$this->_mode = RequestMode::Site;
		}

		return $this->_mode;
	}

	/**
	 * @param $mode
	 * @return void
	 */
	public function setMode($mode)
	{
		$this->_mode = $mode;
	}

	/**
	 * Returns the named GET or POST parameter value, or throws an exception if it's not set
	 *
	 * @param $name
	 * @throws Exception
	 * @return mixed
	 */
	public function getRequiredParam($name)
	{
		$value = $this->getParam($name);

		if ($value === null)
			throw new Exception(Blocks::t('Param “{name}” doesn’t exist.', array('name' => $name)));

		return $value;
	}

	/**
	 * Returns the named GET parameter value, or throws an exception if it's not set
	 *
	 * @param $name
	 * @throws Exception
	 * @return mixed
	 */
	public function getRequiredQuery($name)
	{
		$value = $this->getQuery($name);

		if ($value === null)
			throw new Exception(Blocks::t('GET param “{name}” doesn’t exist.', array('name' => $name)));

		return $value;
	}

	/**
	 * Returns the named GET or POST parameter value, or throws an exception if it's not set
	 *
	 * @param $name
	 * @throws Exception
	 * @return mixed
	 */
	public function getRequiredPost($name)
	{
		$value = $this->getPost($name);

		if ($value === null)
			throw new Exception(Blocks::t('POST param “{name}” doesn’t exist.', array('name' => $name)));

		return $value;
	}

	/**
	 * Returns whether the request is coming from a mobile browser
	 * Detection script courtesy of http://detectmobilebrowsers.com
	 *
	 * @return bool Whether the request is coming from a mobile browser
	 */
	public function isMobileBrowser()
	{
		if (!isset($this->_isMobileBrowser))
		{
			$useragent = $_SERVER['HTTP_USER_AGENT'];

			if (preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
				$this->_isMobileBrowser = true;
			else
				$this->_isMobileBrowser = false;
		}

		return $this->_isMobileBrowser;
	}

	/**
	 * If the request is not on a SSL connection, redirect to the https version of the page.
	 */
	public function requireSecureConnection()
	{
		if (!$this->getIsSecureConnection())
		{
			// Redirect to the secure version of the page.
			$url = 'https://'.$this->getServerName().$this->getRequestUri();
			$this->redirect($url);
		}
	}

	/**
	 * Returns the user preferred languages sorted by preference.
	 * The returned language IDs will be canonicalized using {@link Locale::getCanonicalID}.
	 * This method returns false if the user does not have language preferences.
	 *
	 * @return array the user preferred languages.
	 */
	public function getBrowserLanguages()
	{
		if ($this->_browserLanguages === null)
		{
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ($n = preg_match_all('/([\w\-_]+)\s*(;\s*q\s*=\s*(\d*\.\d*))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) > 0)
			{
				$languages = array();

				for ($i = 0; $i < $n; ++$i)
					$languages[$matches[1][$i]] = empty($matches[3][$i]) ? 1.0 : floatval($matches[3][$i]);

				// Sort by it's weight.
				arsort($languages);

				foreach ($languages as $language => $pref)
					$this->_browserLanguages[] = Locale::getCanonicalID($language);
			}

			if ($this->_browserLanguages === null)
				return false;
		}

		return $this->_browserLanguages;
	}
}
