<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\components\BaseComponentType;
use craft\app\errors\Exception;
use craft\app\helpers\IOHelper;
use craft\app\models\BaseComponentModel;
use yii\base\Component;

/**
 * Class Components service.
 *
 * An instance of the Components service is globally accessible in Craft via [[Application::components `Craft::$app->components`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Components extends Component
{
	// Properties
	// =========================================================================

	/**
	 * The types of components supported by Craft.
	 *
	 * @var array
	 */
	public $types;

	/**
	 * The internal list of components.
	 *
	 * @var array
	 */
	private $_components;

	// Public Methods
	// =========================================================================

	/**
	 * Returns instances of a component type, indexed by their class handles.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function getComponentsByType($type)
	{
		if (!isset($this->_components[$type]))
		{
			$this->_components[$type] = $this->_findComponentsByType($type);
		}

		return $this->_components[$type];
	}

	/**
	 * Returns a new component instance by its type and class.
	 *
	 * @param string $type
	 * @param string $class
	 *
	 * @return BaseComponentType|null
	 */
	public function getComponentByTypeAndClass($type, $class)
	{
		// Make sure this is a valid component type
		if (!isset($this->types[$type]))
		{
			$this->_noComponentTypeExists($type);
		}

		// Add the namespace, initialize, and return
		$fullClass = $this->types[$type]['namespace'].'\\'.$class;

		if (!class_exists($fullClass))
		{
			// Maybe it's a plugin component?
			//if ($this->types[$type]['enableForPlugins'])
			//{
			//	if (($pos = strrpos($class, '_')) !== false)
			//	{
			//		$pluginHandle = substr($class, 0, $pos);
			//	}
			//	else
			//	{
			//		$pluginHandle = $class;
			//	}

			//	$plugin = Craft::$app->plugins->getPlugin($pluginHandle);

			//	if (!$plugin || !Craft::$app->plugins->doesPluginClassExist($plugin, $this->types[$type]['subfolder'], $fullClass))
			//	{
			//		return null;
			//	}
			//}
			//else
			//{
				return null;
			//}
		}

		return $this->initializeComponent($fullClass, $this->types[$type]['instanceof']);
	}

	/**
	 * Populates a new component instance by its type and model.
	 *
	 * @param string             $type
	 * @param BaseComponentModel $model
	 *
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
	 * Making sure a class exists and it's not abstract or an interface.
	 *
	 * @param string $class
	 *
	 * @return bool
	 */
	public function validateClass($class)
	{
		// Make sure the class exists
		if (!class_exists($class))
		{
			return false;
		}

		// Make sure this isn't an abstract class or interface
		$ref = new \ReflectionClass($class);

		if ($ref->isAbstract() || $ref->isInterface())
		{
			return false;
		}

		return true;
	}

	/**
	 * Validates a class and creates an instance of it.
	 *
	 * @param string $class
	 * @param string $instanceOf
	 *
	 * @return mixed
	 */
	public function initializeComponent($class, $instanceOf = null)
	{
		// Validate the class first
		if (!$this->validateClass($class))
		{
			return;
		}

		// Instantiate it
		$component = new $class;

		// Make sure it extends the right base class or implements the correct interface
		if ($instanceOf && !($component instanceof $instanceOf))
		{
			return null;
		}

		// All good. Call the component's init() method and return it.
		$component->init();
		return $component;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Finds all of the components by a given type.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	private function _findComponentsByType($type)
	{
		if (!isset($this->types[$type]))
		{
			$this->_noComponentTypeExists($type);
		}

		$componentClasses = [];

		// Find all of the built-in components
		$files = IOHelper::getFolderContents(Craft::$app->path->getAppPath().'/'.$this->types[$type]['subfolder'], false, '\.php$');

		if ($files)
		{
			foreach ($files as $file)
			{
				$componentClasses[] = $this->types[$type]['namespace'].'\\'.IOHelper::getFileName($file, false);
			}
		}

		// Now load any plugin-supplied components
		//if ($this->types[$type]['enableForPlugins'])
		//{
		//	foreach (Craft::$app->plugins->getPlugins() as $plugin)
		//	{
		//		$pluginClasses = Craft::$app->plugins->getPluginClasses($plugin, $this->types[$type]['subfolder'], $this->types[$type]['suffix']);
		//		$componentClasses = array_merge($componentClasses, $pluginClasses);
		//	}
		//}

		// Initialize, verify, and save them
		$components = [];
		$names      = [];

		foreach ($componentClasses as $class)
		{
			$component = $this->initializeComponent($class, $this->types[$type]['instanceof']);

			if ($component && $component->isSelectable())
			{
				$classHandle = $component->getClassHandle();

				// Make sure we don't have another component with the exact same class name
				if (!isset($components[$classHandle]))
				{
					// Save it
					$components[$classHandle] = $component;
					$names[] = $component->getName();
				}
			}
		}

		// Now sort all the components by their name
		array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $components);

		return $components;
	}

	/**
	 * Throws a "no component type exists" exception.
	 *
	 * @param string $type
	 *
	 * @throws Exception
	 */
	private function _noComponentTypeExists($type)
	{
		throw new Exception(Craft::t('app', 'No component type exists by the name “{type}”', ['type' => $type]));
	}
}
