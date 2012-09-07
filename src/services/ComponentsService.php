<?php
namespace Blocks;

/**
 *
 */
class ComponentsService extends ApplicationComponent
{
	protected static $componentTypes = array(
		'block' => array('folder' => 'blocks', 'suffix' => 'Block'),
		'widget' => array('folder' => 'widgets', 'suffix' => 'Widget'),
	);

	private $_components;

	/**
	 * Returns instances of a component type, indexed by their class handles.
	 *
	 * @param string $type
	 * @return array
	 */
	public function getComponentsByType($type)
	{
		if (!isset(static::$componentTypes[$type]))
			$this->_noComponentTypeExists($type);

		if (!isset($this->_components[$type]))
		{
			$this->_components[$type] = array();

			$folderPath = blx()->path->getComponentsPath().static::$componentTypes[$type]['folder'].'/';
			$classSuffix = static::$componentTypes[$type]['suffix'];
			$files = glob($folderPath.'/*'.$classSuffix.'.php');

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					$class = basename($file, '.php');

					// Add the namespace
					$class = __NAMESPACE__.'\\'.$class;

					// Skip the autoloader
					if (!class_exists($class, false))
						require_once $file;

					// Ignore if we couldn't find the class
					if (!class_exists($class, false))
						continue;

					// Ignore abstract classes and interfaces
					$ref = new \ReflectionClass($class);
					if ($ref->isAbstract() || $ref->isInterface())
						continue;

					// Save an instance of it
					$obj = new $class;
					$classHandle = $obj->getClassHandle();
					$this->_components[$type][$classHandle] = $obj;
				}
			}
		}

		return $this->_components[$type];
	}

	/**
	 * Returns a new component instance by its type and class
	 *
	 * @param string $type
	 * @param string $class
	 * @return BaseComponent
	 */
	public function getComponentByTypeAndClass($type, $class)
	{
		if (!isset(static::$componentTypes[$type]))
			$this->_noComponentTypeExists($type);

		$class = __NAMESPACE__.'\\'.$class.static::$componentTypes[$type]['suffix'];
		return new $class;
	}

	/**
	 * Throws a "no component type exists" exception.
	 *
	 * @access private
	 * @param string $type
	 * @throws Exception
	 */
	private function _noComponentTypeExists($type)
	{
		throw new Exception(Blocks::t('No component type exists by the name “{type}”', array('type' => $type)));
	}
}
