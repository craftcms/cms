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
	 * Attempts to match a request with an entry in the database.
	 *
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchEntry()
	{
		$query = blx()->db->createCommand()
			->select('e.id, e.type')
			->from('entries e')
			->join('entries_i18n e_i18n', 'e_i18n.entryId = e.id');

		$conditions = array('and',
			'e_i18n.uri = :path',
			'e.postDate <= :now',
			array('or', 'e.expiryDate IS NULL', 'e.expiryDate > :now'),
			'e.enabled = 1',
			'e.archived = 0',
		);

		$now = new DateTime();

		$params = array(
			':path' => blx()->request->getPath(),
			':now'  => $now->format(DateTime::MYSQL_DATETIME)
		);

		$localeIds = array_unique(array_merge(
			array(blx()->language),
			blx()->i18n->getSiteLocaleIds()
		));

		if (count($localeIds) == 1)
		{
			$conditions[] = 'e_i18n.locale = :locale';
			$params[':locale'] = $localeIds[0];
		}
		else
		{
			$quotedLocales = array();
			$localeOrder = array();

			foreach ($localeIds as $localeId)
			{
				$quotedLocale = blx()->db->quoteValue($localeId);
				$quotedLocales[] = $quotedLocale;
				$localeOrder[] = "(e_i18n.locale = {$quotedLocale}) DESC";
			}

			$conditions[] = "e_i18n.locale IN (".implode(', ', $quotedLocales).')';
			$query->order($localeOrder);
		}

		$query->where($conditions, $params);

		$row = $query->queryRow();

		if ($row)
		{
			$entryCriteria = blx()->entries->getEntryCriteria($row['type']);
			$entryCriteria->id = $row['id'];

			$entry = blx()->entries->findEntry($entryCriteria);

			if ($entry)
			{
				$entryType = $entryCriteria->getEntryType();
				$template = $entryType->getSiteTemplateForMatchedEntry($entry);

				if ($template !== false)
				{
					$varName = $entryType->getVariableNameForMatchedEntry();
					$this->_templateVariables[$varName] = $entry;
					return $template;
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

			if (isset($this->cpRoutes['pkgRoutes']))
			{
				// Merge in the package routes
				foreach ($this->cpRoutes['pkgRoutes'] as $packageName => $packageRoutes)
				{
					if (Blocks::hasPackage($packageName))
					{
						$this->cpRoutes = array_merge($this->cpRoutes, $packageRoutes);
					}
				}

				unset($this->cpRoutes['pkgRoutes']);
			}

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
