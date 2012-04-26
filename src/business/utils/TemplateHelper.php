<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	/**
	 * @var array
	 */
	public static $services = array(
		'assets',
		'blocks',
		'config',
		'content',
		'cp',
		'dashboard',
		'email',
		'plugins',
		'request',
		'session',
		'settings',
		'sites',
		'updates',
		'user',
		'users',
		'request',
		'security',
	);

	/**
	 * @var array
	 */
	public static $globalVariables = array(
		'app'               => 'Blocks\AppVariable',
		'url'               => 'Blocks\UrlVariable',
		'configHelper'      => 'Blocks\ConfigHelperVariable',
		'date'              => 'Blocks\DateAdapter',
		'dateTimeHelper'    => 'Blocks\DateTimeHelperVariable',
	);

	/**
	 * Returns a new template variable instance, which will either be one of the globals, or a generic template variable, depending on the handle passed in.
	 * @param string $handle The variable handle being used in the template.
	 * @return object The variable instance.
	 */
	public static function getGlobalVariable($handle)
	{
		if (in_array($handle, self::$services))
		{
			$obj = b()->$handle;
			return self::getVariable($obj);
		}
		else if (isset(self::$globalVariables[$handle]))
		{
			$obj = new self::$globalVariables[$handle];
			return self::getVariable($obj);
		}
		else
			return new TemplateVariable();
	}

	/**
	 * Returns whether a variable is a template variable or not
	 * @param mixed $var The variable
	 * @return bool Whether it's a template variable or not
	 */
	public static function isVariable($var)
	{
		$isTemplateVariable = (is_object($var) && get_class($var) == 'Blocks\TemplateVariable');
		return $isTemplateVariable;
	}

	/**
	 * Returns the appropriate template variable for a given variable
	 * @param mixed $var The variable
	 * @param object A template variable instance for the variable
	 * @return ArrayAdapter|BoolAdapter|mixed|DateAdapter|string|NumAdapter|StringAdapter
	 */
	public static function getVariable($var = '')
	{
		// If $var is already a template variable, just return it
		if (self::isVariable($var))
			return $var;
		else
			return new TemplateVariable($var);
	}

	/**
	 * Resolves a template path to an actual template file by possibly adding "/index" to it
	 * @param $templatePath
	 * @return mixed If a template match was found, returns the $templatePath string, possibly with "/index" appended to it, otherwise false
	 * @static
	 */
	public static function resolveTemplatePath($templatePath)
	{
		$copyTemplatePath = $templatePath;

		if (strncmp($templatePath,'///email', 8) === 0)
		{
			$viewPath = b()->path->emailTemplatesPath;
			$templatePath = substr($templatePath, 9);
		}
		else
		{
			$viewPath = b()->viewPath;
			$copyTemplatePath = trim($templatePath, '/');
		}

		// Check if request/path.ext exists
		$testTemplatePath = $templatePath.b()->viewRenderer->fileExtension;
		if (is_file(b()->findLocalizedFile($viewPath.$testTemplatePath)))
			return $copyTemplatePath;

		// Otherwise check if request/path/index.ext exists
		$copyTemplatePath .= '/index';
		$testTemplatePath = $copyTemplatePath.b()->viewRenderer->fileExtension;
		if (is_file(b()->findLocalizedFile($viewPath.$testTemplatePath)))
			return $copyTemplatePath;

		return false;
	}

	/**
	 * Renames input names so they belong to a namespace
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
