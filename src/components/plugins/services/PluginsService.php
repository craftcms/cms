<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends BaseApplicationComponent
{
	/**
	 * Stores all enabled plugins. Populated on init.
	 *
	 * @var array
	 */
	private $_enabledPlugins = array();

	/**
	 * Stores all installed plugins, whether they're enabled or not.
	 *
	 * @var array
	 */
	private $_installedPlugins;

	/**
	 * Stores all initialized plugins for the current request.
	 *
	 * @access private
	 * @var array
	 */
	private $_plugins;

	/**
	 * Stores all plugins, whether installed or not
	 *
	 * @access private
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * @var array
	 */
	private $_pluginRecordPaths = array();

	/**
	 * Get all enabled plugins right away.
	 */
	public function init()
	{
		$records = PluginRecord::model()->findAllByAttributes(array(
			'enabled' => true
		));

		foreach ($records as $record)
		{
			$plugin = $this->getPlugin($record->class);

			if ($plugin)
			{
				$this->_enabledPlugins[$plugin->getClassHandle()] = $plugin;
				$plugin->record = $record;

				$this->_registerPluginServices($plugin->getClassHandle());
				$this->_importPluginRecords($plugin->getClassHandle());
			}
		}
	}

	/**
	 * Returns the enabled plugins.
	 *
	 * @return array
	 */
	public function getEnabledPlugins()
	{
		return $this->_enabledPlugins;
	}

	/**
	 * Returns whether a plugin is installed.
	 *
	 * @param string $class
	 * @return bool
	 */
	public function isPluginInstalled($class)
	{
		if (!isset($this->_installedPlugins))
		{
			$this->_installedPlugins = array();

			$records = blx()->db->createCommand()
				->select('class')
				->from('plugins')
				->queryAll();

			foreach ($records as $record)
			{
				$this->_installedPlugins[] = $record['class'];
			}
		}

		return in_array($class, $this->_installedPlugins);
	}

	/**
	 * Returns a plugin by its class handle, regardless of whether it's installed or not.
	 *
	 * @param string $classHandle
	 * @return BasePlugin
	 */
	public function getPlugin($classHandle)
	{
		if (!isset($this->_plugins[$classHandle]))
		{
			// Get the full class name
			$class = $classHandle.'Plugin';
			$nsClass = __NAMESPACE__.'\\'.$class;

			// Skip the autoloader
			if (!class_exists($nsClass, false))
			{
				$path = blx()->path->getPluginsPath().$classHandle.'/'.$class.'.php';

				if (($path = IOHelper::fileExists($path, false)) !== false)
				{
					require_once $path;
				}
				else
				{
					return false;
				}
			}

			if (!class_exists($nsClass, false))
			{
				return false;
			}

			$plugin = new $nsClass;

			// Make sure the plugin implements the IPlugin interface
			if (!$plugin instanceof IPlugin)
			{
				return false;
			}

			$plugin->init();

			$this->_plugins[$classHandle] = $plugin;
		}

		return $this->_plugins[$classHandle];
	}

	/**
	 * Returns all plugins, whether they're installed or not.
	 *
	 * @return array
	 */
	public function getAllPlugins()
	{
		if (!isset($this->_allPlugins))
		{
			$this->_allPlugins = array();

			// Find all of the plugins in the plugins folder
			$pluginsPath = blx()->path->getPluginsPath();
			$paths = IOHelper::getFolderContents($pluginsPath, true, ".*Plugin\.php");

			if (is_array($paths) && count($paths) > 0)
			{
				foreach ($paths as $path)
				{
					$path = IOHelper::normalizePathSeparators($path);
					$handle = IOHelper::getFileName($path, false);
					$handle = substr($handle, 0, strlen($handle) - strlen('Plugin'));

					// Plugin file name (minus 'Plugin') == the class handle
					$plugin = $this->getPlugin($handle);

					if ($plugin)
					{
						$this->_allPlugins[$plugin->getClassHandle()] = $plugin;
					}
				}
			}

			// Sort plugins by their names
			uasort($this->_allPlugins, array($this, '_comparePluginNames'));

			// Now figure out which of these are installed
			$records = PluginRecord::model()->findAll();

			foreach ($records as $record)
			{
				$plugin = $this->getPlugin($record->class);

				if ($plugin && !isset($plugin->record))
				{
					$plugin->record = $record;
				}
			}
		}

		return $this->_allPlugins;
	}

	/**
	 * Compares two plugins' names.
	 *
	 * @access private
	 * @param $a BasePlugin
	 * @param $b BasePlugin
	 * @return int
	 */
	private function _comparePluginNames($a, $b)
	{
		if ($a->getName() == $b->getName())
		{
			return 0;
		}
		else
		{
			return ($a->getName() < $b->getName()) ? -1 : 1;
		}
	}

	/**
	 * Enables a plugin.
	 *
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function enablePlugin($className)
	{
		$className = strtolower($className);

		$this->getAllPlugins();
		$plugin = $this->getPlugin($className);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if (!$plugin->isInstalled())
		{
			throw new Exception(Blocks::t('“{plugin}” can’t be enabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		$plugin->record->enabled = true;
		return $plugin->record->save();
	}

	/**
	 * Disables a plugin.
	 *
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function disablePlugin($className)
	{
		$className = strtolower($className);
		$plugin = $this->getPlugin($className);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if (!$plugin->isInstalled())
		{
			throw new Exception(Blocks::t('“{plugin}” can’t be disabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		$plugin->record->enabled = false;
		return $plugin->record->save();
	}

	/**
	 * Installs a plugin.
	 *
	 * @param $className
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function installPlugin($className)
	{
		$plugin = $this->getPlugin($className);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if ($plugin->isInstalled())
		{
			throw new Exception(Blocks::t('“{plugin}” is already installed.', array('plugin' => $plugin->getName())));
		}

		$transaction = blx()->db->beginTransaction();
		try
		{
			$installableRecords = $this->instantiateRecordsForClass($className, 'install');

			// Create all tables first.
			foreach ($installableRecords as $record)
			{
				if (method_exists($record, 'createTable'))
				{
					$record->createTable();
				}
			}

			// Create all foreign keys next.
			foreach ($installableRecords as $record)
			{
				if (method_exists($record, 'addForeignKeys'))
				{
					$record->addForeignKeys();
				}
			}

			// See if the plugin has implemented an install method.
			if (method_exists($plugin, 'install'))
			{
				$plugin->install();
			}

			// Add the plugins as a record to the database.
			$record = new PluginRecord();
			$record->class = $plugin->getClassHandle();
			$record->version = $plugin->version;
			$record->enabled = true;

			if ($record->save())
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * Uninstalls a plugin by removing it's record from the database, deleting it's tables and foreign keys and running the plugin's uninstall method if it exists.
	 *
	 * @param $className
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function uninstallPlugin($className)
	{
		$className = strtolower($className);

		$plugin = $this->getPlugin($className);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if (!$plugin->isInstalled())
		{
			throw new Exception(Blocks::t('“{plugin}” is already uninstalled.', array('plugin' => $plugin->getName())));
		}

		$records = $this->instantiateRecordsForClass($className);

		$transaction = blx()->db->beginTransaction();
		try
		{
			// If they have defined an uninstall method, let's call it.
			if (method_exists($plugin, 'uninstall'))
			{
				$plugin->uninstall();
			}

			// Remove any foreign keys.
			foreach ($records as $record)
			{
				$record->dropForeignKeys();
			}

			// Remove any tables.
			foreach ($records as $record)
			{
				$record->dropTable();
			}

			// Remove the row from the database.
			if (blx()->db->createCommand()->delete('plugins', array('class' => $className)))
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * Calls a hook in any plugin that has it.
	 *
	 * @param string $methodName
	 * @param array $args
	 * @return array
	 */
	public function callHook($methodName, $args = array())
	{
		$result = array();

		foreach ($this->_enabledPlugins as $plugin)
		{
			if (method_exists($plugin, $methodName))
			{
				$result[] = call_user_func_array(array($plugin, $methodName), $args);
			}
		}

		return $result;
	}

	/**
	 * Throws a "no plugin exists" exception.
	 *
	 * @access private
	 * @param string $className
	 * @throws Exception
	 */
	private function _noPluginExists($className)
	{
		throw new Exception(Blocks::t('No plugin exists with the class “{class}”', array('class' => $className)));
	}

	/**
	 * Imports any records provided by a plugin.
	 *
	 * @access private
	 * @param string $className
	 */
	private function _importPluginRecords($className)
	{
		$origClassName = $className;
		$className = strtolower($className);
		$modelsFolder = blx()->path->getPluginsPath().$origClassName.'/models/';

		// Make sure it exists.
		if (IOHelper::folderExists($modelsFolder))
		{
			// See if it has any files in ClassName*Record.php format.
			$files = IOHelper::getFolderContents($modelsFolder, false, "{$origClassName}_.*Record\.php");

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					// Get the file name minus the extension.
					$fileName = IOHelper::getFileName($file, false);

					// Import the class.
					Blocks::import('plugins.'.$className.'.models.'.$fileName);

					$this->_pluginRecordPaths[$className][] = $file;
				}
			}
		}
	}

	/**
	 * @param         $className
	 * @param  null   $scenario
	 * @return array
	 */
	public function instantiateRecordsForClass($className, $scenario = null)
	{
		$records = array();

		$this->_importPluginRecords($className);
		$className = strtolower($className);

		foreach ($this->_pluginRecordPaths[$className] as $recordPath)
		{
			$class = __NAMESPACE__.'\\'.IOHelper::getFileName($recordPath, false);

			// Ignore abstract classes and interfaces
			$ref = new \ReflectionClass($class);

			if ($ref->isAbstract() || $ref->isInterface())
				continue;

			$records[] = new $class($scenario);
		}

		return $records;
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @access private
	 * @param string $className
	 */
	private function _registerPluginServices($className)
	{
		// Get the services folder for the plugin.
		$serviceFolder = blx()->path->getPluginsPath().$className.'/services/';

		// Make sure it exists.
		if (IOHelper::folderExists($serviceFolder))
		{
			// See if it has any files in ClassName*Service.php format.
			$files = IOHelper::getFolderContents($serviceFolder, false, "{$className}.*Service\.php");
			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					// Get the file name minus the extension.
					$fileName = IOHelper::getFileName($file, false);

					// Import the class.
					Blocks::import('plugins.'.$className.'.services.'.$fileName);

					// Register the component with the handle as (ClassName or ClassName_*) minus "Service" if multiple.
					blx()->setComponents(array(strtolower(substr($fileName, 0, strpos($fileName, 'Service')) ) => array('class' => __NAMESPACE__.'\\'.$fileName)), false);
				}
			}
		}
	}
}
