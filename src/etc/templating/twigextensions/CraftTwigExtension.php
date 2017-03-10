<?php
namespace Craft;

/**
 * Class CraftTwigExtension
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     2.0
 */
class CraftTwigExtension extends \Twig_Extension
{
	/**
	 * @var TwigEnvironment
	 */
	protected $environment;

	// Public Methods
	// =========================================================================

	/**
	 * CraftTwigExtension constructor.
	 *
	 * @param TwigEnvironment $environment
	 */
	public function __construct(TwigEnvironment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 *
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		$tokenParsers = array(
			new Switch_TokenParser(),
		);

		if (!$this->environment->isSafeMode())
		{
			$tokenParsers = array_merge($tokenParsers, array(
				new Cache_TokenParser(),
				new Exit_TokenParser(),
				new Header_TokenParser(),
				new Hook_TokenParser(),
				new IncludeResource_TokenParser('includeCss', true),
				new IncludeResource_TokenParser('includecss', true),
				new IncludeResource_TokenParser('includeCssFile'),
				new IncludeResource_TokenParser('includecssfile'),
				new IncludeResource_TokenParser('includeCssResource'),
				new IncludeResource_TokenParser('includecssresource'),
				new IncludeResource_TokenParser('includeHiResCss', true),
				new IncludeResource_TokenParser('includehirescss', true),
				new IncludeResource_TokenParser('includeJs', true),
				new IncludeResource_TokenParser('includejs', true),
				new IncludeResource_TokenParser('includeJsFile'),
				new IncludeResource_TokenParser('includejsfile'),
				new IncludeResource_TokenParser('includeJsResource'),
				new IncludeResource_TokenParser('includejsresource'),
				new IncludeTranslations_TokenParser(),
				new Namespace_TokenParser(),
				new Nav_TokenParser(),
				new Paginate_TokenParser(),
				new Redirect_TokenParser(),
				new RequireAdmin_TokenParser(),
				new RequireEdition_TokenParser(),
				new RequireLogin_TokenParser(),
				new RequirePermission_TokenParser(),

				new DeprecatedTag_TokenParser('endpaginate'),
			));
		}

		return $tokenParsers;
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
		$markdownFilter = new \Twig_Filter_Method($this, 'markdownFilter');

		return array(
			'camel'              => new \Twig_Filter_Method($this, 'camelFilter'),
			'currency'           => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatCurrency'),
			'date'               => new \Twig_Filter_Method($this, 'dateFilter', array('needs_environment' => true)),
			'datetime'           => new \Twig_Filter_Function('\Craft\craft()->dateFormatter->formatDateTime'),
			'filesize'           => new \Twig_Filter_Function('\Craft\craft()->formatter->formatSize'),
			'filter'             => new \Twig_Filter_Function('array_filter'),
			'group'              => new \Twig_Filter_Method($this, 'groupFilter'),
			'hash'               => new \Twig_Filter_Function('\Craft\craft()->security->hashData'),
			'indexOf'            => new \Twig_Filter_Method($this, 'indexOfFilter'),
			'intersect'          => new \Twig_Filter_Function('array_intersect'),
			'json_encode'        => new \Twig_Filter_Method($this, 'jsonEncodeFilter'),
			'kebab'              => new \Twig_Filter_Method($this, 'kebabFilter'),
			'lcfirst'            => new \Twig_Filter_Method($this, 'lcfirstFilter'),
			'literal'            => new \Twig_Filter_Method($this, 'literalFilter'),
			'markdown'           => $markdownFilter,
			'md'                 => $markdownFilter,
			'namespace'          => $namespaceFilter,
			'ns'                 => $namespaceFilter,
			'namespaceInputName' => new \Twig_Filter_Function('\Craft\craft()->templates->namespaceInputName'),
			'namespaceInputId'   => new \Twig_Filter_Function('\Craft\craft()->templates->namespaceInputId'),
			'number'             => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatDecimal'),
			'parseRefs'          => new \Twig_Filter_Method($this, 'parseRefsFilter'),
			'pascal'             => new \Twig_Filter_Method($this, 'pascalFilter'),
			'percentage'         => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatPercentage'),
			'replace'            => new \Twig_Filter_Method($this, 'replaceFilter'),
			'snake'              => new \Twig_Filter_Method($this, 'snakeFilter'),
			'translate'          => $translateFilter,
			't'                  => $translateFilter,
			'ucfirst'            => new \Twig_Filter_Method($this, 'ucfirstFilter'),
			'ucwords'            => new \Twig_Filter_Function('ucwords'),
			'values'             => new \Twig_Filter_Function('array_values'),
			'without'            => new \Twig_Filter_Method($this, 'withoutFilter'),
		);
	}

