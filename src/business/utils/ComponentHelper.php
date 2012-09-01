<?php
namespace Blocks;

/**
 *
 */
class ComponentHelper
{
	/**
	 * Returns instances of a component type.
	 *
	 * @param string $subfolder     The subfolder to look in within app/ and each plugin's folder.
	 * @param string $componentType The type of components to load.
	 * @return array
	 */
	public static function getComponents($subfolder, $componentType)
	{
		$components = array();

		$appPath = blx()->path->getAppPath();
		$folderPath = $appPath.$subfolder.'/';
		$files = glob($folderPath.'/*'.$componentType.'.php');

		if (is_array($files) && count($files) > 0)
		{
			foreach ($files as $file)
			{
				$class = basename($file, '.php');

				// Ignore if the name is just the suffix
				if ($class == $componentType)
					continue;

				// Add the namespace
				$class = __NAMESPACE__.'\\'.$class;

				// Skip the autoloader
				if (!class_exists($class, false))
					require_once $file;

				// Ignore if we couldn't find the widget class
				if (!class_exists($class, false))
					continue;

				// Ignore if it's an abstract class
				$ref = new \ReflectionClass($class);
				if ($ref->isAbstract())
					continue;

				// Save an instance of it
				$components[] = new $class;
			}
		}

		return $components;
	}
}
