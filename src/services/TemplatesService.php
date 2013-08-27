<?php
namespace Craft;

/**
 *
 */
class TemplatesService extends BaseApplicationComponent
{
	private $_twigs;
	private $_twigOptions;
	private $_templatePaths;
	private $_objectTemplates;

	private $_defaultTemplateExtensions;
	private $_indexTemplateFilenames;

	private $_headHtml = array();
	private $_footHtml = array();
	private $_cssFiles = array();
	private $_jsFiles = array();
	private $_css = array();
	private $_hiResCss = array();
	private $_js = array();
	private $_translations = array();

	/**
	 * Registers the Twig autoloader.
	 */
	public function registerTwigAutoloader()
	{
		if (!class_exists('\Twig_Autoloader', false))
		{
			require_once craft()->path->getLibPath().'Twig/Autoloader.php';
			Craft::registerAutoloader(array(new \Twig_Autoloader, 'autoload'));
		}
	}

	/**
	 * Gets the Twig instance.
	 *
	 * @param  string            $loaderClass The template loader class to use with the environment.
	 * @return \Twig_Environment
	 */
	public function getTwig($loaderClass = null)
	{
		if (!$loaderClass)
		{
			$loaderClass = __NAMESPACE__.'\\TemplateLoader';
		}

		if (!isset($this->_twigs[$loaderClass]))
		{
			// Is this the first Twig instance EVER?
			if (!isset($this->_twigs))
			{
				$this->registerTwigAutoloader();
			}

			$loader = new $loaderClass();
			$options = $this->_getTwigOptions();

			$twig = new \Twig_Environment($loader, $options);

			$twig->addExtension(new \Twig_Extension_StringLoader());
			$twig->addExtension(new CraftTwigExtension());

			if (craft()->config->get('devMode'))
			{
				$twig->addExtension(new \Twig_Extension_Debug());
			}

			// Give plugins a chance to add their own Twig extensions

			// All plugins may not have been loaded yet if an exception is being thrown
			// or a plugin is loading a template as part of of its init() function.
			if (craft()->plugins->arePluginsLoaded())
			{
				$pluginExtensions = craft()->plugins->call('addTwigExtension');

				foreach ($pluginExtensions as $extension)
				{
					$twig->addExtension($extension);
				}
			}
			else
			{
				// Wait around for plugins to actually be loaded,
				// then do it for all Twig environments that have been created.
				craft()->on('plugins.loadPlugins', array($this, '_onPluginsLoaded'));
			}

			$this->_twigs[$loaderClass] = $twig;
		}

		return $this->_twigs[$loaderClass];
	}

	/**
	 * Renders a template.
	 *
	 * @param  string $template The name of the template to load, or a StringTemplate object
	 * @param  array  $variables The variables that should be available to the template
	 * @return string            The rendered template
	 */
	public function render($template, $variables = array())
	{
		$twig = $this->getTwig();
		return $twig->render($template, $variables);
	}

	/**
	 * Renders a macro within a given template.
	 *
	 * @param  string $template
	 * @param  string $macro
	 * @param  array  $args
	 * @return string
	 */
	public function renderMacro($template, $macro, $args = array())
	{
		$twig = $this->getTwig();
		$twigTemplate = $twig->loadTemplate($template);
		return call_user_func_array(array($twigTemplate, 'get'.$macro), $args);
	}

	/**
	 * Renders a template string.
	 *
	 * @param  string $template  The source template string
	 * @param  array  $variables The variables that should be available to the template
	 * @return string            The rendered template
	 */
	public function renderString($template, $variables = array())
	{
		$stringTemplate = new StringTemplate(md5($template), $template);
		return $this->render($stringTemplate, $variables);
	}

	/**
	 * Renders a micro template for accessing properties of a single object.
	 *
	 * @param string $template
	 * @param mixed $object
	 * @return string
	 */
	public function renderObjectTemplate($template, $object)
	{
		// Get a Twig instance with the String template loader
		$twig = $this->getTwig('Twig_Loader_String');

		// Have we already parsed this template?
		if (!isset($this->_objectTemplates[$template]))
		{
			$formattedTemplate = str_replace(array('{', '}'), array('{{object.', '}}'), $template);
			$this->_objectTemplates[$template] = $twig->loadTemplate($formattedTemplate);
		}

		// Temporarily disable strict variables if it's enabled
		$strictVariables = $twig->isStrictVariables();
		if ($strictVariables)
		{
			$twig->disableStrictVariables();
		}

		// Render it!
		$return = $this->_objectTemplates[$template]->render(array(
			'object' => $object
		));

		// Re-enable strict variables
		if ($strictVariables)
		{
			$twig->enableStrictVariables();
		}

		return $return;
	}


