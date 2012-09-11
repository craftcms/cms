<?php
namespace Blocks;

/**
 *
 */
class ComponentsService extends ApplicationComponent
{
	protected static $componentTypes = array(
		'block' => array('folder' => 'blocks', 'suffix' => 'Block', 'interface' => 'IBlock'),
		'widget' => array('folder' => 'widgets', 'suffix' => 'Widget', 'interface' => 'Iwidget'),
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
		if (!isset($this->_components[$type]))
		{
			if (!isset(static::$componentTypes[$type]))
				$this->_noComponentTypeExists($type);

			$ctype = static::$componentTypes[$type];

			$this->_components[$type] = array();

			$folderPath = blx()->path->getComponentsPath().$ctype['folder'].'/';
			$classSuffix = $ctype['suffix'];
			$files = IOHelper::getFolderContents($folderPath, false, ".*{$classSuffix}\.php");

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					$class = IOHelper::getFileName($file);

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

					// Instantiate it
					$obj = new $class;

					// Make sure it implements the correct interface
					$interface = __NAMESPACE__.'\\'.$ctype['interface'];
					if (!$obj instanceof $interface)
						continue;

					// Save it
					$classHandle = $obj->getClassHandle();
					$this->_components[$type][$classHandle] = $obj;
				}
			}
		}

		return $this->_components[$type];
	}

	/**
	 * Returns a new component instance by its type and class.
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

		if (class_exists($class))
			return new $class;
	}

	/**
	 * Populates a component instance with a given record.
	 *
	 * @param string $type
	 * @param BaseRecord $record
	 * @return BaseComponent
	 */
	public function populateComponent($type, BaseRecord $record)
	{
		$component = $this->getComponentByTypeAndClass($type, $record->class);
		if ($component)
		{
			$component->record = $record;
			$component->init();
			return $component;
		}
	}

	/**
	 * Creates an array of component instances based on an array of records.
	 *
	 * @param string $type
	 * @param array $records
	 * @return array
	 */
	public function populateComponents($type, $records)
	{
		$components = array();

		foreach ($records as $record)
		{
			$component = $this->populateComponent($type, $record);
			if ($component)
				$components[] = $component;
		}

		return $components;
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
