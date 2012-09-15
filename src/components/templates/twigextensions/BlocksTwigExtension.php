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
			new IncludeResource_TokenParser('includeCssFile'),
			new IncludeResource_TokenParser('includeJsFile'),
			new IncludeResource_TokenParser('includeCssResource'),
			new IncludeResource_TokenParser('includeJsResource'),
			new IncludeTranslation_TokenParser(),
			new Exit_TokenParser(),
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
			'text'       => new \Twig_Filter_Method($this, 'textFilter'),
			'without'    => new \Twig_Filter_Method($this, 'withoutFilter'),
			'filter'     => new \Twig_Filter_Function('array_filter'),
			'ucfirst'    => new \Twig_Filter_Function('ucfirst'),
			'lcfirst'    => new \Twig_Filter_Function('lcfirst'),
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
			$exclude = array($exclude);

		foreach ($arr as $key => $value)
		{
			if (!in_array($value, $exclude))
				$filteredArray[$key] = $value;
		}

		return $filteredArray;
	}

	/**
	 * Returns the text without any HTML tags.
	 *
	 * @param string $str
	 * @return string
	 */
	public function textFilter($str)
	{
		return preg_replace('/\<[^\>]+\>/', '', $str);
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			'url'          => new \Twig_Function_Function('\Blocks\UrlHelper::generateUrl'),
			'resourceUrl'  => new \Twig_Function_Function('\Blocks\UrlHelper::generateResourceUrl'),
			'actionUrl'    => new \Twig_Function_Function('\Blocks\UrlHelper::generateActionUrl'),
			'getHeadNodes' => new \Twig_Function_Function('\Blocks\blx()->templates->getHeadNodes'),
			'getFootNodes' => new \Twig_Function_Function('\Blocks\blx()->templates->getFootNodes'),
			'round'        => new \Twig_Function_Function('round'),
			'ceil'         => new \Twig_Function_Function('ceil'),
			'floor'        => new \Twig_Function_Function('floor'),
		);
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		$globals['blx'] = new BlxVariable();

		if (blx()->isInstalled())
		{
			$globals['siteName'] = Blocks::getSiteName();
			$globals['siteUrl'] = Blocks::getSiteUrl();

			if (($user = blx()->accounts->getCurrentUser()) !== null)
				$globals['userName'] = $user->getFullName();
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
}
