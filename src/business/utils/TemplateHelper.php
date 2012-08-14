<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	private static $_twig;

	/**
	 * Gets the Twig instance
	 *
	 * @return \Twig_Environment
	 */
	public static function getTwig()
	{
		if (!isset(static::$_twig))
		{
			// Register Twig's autoloader
			require_once blx()->path->getAppPath().'business/lib/Twig/Autoloader.php';
			Blocks::registerAutoloader(array(new \Twig_Autoloader, 'autoload'), true);

			$loader = new TemplateLoader();

			static::$_twig = new \Twig_Environment($loader, array(
				'debug'               => blx()->config->devMode,
				//'base_template_class' => '\Blocks\BaseTemplate',
				'cache'               => blx()->path->getCompiledTemplatesPath(),
				'auto_reload'         => true,
				//'strict_variables'  => true,
			));

			if (blx()->config->devMode)
				static::$_twig->addExtension(new \Twig_Extension_Debug());
		}

		return static::$_twig;
	}

	/**
	 * Renders a template.
	 *
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @param array $variables The variables that should be available to the template
	 * @return string The rendered template
	 */
	public static function render($template, $variables)
	{
		// Add the global blx object
		$variables['blx'] = new BlxVariable();

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
	 * Renders a template string
	 *
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
		$template = preg_replace('/((id=|for=)(\'|"))([^\'"]+)\3/', '$1'.$namespace.'-$4$3', $template);

		return $template;
	}
}