	/**
	 * Prepares some HTML for inclusion in the <head> of the template.
	 *
	 * @param string    $node
	 * @param bool|null $first
	 */
	public function includeHeadHtml($node, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_headHtml, $node, $first);
	}

	/**
	 * Prepares an HTML node for inclusion right above the </body> in the template.
	 *
	 * @param string    $node
	 * @param bool|null $first
	 */
	public function includeFootHtml($node, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_footHtml, $node, $first);
	}

	/**
	 * Prepares some HTML for inclusion in the <head> of the template.
	 *
	 * @param string    $node
	 * @param bool|null $first
	 * @deprecated Deprecated since 1.1
	 */
	public function includeHeadNode($node, $first = false)
	{
		Craft::log('The craft()->templates->includeHeadNode() method has been deprecated. Use craft()->templates->includeHeadHtml() instead.', LogLevel::Warning);
		$this->includeHeadHtml($node, $first);
	}

	/**
	 * Prepares an HTML node for inclusion right above the </body> in the template.
	 *
	 * @param string    $node
	 * @param bool|null $first
	 * @deprecated Deprecated since 1.1
	 */
	public function includeFootNode($node, $first = false)
	{
		Craft::log('The craft()->templates->includeFootNode() method has been deprecated. Use craft()->templates->includeFootHtml() instead.', LogLevel::Warning);
		$this->includeFootHtml($node, $first);
	}

	/**
	 * Prepares a CSS file for inclusion in the template.
	 *
	 * @param string    $url
	 * @param bool|null $first
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
	 * @param string    $url
	 * @param bool|null $first
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
	 * @param string    $path
	 * @param bool|null $first
	 */
	public function includeCssResource($path, $first = false)
	{
		$url = UrlHelper::getResourceUrl($path);
		$this->includeCssFile($url, $first);
	}

	/**
	 * Prepares a JS file from resources/ for inclusion in the template.
	 *
	 * @param string    $path
	 * @param bool|null $first
	 */
	public function includeJsResource($path, $first = false)
	{
		$url = UrlHelper::getResourceUrl($path);
		$this->includeJsFile($url, $first);
	}

	/**
	 * Prepares CSS for inclusion in the template.
	 *
	 * @param string    $css
	 * @param bool|null $first
	 * @return void
	 */
	public function includeCss($css, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_css, trim($css), $first);
	}

	/**
	 * Prepares Hi-res targetted CSS for inclusion in the template.
	 *
	 * @param string    $css
	 * @param bool|null $first
	 * @return void
	 */
	public function includeHiResCss($css, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_hiResCss, trim($css), $first);
	}

	/**
	 * Prepares JS for inclusion in the template.
	 *
	 * @param           $js
	 * @param bool|null $first
	 * @return void
	 */
	public function includeJs($js, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_js, trim($js), $first);
	}

	/**
	 * Returns the nodes prepared for inclusion in the <head> of the template,
	 * and flushes out the head nodes queue.
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
				$node = '<link rel="stylesheet" type="text/css" href="'.$url.'"/>';
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
	 * Returns the nodes prepared for inclusion right above the </body> in the template,
	 * and flushes out the foot nodes queue.
	 *
	 * @return string
	 */
	public function getFootHtml()
	{
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
		if (!empty($this->_js))
		{
			$js = implode("\n\n", $this->_js);
			$node = "<script type=\"text/javascript\">\n/*<![CDATA[*/\n".$js."\n/*]]>*/\n</script>";
			$this->includeFootHtml($node);

			$this->_js = array();
		}

		if (!empty($this->_footHtml))
		{
			$footNodes = implode("\n", $this->_footHtml);
			$this->_footHtml = array();
			return $footNodes;
		}
	}

	/**
	 * Prepares translations for inclusion in the template, to be used by the JS.
	 *
	 * @return void
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
	 * Returns the translations prepared for inclusion by includeTranslations(), in JSON,
	 * and flushes out the translations queue.
	 *
	 * @return string
	 */
	public function getTranslations()
	{
		$translations = JsonHelper::encode(array_filter($this->_translations));
		$this->_translations = array();
		return $translations;
	}

	/**
	 * Finds a template on the file system and returns its path.
	 *
	 * @param string $name
	 * @throws TemplateLoaderException
	 * @return string
	 */
	public function findTemplate($name)
	{
		// Normalize the template name
		$name = trim(preg_replace('#/{2,}#', '/', strtr($name, '\\', '/')), '/');

		// Is this template path already cached?
		if (isset($this->_templatePaths[$name]))
		{
			return $this->_templatePaths[$name];
		}

		// Validate the template name
		$this->_validateTemplateName($name);

		// Check if the template exists in the main templates path

		// Set the view path
		//  - We need to set this for each template request, in case it was changed to a plugin's template path
		$basePath = craft()->path->getTemplatesPath();

		if (($path = $this->_findTemplate($basePath.$name)) !== null)
		{
			return $this->_templatePaths[$name] = $path;
		}

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (craft()->request->isCpRequest() || craft()->request->isActionRequest())
		{
			// Sanitize
			$name = craft()->request->decodePathInfo($name);

			$parts = array_filter(explode('/', $name));
			$pluginHandle = strtolower(array_shift($parts));

			if ($pluginHandle && ($plugin = craft()->plugins->getPlugin($pluginHandle)) !== null)
			{
				// Get the template path for the plugin.
				$basePath = craft()->path->getPluginsPath().strtolower($plugin->getClassHandle()).'/templates/';

				// Chop off the plugin segment, since that's already covered by $basePath
				$tempName = implode('/', $parts);

				if (($path = $this->_findTemplate($basePath.$tempName)) !== null)
				{
					return $this->_templatePaths[$name] = $path;
				}
			}
		}

		if (!isset($this->_twigs))
		{
			$this->registerTwigAutoloader();
		}

		throw new TemplateLoaderException($name);
	}

	/**
	 * Renames input names so they belong to a namespace.
	 *
	 * @param string $html The template with the inputs
	 * @param string $namespace The namespace to make inputs belong to
	 * @param bool $otherAttributes Whether id=, for=, etc., should also be namespaced. Defaults to true.
	 * @return string The template with namespaced inputs
	 */
	public function namespaceInputs($html, $namespace, $otherAttributes = true)
	{
		// name= attributes
		$html = preg_replace('/(?<![\w\-])(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $html);

		// id= and for= attributes
		if ($otherAttributes)
		{
			$idNamespace = rtrim(preg_replace('/[\[\]]+/', '-', $namespace), '-');
			$html = preg_replace('/(?<![\w\-])((id=|for=|data\-target=|data-target-prefix=)(\'|"))([^\'"]+)\3/', '$1'.$idNamespace.'-$4$3', $html);
		}

		return $html;
	}

	/**
	 * Returns the Twig environment options
	 *
	 * @access private
	 * @return array
	 */
	private function _getTwigOptions()
	{
		if (!isset($this->_twigOptions))
		{
			$this->_twigOptions = array(
				'cache'       => craft()->path->getCompiledTemplatesPath(),
				'auto_reload' => true,
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
	 * Ensures that a template name isn't null, and that it doesn't lead outside the template folder.
	 * Borrowed from Twig_Loader_Filesystem.
	 *
	 * @access private
	 * @param string $name
	 * @throws \Twig_Error_Loader
	 */
	private function _validateTemplateName($name)
	{
		if (strpos($name, "\0") !== false)
		{
			throw new \Twig_Error_Loader(Craft::t('A template name cannot contain NUL bytes.'));
		}

		if (PathHelper::ensurePathIsContained($name) === false)
		{
			throw new \Twig_Error_Loader(Craft::t('Looks like you try to load a template outside the template folder: {template}.', array('template' => $name)));
		}
	}

	/**
	 * Searches for localized template files, and returns the first match if there is one.
	 *
	 * @access private
	 * @param string $path
	 * @return mixed
	 */
	private function _findTemplate($path)
	{
		// Get the extension on the path, if there is one
		$extension = IOHelper::getExtension($path);

		$path = rtrim(IOHelper::normalizePathSeparators($path), '/');

		if ($extension)
		{
			$testPaths = array($path);
		}
		else
		{
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

			$testPaths = array();

			foreach ($this->_defaultTemplateExtensions as $extension)
			{
				$testPaths[] = $path.'.'.$extension;
			}

			foreach ($this->_indexTemplateFilenames as $filename)
			{
				foreach ($this->_defaultTemplateExtensions as $extension)
				{
					$testPaths[] = $path.'/'.$filename.'.'.$extension;
				}
			}
		}

		foreach ($testPaths as $path)
		{
			if (IOHelper::fileExists(craft()->findLocalizedFile($path)))
			{
				return $path;
			}
		}

		return null;
	}

	/**
	 * Loads plugin-supplied Twig extensions now that all plugins have been loaded.
	 *
	 * @access private
	 * @param Event $event
	 */
	public function _onPluginsLoaded(Event $event)
	{
		$pluginExtensions = craft()->plugins->call('addTwigExtension');

		foreach ($this->_twigs as $twig)
		{
			foreach ($pluginExtensions as $extension)
			{
				$twig->addExtension($extension);
			}
		}
	}
}
