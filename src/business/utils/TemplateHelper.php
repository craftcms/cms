<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	private static $_twig;
	private static $_templatePaths;

	/**
	 * Registers the Twig autoloader.
	 *
	 * @static
	 */
	public static function registerTwigAutoloader()
	{
		if (!class_exists('\Twig_Autoloader', false))
		{
			require_once blx()->path->getAppPath().'business/lib/Twig/Autoloader.php';
			Blocks::registerAutoloader(array(new \Twig_Autoloader, 'autoload'), true);
		}
	}

	/**
	 * Gets the Twig instance.
	 *
	 * @static
	 * @return \Twig_Environment
	 */
	public static function getTwig()
	{
		if (!isset(static::$_twig))
		{
			static::registerTwigAutoloader();

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
				$twig->addExtension(new \Twig_Extension_Debug());

			static::$_twig = $twig;
		}

		return static::$_twig;
	}

	/**
	 * Renders a template.
	 *
	 * @static
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @param array $variables The variables that should be available to the template
	 * @return string The rendered template
	 */
	public static function render($template, $variables = array())
	{
		$twig = static::getTwig();

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
	 * @static
	 * @param string $cacheKey A unique key for the template
	 * @param string $template The source template string
	 * @param array $variables The variables that should be available to the template
	 * @return string The rendered template
	 */
	public static function renderString($cacheKey, $template, $variables)
	{
		$stringTemplate = new StringTemplate($cacheKey, $template);
		return static::render($stringTemplate, $variables);
	}

	/**
	 * Finds a template on the file system and returns its path.
	 *
	 * @static
	 * @param string $name
	 * @throws TemplateLoaderException
	 * @return string
	 */
	public static function findTemplate($name)
	{
		// Normalize the template name
		$name = trim(preg_replace('#/{2,}#', '/', strtr($name, '\\', '/')), '/');

		// Is this template path already cached?
		if (isset(static::$_templatePaths[$name]))
			return static::$_templatePaths[$name];

		// Validate the template name
		static::_validateTemplateName($name);

		// Check if the template exists in the main templates path

		// Set the view path
		//  - We need to set this for each template request, in case it was changed to a plugin's template path
		$basePath = realpath(blx()->path->getTemplatesPath()).'/';

		// If it's an error template we might need to check for a user-defined template on the front-end of the site.
		if (static::_isErrorTemplate($name))
		{
			$viewPaths = array();

			if (blx()->request->getMode() == RequestMode::Site)
				$viewPaths[] = blx()->path->getSiteTemplatesPath();

			$viewPaths[] = blx()->path->getCpTemplatesPath();

			foreach ($viewPaths as $viewPath)
			{
				if (is_file($viewPath.$name.'.html'))
				{
					$basePath = realpath($viewPath).'/';
					break;
				}
			}
		}

		if (($path = static::_findTemplate($basePath.$name)) !== null)
			return static::$_templatePaths[$name] = $path;

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (($mode = blx()->request->getMode()) == RequestMode::CP || $mode == RequestMode::Action)
		{
			$parts = array_filter(explode('/', $name));
			$pluginHandle = strtolower(array_shift($parts));

			if ($pluginHandle && ($plugin = blx()->plugins->getPlugin($pluginHandle)) !== false)
			{
				// Get the template path for the plugin.
				$basePath = blx()->path->getPluginsPath().$plugin->getClassHandle().'/templates/';

				// Chop off the plugin segment, since that's already covered by $basePath
				$name = implode('/', $parts);

				if (($path = static::_findTemplate($basePath.$name)) !== null)
					return static::$_templatePaths[$name] = $path;
			}
		}

		throw new TemplateLoaderException($name);
	}

	/**
	 * Ensures that a template name isn't null, and that it doesn't lead outside the template directory.
	 * Borrowed from Twig_Loader_Filesystem.
	 *
	 * @static
	 * @access private
	 * @param string $name
	 * @throws \Twig_Error_Loader
	 */
	private static function _validateTemplateName($name)
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
				throw new \Twig_Error_Loader(Blocks::t('Looks like you try to load a template outside the template directory: {template}.', array('template' => $name)));
		}
	}

	/**
	 * Checks to see if the template name matches error, error400, error500, etc. or exception.
	 *
	 * @static
	 * @access private
	 * @param $name
	 * @return int
	 */
	private static function _isErrorTemplate($name)
	{
		return preg_match("/^(error([0-9]{3})?|exception)$/uis", $name);
	}

	/**
	 * Searches for localized template files, and returns the first match if there is one.
	 *
	 * @static
	 * @access private
	 * @param string $path
	 * @return mixed
	 */
	private static function _findTemplate($path)
	{
		// Get the extension on the path, if there is one
		$extension = FileHelper::getExtension($path);

		if ($extension)
			$testPaths = array($path);
		else
			$testPaths = array($path.'.html', $path.'/index.html');

		foreach ($testPaths as $path)
		{
			if (is_file(blx()->findLocalizedFile($path)))
			{
				$path = str_replace('\\', '/', $path);
				$path = str_replace('//', '/', $path);
				return $path;
			}
		}

		return null;
	}

	/**
	 * Renames input names so they belong to a namespace.
	 *
	 * @static
	 * @param string $template The template with the inputs
	 * @param string $namespace The namespace to make inputs belong to
	 * @return string The template with namespaced inputs
	 */
	public static function namespaceInputs($template, $namespace)
	{
		// name= attributes
		$template = preg_replace('/(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $template);

		// id= and for= attributes
		$idNamespace = rtrim(preg_replace('/[\[\]]+/', '-', $namespace), '-');
		$template = preg_replace('/((id=|for=|data\-target=)(\'|"))([^\'"]+)\3/', '$1'.$idNamespace.'-$4$3', $template);

		return $template;
	}
}