	/**
	 * Uppercases the first character of a multibyte string.
	 *
	 * @param string $string The multibyte string.
	 *
	 * @return string The string with the first character converted to upercase.
	 */
	public function ucfirstFilter($string)
	{
		return StringHelper::uppercaseFirst($string);
	}

	/**
	 * Lowercases the first character of a multibyte string.
	 *
	 * @param string $string The multibyte string.
	 *
	 * @return string The string with the first character converted to lowercase.
	 */
	public function lcfirstFilter($string)
	{
		return StringHelper::lowercaseFirst($string);
	}

	/**
	 * kebab-cases a string.
	 *
	 * @param string $string The string
	 * @param string $glue The string used to glue the words together (default is a hyphen)
	 * @param boolean $lower Whether the string should be lowercased (default is true)
	 * @param boolean $removePunctuation Whether punctuation marks should be removed (default is true)
	 *
	 * @return string
	 */
	public function kebabFilter($string, $glue = '-', $lower = true, $removePunctuation = true)
	{
		return StringHelper::toKebabCase($string, $glue, $lower, $removePunctuation);
	}

	/**
	 * camelCases a string.
	 *
	 * @param string $string The string
	 *
	 * @return string
	 */
	public function camelFilter($string)
	{
		return StringHelper::toCamelCase($string);
	}

	/**
	 * PascalCases a string.
	 *
	 * @param string $string The string
	 *
	 * @return string
	 */
	public function pascalFilter($string)
	{
		return StringHelper::toPascalCase($string);
	}

	/**
	 * snake_cases a string.
	 *
	 * @param string $string The string
	 *
	 * @return string
	 */
	public function snakeFilter($string)
	{
		return StringHelper::toSnakeCase($string);
	}

	/**
	 * This method will JSON encode a variable. We're overriding Twig's default implementation to set some stricter
	 * encoding options on text/html/xml requests.
	 *
	 * @param mixed    $value   The value to JSON encode.
	 * @param null|int $options Either null or a bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP,
	 *                          JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES,
	 *                          JSON_FORCE_OBJECT
	 *
	 * @return mixed The JSON encoded value.
	 */
	public function jsonEncodeFilter($value, $options = null)
	{
		if ($options === null && (in_array(HeaderHelper::getMimeType(), array('text/html', 'application/xhtml+xml'))))
		{
			$options = JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT;
		}

		return twig_jsonencode_filter($value, $options);
	}

	/**
	 * Returns an array without certain values.
	 *
	 * @param array $arr
	 * @param mixed $exclude
	 *
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
	 * Parses a string for reference tags.
	 *
	 * @param string $str
	 *
	 * @return \Twig_Markup
	 */
	public function parseRefsFilter($str)
	{
		$str = craft()->elements->parseRefs($str);
		return TemplateHelper::getRaw($str);
	}

