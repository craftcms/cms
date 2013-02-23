<?php
namespace Blocks;

/**
 *
 */
class ComponentsService extends BaseApplicationComponent
{
	/**
	 * @var array The types of components supported by Blocks.
	 */
	public $types;

	/**
	 * @access private
	 * @var array The internal list of components
	 */
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
			if (!isset($this->types[$type]))
			{
				$this->_noComponentTypeExists($type);
			}

			$ctype = $this->types[$type];
			$this->_components[$type] = array();
			$names = array();

			$filter = $ctype['suffix'].'\.php$';
			$files = IOHelper::getFolderContents(blx()->path->getAppPath().$ctype['subfolder'], false, $filter);

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

					$component = $this->_initializeComponent($class, $type);

					if (!$component)
					{
						continue;
					}

					// Save it
					$classHandle = $component->getClassHandle();
					$this->_components[$type][$classHandle] = $component;
					$names[] = $component->getName();
				}
			}

			// Now load any plugin-supplied components
			$pluginComponents = blx()->plugins->getAllComponentsByType($ctype['subfolder']);

			foreach ($pluginComponents as $component)
			{
				if (!empty($ctype['instanceof']))
				{
					if (!$this->_verifyInstanceOf($component, $ctype['instanceof']))
					{
						continue;
					}
				}

				$component->init();

				$this->_components[$type][$component->getClassHandle()] = $component;
				$names[] = $component->getName();
			}

			array_multisort($names, $this->_components[$type]);
		}

		return $this->_components[$type];
	}

	/**
	 * Returns a new component instance by its type and class.
	 *
	 * @param string $type
	 * @param string $class
	 * @return BaseComponentType|null
	 */
	public function getComponentByTypeAndClass($type, $class)
	{
		if (!isset($this->types[$type]))
		{
			$this->_noComponentTypeExists($type);
		}

		$class = __NAMESPACE__.'\\'.$class.$this->types[$type]['suffix'];

		if (class_exists($class))
		{
			return $this->_initializeComponent($class, $type);
		}
	}

	/**
	 * Populates a new component instance by its type and model.
	 *
	 * @param string $type
	 * @param BaseComponentModel $model
	 * @return BaseComponentType|null
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
	 * Validates a class and creates an instance of it.
	 *
	 * @access private
	 * @param string $class
	 * @param string $type
	 * @return BaseComponentType|null
	 */
	private function _initializeComponent($class, $type)
	{
		// Ignore abstract classes and interfaces
		$ref = new \ReflectionClass($class);

		if ($ref->isAbstract() || $ref->isInterface())
		{
			return;
		}

		// Instantiate and return it
		$component = new $class;

		// Make sure it implements the correct base class or interface
		if (!empty($this->types[$type]['instanceof']))
		{
			if (!$this->_verifyInstanceOf($component, $this->types[$type]['instanceof']))
			{
				return;
			}
		}

		$component->init();

		return $component;
	}

	private function _verifyInstanceOf($component, $baseClass)
	{
		$nsBaseClass = __NAMESPACE__.'\\'.$baseClass;
		return ($component instanceof $nsBaseClass);
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
