<?php
namespace Blocks;

/**
 *
 */
class BlocksTwigExtension extends \Twig_Extension
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
		$translateFilter = new \Twig_Filter_Function('\Blocks\Blocks::t');
		$namespaceFilter = new \Twig_Filter_Function('\Blocks\blx()->templates->namespaceInputs');

		return array(
			'translate'  => $translateFilter,
			't'          => $translateFilter,
			'namespace'  => $namespaceFilter,
			'ns'         => $namespaceFilter,
			'number'     => new \Twig_Filter_Function('\Blocks\blx()->numberFormatter->formatDecimal'),
			'currency'   => new \Twig_Filter_Function('\Blocks\blx()->numberFormatter->formatCurrency'),
			'percentage' => new \Twig_Filter_Function('\Blocks\blx()->numberFormatter->formatPercentage'),
			'datetime'   => new \Twig_Filter_Function('\Blocks\blx()->dateFormatter->formatDateTime'),
			'without'    => new \Twig_Filter_Method($this, 'withoutFilter'),
			'filter'     => new \Twig_Filter_Function('array_filter'),
			'ucfirst'    => new \Twig_Filter_Function('ucfirst'),
			'lcfirst'    => new \Twig_Filter_Function('lcfirst'),
			'filesize'	 => new \Twig_Filter_Function('\Blocks\blx()->formatter->formatSize'),
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
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			'url'             => new \Twig_Function_Function('\Blocks\UrlHelper::getUrl'),
			'cpUrl'           => new \Twig_Function_Function('\Blocks\UrlHelper::getCpUrl'),
			'siteUrl'         => new \Twig_Function_Function('\Blocks\UrlHelper::getSiteUrl'),
			'resourceUrl'     => new \Twig_Function_Function('\Blocks\UrlHelper::getResourceUrl'),
			'actionUrl'       => new \Twig_Function_Function('\Blocks\UrlHelper::getActionUrl'),
			'getHeadHtml'    => new \Twig_Function_Method($this, 'getHeadHtmlFunction'),
			'getFootHtml'    => new \Twig_Function_Method($this, 'getFootHtmlFunction'),
			'getTranslations' => new \Twig_Function_Function('\Blocks\blx()->templates->getTranslations'),
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
		$html = blx()->templates->getHeadHtml();
		return $this->getTwigMarkup($html);
	}

	/**
	 * Returns getFootHtml() wrapped in a Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getFootHtmlFunction()
	{
		$html = blx()->templates->getFootHtml();
		return $this->getTwigMarkup($html);
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		$globals['blx'] = new BlxVariable();
		$globals['now'] = DateTimeHelper::currentUTCDateTime();
		$globals['loginUrl'] = UrlHelper::getUrl(blx()->config->get('loginPath'));
		$globals['logoutUrl'] = UrlHelper::getUrl(blx()->config->get('logoutPath'));

		if (blx()->isInstalled())
		{
			$globals['siteName'] = Blocks::getSiteName();
			$globals['siteUrl'] = Blocks::getSiteUrl();

			// TODO: Deprecate after next breakpoint
			if (Blocks::getStoredBuild() > 2157)
			{
				$globals['globals'] = blx()->globals->getGlobalContent();
				$globals['user'] = blx()->userSession->getUser();
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
		return 'blocks';
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
		$charset = blx()->templates->getTwig()->getCharset();
		return new \Twig_Markup($str, $charset);
	}
}
