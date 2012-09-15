<?php
namespace Blocks;

/**
 *
 */
class TemplatesService extends BaseApplicationComponent
{
	private $_twig;
	private $_templatePaths;

	private $_headNodes;
	private $_footNodes;
	private $_css;
	private $_js;

	/**
	 * Registers the Twig autoloader.
	 */
	public function registerTwigAutoloader()
	{
		if (!class_exists('\Twig_Autoloader', false))
		{
			require_once blx()->path->getLibPath().'Twig/Autoloader.php';
			Blocks::registerAutoloader(array(new \Twig_Autoloader, 'autoload'), true);
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

			$twig = new \Twig_Environment($loader, array(
				'debug'               => blx()->config->devMode,
				//'base_template_class' => '\Blocks\BaseTemplate',
				'cache'               => blx()->path->getCompiledTemplatesPath(),
				'auto_reload'         => true,
				//'strict_variables'  => true,
			));

			$twig->addExtension(new BlocksTwigExtension());

			if (blx()->config->devMode)
			{
				$twig->addExtension(new \Twig_Extension_Debug());
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

		//try
		//{
			return $twig->render($template, $variables);
		//}
		//catch (\Twig_Error_Syntax $e)
		//{
		//
		//}
	}

	/**
	 * Renders a template string.
	 *
	 * @param string $cacheKey A unique key for the template
	 * @param string $template The source template string
	 * @param array $variables The variables that should be available to the template
	 * @return string The rendered template
	 */
	public function renderString($cacheKey, $template, $variables)
	{
		$stringTemplate = new StringTemplate($cacheKey, $template);
		return $this->render($stringTemplate, $variables);
	}

	/**
	 * Prepares an HTML node for inclusion in the <head> of the template.
	 *
	 * @param string $node
	 */
	public function includeHeadNode($node)
	{
		$this->_headNodes[] = $node;
	}

	/**
	 * Prepares an HTML node for inclusion right above the </body> in the template.
	 *
	 * @param string $node
	 */
	public function includeFootNode($node)
	{
		$this->_footNodes[] = $node;
	}

	/**
	 * Prepares a CSS file for inclusion in the template.
	 *
	 * @param string $url
	 */
	public function includeCssFile($url)
	{
		$node = '<link rel="stylesheet" type="text/css" href="'.$url.'"/>';
		$this->includeHeadNode($node);
	}

	/**
	 * Prepares a JS file for inclusion in the template.
	 *
	 * @param string $url
	 */
	public function includeJsFile($url)
	{
		$node = '<script type="text/javascript" src="'.$url.'"></script>';
		$this->includeFootNode($node);
	}

	/**
	 * Prepares a CSS file from resources/ for inclusion in the template.
	 *
	 * @param string      $path
	 * @param string|null $plugin
	 */
	public function includeCssResource($path, $plugin = null)
	{
		$url = UrlHelper::getResourceUrl(($plugin ? $plugin.'/' : '').'css/'.$path);
		$this->includeCssFile($url);
	}

	/**
	 * Prepares a JS file from resources/ for inclusion in the template.
	 *
	 * @param string      $path
	 * @param string|null $plugin
	 */
	public function includeJsResource($path, $plugin = null)
	{
		$url = UrlHelper::getResourceUrl(($plugin ? $plugin.'/' : '').'js/'.$path);
		$this->includeJsFile($url);
	}

	/**
	 * Prepares CSS for inclusion in the template.
	 *
	 * @param string $url
	 */
	public function includeCss($css)
	{
		$this->_css[] = trim($css);
	}

	/**
	 * Prepares JS for inclusion in the template.
	 *
	 * @param string $url
	 */
	public function includeJs($js)
	{
		$this->_js[] = trim($js);
	}

	/**
	 * Returns the nodes prepared for inclusion in the <head> of the template,
	 * and flushes out the head nodes queue.
	 *
	 * @return string
	 */
	public function getHeadNodes()
	{
		// Is there any CSS to include?
		if (!empty($this->_css))
		{
			$css = implode("\n\n", array_unique($this->_css));
			$node = "<style type=\"text/css\">\n".$css."\n</style>";
			$this->includeHeadNode($node);
		}

		if (!empty($this->_headNodes))
		{
			$headNodes = implode("\n", array_unique($this->_headNodes));
			$this->_headNodes = null;
			return $headNodes;
		}
	}

	/**
	 * Returns the nodes prepared for inclusion right above the </body> in the template,
	 * and flushes out the foot nodes queue.
	 *
	 * @return string
	 */
	public function getFootNodes()
	{
		// Is there any JS to include?
		if (!empty($this->_js))
		{
			$js = implode("\n\n", array_unique($this->_js));
			$node = "<script type=\"text/javascript\">\n/*<![CDATA[*/\n".$js."\n/*]]>*/\n</script>";
			$this->includeFootNode($node);
		}

		if (!empty($this->_footNodes))
		{
			$footNodes = implode("\n", array_unique($this->_footNodes));
			$this->_footNodes = null;
			return $footNodes;
		}
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
			return $this->_templatePaths[$name];

		// Validate the template name
		$this->_validateTemplateName($name);

		// Check if the template exists in the main templates path

		// Set the view path
		//  - We need to set this for each template request, in case it was changed to a plugin's template path
		$basePath = blx()->path->getTemplatesPath();

		// If it's an error template we might need to check for a user-defined template on the front-end of the site.
		if ($this->_isErrorTemplate($name))
		{
			$viewPaths = array();

			if (blx()->request->getMode() == HttpRequestMode::Site)
				$viewPaths[] = blx()->path->getSiteTemplatesPath();

			$viewPaths[] = blx()->path->getCpTemplatesPath();

			foreach ($viewPaths as $viewPath)
			{
				if (IOHelper::fileExists($viewPath.$name.'.html'))
				{
					$basePath = IOHelper::getRealPath($viewPath);
					break;
				}
			}
		}

		if (($path = $this->_findTemplate($basePath.$name)) !== null)
			return $this->_templatePaths[$name] = $path;

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (($mode = blx()->request->getMode()) == HttpRequestMode::CP || $mode == HttpRequestMode::Action)
		{
			$parts = array_filter(explode('/', $name));
			$pluginHandle = strtolower(array_shift($parts));

			if ($pluginHandle && ($plugin = blx()->plugins->getPlugin($pluginHandle)) !== false)
			{
				// Get the template path for the plugin.
				$basePath = blx()->path->getPluginsPath().$plugin->getClassHandle().'/templates/';

				// Chop off the plugin segment, since that's already covered by $basePath
				$name = implode('/', $parts);

				if (($path = $this->_findTemplate($basePath.$name)) !== null)
					return $this->_templatePaths[$name] = $path;
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
		if (false !== strpos($name, "\0"))
			throw new \Twig_Error_Loader(Blocks::t('A template name cannot contain NUL bytes.'));

		$parts = explode('/', $name);
		$level = 0;
		foreach ($parts as $part)
		{
			if ($part === '..')
				$level--;
			elseif ($part !== '.')
				$level++;

			if ($level < 0)
				throw new \Twig_Error_Loader(Blocks::t('Looks like you try to load a template outside the template folder: {template}.', array('template' => $name)));
		}
	}

	/**
	 * Checks to see if the template name matches error, error400, error500, etc. or exception.
	 *
	 * @access private
	 * @param $name
	 * @return int
	 */
	private function _isErrorTemplate($name)
	{
		return preg_match("/^(error([0-9]{3})?|exception)$/uis", $name);
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

		if ($extension)
			$testPaths = array($path);
		else
			$testPaths = array($path.'.html', $path.'/index.html');

		foreach ($testPaths as $path)
		{
			$path = IOHelper::normalizePathSeparators($path);

			if (IOHelper::fileExists(blx()->findLocalizedFile($path)))
				return $path;
		}

		return null;
	}

	/**
	 * Renames input names so they belong to a namespace.
	 *
	 * @param string $template The template with the inputs
	 * @param string $namespace The namespace to make inputs belong to
	 * @return string The template with namespaced inputs
	 */
	public function namespaceInputs($template, $namespace)
	{
		// name= attributes
		$template = preg_replace('/(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $template);

		// id= and for= attributes
		$idNamespace = rtrim(preg_replace('/[\[\]]+/', '-', $namespace), '-');
		$template = preg_replace('/((id=|for=|data\-target=)(\'|"))([^\'"]+)\3/', '$1'.$idNamespace.'-$4$3', $template);

		return $template;
	}
}
