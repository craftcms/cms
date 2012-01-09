<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_pathSegments;
	private $_extension;
	private $_isServerPathInfoRequest = null;

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
		if ($this->_isServerPathInfoRequest == null)
		{
			// Check if it's cache first.
			$this->_isServerPathInfoRequest = Blocks::app()->fileCache->get('pathInfoRequestStatus');

			// No instance variable and not in cache, so figure it out.
			if ($this->_isServerPathInfoRequest === false)
			{
				$this->_isServerPathInfoRequest = 0;

				// if there is already a routeVar=value in the current request, we're going to assume it's a QueryString request
				if ($this->getParam(Blocks::app()->config('pathVar'), null) !== null)
				{
					$this->_isServerPathInfoRequest = 0;
				}
				else
				{
					// if UrlFormat is set to PathInfo, then we assume the user knows that PATH_INFO is enabled on their server.
					if (Blocks::app()->config('urlFormat') == UrlFormat::PathInfo)
					{
						$this->_isServerPathInfoRequest = 1;
					}
					else
					{
						// if UrlFormat is set to QueryString, then we assume the user knows that PATH_INFO is not enabled on their server.
						if (Blocks::app()->config('urlFormat') == UrlFormat::QueryString)
						{
							$this->_isServerPathInfoRequest = 0;
						}
						else
						{
							// Last ditch, let's try to determine if PATH_INFO is enabled on the server.
							try
							{
								$context = stream_context_create(array('http' => array('header' => 'Connection: close')));
								if (($result = @file_get_contents(Blocks::app()->request->hostInfo.'/blocks/app/business/web/PathInfoCheck.php/test', 0, $context)) !== false)
								{
									if ($result === '/test' )
									{
										$this->_isServerPathInfoRequest = 1;
									}
								}
							}
							catch (Exception $e)
							{
								Blocks::log('Unable to determine if server PATH_INFO is enabled: '.$e->getMessage());
							}
						}
					}
				}

				// cache it and set it to expire according to config
				Blocks::app()->fileCache->set('pathInfoRequestStatus', $this->_isServerPathInfoRequest, Blocks::app()->config('cacheTimeSeconds'));
			}
		}

			return $this->_isServerPathInfoRequest == 0 ? false : true;
		}
}
