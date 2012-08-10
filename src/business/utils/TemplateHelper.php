<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	/**
	 * Returns whether a variable is a template variable or not.
	 *
	 * @static
	 * @param mixed $var The variable
	 * @return bool Whether it's a template variable or not
	 */
	public static function isVariable($var)
	{
		$isVariable = (is_object($var) && get_class($var) == 'Blocks\Variable');
		return $isVariable;
	}

	/**
	 * Returns the appropriate template variable for a given variable.
	 *
	 * @static
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
	 * Preps all variables that will be available to the template.
	 *
	 * @static
	 * @param array $variables
	 * @return array
	 */
	public function prepTemplateVariables($variables)
	{
		foreach ($variables as $name => $var)
		{
			$variables[$name] = self::getVariable($var);
		}

		return $variables;
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
