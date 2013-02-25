<?php
namespace Craft;

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
		if (craft()->config->usePathInfo())
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
		// we'll never have a db element match on a control panel request
		if (craft()->isInstalled() && craft()->request->isSiteRequest())
		{
			if (($path = $this->matchElement()) !== false)
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
	 * Attempts to match a request with an element in the database.
	 *
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchElement()
	{
		$query = craft()->db->createCommand()
			->select('elements.id, elements.type')
			->from('elements elements')
			->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id');

		$conditions = array('and', 'elements_i18n.uri = :path', 'elements.enabled = 1', 'elements.archived = 0');
		$params = array(':path' => craft()->request->getPath());

		$localeIds = array_unique(array_merge(
			array(craft()->language),
			craft()->i18n->getSiteLocaleIds()
		));

		if (count($localeIds) == 1)
		{
			$conditions[] = 'elements_i18n.locale = :locale';
			$params[':locale'] = $localeIds[0];
		}
		else
		{
			$quotedLocales = array();
			$localeOrder = array();

			foreach ($localeIds as $localeId)
			{
				$quotedLocale = craft()->db->quoteValue($localeId);
				$quotedLocales[] = $quotedLocale;
				$localeOrder[] = "(elements_i18n.locale = {$quotedLocale}) DESC";
			}

			$conditions[] = "elements_i18n.locale IN (".implode(', ', $quotedLocales).')';
			$query->order($localeOrder);
		}

		$query->where($conditions, $params);

		$row = $query->queryRow();

		if ($row)
		{
			$elementCriteria = craft()->elements->getCriteria($row['type']);
			$elementCriteria->id = $row['id'];

			$element = craft()->elements->findElement($elementCriteria);

			if ($element)
			{
				$elementType = $elementCriteria->getElementType();
				$template = $elementType->getSiteTemplateForMatchedElement($element);

				if ($template !== false)
				{
					$varName = $elementType->getVariableNameForMatchedElement();
					$this->_templateVariables[$varName] = $element;
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
		if (craft()->request->isCpRequest())
		{
			// Check the Craft predefined routes.

			if (isset($this->cpRoutes['pkgRoutes']))
			{
				// Merge in the package routes
				foreach ($this->cpRoutes['pkgRoutes'] as $packageName => $packageRoutes)
				{
					if (Craft::hasPackage($packageName))
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
			$pluginCpRoutes = craft()->plugins->callHook('registerCpRoutes');
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
			$siteRoutes = craft()->routes->getAllRoutes();

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
			if (preg_match('/^'.$pattern.'$/', craft()->request->getPath(), $match))
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
		if (!craft()->request->isAjaxRequest())
		{
			foreach (craft()->request->getSegments() as $requestPathSeg)
			{
				if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
				{
					return false;
				}
			}
		}

		return craft()->request->getPath();
	}
}
