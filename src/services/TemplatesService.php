<?php
namespace Craft;

/**
 * TemplatesService provides APIs for rendering templates, as well as interacting with other areas of Craft’s
 * templating system.
 *
 * An instance of TemplatesService is globally accessible in Craft via {@link WebApp::templates `craft()->templates`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class TemplatesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_twigs;

	/**
	 * @var
	 */
	private $_twigOptions;

	/**
	 * @var
	 */
	private $_templatePaths;

	/**
	 * @var
	 */
	private $_objectTemplates;

	/**
	 * @var
	 */
	private $_defaultTemplateExtensions;

	/**
	 * @var
	 */
	private $_indexTemplateFilenames;

	/**
	 * @var
	 */
	private $_namespace;

	/**
	 * @var array
	 */
	private $_headHtml = array();

	/**
	 * @var array
	 */
	private $_footHtml = array();

	/**
	 * @var array
	 */
	private $_cssFiles = array();

	/**
	 * @var array
	 */
	private $_jsFiles = array();

	/**
	 * @var array
	 */
	private $_css = array();

	/**
	 * @var array
	 */
	private $_hiResCss = array();

	/**
	 * @var array
	 */
	private $_jsBuffers = array(array());

	/**
	 * @var array
	 */
	private $_translations = array();

	/**
	 * @var
	 */
	private $_hooks;

	/**
	 * @var
	 */
	private $_textareaMarkers;

	/**
	 * @var
	 */
	private $_renderingTemplate;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		$this->hook('cp.elements.element', array($this, '_getCpElementHtml'));
	}

	/**
	 * Returns the Twig Environment instance for a given template loader class.
	 *
	 * @param string $loaderClass The name of the class that should be initialized as the Twig instance’s template
	 *                            loader. If no class is passed in, {@link TemplateLoader} will be used.
	 *
	 * @return \Twig_Environment The Twig Environment instance.
	 */
	public function getTwig($loaderClass = null)
	{
		if (!$loaderClass)
		{
			$loaderClass = __NAMESPACE__.'\\TemplateLoader';
		}

		if (!isset($this->_twigs[$loaderClass]))
		{
			$loader = new $loaderClass();
			$options = $this->_getTwigOptions();

			$twig = new \Twig_Environment($loader, $options);

			$twig->addExtension(new \Twig_Extension_StringLoader());
			$twig->addExtension(new CraftTwigExtension());

			if (craft()->config->get('devMode'))
			{
				$twig->addExtension(new \Twig_Extension_Debug());
			}

			// Set our timezone
			$timezone = craft()->getTimeZone();
			$twig->getExtension('core')->setTimezone($timezone);

			// Give plugins a chance to add their own Twig extensions
			$this->_addPluginTwigExtensions($twig);

			$this->_twigs[$loaderClass] = $twig;
		}

		return $this->_twigs[$loaderClass];
	}

	/**
	 * Returns whether a template is currently being rendered.
	 *
	 * @return bool Whether a template is currently being rendered.
	 */
	public function isRendering()
	{
		return isset($this->_renderingTemplate);
	}

	/**
	 * Returns the template path that is currently being rendered, or the full template if {@link renderString()} or
	 * {@link renderObjectTemplate()} was called.
	 *
	 * @return mixed The template that is being rendered.
	 */
	public function getRenderingTemplate()
	{
		if ($this->isRendering())
		{
			if (strncmp($this->_renderingTemplate, 'string:', 7) === 0)
			{
				$template = $this->_renderingTemplate;
			}
			else
			{
				$template = $this->findTemplate($this->_renderingTemplate);

				if (!$template)
				{
					$template = craft()->path->getTemplatesPath().$this->_renderingTemplate;
				}
			}

			return $template;
		}
	}

	/**
	 * Renders a template.
	 *
	 * @param mixed $template  The name of the template to load, or a StringTemplate object.
	 * @param array $variables The variables that should be available to the template.
	 *
	 * @return string The rendered template.
	 */
	public function render($template, $variables = array())
	{
		$twig = $this->getTwig();

		$lastRenderingTemplate = $this->_renderingTemplate;
		$this->_renderingTemplate = $template;
		$result = $twig->render($template, $variables);
		$this->_renderingTemplate = $lastRenderingTemplate;
		return $result;
	}

	/**
	 * Renders a macro within a given template.
	 *
	 * @param string $template The name of the template the macro lives in.
	 * @param string $macro    The name of the macro.
	 * @param array  $args     Any arguments that should be passed to the macro.
	 *
	 * @return string The rendered macro output.
	 */
	public function renderMacro($template, $macro, $args = array())
	{
		$twig = $this->getTwig();
		$twigTemplate = $twig->loadTemplate($template);

		$lastRenderingTemplate = $this->_renderingTemplate;
		$this->_renderingTemplate = $template;
		$result = call_user_func_array(array($twigTemplate, 'get'.$macro), $args);
		$this->_renderingTemplate = $lastRenderingTemplate;

		return $result;
	}

	/**
	 * Renders a template defined in a string.
	 *
	 * @param string $template  The source template string.
	 * @param array  $variables Any variables that should be available to the template.
	 *
	 * @return string The rendered template.
	 */
	public function renderString($template, $variables = array())
	{
		$stringTemplate = new StringTemplate(md5($template), $template);

		$lastRenderingTemplate = $this->_renderingTemplate;
		$this->_renderingTemplate = 'string:'.$template;
		$result = $this->render($stringTemplate, $variables);
		$this->_renderingTemplate = $lastRenderingTemplate;
		return $result;
	}

	/**
	 * Renders a micro template for accessing properties of a single object.
	 *
	 * The template will be parsed for {variables} that are delimited by single braces, which will get replaced with
	 * full Twig output tags, i.e. {{ object.variable }}. Regular Twig tags are also supported.
	 *
	 * @param string $template The source template string.
	 * @param mixed  $object   The object that should be passed into the template.
	 *
	 * @return string The rendered template.
	 */
	public function renderObjectTemplate($template, $object)
	{
		// If there are no dynamic tags, just return the template
		if (strpos($template, '{') === false)
		{
			return $template;
		}

		// Get a Twig instance with the String template loader
		$twig = $this->getTwig('Twig_Loader_String');

		// Have we already parsed this template?
		if (!isset($this->_objectTemplates[$template]))
		{
			// Replace shortcut "{var}"s with "{{object.var}}"s, without affecting normal Twig tags
			$formattedTemplate = preg_replace('/(?<![\{\%])\{(?![\{\%])/', '{{object.', $template);
			$formattedTemplate = preg_replace('/(?<![\}\%])\}(?![\}\%])/', '|raw}}', $formattedTemplate);
			$this->_objectTemplates[$template] = $twig->loadTemplate($formattedTemplate);
		}

		// Temporarily disable strict variables if it's enabled
		$strictVariables = $twig->isStrictVariables();

		if ($strictVariables)
		{
			$twig->disableStrictVariables();
		}

		// Render it!
		$lastRenderingTemplate = $this->_renderingTemplate;
		$this->_renderingTemplate = 'string:'.$template;
		$result = $this->_objectTemplates[$template]->render(array(
			'object' => $object
		));

		$this->_renderingTemplate = $lastRenderingTemplate;

		// Re-enable strict variables
		if ($strictVariables)
		{
			$twig->enableStrictVariables();
		}

		return $result;
	}


	/**
	 * Prepares some HTML for inclusion in the `<head>` of the template.
	 *
	 * @param string $node  The HTML to be included in the template.
	 * @param bool   $first Whether the HTML should be included before any other HTML that was already included with this
	 *                      method.
	 *
	 * @return null
	 */
	public function includeHeadHtml($node, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_headHtml, $node, $first);
	}

	/**
	 * Prepares an HTML node for inclusion right above the `</body>` in the template.
	 *
	 * @param string $node The HTML to be included in the template.
	 * @param bool   $first Whether the HTML should be included before any other HTML that was already included with this
	 *                      method.
	 *
	 * @return null
	 */
	public function includeFootHtml($node, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_footHtml, $node, $first);
	}

	/**
	 * Prepares some HTML for inclusion in the `<head>` of the template.
	 *
	 * @param string $node  The HTML to be included in the template.
	 * @param bool   $first Whether the HTML should be included before any other HTML that was already included with this
	 *                      method.
	 *
	 * @deprecated Deprecated in 1.1. Use {@link includeHeadHtml()} instead.
	 * @return null
	 */
	public function includeHeadNode($node, $first = false)
	{
		craft()->deprecator->log('TemplatesService::includeHeadNode()', 'TemplatesService::includeHeadNode() has been deprecated. includeHeadHtml() instead.');
		$this->includeHeadHtml($node, $first);
	}

	/**
	 * Prepares an HTML node for inclusion right above the </body> in the template.
	 *
	 * @param string $node The HTML to be included in the template.
	 * @param bool   $first Whether the HTML should be included before any other HTML that was already included with this
	 *                      method.
	 */
	public function includeFootNode($node, $first = false)
	{
		craft()->deprecator->log('TemplatesService::includeFootNode()', 'TemplatesService::includeFootNode() has been deprecated. Use includeFootNode() instead.');
		$this->includeFootHtml($node, $first);
	}

	/**
	 * Prepares a CSS file for inclusion in the template.
	 *
	 * @param string $url   The URL to the CSS file.
	 * @param bool   $first Whether the CSS file should be included before any other CSS files that were already
	 *                      included with this method.
	 *
	 * @return null
	 */
	public function includeCssFile($url, $first = false)
	{
		if (!in_array($url, $this->_cssFiles))
		{
			ArrayHelper::prependOrAppend($this->_cssFiles, $url, $first);
		}
	}

	/**
	 * Prepares a JS file for inclusion in the template.
	 *
	 * @param string $url   The URL to the JS file.
	 * @param bool   $first Whether the JS file should be included before any other JS files that were already
	 *                      included with this method.
	 *
	 * @return null
	 */
	public function includeJsFile($url, $first = false)
	{
		if (!in_array($url, $this->_jsFiles))
		{
			ArrayHelper::prependOrAppend($this->_jsFiles, $url, $first);
		}
	}

	/**
	 * Prepares a CSS file from resources/ for inclusion in the template.
	 *
	 * @param string $path  The resource path to the CSS file.
	 * @param bool   $first Whether the CSS file should be included before any other CSS files that were already
	 *                      included with this method.
	 *
	 * @return null
	 */
	public function includeCssResource($path, $first = false)
	{
		$url = UrlHelper::getResourceUrl($path);
		$this->includeCssFile($url, $first);
	}

	/**
	 * Prepares a JS file from resources/ for inclusion in the template.
	 *
	 * @param string $path  The resource path to the JS file.
	 * @param bool   $first Whether the JS file should be included before any other JS files that were already
	 *                      included with this method.
	 *
	 * @return null
	 */
	public function includeJsResource($path, $first = false)
	{
		$url = UrlHelper::getResourceUrl($path);
		$this->includeJsFile($url, $first);
	}

	/**
	 * Prepares CSS for inclusion in the template.
	 *
	 * @param string $css   The CSS.
	 * @param bool   $first Whether the CSS should be included before any other CSS that was already
	 *                      included with this method.
	 *
	 * @return null
	 */
	public function includeCss($css, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_css, trim($css), $first);
	}

	/**
	 * Prepares hi-res screen-targeting CSS for inclusion in the template.
	 *
	 * @param string $css   The CSS.
	 * @param bool   $first Whether the CSS should be included before any other CSS that was already
	 *                      included with this method.
	 *
	 * @return null
	 */
	public function includeHiResCss($css, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_hiResCss, trim($css), $first);
	}

	/**
	 * Prepares JS for inclusion in the template.
	 *
	 * @param string $js    The Javascript code.
	 * @param bool   $first Whether the Javascript code should be included before any other Javascript code that was
	 *                      already included with this method.
	 *
	 * @return null
	 */
	public function includeJs($js, $first = false)
	{
		// Trim any whitespace and ensure it ends with a semicolon.
		$js = trim($js, " \t\n\r\0\x0B;").';';

		$latestBuffer =& $this->_jsBuffers[count($this->_jsBuffers)-1];
		ArrayHelper::prependOrAppend($latestBuffer, $js, $first);
	}

	/**
	 * Wraps some Javascript code in a `<script>` tag.
	 *
	 * @param string|array $js The Javascript code.
	 *
	 * @return string The `<script>` tag.
	 */
	public function getScriptTag($js)
	{
		if (is_array($js))
		{
			$js = $this->_combineJs($js);
		}

		return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n".$js."\n/*]]>*/\n</script>";
	}

	/**
	 * Starts a Javascript buffer.
	 *
	 * Javascript buffers work similarly to [output buffers](http://php.net/manual/en/intro.outcontrol.php) in PHP.
	 * Once you’ve started a Javascript buffer, any Javascript code included with {@link includeJs()} will be included
	 * in a buffer, and you will have the opportunity to fetch all of that code via {@link clearJsBuffer()} without
	 * having it actually get output to the page.
	 *
	 * @return null
	 */
	public function startJsBuffer()
	{
		$this->_jsBuffers[] = array();
	}

	/**
	 * Clears and ends a Javascript buffer, returning whatever Javascript code was included while the buffer was active.
	 *
	 * @param bool $scriptTag Whether the Javascript code should be wrapped in a `<script>` tag. Defaults to `true`.
	 *
	 * @return string|null|false Returns `false` if there isn’t an active Javascript buffer, `null` if there is an
	 *                           active buffer but no Javascript code was actually added to it, or a string of the
	 *                           included Javascript code if there was any.
	 */
	public function clearJsBuffer($scriptTag = true)
	{
		if (count($this->_jsBuffers) <= 1)
		{
			return false;
		}

		$buffer = array_pop($this->_jsBuffers);

		if ($buffer)
		{
			$js = $this->_combineJs($buffer);

			if ($scriptTag)
			{
				return $this->getScriptTag($buffer);
			}
			else
			{
				return $js;
			}
		}
	}

	/**
	 * Returns the HTML prepared for inclusion in the `<head>` of the template, and flushes out the head HTML queue.
	 *
	 * This will include:
	 *
	 * - Any CSS files included using {@link includeCssFile()} or {@link includeCssResource()}
	 * - Any CSS included using {@link includeCss()} or {@link includeHiResCss()}
	 * - Any HTML included using {@link includeHeadHtml()}
	 *
	 * @return string
	 */
	public function getHeadHtml()
	{
		// Are there any CSS files to include?
		if (!empty($this->_cssFiles))
		{
			foreach ($this->_cssFiles as $url)
			{
				$node = HtmlHelper::encodeParams('<link rel="stylesheet" type="text/css" href="{url}"/>', array('url' => $url));
				$this->includeHeadHtml($node);
			}

			$this->_cssFiles = array();
		}

		// Is there any hi-res CSS to include?
		if (!empty($this->_hiResCss))
		{
			$this->includeCss("@media only screen and (-webkit-min-device-pixel-ratio: 1.5),\n" .
				"only screen and (   -moz-min-device-pixel-ratio: 1.5),\n" .
				"only screen and (     -o-min-device-pixel-ratio: 3/2),\n" .
				"only screen and (        min-device-pixel-ratio: 1.5),\n" .
				"only screen and (        min-resolution: 1.5dppx){\n" .
				implode("\n\n", $this->_hiResCss)."\n" .
			'}');

			$this->_hiResCss = array();
		}

		// Is there any CSS to include?
		if (!empty($this->_css))
		{
			$css = implode("\n\n", $this->_css);
			$node = "<style type=\"text/css\">\n".$css."\n</style>";
			$this->includeHeadHtml($node);

			$this->_css = array();
		}

		if (!empty($this->_headHtml))
		{
			$headNodes = implode("\n", $this->_headHtml);
			$this->_headHtml = array();
			return $headNodes;
		}
	}

	/**
	 * Returns the HTML prepared for inclusion right above the `</body>` in the template, and flushes out the foot HTML
	 * queue.
	 *
	 * This will include:
	 *
	 * - Any Javascript files included in the previous request using {@link UserSessionService::addJsResourceFlash()}
	 * - Any Javascript included in the previous request using {@link UserSessionService::addJsFlash()}
	 * - Any Javascript files included using {@link includeJsFile()} or {@link includeJsResource()}
	 * - Any Javascript code included using {@link includeJs()}
	 * - Any HTML included using {@link includeFootHtml()}
	 *
	 * @return string
	 */
	public function getFootHtml()
	{
		if (craft()->isInstalled() && craft()->request->isCpRequest())
		{
			// Include any JS/resource flashes
			foreach (craft()->userSession->getJsResourceFlashes() as $path)
			{
				$this->includeJsResource($path);
			}

			foreach (craft()->userSession->getJsFlashes() as $js)
			{
				$this->includeJs($js, true);
			}
		}

		// Are there any JS files to include?
		if (!empty($this->_jsFiles))
		{
			foreach($this->_jsFiles as $url)
			{
				$node = '<script type="text/javascript" src="'.$url.'"></script>';
				$this->includeFootHtml($node);
			}

			$this->_jsFiles = array();
		}

		// Is there any JS to include?
		foreach ($this->_jsBuffers as $buffer)
		{
			if ($buffer)
			{
				$this->includeFootHtml($this->getScriptTag($buffer));
			}
		}

		$this->_jsBuffers = array(array());

		if (!empty($this->_footHtml))
		{
			$footNodes = implode("\n", $this->_footHtml);
			$this->_footHtml = array();
			return $footNodes;
		}
	}

	/**
	 * Returns the HTML for the CSRF hidden input token.  Used for when the config setting
	 * [enableCsrfValidation](http://buildwithcraft.com/docs/config-settings#enableCsrfValidation) is set to true.
	 *
	 * @return string If 'enabledCsrfProtection' is enabled, the HTML for the hidden input, otherwise an empty string.
	 */
	public function getCsrfInput()
	{
		if (craft()->config->get('enableCsrfProtection') === true)
		{
			return '<input type="hidden" name="'.craft()->config->get('csrfTokenName').'" value="'.craft()->request->getCsrfToken().'">';
		}

		return '';
	}

	/**
	 * Prepares translations for inclusion in the template, to be used by the JS.
	 *
	 * @return null
	 */
	public function includeTranslations()
	{
		$messages = func_get_args();

		foreach ($messages as $message)
		{
			if (!array_key_exists($message, $this->_translations))
			{
				$translation = Craft::t($message);

				if ($translation != $message)
				{
					$this->_translations[$message] = $translation;
				}
				else
				{
					$this->_translations[$message] = null;
				}
			}
		}
	}

	/**
	 * Returns the translations prepared for inclusion by includeTranslations(), in JSON, and flushes out the
	 * translations queue.
	 *
	 * @return string A JSON-encoded array of source/translation message mappings.
	 *
	 * @todo Add a $json param that determines whether the returned array should be JSON-encoded (defaults to true).
	 */
	public function getTranslations()
	{
		$translations = JsonHelper::encode(array_filter($this->_translations));
		$this->_translations = array();
		return $translations;
	}

	/**
	 * Returns whether a template exists.
	 *
	 * Internally, this will just call {@link findTemplate()} with the given template name, and return whether that
	 * method found anything.
	 *
	 * @param string $name The name of the template.
	 *
	 * @return bool Whether the template exists.
	 */
	public function doesTemplateExist($name)
	{
		try
		{
			return (bool) $this->findTemplate($name);
		}
		catch (\Twig_Error_Loader $e)
		{
			// _validateTemplateName() han an issue with it
			return false;
		}
	}

	/**
	 * Finds a template on the file system and returns its path.
	 *
	 * All of the following files will be searched for, in this order:
	 *
	 * - TemplateName
	 * - TemplateName.html
	 * - TemplateName.twig
	 * - TemplateName/index.html
	 * - TemplateName/index.twig
	 *
	 * If this is a front-end request, the actual list of file extensions and index filenames are configurable via the
	 * [defaultTemplateExtensions](http://buildwithcraft.com/docs/config-settings#defaultTemplateExtensions) and
	 * [indexTemplateFilenames](http://buildwithcraft.com/docs/config-settings#indexTemplateFilenames) config settings.
	 *
	 * For example if you set the following in config/general.php:
	 *
	 * ```php
	 * 'defaultTemplateExtensions' => array('htm'),
	 * 'indexTemplateFilenames' => array('default'),
	 * ```
	 *
	 * then the following files would be searched for instead:
	 *
	 * - TemplateName
	 * - TemplateName.htm
	 * - TemplateName/default.htm
	 *
	 * The actual directory that those files will be searched for is whatever {@link PathService::getTemplatesPath()}
	 * returns (probably craft/templates/ if it’s a front-end site request, and craft/app/templates/ if it’s a Control
	 * Panel request).
	 *
	 * If this is a front-end site request, a folder named after the current locale ID will be checked first.
	 *
	 * - craft/templates/LocaleID/...
	 * - craft/templates/...
	 *
	 * And finaly, if this is a Control Panel request _and_ the template name includes multiple segments _and_ the first
	 * segment of the template name matches a plugin’s handle, then Craft will look for a template named with the
	 * remaining segments within that plugin’s templates/ subfolder.
	 *
	 * To put it all together, here’s where Craft would look for a template named “foo/bar”, depending on the type of
	 * request it is:
	 *
	 * - Front-end site requests:
	 *
	 *     - craft/templates/LocaleID/foo/bar
	 *     - craft/templates/LocaleID/foo/bar.html
	 *     - craft/templates/LocaleID/foo/bar.twig
	 *     - craft/templates/LocaleID/foo/bar/index.html
	 *     - craft/templates/LocaleID/foo/bar/index.twig
	 *     - craft/templates/foo/bar
	 *     - craft/templates/foo/bar.html
	 *     - craft/templates/foo/bar.twig
	 *     - craft/templates/foo/bar/index.html
	 *     - craft/templates/foo/bar/index.twig
	 *
	 * - Control Panel requests:
	 *
	 *     - craft/app/templates/foo/bar
	 *     - craft/app/templates/foo/bar.html
	 *     - craft/app/templates/foo/bar.twig
	 *     - craft/app/templates/foo/bar/index.html
	 *     - craft/app/templates/foo/bar/index.twig
	 *     - craft/plugins/foo/templates/bar
	 *     - craft/plugins/foo/templates/bar.html
	 *     - craft/plugins/foo/templates/bar.twig
	 *     - craft/plugins/foo/templates/bar/index.html
	 *     - craft/plugins/foo/templates/bar/index.twig
	 *
	 * @param string $name The name of the template.
	 *
	 * @return string|false The path to the template if it exists, or `false`.
	 */
	public function findTemplate($name)
	{
		// Normalize the template name
		$name = trim(preg_replace('#/{2,}#', '/', strtr($name, '\\', '/')), '/');

		// Get the latest template base path
		$templatesPath = rtrim(craft()->path->getTemplatesPath(), '/').'/';

		$key = $templatesPath.':'.$name;

		// Is this template path already cached?
		if (isset($this->_templatePaths[$key]))
		{
			return $this->_templatePaths[$key];
		}

		// Validate the template name
		$this->_validateTemplateName($name);

		// Look for the template in the main templates folder
		$basePaths = array();

		// Should we be looking for a localized version of the template?
		if (craft()->request->isSiteRequest() && IOHelper::folderExists($templatesPath.craft()->language))
		{
			$basePaths[] = $templatesPath.craft()->language.'/';
		}

		$basePaths[] = $templatesPath;

		foreach ($basePaths as $basePath)
		{
			if (($path = $this->_findTemplate($basePath, $name)) !== null)
			{
				return $this->_templatePaths[$key] = $path;
			}
		}

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (craft()->request->isCpRequest() || craft()->request->isActionRequest())
		{
			// Sanitize
			$name = craft()->request->decodePathInfo($name);

			$parts = array_filter(explode('/', $name));
			$pluginHandle = StringHelper::toLowerCase(array_shift($parts));

			if ($pluginHandle && ($plugin = craft()->plugins->getPlugin($pluginHandle)) !== null)
			{
				// Get the template path for the plugin.
				$basePath = craft()->path->getPluginsPath().StringHelper::toLowerCase($plugin->getClassHandle()).'/templates/';

				// Get the new template name to look for within the plugin's templates folder
				$tempName = implode('/', $parts);

				if (($path = $this->_findTemplate($basePath, $tempName)) !== null)
				{
					return $this->_templatePaths[$key] = $path;
				}
			}
		}

		return false;
	}

	/**
	 * Returns the active namespace.
	 *
	 * This is the default namespaces that will be used when {@link namespaceInputs()}, {@link namespaceInputName()},
	 * and {@link namespaceInputId()} are called, if their $namespace arguments are null.
	 *
	 * @return string The namespace.
	 */
	public function getNamespace()
	{
		return $this->_namespace;
	}

	/**
	 * Sets the active namespace.
	 *
	 * This is the default namespaces that will be used when {@link namespaceInputs()}, {@link namespaceInputName()},
	 * and {@link namespaceInputId()} are called, if their $namespace arguments are null.
	 *
	 * @param string $namespace The new namespace.
	 *
	 * @return null
	 */
	public function setNamespace($namespace)
	{
		$this->_namespace = $namespace;
	}

	/**
	 * Renames HTML input names so they belong to a namespace.
	 *
	 * This method will go through the passed-in $html looking for `name=` attributes, and renaming their values such
	 * that they will live within the passed-in $namespace (or the {@link getNamespace() active namespace}).
	 *
	 * By default, any `id=`, `for=`, `list=`, `data-target=`, `data-reverse-target=`, and `data-target-prefix=`
	 * attributes will get namespaced as well, by prepending the namespace and a dash to their values.
	 *
	 * For example, the following HTML:
	 *
	 * ```markup
	 * <label for="title">Title</label>
	 * <input type="text" name="title" id="title">
	 * ```
	 *
	 * would become this, if it were namespaced with “foo”:
	 *
	 * ```markup
	 * <label for="foo-title">Title</label>
	 * <input type="text" name="foo[title]" id="foo-title">
	 * ```
	 *
	 * Attributes that are already namespaced will get double-namespaced. For example, the following HTML:
	 *
	 * ```markup
	 * <label for="bar-title">Title</label>
	 * <input type="text" name="bar[title]" id="title">
	 * ```
	 *
	 * would become:
	 *
	 * ```markup
	 * <label for="foo-bar-title">Title</label>
	 * <input type="text" name="foo[bar][title]" id="foo-bar-title">
	 * ```
	 *
	 * @param string $html            The template with the inputs.
	 * @param string $namespace       The namespace. Defaults to the {@link getNamespace() active namespace}.
	 * @param bool   $otherAttributes Whether id=, for=, etc., should also be namespaced. Defaults to `true`.
	 *
	 * @return string The HTML with namespaced input names.
	 */
	public function namespaceInputs($html, $namespace = null, $otherAttributes = true)
	{
		if ($namespace === null)
		{
			$namespace = $this->getNamespace();
		}

		if ($namespace)
		{
			// Protect the textarea content
			$this->_textareaMarkers = array();
			$html = preg_replace_callback('/(<textarea\b[^>]*>)(.*?)(<\/textarea>)/is', array($this, '_createTextareaMarker'), $html);

			// name= attributes
			$html = preg_replace('/(?<![\w\-])(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $html);

			// id= and for= attributes
			if ($otherAttributes)
			{
				$idNamespace = $this->formatInputId($namespace);
				$html = preg_replace('/(?<![\w\-])((id|for|list|data\-target|data\-reverse\-target|data\-target\-prefix)=(\'|")#?)([^\.\'"][^\'"]*)\3/i', '$1'.$idNamespace.'-$4$3', $html);
			}

			// Bring back the textarea content
			$html = str_replace(array_keys($this->_textareaMarkers), array_values($this->_textareaMarkers), $html);
		}

		return $html;
	}

	/**
	 * Namespaces an input name.
	 *
	 * This method applies the same namespacing treatment that {@link namespaceInputs()} does to `name=` attributes,
	 * but only to a single value, which is passed directly into this method.
	 *
	 * @param string $inputName The input name that should be namespaced.
	 * @param string $namespace The namespace. Defaults to the {@link getNamespace() active namespace}.
	 *
	 * @return string The namespaced input name.
	 */
	public function namespaceInputName($inputName, $namespace = null)
	{
		if ($namespace === null)
		{
			$namespace = $this->getNamespace();
		}

		if ($namespace)
		{
			$inputName = preg_replace('/([^\'"\[\]]+)([^\'"]*)/', $namespace.'[$1]$2', $inputName);
		}

		return $inputName;
	}

	/**
	 * Namespaces an input ID.
	 *
	 * This method applies the same namespacing treatment that {@link namespaceInputs()} does to `id=` attributes,
	 * but only to a single value, which is passed directly into this method.
	 *
	 * @param string $inputId   The input ID that should be namespaced.
	 * @param string $namespace The namespace. Defaults to the {@link getNamespace() active namespace}.
	 *
	 * @return string The namespaced input ID.
	 */
	public function namespaceInputId($inputId, $namespace = null)
	{
		if ($namespace === null)
		{
			$namespace = $this->getNamespace();
		}

		if ($namespace)
		{
			$inputId = $this->formatInputId($namespace).'-'.$inputId;
		}

		return $inputId;
	}

	/**
	 * Formats an ID out of an input name.
	 *
	 * This method takes a given input name and returns a valid ID based on it.
	 *
	 * For example, if given the following input name:
	 *
	 *     foo[bar][title]
	 *
	 * the following ID would be returned:
	 *
	 *     foo-bar-title
	 *
	 * @param string $inputName The input name.
	 *
	 * @return string The input ID.
	 */
	public function formatInputId($inputName)
	{
		return rtrim(preg_replace('/[\[\]]+/', '-', $inputName), '-');
	}

	/**
	 * Queues up a method to be called by a given template hook.
	 *
	 * For example, if you place this in your plugin’s {@link BasePlugin::init() init()} method:
	 *
	 * ```php
	 * craft()->templates->hook('myAwesomeHook', function(&$context)
	 * {
	 *     $context['foo'] = 'bar';
	 *
	 *     return 'Hey!';
	 * });
	 * ```
	 *
	 * you would then be able to add this to any template:
	 *
	 * ```twig
	 * {% hook "myAwesomeHook" %}
	 * ```
	 *
	 * When the hook tag gets invoked, your template hook function will get called. The $context argument will be the
	 * current Twig context array, which you’re free to manipulate. Any changes you make to it will be available to the
	 * template following the tag. Whatever your template hook function returns will be output in place of the tag in
	 * the template as well.
	 *
	 * @param string   $hook   The hook name.
	 * @param callback $method The callback function.
	 *
	 * @return null
	 */
	public function hook($hook, $method)
	{
		$this->_hooks[$hook][] = $method;
	}

	/**
	 * Invokes a template hook.
	 *
	 * This is called by {@link Hook_Node `{% hook %}` tags).
	 *
	 * @param string $hook     The hook name.
	 * @param array  &$context The current template context.
	 *
	 * @return string Whatever the hooks returned.
	 */
	public function invokeHook($hook, &$context)
	{
		$return = '';

		if (isset($this->_hooks[$hook]))
		{
			foreach ($this->_hooks[$hook] as $method)
			{
				$return .= call_user_func_array($method, array(&$context));
			}
		}

		return $return;
	}

	/**
	 * Loads plugin-supplied Twig extensions now that all plugins have been loaded.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onPluginsLoaded(Event $event)
	{
		foreach ($this->_twigs as $twig)
		{
			$this->_addPluginTwigExtensions($twig);
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the Twig environment options
	 *
	 * @return array
	 */
	private function _getTwigOptions()
	{
		if (!isset($this->_twigOptions))
		{
			$this->_twigOptions = array(
				'base_template_class' => 'Craft\BaseTemplate',
				'cache'               => craft()->path->getCompiledTemplatesPath(),
				'auto_reload'         => true,
			);

			if (craft()->config->get('devMode'))
			{
				$this->_twigOptions['debug'] = true;
				$this->_twigOptions['strict_variables'] = true;
			}
		}

		return $this->_twigOptions;
	}

	/**
	 * Ensures that a template name isn't null, and that it doesn't lead outside the template folder. Borrowed from
	 * {@link Twig_Loader_Filesystem}.
	 *
	 * @param string $name
	 *
	 * @throws \Twig_Error_Loader
	 */
	private function _validateTemplateName($name)
	{
		if (mb_strpos($name, "\0") !== false)
		{
			throw new \Twig_Error_Loader(Craft::t('A template name cannot contain NUL bytes.'));
		}

		if (PathHelper::ensurePathIsContained($name) === false)
		{
			throw new \Twig_Error_Loader(Craft::t('Looks like you try to load a template outside the template folder: {template}.', array('template' => $name)));
		}
	}

	/**
	 * Searches for a template files, and returns the first match if there is one.
	 *
	 * @param string $basePath The base path to be looking in.
	 * @param string $name     The name of the template to be looking for.
	 *
	 * @return string|null The matching file path, or `null`.
	 */
	private function _findTemplate($basePath, $name)
	{
		// Normalize the path and name
		$basePath = rtrim(IOHelper::normalizePathSeparators($basePath), '/').'/';
		$name = trim(IOHelper::normalizePathSeparators($name), '/');

		// Set the defaultTemplateExtensions and indexTemplateFilenames vars
		if (!isset($this->_defaultTemplateExtensions))
		{
			if (craft()->request->isCpRequest())
			{
				$this->_defaultTemplateExtensions = array('html', 'twig');
				$this->_indexTemplateFilenames = array('index');
			}
			else
			{
				$this->_defaultTemplateExtensions = craft()->config->get('defaultTemplateExtensions');
				$this->_indexTemplateFilenames = craft()->config->get('indexTemplateFilenames');
			}
		}

		// $name could be an empty string (e.g. to load the homepage template)
		if ($name)
		{
			// Maybe $name is already the full file path
			$testPath = $basePath.$name;

			if (IOHelper::fileExists($testPath))
			{
				return $testPath;
			}

			foreach ($this->_defaultTemplateExtensions as $extension)
			{
				$testPath = $basePath.$name.'.'.$extension;

				if (IOHelper::fileExists($testPath))
				{
					return $testPath;
				}
			}
		}

		foreach ($this->_indexTemplateFilenames as $filename)
		{
			foreach ($this->_defaultTemplateExtensions as $extension)
			{
				$testPath = $basePath.($name ? $name.'/' : '').$filename.'.'.$extension;

				if (IOHelper::fileExists($testPath))
				{
					return $testPath;
				}
			}
		}
	}

	/**
	 * Adds any plugin-supplied Twig extensions to a given Twig instance.
	 *
	 * @param \Twig_Environment $twig
	 *
	 * @return null
	 */
	private function _addPluginTwigExtensions(\Twig_Environment $twig)
	{
		// Check if the PluginsService has been loaded yet
		$pluginsService = craft()->getComponent('plugins', false);

		if (!$pluginsService)
		{
			// It hasn't, so let's load it and call loadPlugins()
			$pluginsService = craft()->getComponent('plugins');
			$pluginsService->loadPlugins();
		}

		// Make sure that loadPlugins() has finished
		if ($pluginsService->arePluginsLoaded())
		{
			$pluginExtensions = $pluginsService->call('addTwigExtension');

			try
			{
				foreach ($pluginExtensions as $extension)
				{
					$twig->addExtension($extension);
				}
			}
			catch (\LogicException $e)
			{
				Craft::log('Tried to register plugin-supplied Twig extensions, but Twig environment has already initialized its extensions.', LogLevel::Warning);
				return;
			}
		}
		else
		{
			// Wait around for plugins to actually be loaded, then do it for all Twig environments that have been created.
			craft()->on('plugins.loadPlugins', array($this, 'onPluginsLoaded'));
		}
	}

	/**
	 * Replaces textarea contents with a marker.
	 *
	 * @param array $matches
	 *
	 * @return string
	 */
	private function _createTextareaMarker($matches)
	{
		$marker = '{marker:'.StringHelper::randomString().'}';
		$this->_textareaMarkers[$marker] = $matches[2];
		return $matches[1].$marker.$matches[3];
	}

	/**
	 * Combines the JS in a buffer.
	 *
	 * @param string $js
	 *
	 * @return string
	 */
	private function _combineJs($js)
	{
		return implode("\n\n", $js);
	}

	/**
	 * Returns the HTML for an element in the CP.
	 *
	 * @param array &$context
	 *
	 * @return string
	 */
	private function _getCpElementHtml(&$context)
	{
		if (!isset($context['element']))
		{
			return;
		}

		if (!isset($context['context']))
		{
			$context['context'] = 'index';
		}

		if (!isset($context['viewMode']))
		{
			$context['viewMode'] = 'table';
		}

		$thumbClass = 'elementthumb'.$context['element']->id;
		$iconClass = 'elementicon'.$context['element']->id;

		if ($context['viewMode'] == 'thumbs')
		{
			$thumbSize = 100;
			$iconSize = 90;
			$thumbSelectorPrefix = '.thumbsview ';
		}
		else
		{
			$thumbSize = 30;
			$iconSize = 20;
			$thumbSelectorPrefix = '';
		}

		$thumbUrl = $context['element']->getThumbUrl($thumbSize);

		if ($thumbUrl)
		{
			$this->includeCss($thumbSelectorPrefix.'.'.$thumbClass." { background-image: url('".$thumbUrl."'); }");
			$this->includeHiResCss($thumbSelectorPrefix.'.'.$thumbClass." { background-image: url('".$context['element']->getThumbUrl($thumbSize * 2)."'); background-size: ".$thumbSize.'px; }');
		}
		else
		{
			$iconUrl = $context['element']->getIconUrl($iconSize);

			if ($iconUrl)
			{
				$this->includeCss($thumbSelectorPrefix.'.'.$iconClass." { background-image: url('".$iconUrl."'); }");
				$this->includeHiResCss($thumbSelectorPrefix.'.'.$iconClass." { background-image: url('".$context['element']->getIconUrl($iconSize * 2)."); background-size: ".$iconSize.'px; }');
			}
		}

		$html = '<div class="element';

		if ($context['context'] == 'field')
		{
			$html .= ' removable';
		}

		if ($thumbUrl)
		{
			$html .= ' hasthumb';
		}
		else if ($iconUrl)
		{
			$html .= ' hasicon';
		}

		$label = $context['element'];

		$html .= '" data-id="'.$context['element']->id.'" data-locale="'.$context['element']->locale.'" data-status="'.$context['element']->getStatus().'" data-label="'.$label.'" data-url="'.$context['element']->getUrl().'"';

		if ($context['element']->level)
		{
			$html .= ' data-level="'.$context['element']->level.'"';
		}

		$isEditable = ElementHelper::isElementEditable($context['element']);

		if ($isEditable)
		{
			$html .= ' data-editable';
		}

		$html .= '>';

		if ($context['context'] == 'field' && isset($context['name']))
		{
			$html .= '<input type="hidden" name="'.$context['name'].'[]" value="'.$context['element']->id.'">';
			$html .= '<a class="delete icon" title="'.Craft::t('Remove').'"></a> ';
		}

		if ($thumbUrl)
		{
			$html .= '<div class="elementthumb '.$thumbClass.'"></div> ';
		}
		else if ($iconUrl)
		{
			$html .= '<div class="elementicon '.$iconClass.'"></div> ';
		}

		$html .= '<div class="label">';

		if (isset($context['elementType']))
		{
			$elementType = $context['elementType'];
		}
		else
		{
			$elementType = craft()->elements->getElementType($context['element']->getElementType());
		}

		if ($elementType->hasStatuses())
		{
			$html .= '<span class="status '.$context['element']->getStatus().'"></span>';
		}

		$html .= '<span class="title">';

		if ($context['context'] == 'index' && ($cpEditUrl = $context['element']->getCpEditUrl()))
		{
			$html .= HtmlHelper::encodeParams('<a href="{cpEditUrl}">{label}</a>', array('cpEditUrl' => $cpEditUrl, 'label' => $label));
		}
		else
		{
			$html .= HtmlHelper::encode($label);
		}

		$html .= '</span></div></div>';

		return $html;
	}
}
