<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends BaseApplicationComponent
{
	/**
	 * Stores all plugins, whether installed or not
	 *
	 * @access private
	 * @var array
	 */
	private $_plugins = array();

	/**
	 * @var array
	 */
	private $_defaultFolders = array(
		'controllers'   => 'Controller',
		'models'        => 'Model',
		'records'       => 'Record',
		'services'      => 'Service',
		'variables'     => 'Variable',
		'packages'      => 'Package',
		'helpers'       => 'Helper',
		'blocks'        => 'Block',
		'widgets'       => 'Widget',
		'validators'    => 'Validator',
	);

	/**
	 * @var array
	 */
	private $_pluginFileMap = array();

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
				$this->_plugins[strtolower($plugin->getClassHandle())] = $plugin;
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
		$enabledPlugins = array();

		foreach ($this->_plugins as $plugin)
		{
			if ($plugin->isEnabled())
			{
				$enabledPlugins[] = $plugin;
			}
		}

		return $enabledPlugins;
	}

	/**
	 * Returns whether a plugin is installed.
	 *
	 * @param string $class
	 * @return bool
	 */
	public function isPluginInstalled($class)
	{
		$class = strtolower($class);

		if (($plugin = $this->getPlugin($class, false)) !== false)
		{
			if ($plugin->record)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns an enabled plugin.
	 *
	 * @param       $classHandle
	 * @param  bool $enabledOnly
	 * @return null
	 */
	public function getPlugin($classHandle, $enabledOnly = true)
	{
		$classHandle = strtolower($classHandle);

		if ($enabledOnly && isset($this->_plugins[$classHandle]) && $this->_plugins[$classHandle]->record !== null && $this->_plugins[$classHandle]->record->enabled)
		{
			return $this->_plugins[$classHandle];
		}

		if ((!isset($this->_plugins[$classHandle])))
		{
			if (($plugin = $this->_processPlugin($classHandle)) == false)
			{
				return null;
			}

			$pluginRecord = PluginRecord::model()->findByAttributes(array(
				'class' => $classHandle
			));

			// See if the plugin is installed && enabled.
			if ($pluginRecord)
			{
				$plugin->record = $pluginRecord;

				if ($pluginRecord->enabled)
				{
					$this->_processPluginClasses($plugin->getClassHandle());
					$this->_registerPluginServices($plugin->getClassHandle());
				}

				$plugin->init();
			}
			else
			{
				// Not enabled, but let's add the classes to our file map.
				$this->_processPluginClasses($plugin->getClassHandle(), false);
			}

			$this->_plugins[$classHandle] = $plugin;
		}

		if ($enabledOnly)
		{
			if (!$this->_plugins[$classHandle]->record || !$this->_plugins[$classHandle]->record->enabled)
			{
				return null;
			}
		}

		return $this->_plugins[$classHandle];
	}

	/**
	 * Returns all plugins, whether they're installed or not.
	 *
	 * @return array
	 */
	public function getPlugins()
	{
		// Find all of the plugins in the plugins folder
		$pluginsPath = blx()->path->getPluginsPath();
		$paths = IOHelper::getFolderContents($pluginsPath, true, ".*Plugin\.php");

		if (is_array($paths) && count($paths) > 0)
		{
			foreach ($paths as $path)
			{
				$path = IOHelper::normalizePathSeparators($path);
				$handle = IOHelper::getFileName($path, false);
				$handle = strtolower(substr($handle, 0, strlen($handle) - strlen('Plugin')));

				// See if we've already loaded this plugin
				if (isset($this->_plugins[$handle]))
				{
					continue;
				}

				// Plugin file name (minus 'Plugin') == the class handle
				if (($plugin = $this->getPlugin($handle, false)) !== false)
				{
					$this->_plugins[strtolower($plugin->getClassHandle())] = $plugin;
				}
			}
		}

		// Sort plugins by their names
		uasort($this->_plugins, array($this, '_comparePluginNames'));

		return $this->_plugins;
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
		$plugin = $this->getPlugin($className, false);

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
		$className = strtolower($className);
		$plugin = $this->getPlugin($className, false);

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
			$installableRecords = $this->getPluginRecords($className, 'install');

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

		$records = $this->getPluginRecords($className);

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

		foreach ($this->_plugins as $plugin)
		{
			if ($plugin->isEnabled())
			{
				if (method_exists($plugin, $methodName))
				{
					$result[] = call_user_func_array(array($plugin, $methodName), $args);
				}
			}
		}

		return $result;
	}

	/**
	 * @param $className
	 * @return mixed
	 */
	public function getPluginModels($className)
	{
		$className = strtolower($className);
		$models = array();

		$plugin = $this->getPlugin($className, false);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if (isset($this->_pluginFileMap[$className]) && isset($this->_pluginFileMap[$className]['models']))
		{
			foreach ($this->_pluginFileMap[$className]['models'] as $modelPath)
			{
				$class = __NAMESPACE__.'\\'.IOHelper::getFileName($modelPath, false);

				if (!class_exists($class, false))
				{
					require_once $modelPath;
				}

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($class);

				if ($ref->isAbstract() || $ref->isInterface())
					continue;

				$models[] = new $class;
			}
		}

		return $models;
	}

	/**
	 * @param $className
	 * @return mixed
	 */
	public function getPluginServices($className)
	{
		$className = strtolower($className);
		$services = array();

		$plugin = $this->getPlugin($className, false);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if (isset($this->_pluginFileMap[$className]) && isset($this->_pluginFileMap[$className]['services']))
		{
			foreach ($this->_pluginFileMap[$className]['services'] as $servicePath)
			{
				$class = __NAMESPACE__.'\\'.IOHelper::getFileName($servicePath, false);

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($class);

				if ($ref->isAbstract() || $ref->isInterface())
					continue;

				$services[] = $servicePath;
			}
		}

		return $services;
	}

	/**
	 * @param      $className
	 * @param null $scenario
	 * @return mixed
	 */
	public function getPluginRecords($className, $scenario = null)
	{
		$className = strtolower($className);
		$records = array();

		$plugin = $this->getPlugin($className, false);

		if (!$plugin)
		{
			$this->_noPluginExists($className);
		}

		if (isset($this->_pluginFileMap[$className]) && isset($this->_pluginFileMap[$className]['records']))
		{
			foreach ($this->_pluginFileMap[$className]['records'] as $recordPath)
			{
				$class = __NAMESPACE__.'\\'.IOHelper::getFileName($recordPath, false);

				if (!class_exists($class, false))
				{
					require_once $recordPath;
				}

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($class);

				if ($ref->isAbstract() || $ref->isInterface())
				{
					continue;
				}

				$records[] = new $class($scenario);
			}
		}

		return $records;
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
	 * @param      $className
	 * @param bool $import
	 * @return void
	 */
	private function _processPluginClasses($className, $import = true)
	{
		$origClassName = $className;
		$className = strtolower($className);
		$pluginFolder = blx()->path->getPluginsPath().$className.'/';

		foreach ($this->_defaultFolders as $folderName => $suffix)
		{
			if (IOHelper::folderExists($pluginFolder.$folderName))
			{
				// See if it has any files in ClassName*Suffix.php format.
				$files = IOHelper::getFolderContents($pluginFolder.$folderName, false, "{$origClassName}_?.*{$suffix}\.php");

				if (is_array($files) && count($files) > 0)
				{
					foreach ($files as $file)
					{
						// Get the file name minus the extension.
						$fileName = IOHelper::getFileName($file, false);

						if ($import)
						{
							// Import the class.
							Blocks::import("plugins.{$className}.{$folderName}.{$fileName}");
						}

						if (!isset($this->_pluginFileMap[$className][$folderName]))
						{
							$this->_pluginFileMap[$className][$folderName][] = $file;
						}
						else
						{
							if (!in_array($file, $this->_pluginFileMap[$className][$folderName]))
							{
								$this->_pluginFileMap[$className][$folderName][] = $file;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @access private
	 * @param string $className
	 * @throws Exception
	 * @return void
	 */
	private function _registerPluginServices($className)
	{
		$className = strtolower($className);

		if (isset($this->_pluginFileMap[$className]) && isset($this->_pluginFileMap[$className]['services']))
		{
			foreach ($this->_pluginFileMap[$className]['services'] as $filePath)
			{
				$fileName = IOHelper::getFileName($filePath, false);
				$parts = explode('_', $fileName);

				foreach ($parts as $index => $part)
				{
					$parts[$index] = lcfirst($part);
				}

				$serviceName = implode('_', $parts);
				$serviceName = substr($serviceName, 0, strpos($serviceName, 'Service'));

				if (!blx()->getComponent($serviceName, false))
				{
					// Register the component with the handle as (className or className_*) minus "Service" if multiple.
					blx()->setComponents(array($serviceName => array('class' => __NAMESPACE__.'\\'.$fileName)), false);
				}
				else
				{
					throw new Exception(Blocks::t('The plugin “{className}” tried to register a service “{serviceName}” that conflicts with a core service name.', array('className' => $className, 'serviceName' => $serviceName)));
				}
			}
		}
	}

	/**
	 * @param $classHandle
	 * @return bool
	 */
	private function _processPlugin($classHandle)
	{
		$classHandle = strtolower($classHandle);

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

		return $plugin;
	}
}
