<?php
namespace Blocks;

/**
 *
 */
class TemplatesService extends BaseApplicationComponent
{
	private $_twig;
	private $_templatePaths;

	private $_headNodes = array();
	private $_footNodes = array();
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
			require_once blx()->path->getLibPath().'Twig/Autoloader.php';
			Blocks::registerAutoloader(array(new \Twig_Autoloader, 'autoload'));
		}
	}

	/**
	 * Gets the Twig instance.
	 *
	 * @return \Twig_Environment
	 */
	public function getTwig()
	{
		if (!isset($this->_twig))
		{
			$this->registerTwigAutoloader();

			$loader = new TemplateLoader();

			$options['cache'] = blx()->path->getCompiledTemplatesPath();
			$options['auto_reload'] = true;

			if (blx()->config->get('devMode'))
			{
				$options['debug'] = true;
				$options['strict_variables'] = true;
			}

			$twig = new \Twig_Environment($loader, $options);
			$twig->addExtension(new BlocksTwigExtension());

			if (blx()->config->get('devMode'))
			{
				$twig->addExtension(new \Twig_Extension_Debug());
			}

			// Give plugins a chance to add their own Twig extensions
			$pluginExtensions = blx()->plugins->callHook('addTwigExtension');
			foreach ($pluginExtensions as $extension)
			{
				$twig->addExtension($extension);
			}

			$this->_twig = $twig;
		}

		return $this->_twig;
	}

	/**
	 * Renders a template.
	 *
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @param array $variables The variables that should be available to the template
	 * @return string The rendered template
	 */
	public function render($template, $variables = array())
	{
		$twig = $this->getTwig();
		return $twig->render($template, $variables);
	}

	/**
	 * Renders a template string.
	 *
	 * @param string $cacheKey A unique key for the template
	 * @param string $template The source template string
	 * @param array $variables The variables that should be available to the template
	 * @return string The rendered template
	 */
	public function renderString($cacheKey, $template, $variables = array())
	{
		$stringTemplate = new StringTemplate($cacheKey, $template);
		return $this->render($stringTemplate, $variables);
	}

	/**
	 * Prepares an HTML node for inclusion in the <head> of the template.
	 *
	 * @param string    $node
	 * @param bool|null $first
	 */
	public function includeHeadNode($node, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_headNodes, $node, $first);
	}

	/**
	 * Prepares an HTML node for inclusion right above the </body> in the template.
	 *
	 * @param string    $node
	 * @param bool|null $first
	 */
	public function includeFootNode($node, $first = false)
	{
		ArrayHelper::prependOrAppend($this->_footNodes, $node, $first);
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
		foreach($this->_cssFiles as $url)
		{
			$node = '<link rel="stylesheet" type="text/css" href="'.$url.'"/>';
			$this->includeHeadNode($node);
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
		}

		// Is there any CSS to include?
		if (!empty($this->_css))
		{
			$css = implode("\n\n", $this->_css);
			$node = "<style type=\"text/css\">\n".$css."\n</style>";
			$this->includeHeadNode($node);
		}

		if (!empty($this->_headNodes))
		{
			$headNodes = implode("\n", $this->_headNodes);
			$this->_headNodes = array();
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
		foreach($this->_jsFiles as $url)
		{
			$node = '<script type="text/javascript" src="'.$url.'"></script>';
			$this->includeFootNode($node);
		}

		// Is there any JS to include?
		if (!empty($this->_js))
		{
			$js = implode("\n\n", $this->_js);
			$node = "<script type=\"text/javascript\">\n/*<![CDATA[*/\n".$js."\n/*]]>*/\n</script>";
			$this->includeFootNode($node);
		}

		if (!empty($this->_footNodes))
		{
			$footNodes = implode("\n", $this->_footNodes);
			$this->_footNodes = array();
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
				$translation = Blocks::t($message);

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
		$basePath = blx()->path->getTemplatesPath();

		if (($path = $this->_findTemplate($basePath.$name)) !== null)
		{
			return $this->_templatePaths[$name] = $path;
		}

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (blx()->request->isCpRequest() || blx()->request->isActionRequest())
		{
			$parts = array_filter(explode('/', $name));
			$pluginHandle = strtolower(array_shift($parts));

			if ($pluginHandle && ($plugin = blx()->plugins->getPlugin($pluginHandle)) !== null)
			{
				// Get the template path for the plugin.
				$basePath = blx()->path->getPluginsPath().strtolower($plugin->getClassHandle()).'/templates/';

				// Chop off the plugin segment, since that's already covered by $basePath
				$tempName = implode('/', $parts);

				if (($path = $this->_findTemplate($basePath.$tempName)) !== null)
				{
					return $this->_templatePaths[$name] = $path;
				}
			}
		}

		throw new TemplateLoaderException($name);
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
			throw new \Twig_Error_Loader(Blocks::t('A template name cannot contain NUL bytes.'));
		}

		if (PathHelper::ensurePathIsContained($name) === false)
		{
			throw new \Twig_Error_Loader(Blocks::t('Looks like you try to load a template outside the template folder: {template}.', array('template' => $name)));
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
			$testPaths = array($path.'.html', $path.'/index.html');
		}

		foreach ($testPaths as $path)
		{
			if (IOHelper::fileExists(blx()->findLocalizedFile($path)))
			{
				return $path;
			}
		}

		return null;
	}

	/**
	 * Renames input names so they belong to a namespace.
	 *
	 * @param string $html The template with the inputs
	 * @param string $namespace The namespace to make inputs belong to
	 * @return string The template with namespaced inputs
	 */
	public function namespaceInputs($html, $namespace)
	{
		// name= attributes
		$html = preg_replace('/(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $html);

		// id= and for= attributes
		$idNamespace = rtrim(preg_replace('/[\[\]]+/', '-', $namespace), '-');
		$html = preg_replace('/((id=|for=|data\-target=|data-target-prefix=)(\'|"))([^\'"]+)\3/', '$1'.$idNamespace.'-$4$3', $html);

		return $html;
	}
}
