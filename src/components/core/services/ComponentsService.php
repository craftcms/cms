<?php
namespace Blocks;

/**
 *
 */
class ComponentsService extends BaseApplicationComponent
{
	protected static $componentTypes = array(
		'assetSource' => array('subfolder' => 'assetsourcetypes', 'suffix' => 'AssetSource', 'interface' => 'IAssetSource'),
		'block'       => array('subfolder' => 'blocktypes', 'suffix' => 'Block', 'interface' => 'IBlock'),
		'widget'      => array('subfolder' => 'widgettypes', 'suffix' => 'Widget', 'interface' => 'Iwidget'),
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
			{
				$this->_noComponentTypeExists($type);
			}

			$ctype = static::$componentTypes[$type];

			$this->_components[$type] = array();

			$filter = '\/'.$ctype['subfolder'].'\/.*'.$ctype['suffix'].'\.php';
			$files = IOHelper::getFolderContents(blx()->path->getComponentsPath(), true, $filter);

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					$class = IOHelper::getFileName($file, false);

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
	 * @return BaseComponent|null
	 */
	public function getComponentByTypeAndClass($type, $class)
	{
		if (!isset(static::$componentTypes[$type]))
		{
			$this->_noComponentTypeExists($type);
		}

		$class = __NAMESPACE__.'\\'.$class.static::$componentTypes[$type]['suffix'];

		if (class_exists($class))
		{
			return new $class;
		}
	}

	/**
	 * Populates a new component instance by its type and package.
	 *
	 * @param string $type
	 * @param BaseComponentPackage $package
	 * @return BaseComponent|null
	 */
	public function populateComponentByTypeAndPackage($type, BaseComponentPackage $package)
	{
		$component = $this->getComponentByTypeAndClass($type, $package->type);

		if ($component)
		{
			if ($package->settings)
			{
				$component->setSettings($package->settings);
			}

			if ($package->settingsErrors)
			{
				$component->getSettings()->addErrors($package->settingsErrors);
			}

			return $component;
		}
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
