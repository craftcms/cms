<?php
namespace Blocks;

/**
 * Helper class for template variables
 */
class VariableHelper
{
	/**
	 * Returns an array of variables for a given set of class instances.
	 *
	 * @static
	 * @param array $instances
	 * @param string $class
	 * @return array
	 */
	public static function populateVariables($instances, $class)
	{
		$variables = array();

		if (is_array($instances))
		{
			$nsClass = __NAMESPACE__.'\\'.$class;

			foreach ($instances as $key => $instance)
			{
				$variables[$key] = new $nsClass($instance);
			}
		}

		return $variables;
	}

	/**
	 * Returns an array of ModelVariable's for a given set of models.
	 *
	 * @static
	 * @param array $models
	 * @return array
	 */
	public static function populateModelVariables($models)
	{
		return static::populateVariables($models, 'ModelVariable');
	}

	/**
	 * Returns an array of ComponentVariable's for a given set of components.
	 *
	 * @static
	 * @param array $components
	 * @return array
	 */
	public static function populateComponentVariables($components)
	{
		return static::populateVariables($components, 'ComponentVariable');
	}
}