	/**
	 * Replaces Twig's |replace filter, adding support for passing in separate
	 * search and replace arrays.
	 *
	 * @param mixed $str
	 * @param mixed $search
	 * @param mixed $replace
	 *
	 * @return mixed
	 */
	public function replaceFilter($str, $search, $replace = null)
	{
		// Are they using the standard Twig syntax?
		if (is_array($search) && $replace === null)
		{
			return strtr($str, $search);
		}
		// Is this a regular expression?
		else if (preg_match('/^\/.+\/[a-zA-Z]*$/', $search))
		{
			return preg_replace($search, $replace, $str);
		}
		else
		{
			// Otherwise use str_replace
			return str_replace($search, $replace, $str);
		}
	}

	/**
	 * Extending Twig's |date filter so we can run any translations on the output.
	 *
	 * @param \Twig_Environment $env
	 * @param                   $date
	 * @param null              $format
	 * @param null              $timezone
	 *
	 * @return mixed|string
	 */
	public function dateFilter(\Twig_Environment $env, $date, $format = null, $timezone = null)
	{
		// Let Twig do it's thing.
		$value = \twig_date_format_filter($env, $date, $format, $timezone);

		// Get the "words".  Split on anything that is not a unicode letter or number.
		preg_match_all('/[\p{L}\p{N}]+/u', $value, $words);

		if ($words && isset($words[0]) && count($words[0]) > 0)
		{
			foreach ($words[0] as $word)
			{
				$originalWord = $word;

				if ($word === 'May')
				{
					if (strpos($format, 'F') !== false)
					{
						$word = 'May-W';
					}
				}

				// Translate and swap out.
				$translatedWord = Craft::t($word);

				$value = str_replace($originalWord, $translatedWord, $value);
			}
		}

		// Return the translated value.
		return $value;

	}

	/**
	 * Groups an array by a common property.
	 *
	 * @param array  $arr
	 * @param string $item
	 *
	 * @return array
	 */
	public function groupFilter($arr, $item)
	{
		$groups = array();

		$template = '{'.$item.'}';
		$safeMode = $this->environment->isSafeMode();

		foreach ($arr as $key => $object)
		{
			$value = craft()->templates->renderObjectTemplate($template, $object, $safeMode);
			$groups[$value][] = $object;
		}

		return $groups;
	}

