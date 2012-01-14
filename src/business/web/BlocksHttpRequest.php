<?php

/**
 *
 */
class BlocksHttpRequest extends CHttpRequest
{
	private $_urlFormat;
	private $_path;
	private $_pathSegments;
	private $_pathExtension;

	public function getPath()
	{
		if (!isset($this->_path))
		{
			if ($this->urlFormat == UrlFormat::PathInfo)
			{
				$this->_path = $this->pathInfo;
			}
			else
			{
				$pathVar = Blocks::app()->config('pathVar');
				$this->_path = $this->getParam($pathVar, '');

				// trim trailing/leading slashes
				$this->_path = trim($this->_path, '/');
			}
		}

		return $this->_path;
	}

	/**
	 * @return mixed
	 */
	public function getPathSegments()
	{
		if (!isset($this->_pathSegments))
		{
			$this->_pathSegments = array_filter(explode('/', $this->path));
		}

		return $this->_pathSegments;
	}

	/**
	 * @return mixed
	 */
	public function getPathExtension()
	{
		if (!isset($this->_pathExtension))
		{
			$ext = pathinfo($this->path, PATHINFO_EXTENSION);
			$this->_pathExtension = strtolower($ext);
		}

		return $this->_pathExtension;
	}

	/**
	 * 
	 * @return Returns which URL format we're using (PATH_INFO or the query string)
	 */
	public function getUrlFormat()
	{
		if (!isset($this->_urlFormat))
		{
			// If config[urlFormat] is set to either PathInfo or QueryString, take their word for it.
			if (Blocks::app()->config('urlFormat') == UrlFormat::PathInfo)
			{
				$this->_urlFormat = UrlFormat::PathInfo;
			}
			else if (Blocks::app()->config('urlFormat') == UrlFormat::QueryString)
			{
				$this->_urlFormat = UrlFormat::QueryString;
			}
			// Check if it's cached
			else if (($cachedUrlFormat = Blocks::app()->fileCache->get('urlFormat')) !== false)
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
				// If there is already a routeVar=value in the current request URL, we're going to assume it's a QueryString request
				else if ($this->getParam(Blocks::app()->config('pathVar'), null) !== null)
				{
					$this->_urlFormat = UrlFormat::QueryString;
				}
				else
				{
					$this->_urlFormat = UrlFormat::QueryString;

					// Last ditch, let's try to determine if PATH_INFO is enabled on the server.
					try
					{
						$context = stream_context_create(array('http' => array('header' => 'Connection: close')));
						if (($result = @file_get_contents(Blocks::app()->request->hostInfo.'/blocks/app/business/web/PathInfoCheck.php/test', 0, $context)) !== false)
						{
							if ($result === '/test' )
							{
								$this->_urlFormat = UrlFormat::PathInfo;
							}
						}
					}
					catch (Exception $e)
					{
						Blocks::log('Unable to determine if server PATH_INFO is enabled: '.$e->getMessage());
					}
				}

				// cache it and set it to expire according to config
				Blocks::app()->fileCache->set('urlFormat', $this->_urlFormat, Blocks::app()->config('cacheTimeSeconds'));
			}
		}

		return $this->_urlFormat;
	}
}
