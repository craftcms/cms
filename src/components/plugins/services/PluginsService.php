<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends BaseApplicationComponent
{
	/**
	 * Stores all plugins, whether installed or not.
	 *
	 * @access private
	 * @var array
	 */
	private $_plugins = array();

	/**
	 * Stores all enabled plugins.
	 *
	 * @access private
	 * @var array
	 */
	private $_enabledPlugins = array();

	/**
	 * Stores all plugins in the system, regardless of whether they're installed/enabled or not.
	 *
	 * @access private
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * List of the supported plugin components that will get autoloaded for enabled plugins.
	 *
	 * The keys are folder names, and values are class suffixes.
	 *
	 * @access private
	 * @var array
	 */
	private $_supportedComponents = array(
		'controllers'   => 'Controller',
		'models'        => 'Model',
		'records'       => 'Record',
		'services'      => 'Service',
		'variables'     => 'Variable',
		'helpers'       => 'Helper',
		'blocktypes'    => 'BlockType',
		'widgets'       => 'Widget',
		'validators'    => 'Validator',
	);

	/**
	 * List of the known component classes for each plugin,
	 * indexed by the component type, then the plugin handle.
	 *
	 * @access private
	 * @var array
	 */
	private $_pluginComponentClasses = array();

	/**
	 * Init
	 */
	public function init()
	{
		if (blx()->isInstalled())
		{
			// Find all of the enabled plugins
			$records = PluginRecord::model()->findAllByAttributes(array(
				'enabled' => true
			));

			$names = array();

			foreach ($records as $record)
			{
				$plugin = $this->_getPlugin($record->class);

				if ($plugin)
				{
					$lcHandle = strtolower($plugin->getClassHandle());
					$this->_plugins[$lcHandle] = $plugin;
					$this->_enabledPlugins[$lcHandle] = $plugin;
					$names[] = $plugin->getName();

					$plugin->setSettings($record->settings);

					$plugin->isInstalled = true;
					$plugin->isEnabled = true;

					$this->_importPluginComponents($plugin);
					$this->_registerPluginServices($plugin->getClassHandle());
				}
			}

			// Sort plugins by name
			array_multisort($names, $this->_enabledPlugins);

			// Now that all of the components have been imported,
			// initialize all the plugins
			foreach ($this->_enabledPlugins as $plugin)
			{
				$plugin->init();
			}
		}
	}

	/**
	 * Returns a plugin.
	 *
	 * @param string $handle
	 * @param bool   $enabledOnly
	 * @return BasePlugin|null
	 */
	public function getPlugin($handle, $enabledOnly = true)
	{
		$lcHandle = strtolower($handle);

		if ($enabledOnly)
		{
			if (isset($this->_enabledPlugins[$lcHandle]))
			{
				return $this->_enabledPlugins[$lcHandle];
			}
			else
			{
				return null;
			}
		}
		else
		{
			if (!array_key_exists($lcHandle, $this->_plugins))
			{
				// Make sure $handle has the right casing
				$handle = $this->_getPluginHandleFromFileSystem($handle);

				$plugin = $this->_getPlugin($handle);

				if ($plugin)
				{
					// Is it installed (but disabled)?
					$plugin->isInstalled = (bool) blx()->db->createCommand()
						->select('count(id)')
						->from('plugins')
						->where(array('class' => $plugin->getClassHandle()))
						->queryScalar();
				}

				$this->_plugins[$lcHandle] = $plugin;
			}

			return $this->_plugins[$lcHandle];
		}
	}

	/**
	 * Returns all plugins, whether they're installed or not.
	 *
	 * @param bool $enabledOnly
	 * @return array
	 */
	public function getPlugins($enabledOnly = true)
	{
		if ($enabledOnly)
		{
			return $this->_enabledPlugins;
		}
		else
		{
			if (!isset($this->_allPlugins))
			{
				$this->_allPlugins = array();
				$names = array();

				// Find all of the plugins in the plugins folder
				$pluginsPath = blx()->path->getPluginsPath();
				$paths = IOHelper::getFolderContents($pluginsPath, true, ".*Plugin\.php");

				if (is_array($paths) && count($paths) > 0)
				{
					foreach ($paths as $path)
					{
						$path = IOHelper::normalizePathSeparators($path);
						$fileName = IOHelper::getFileName($path, false);

						// Chop off the "Plugin" suffix
						$handle = substr($fileName, 0, strlen($fileName) - 6);

						$plugin = $this->getPlugin($handle, false);

						if ($plugin)
						{
							$this->_allPlugins[] = $plugin;
							$names[] = $plugin->getName();
						}
					}
				}

				// Sort plugins by name
				array_multisort($names, $this->_allPlugins);
			}

			return $this->_allPlugins;
		}
	}

	/**
	 * Enables a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function enablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Blocks::t('“{plugin}” can’t be enabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		blx()->db->createCommand()->update('plugins',
			array('enabled' => 1),
			array('class' => $plugin->getClassHandle())
		);

		$plugin->isEnabled = true;

		$lcHandle = strtolower($plugin->getClassHandle());
		$this->_enabledPlugins[$lcHandle] = $plugin;

		return true;
	}

	/**
	 * Disables a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function disablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Blocks::t('“{plugin}” can’t be disabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		blx()->db->createCommand()->update('plugins',
			array('enabled' => 0),
			array('class' => $plugin->getClassHandle())
		);

		$plugin->isEnabled = false;

		$lcHandle = strtolower($plugin->getClassHandle());
		unset($this->_enabledPlugins[$lcHandle]);

		return true;
	}

	/**
	 * Installs a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function installPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if ($plugin->isInstalled)
		{
			throw new Exception(Blocks::t('“{plugin}” is already installed.', array('plugin' => $plugin->getName())));
		}

		$transaction = blx()->db->beginTransaction();
		try
		{
			// Add the plugins as a record to the database.
			$record = new PluginRecord();
			$record->class = $plugin->getClassHandle();
			$record->version = $plugin->version;
			$record->enabled = true;
			$record->save();

			$plugin->isInstalled = true;
			$plugin->isEnabled = true;

			$lcHandle = strtolower($plugin->getClassHandle());
			$this->_enabledPlugins[$lcHandle] = $plugin;

			$this->_importPluginComponents($plugin);
			$plugin->createTables();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		$plugin->onAfterInstall();

		return true;
	}

	/**
	 * Uninstalls a plugin by removing it's record from the database, deleting it's tables and foreign keys and running the plugin's uninstall method if it exists.
	 *
	 * @param $handle
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function uninstallPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Blocks::t('“{plugin}” is already uninstalled.', array('plugin' => $plugin->getName())));
		}

		if (!$plugin->isEnabled)
		{
			// Pretend that the plugin is enabled just for this request
			$lcHandle = strtolower($plugin->getClassHandle());
			$this->_enabledPlugins[$lcHandle] = $plugin;

			$this->_importPluginComponents($plugin);
		}

		$plugin->onBeforeUninstall();

		$transaction = blx()->db->beginTransaction();
		try
		{
			$plugin->dropTables();

			// Remove the row from the database.
			blx()->db->createCommand()->delete('plugins', array('class' => $plugin->getClassHandle()));

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		unset($this->_plugins[strtolower($handle)]);

		return true;
	}

	/**
	 * Saves a plugin's settings.
	 *
	 * @param BasePlugin $plugin
	 * @param mixed $settings
	 * @return true
	 */
	public function savePluginSettings($plugin, $settings)
	{
		$record = PluginRecord::model()->findByAttributes(array(
			'class' => $plugin->getClassHandle()
		));

		if ($record)
		{
			// Give the plugin a chance to modify the settings
			$record->settings = $plugin->prepSettings($settings);
			$record->save();

			return true;
		}

		return false;
	}

	/**
	 * Calls a hook in any plugin that has it.
	 *
	 * @param string $hook
	 * @param array $args
	 * @return array
	 */
	public function callHook($hook, $args = array())
	{
		$result = array();
		$methodName = 'hook'.ucfirst($hook);

		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $methodName))
			{
				$result[] = call_user_func_array(array($plugin, $methodName), $args);
			}
		}

		return $result;
	}

	/**
	 * Returns all components of a certain type, across all plugins.
	 *
	 * @param string $componentType
	 * @return array
	 */
	public function getAllComponentsByType($componentType)
	{
		$allClasses = array();

		foreach ($this->getPlugins() as $plugin)
		{
			$classes = $this->getPluginComponentsByType($plugin->getClassHandle(), $componentType);

			$allClasses = array_merge($allClasses, $classes);
		}

		return $allClasses;
	}

	/**
	 * Returns all of a plugin's components of a certain type.
	 *
	 * @param string $pluginHandle
	 * @param string $componentType
	 * @return array
	 */
	public function getPluginComponentsByType($pluginHandle, $componentType)
	{
		$classes = $this->getPluginComponentClassesByType($pluginHandle, $componentType);

		$components = array();

		foreach ($classes as $class)
		{
			$nsClass = __NAMESPACE__.'\\'.$class;
			$components[] = new $nsClass();
		}

		return $components;
	}

	/**
	 * Throws a "no plugin exists" exception.
	 *
	 * @access private
	 * @param string $handle
	 * @throws Exception
	 */
	private function _noPluginExists($handle)
	{
		throw new Exception(Blocks::t('No plugin exists with the class “{class}”', array('class' => $handle)));
	}

	/**
	 * Finds and imports all of the supported component classes for a given plugin.
	 *
	 * @access private
	 * @param BasePlugin $plugin
	 */
	private function _importPluginComponents(BasePlugin $plugin)
	{
		$lcHandle = strtolower($plugin->getClassHandle());
		$pluginFolder = blx()->path->getPluginsPath().$lcHandle.'/';

		foreach ($this->_supportedComponents as $folderName => $classSuffix)
		{
			if (IOHelper::folderExists($pluginFolder.$folderName))
			{
				// See if it has any files in ClassName*Suffix.php format.
				$files = IOHelper::getFolderContents($pluginFolder.$folderName, false, $plugin->getClassHandle().'_?.*'.$classSuffix.'\.php');

				if ($files)
				{
					foreach ($files as $file)
					{
						// Get the file name minus the extension.
						$fileName = IOHelper::getFileName($file, false);

						// Import the class.
						Blocks::import("plugins.{$lcHandle}.{$folderName}.{$fileName}");

						// Remember it
						$this->_pluginComponentClasses[$folderName][$plugin->getClassHandle()][] = $fileName;
					}
				}
			}
		}
	}

	/**
	 * Returns all of a plugin's component class names of a certain type.
	 *
	 * @param string $pluginHandle
	 * @param string $componentType
	 * @return array
	 */
	public function getPluginComponentClassesByType($pluginHandle, $componentType)
	{
		$plugin = $this->getPlugin($pluginHandle);

		if (!$plugin)
		{
			$this->_noPluginExists($pluginHandle);
		}

		$allClasses = array();

		if (isset($this->_pluginComponentClasses[$componentType][$plugin->getClassHandle()]))
		{
			$classes = $this->_pluginComponentClasses[$componentType][$plugin->getClassHandle()];

			foreach ($classes as $class)
			{
				$nsClass = __NAMESPACE__.'\\'.$class;

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($nsClass);

				if ($ref->isAbstract() || $ref->isInterface())
				{
					continue;
				}

				$allClasses[] = $class;
			}
		}

		return $allClasses;
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @access private
	 * @param string $handle
	 * @throws Exception
	 * @return void
	 */
	private function _registerPluginServices($handle)
	{
		$classes = $this->getPluginComponentClassesByType($handle, 'services');

		$services = array();

		foreach ($classes as $class)
		{
			$parts = explode('_', $class);

			foreach ($parts as $index => $part)
			{
				$parts[$index] = lcfirst($part);
			}

			$serviceName = implode('_', $parts);
			$serviceName = substr($serviceName, 0, strpos($serviceName, 'Service'));

			if (!blx()->getComponent($serviceName, false))
			{
				// Register the component with the handle as (className or className_*) minus the "Service" suffix
				$nsClass = __NAMESPACE__.'\\'.$class;
				$services[$serviceName] = array('class' => $nsClass);
			}
			else
			{
				throw new Exception(Blocks::t('The plugin “{handle}” tried to register a service “{service}” that conflicts with a core service name.', array('handle' => $handle, 'service' => $serviceName)));
			}
		}

		blx()->setComponents($services, false);
	}

	/**
	 * Returns a new plugin instance based on its class handle.
	 *
	 * @param $handle
	 * @return BasePlugin|null
	 */
	private function _getPlugin($handle)
	{
		// Get the full class name
		$class = $handle.'Plugin';
		$nsClass = __NAMESPACE__.'\\'.$class;

		// Skip the autoloader
		if (!class_exists($nsClass, false))
		{
			$path = blx()->path->getPluginsPath().strtolower($handle).'/'.$class.'.php';

			if (($path = IOHelper::fileExists($path, false)) !== false)
			{
				require_once $path;
			}
			else
			{
				return null;
			}
		}

		if (!class_exists($nsClass, false))
		{
			return null;
		}

		$plugin = new $nsClass;

		// Make sure the plugin implements the BasePlugin abstract class
		if (!$plugin instanceof BasePlugin)
		{
			return null;
		}

		return $plugin;
	}

	/**
	 * Returns the actual plugin class handle based on a case-insensitive handle.
	 *
	 * @param $iHandle
	 * @return bool|string
	 */
	private function _getPluginHandleFromFileSystem($iHandle)
	{
		$pluginsPath = blx()->path->getPluginsPath();
		$fullPath = $pluginsPath.strtolower($iHandle).'/'.$iHandle.'Plugin.php';

		if (($file = IOHelper::fileExists($fullPath, true)) !== false)
		{
			$file = IOHelper::getFileName($file, false);
			return substr($file, 0, strlen($file) - strlen('Plugin'));
		}

		return false;
	}
}
