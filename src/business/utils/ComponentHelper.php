<?php
namespace Blocks;

/**
 *
 */
class ComponentHelper
{
	/**
	 * Returns instances of a component type.
	 * @param string $subfolder   The subfolder to look in within app/ and each plugin's folder.
	 * @param string $classSuffix The suffix each class name must have.
	 * @return array
	 */
	public static function getComponents($subfolder, $classSuffix)
	{
		$components = array();

		$appPath = b()->path->getAppPath();
		$folderPath = $appPath.$subfolder.'/';
		$files = @glob($folderPath.'/*'.$classSuffix.'.php');

		if ($files !== false)
		{
			foreach ($files as $file)
			{
				$class = basename($file, '.php');

				// Ignore if the name is just the suffix
				if ($class == $classSuffix)
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
