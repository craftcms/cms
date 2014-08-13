<?php
namespace Craft;

/**
 * Helper class for template variables.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class VariableHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns an array of variables for a given set of class instances.
	 *
	 * @param array  $instances
	 * @param string $class
	 *
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
