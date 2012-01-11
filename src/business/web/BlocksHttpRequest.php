<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_pathSegments;
	private $_extension;
	private $_isServerPathInfoRequest;

	public function getPathSegments()
	{
		if (!isset($this->_pathSegments))
		{
			$this->_pathSegments = array_merge(array_filter(explode('/', $this->getPathInfo())));
		}

		return $this->_pathSegments;
	}

	public function getPathExtension()
	{
		if (!isset($this->_extension))
		{
			$ext = pathinfo($this->getPathInfo(), PATHINFO_EXTENSION);
			$this->_extension = strtolower($ext);
		}

		return $this->_extension;
	}

	/**
	 * @return Returns whether the $_SERVER["PATH_INFO"] variable is set or not.
	 */
	public function getIsServerPathInfoRequest()
	{
		// Check if the instance variable has been set.
		if (isset($this->_isServerPathInfoRequest))
			return $this->_isServerPathInfoRequest;

		// If config[urlFormat] is set to either PathInfo or QueryString, take their word for it.
		if (Blocks::app()->config('urlFormat') == UrlFormat::PathInfo)
		{
			$this->_isServerPathInfoRequest = true;
		}
		else if (Blocks::app()->config('urlFormat') == UrlFormat::QueryString)
		{
			$this->_isServerPathInfoRequest = false;
		}
		// Check if it's cached
		else if (($cachedValue = Blocks::app()->fileCache->get('pathInfoRequestStatus')) !== false)
		{
			$this->_isServerPathInfoRequest = (bool)$cachedValue;
		}
		else
		{
			// If there is already a PATH_INFO var available, we know it supports it.
			if (isset($_SERVER['PATH_INFO']))
			{
				$this->_isServerPathInfoRequest = true;
			}
			// If there is already a routeVar=value in the current request URL, we're going to assume it's a QueryString request
			else if ($this->getParam(Blocks::app()->config('pathVar'), null) !== null)
			{
				$this->_isServerPathInfoRequest = false;
			}
			else
			{
				$this->_isServerPathInfoRequest = false;

				// Last ditch, let's try to determine if PATH_INFO is enabled on the server.
				try
				{
					$context = stream_context_create(array('http' => array('header' => 'Connection: close')));
					if (($result = @file_get_contents(Blocks::app()->request->hostInfo.'/blocks/app/business/web/PathInfoCheck.php/test', 0, $context)) !== false)
					{
						if ($result === '/test' )
						{
							$this->_isServerPathInfoRequest = true;
						}
					}
				}
				catch (Exception $e)
				{
					Blocks::log('Unable to determine if server PATH_INFO is enabled: '.$e->getMessage());
				}
			}

			// cache it and set it to expire according to config
			$cachedValue = $this->_isServerPathInfoRequest ? 1 : 0;
			Blocks::app()->fileCache->set('pathInfoRequestStatus', $cachedValue, Blocks::app()->config('cacheTimeSeconds'));
		}

		return $this->_isServerPathInfoRequest;
	}
}
