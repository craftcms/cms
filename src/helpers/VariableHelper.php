<?php
namespace Craft;

/**
 * Helper class for template variables.
 *
 * @package craft.app.helpers
 */
class VariableHelper
{
	/**
	 * Returns an array of variables for a given set of class instances.
	 *
	 * @param array $instances
	 * @param string $class
	 * @return array
	 */
	public static function populateVariables($instances, $class)
	{
		$variables = array();

		if (is_array($instances))
		{
			$namespace = __NAMESPACE__.'\\';
			if (strncmp($class, $namespace, mb_strlen($namespace)) != 0)
			{
				$class = $namespace.$class;
			}

			foreach ($instances as $key => $instance)
			{
				$variables[$key] = new $class($instance);
			}
		}

		return $variables;
	}
}