	/**
	 * Returns the index of an item in a string or array, or -1 if it cannot be found.
	 *
	 * @param mixed $haystack
	 * @param mixed $needle
	 *
	 * @return int
	 */
	public function indexOfFilter($haystack, $needle)
	{
		if (is_string($haystack))
		{
			$index = strpos($haystack, $needle);
		}
		else if (is_array($haystack))
		{
			$index = array_search($needle, $haystack);
		}
		else if (is_object($haystack) && $haystack instanceof \IteratorAggregate)
		{
			$index = false;

			foreach ($haystack as $i => $item)
			{
				if ($item == $needle)
				{
					$index = $i;
					break;
				}
			}
		}
		else
		{
			$index = null;
		}

		if ($index !== false)
		{
			return $index;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Escapes commas and asterisks in a string so they are not treated as special characters in
	 * {@link DbHelper::parseParam()}.
	 *
	 * @param string $value The param value.
	 *
	 * @return string The escaped param value.
	 */
	public function literalFilter($value)
	{
		return DbHelper::escapeParam($value);
	}

	/**
	 * Parses text through Markdown.
	 *
	 * @param string $str
	 *
	 * @return \Twig_Markup
	 */
	public function markdownFilter($str)
	{
		$html = StringHelper::parseMarkdown($str);
		return TemplateHelper::getRaw($html);
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			'actionUrl'            => new \Twig_Function_Function('\Craft\UrlHelper::getActionUrl'),
			'cpUrl'                => new \Twig_Function_Function('\Craft\UrlHelper::getCpUrl'),
			'ceil'                 => new \Twig_Function_Function('ceil'),
			'floor'                => new \Twig_Function_Function('floor'),
			'getCsrfInput'         => new \Twig_Function_Method($this, 'getCsrfInputFunction'),
			'getHeadHtml'          => new \Twig_Function_Method($this, 'getHeadHtmlFunction'),
			'getFootHtml'          => new \Twig_Function_Method($this, 'getFootHtmlFunction'),
			'getTranslations'      => new \Twig_Function_Function('\Craft\craft()->templates->getTranslations'),
			'max'                  => new \Twig_Function_Function('max'),
			'min'                  => new \Twig_Function_Function('min'),
			'renderObjectTemplate' => new \Twig_Function_Method($this, 'renderObjectTemplate'),
			'round'                => new \Twig_Function_Function('round'),
			'resourceUrl'          => new \Twig_Function_Function('\Craft\UrlHelper::getResourceUrl'),
			'shuffle'              => new \Twig_Function_Method($this, 'shuffleFunction'),
			'siteUrl'              => new \Twig_Function_Function('\Craft\UrlHelper::getSiteUrl'),
			'url'                  => new \Twig_Function_Function('\Craft\UrlHelper::getUrl'),
		);
	}

	/**
	 * Returns getCsrfInput() wrapped in a \Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getCsrfInputFunction()
	{
		$html = craft()->templates->getCsrfInput();
		return TemplateHelper::getRaw($html);
	}

	/**
	 * Returns getHeadHtml() wrapped in a \Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getHeadHtmlFunction()
	{
		$html = craft()->templates->getHeadHtml();
		return TemplateHelper::getRaw($html);
	}

	/**
	 * Returns getFootHtml() wrapped in a \Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getFootHtmlFunction()
	{
		$html = craft()->templates->getFootHtml();
		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param $template
	 * @param $object
	 *
	 * @return string
	 */
	public function renderObjectTemplate($template, $object)
	{
		$safeMode = $this->environment->isSafeMode();
		return craft()->templates->renderObjectTemplate($template, $object, $safeMode);
	}

	/**
	 * Shuffles an array.
	 *
	 * @param mixed $arr
	 *
	 * @return mixed
	 */
	public function shuffleFunction($arr)
	{
		if ($arr instanceof \Traversable)
		{
			$arr = iterator_to_array($arr, false);
		}
		else
		{
			$arr = array_merge($arr);
		}

		shuffle($arr);

		return $arr;
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		$safeMode = $this->environment->isSafeMode();
		$isInstalled = craft()->isInstalled();

		$globals = array(
			'user' => null,
			'currentUser' => null,
		);

		if (!$safeMode)
		{
			// Keep the 'blx' variable around for now
			$craftVariable = new CraftVariable();
			$globals['craft'] = $craftVariable;
			$globals['blx'] = $craftVariable;

			$globals['loginUrl'] = UrlHelper::getUrl(craft()->config->getLoginPath());
			$globals['logoutUrl'] = UrlHelper::getUrl(craft()->config->getLogoutPath());
			$globals['isInstalled'] = $isInstalled;

			if ($isInstalled && !craft()->isConsole())
			{
				$globals['currentUser'] = craft()->userSession->getUser();
			}

			// Keep 'user' around so long as it's not hurting anyone.
			// Technically deprecated, though.
			$globals['user'] = $globals['currentUser'];

			if (craft()->request->isCpRequest())
			{
				$globals['CraftEdition']  = craft()->getEdition();
				$globals['CraftPersonal'] = Craft::Personal;
				$globals['CraftClient']   = Craft::Client;
				$globals['CraftPro']      = Craft::Pro;
			}
		}

		$globals['now'] = new DateTime(null, new \DateTimeZone(craft()->getTimeZone()));

		if ($isInstalled && !craft()->updates->isCraftDbMigrationNeeded())
		{
			$globals['siteName'] = craft()->getSiteName();
			$globals['siteUrl'] = craft()->getSiteUrl();

			if (craft()->request->isSiteRequest())
			{
				foreach (craft()->globals->getAllSets() as $globalSet)
				{
					$globals[$globalSet->handle] = $globalSet;
				}
			}
		}
		else
		{
			$globals['siteName'] = null;
			$globals['siteUrl'] = null;
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
}
