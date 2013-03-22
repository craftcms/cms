<?php
namespace Craft;

/**
 *
 */
class CraftTwigExtension extends \Twig_Extension
{
	/**
	 * Returns the token parser instances to add to the existing list.
	 *
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array(
			new Redirect_TokenParser(),
			new RequireLogin_TokenParser(),
			new RequirePackage_TokenParser(),
			new RequirePermission_TokenParser(),
			new IncludeResource_TokenParser('includeCssFile'),
			new IncludeResource_TokenParser('includeJsFile'),
			new IncludeResource_TokenParser('includeCssResource'),
			new IncludeResource_TokenParser('includeJsResource'),
			new IncludeResource_TokenParser('includeCss'),
			new IncludeResource_TokenParser('includeHiResCss'),
			new IncludeResource_TokenParser('includeJs'),
			new IncludeTranslations_TokenParser(),
			new Exit_TokenParser(),
			new Paginate_TokenParser(),
		);
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		$translateFilter = new \Twig_Filter_Function('\Craft\Craft::t');
		$namespaceFilter = new \Twig_Filter_Function('\Craft\craft()->templates->namespaceInputs');

		return array(
			'translate'  => $translateFilter,
			't'          => $translateFilter,
			'namespace'  => $namespaceFilter,
			'ns'         => $namespaceFilter,
			'number'     => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatDecimal'),
			'currency'   => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatCurrency'),
			'percentage' => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatPercentage'),
			'datetime'   => new \Twig_Filter_Function('\Craft\craft()->dateFormatter->formatDateTime'),
			'intersect'  => new \Twig_Filter_Function('array_intersect'),
			'without'    => new \Twig_Filter_Method($this, 'withoutFilter'),
			'replace'    => new \Twig_Filter_Method($this, 'replaceFilter'),
			'filter'     => new \Twig_Filter_Function('array_filter'),
			'ucfirst'    => new \Twig_Filter_Function('ucfirst'),
			'lcfirst'    => new \Twig_Filter_Function('lcfirst'),
			'filesize'	 => new \Twig_Filter_Function('\Craft\craft()->formatter->formatSize'),
		);
	}

	/**
	 * Returns an array without certain values.
	 *
	 * @param array $arr
	 * @param mixed $exclude
	 * @return array
	 */
	public function withoutFilter($arr, $exclude)
	{
		$filteredArray = array();

		if (!is_array($exclude))
		{
			$exclude = array($exclude);
		}

		foreach ($arr as $key => $value)
		{
			if (!in_array($value, $exclude))
			{
				$filteredArray[$key] = $value;
			}
		}

		return $filteredArray;
	}

	/**
	 * Replacecs Twig's |replace filter, adding support for passing in separate search and replace arrays.
	 *
	 * @param mixed $str
	 * @param mixed $search
	 * @param mixed $replace
	 */
	public function replaceFilter($str, $search, $replace = null)
	{
		// Are they using the standard Twig syntax?
		if (is_array($search) && $replace === null)
		{
			return strtr($str, $search);
		}
		else
		{
			// Otherwise use str_replace
			return str_replace($search, $replace, $str);
		}
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			'url'             => new \Twig_Function_Function('\Craft\UrlHelper::getUrl'),
			'cpUrl'           => new \Twig_Function_Function('\Craft\UrlHelper::getCpUrl'),
			'siteUrl'         => new \Twig_Function_Function('\Craft\UrlHelper::getSiteUrl'),
			'resourceUrl'     => new \Twig_Function_Function('\Craft\UrlHelper::getResourceUrl'),
			'actionUrl'       => new \Twig_Function_Function('\Craft\UrlHelper::getActionUrl'),
			'getHeadHtml'     => new \Twig_Function_Method($this, 'getHeadHtmlFunction'),
			'getFootHtml'     => new \Twig_Function_Method($this, 'getFootHtmlFunction'),
			'getTranslations' => new \Twig_Function_Function('\Craft\craft()->templates->getTranslations'),
			'round'           => new \Twig_Function_Function('round'),
			'ceil'            => new \Twig_Function_Function('ceil'),
			'floor'           => new \Twig_Function_Function('floor'),
			'min'             => new \Twig_Function_Function('min'),
			'max'             => new \Twig_Function_Function('max'),
		);
	}

	/**
	 * Returns getHeadHtml() wrapped in a Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getHeadHtmlFunction()
	{
		$html = craft()->templates->getHeadHtml();
		return $this->getTwigMarkup($html);
	}

	/**
	 * Returns getFootHtml() wrapped in a Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getFootHtmlFunction()
	{
		$html = craft()->templates->getFootHtml();
		return $this->getTwigMarkup($html);
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		// Keep the 'blx' variable around for now
		$craftVariable = new CraftVariable();
		$globals['craft'] = $craftVariable;
		$globals['blx']   = $craftVariable;

		$globals['now'] = DateTimeHelper::currentUTCDateTime();
		$globals['loginUrl'] = UrlHelper::getUrl(craft()->config->get('loginPath'));
		$globals['logoutUrl'] = UrlHelper::getUrl(craft()->config->get('logoutPath'));

		if (Craft::isInstalled())
		{
			$globals['siteName'] = Craft::getSiteName();
			$globals['siteUrl'] = Craft::getSiteUrl();

			// TODO: Deprecate after next breakpoint
			if (Craft::getBuild() > 2157)
			{
				$globals['user'] = craft()->userSession->getUser();

				// TODO: Deprecate the build conditional after the next breakpoint
				if (craft()->request->isSiteRequest() && Craft::getBuild() >= 2168)
				{
					foreach (craft()->globals->getAllSets() as $globalSet)
					{
						$globalSet->locale = craft()->language;
						$globals[$globalSet->handle] = $globalSet;
					}
				}
			}
		}

		return $globals;
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'craft';
	}

	/**
	 * Returns a string wrapped in a Twig_Markup object.
	 *
	 * @access private
	 * @param string $str
	 * @return \Twig_Markup
	 */
	private function getTwigMarkup($str)
	{
		$charset = craft()->templates->getTwig()->getCharset();
		return new \Twig_Markup($str, $charset);
	}
}
