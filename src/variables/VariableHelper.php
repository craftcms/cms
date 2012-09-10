<?php
namespace Blocks;

/**
 * Helper class for template variables
 */
class VariableHelper
{
	/**
	 * Returns an array of ModelVariable's for a given set of models.
	 *
	 * @static
	 * @param array       $models
	 * @param string|null $class
	 * @return array
	 */
	public static function populateModelVariables($models, $class = 'ModelVariable')
	{
		$variables = array();

		if (is_array($models))
		{
			$nsClass = __NAMESPACE__.'\\'.$class;

			foreach ($models as $key => $model)
			{
				$variables[$key] = new $nsClass($model);
			}
		}

		return $variables;
	}

	/**
	 * Returns an array of ComponentVariable's for a given set of components.
	 *
	 * @static
	 * @param array       $components
	 * @param string|null $class
	 * @return array
	 */
	public static function populateComponentVariables($components, $class = 'ComponentVariable')
	{
		$variables = array();

		if (is_array($components))
		{
			$nsClass = __NAMESPACE__.'\\'.$class;

			foreach ($components as $key => $component)
			{
				$variables[$key] = new $nsClass($component);
			}
		}

		return $variables;
	}
}
