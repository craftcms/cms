<?php
namespace Blocks;

/**
 *
 */
class UrlManager extends \CUrlManager
{
	private $_templateVariables = array();

	public $cpRoutes;
	public $pathParam;

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		// set this to false so extra query string parameters don't get the path treatment
		$this->appendParams = false;

		// makes more sense to set in HttpRequest
		if (blx()->config->usePathInfo())
		{
			$this->setUrlFormat(static::PATH_FORMAT);
		}
		else
		{
			$this->setUrlFormat(static::GET_FORMAT);
		}
	}

	/**
	 * @return null
	 */
	public function processTemplateMatching()
	{
		// we'll never have a db entry match on a control panel request
		if (blx()->isInstalled() && blx()->request->isSiteRequest())
		{
			if (($path = $this->matchPage()) !== false)
			{
				return $path;
			}

			if (($path = $this->matchEntry()) !== false)
			{
				return $path;
			}
		}

		if (($path = $this->matchRoute()) !== false)
		{
			return $path;
		}
		else
		{
			return $this->matchTemplatePath();
		}
	}

	/**
	 * @return array Any variables that should be passed into the matched template
	 */
	public function getTemplateVariables()
	{
		return $this->_templateVariables;
	}

	/**
	 * Attempts to match a request with a page in the database.
	 *
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchPage()
	{
		$page = blx()->pages->getPageByUri(blx()->request->getPath());

		if ($page)
		{
			$this->_templateVariables['page'] = $page;
			return $page->template;
		}

		return false;
	}

	/**
	 * Attempts to match a request with an entry in the database.
	 *
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchEntry()
	{
		$path = blx()->request->getPath();
		if ($path)
		{
			$criteria = new EntryCriteria();
			$criteria->uri = blx()->request->getPath();
			$entry = blx()->entries->findEntry($criteria);

			if ($entry)
			{
				$this->_templateVariables['entry'] = $entry;
				if (Blocks::hasPackage(BlocksPackage::PublishPro))
				{
					return $entry->getSection()->template;
				}
				else
				{
					return 'blog/_entry';
				}
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchRoute()
	{
		if (blx()->request->isCpRequest())
		{
			// Check the Blocks predefined routes.
			if (($template = $this->_matchRoutes($this->cpRoutes)) !== false)
			{
				return $template;
			}

			// As a last ditch to match routes, check to see if any plugins have routes registered that will match.
			$pluginCpRoutes = blx()->plugins->callHook('registerCpRoutes');
			foreach ($pluginCpRoutes as $pluginRoutes)
			{
				if (($template = $this->_matchRoutes($pluginRoutes)) !== false)
				{
					return $template;
				}
			}
		}
		else
		{
			// Check the user-defined routes
			$siteRoutes = blx()->routes->getAllRoutes();

			if (($template = $this->_matchRoutes($siteRoutes)) !== false)
			{
				return $template;
			}
		}

		return false;
	}

	/**
	 * Tests the request path against a series of routes, and returns the matched route's template, or false.
	 *
	 * @access private
	 * @param array $routes
	 * @return string|false
	 */
	private function _matchRoutes($routes)
	{
		foreach ($routes as $pattern => $template)
		{
			// Parse {handle} tokens
			$pattern = str_replace('{handle}', '[a-zA-Z][a-zA-Z0-9_]*', $pattern);

			// Does it match?
			if (preg_match('/^'.$pattern.'$/', blx()->request->getPath(), $match))
			{
				// Set any capture variables
				foreach ($match as $key => $value)
				{
					if (!is_numeric($key))
					{
						$this->_templateVariables[$key] = $value;
					}
				}

				return $template;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchTemplatePath()
	{
		// Make sure they're not trying to access a private template
		if (!blx()->request->isAjaxRequest())
		{
			foreach (blx()->request->getSegments() as $requestPathSeg)
			{
				if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
				{
					return false;
				}
			}
		}

		return blx()->request->getPath();
	}
}
