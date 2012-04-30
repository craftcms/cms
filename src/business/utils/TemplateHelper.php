<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	/**
	 * Returns whether a variable is a template variable or not
	 * @param mixed $var The variable
	 * @return bool Whether it's a template variable or not
	 */
	public static function isVariable($var)
	{
		$isVariable = (is_object($var) && get_class($var) == 'Blocks\Variable');
		return $isVariable;
	}

	/**
	 * Returns the appropriate template variable for a given variable
	 * @param mixed $var The variable
	 * @param object A template variable instance for the variable
	 * @return mixed
	 */
	public static function getVariable($var = '')
	{
		// If $var is already a template variable, just return it
		if (self::isVariable($var))
			return $var;
		else
			return new Variable($var);
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
