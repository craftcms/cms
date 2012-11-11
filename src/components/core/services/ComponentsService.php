<?php
namespace Blocks;

/**
 *
 */
class ComponentsService extends BaseApplicationComponent
{
	protected static $componentTypes = array(
		'assetSource' => array('subfolder' => 'assetsourcetypes', 'suffix' => 'AssetSourceType', 'baseclass' => 'BaseAssetSourceType'),
		'block'       => array('subfolder' => 'blocktypes', 'suffix' => 'BlockType', 'baseclass' => 'BaseBlockType'),
		'link'        => array('subfolder' => 'linktypes', 'suffix' => 'LinkType', 'baseclass' => 'BaseLinkType'),
		'widget'      => array('subfolder' => 'widgets', 'suffix' => 'Widget', 'baseclass' => 'BaseWidget'),
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
			$baseClass = __NAMESPACE__.'\\'.$ctype['baseclass'];

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
					{
						require_once $file;
					}

					// Ignore if we couldn't find the class
					if (!class_exists($class, false))
					{
						continue;
					}

					// Ignore abstract classes and interfaces
					$ref = new \ReflectionClass($class);
					if ($ref->isAbstract() || $ref->isInterface())
					{
						continue;
					}

					// Instantiate it
					$obj = new $class;

					// Make sure it implements the correct abstract base class
					if (!$obj instanceof $baseClass)
					{
						continue;
					}

					// Save it
					$classHandle = $obj->getClassHandle();
					$this->_components[$type][$classHandle] = $obj;
				}
			}

			// Now load any plugin-supplied components
			$pluginComponents = blx()->plugins->getAllComponentsByType($ctype['subfolder']);

			foreach ($pluginComponents as $component)
			{
				if ($component instanceof $baseClass)
				{
					$this->_components[$type][$component->getClassHandle()] = $component;
				}
			}

			ksort($this->_components[$type]);
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
	 * Populates a new component instance by its type and model.
	 *
	 * @param string $type
	 * @param BaseComponentModel $model
	 * @return BaseComponent|null
	 */
	public function populateComponentByTypeAndModel($type, BaseComponentModel $model)
	{
		$component = $this->getComponentByTypeAndClass($type, $model->type);

		if ($component)
		{
			$component->model = $model;

			if ($model->settings)
			{
				$component->setSettings($model->settings);
			}

			if ($model->hasSettingErrors())
			{
				$component->getSettings()->addErrors($model->getSettingErrors());
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
