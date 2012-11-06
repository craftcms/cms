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
	private $_enabledPlugins = array();
	private $_allPlugins;

	/**
	 * @var array
	 */
	private $_defaultFolders = array(
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
	 * @var array
	 */
	private $_pluginFileMap = array();

	/**
	 * Get all enabled plugins right away.
	 */
	public function init()
	{
		if (blx()->isInstalled())
		{
			$records = PluginRecord::model()->findAllByAttributes(array(
				'enabled' => true
			));

			foreach ($records as $record)
			{
				$plugin = $this->_getPlugin($record->class);

				if ($plugin)
				{
					$lcHandle = strtolower($plugin->getClassHandle());
					$this->_plugins[$lcHandle] = $plugin;
					$this->_enabledPlugins[$lcHandle] = $plugin;

					$plugin->setSettings($record->settings);

					$plugin->isInstalled = true;
					$plugin->isEnabled = true;

					$this->_processPluginClasses($plugin->getClassHandle());
					$this->_registerPluginServices($plugin->getClassHandle());

					$plugin->init();
				}
			}
		}
	}

	/**
	 * Returns a plugin.
	 *
	 * @param string $handle
	 * @param bool   $enabledOnly
	 * @return mixed
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
			if (!isset($this->_plugins[$lcHandle]))
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
						}
					}
				}
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
			$installableRecords = $this->getPluginRecords($handle, 'install');

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

			// Add the plugins as a record to the database.
			$record = new PluginRecord();
			$record->class = $plugin->getClassHandle();
			$record->version = $plugin->version;
			$record->enabled = true;
			$record->save();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		$plugin->isInstalled = true;
		$plugin->isEnabled = true;

		$lcHandle = strtolower($plugin->getClassHandle());
		$this->_enabledPlugins[$lcHandle] = $plugin;

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
		$plugin = $this->getPlugin($handle);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Blocks::t('“{plugin}” is already uninstalled.', array('plugin' => $plugin->getName())));
		}

		$plugin->onBeforeUninstall();

		$records = $this->getPluginRecords($handle);

		$transaction = blx()->db->beginTransaction();
		try
		{
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
			blx()->db->createCommand()->delete('plugins', array('class' => $plugin->getClassHandle()));

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

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
	 * @param string $methodName
	 * @param array $args
	 * @return array
	 */
	public function callHook($methodName, $args = array())
	{
		$result = array();

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
	 * @param $handle
	 * @return mixed
	 */
	public function getPluginModels($handle)
	{
		$models = array();

		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (isset($this->_pluginFileMap[$plugin->getClassHandle()]['models']))
		{
			foreach ($this->_pluginFileMap[$plugin->getClassHandle()]['models'] as $modelPath)
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
	 * @param $handle
	 * @return mixed
	 */
	public function getPluginServices($handle)
	{
		$services = array();

		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (isset($this->_pluginFileMap[$plugin->getClassHandle()]['services']))
		{
			foreach ($this->_pluginFileMap[$plugin->getClassHandle()]['services'] as $servicePath)
			{
				$class = __NAMESPACE__.'\\'.IOHelper::getFileName($servicePath, false);

				// Ignore abstract classes and interfaces
				$ref = new \ReflectionClass($class);

				if ($ref->isAbstract() || $ref->isInterface())
				{
					continue;
				}

				$services[] = $servicePath;
			}
		}

		return $services;
	}

	/**
	 * @param      $handle
	 * @param null $scenario
	 * @return mixed
	 */
	public function getPluginRecords($handle, $scenario = null)
	{
		$records = array();

		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (isset($this->_pluginFileMap[$plugin->getClassHandle()]['records']))
		{
			foreach ($this->_pluginFileMap[$plugin->getClassHandle()]['records'] as $recordPath)
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
	 * @param string $handle
	 * @throws Exception
	 */
	private function _noPluginExists($handle)
	{
		throw new Exception(Blocks::t('No plugin exists with the class “{class}”', array('class' => $handle)));
	}

	/**
	 * @param      $handle
	 * @param bool $import
	 * @return void
	 */
	private function _processPluginClasses($handle, $import = true)
	{
		$lcHandle = strtolower($handle);
		$pluginFolder = blx()->path->getPluginsPath().$lcHandle.'/';

		foreach ($this->_defaultFolders as $folderName => $suffix)
		{
			if (IOHelper::folderExists($pluginFolder.$folderName))
			{
				// See if it has any files in ClassName*Suffix.php format.
				$files = IOHelper::getFolderContents($pluginFolder.$folderName, false, "{$handle}_?.*{$suffix}\.php");

				if (is_array($files) && count($files) > 0)
				{
					foreach ($files as $file)
					{
						// Get the file name minus the extension.
						$fileName = IOHelper::getFileName($file, false);

						if ($import)
						{
							// Import the class.
							Blocks::import("plugins.{$lcHandle}.{$folderName}.{$fileName}");
						}

						if (!isset($this->_pluginFileMap[$handle][$folderName]))
						{
							$this->_pluginFileMap[$handle][$folderName][] = $file;
						}
						else
						{
							if (!in_array($file, $this->_pluginFileMap[$handle][$folderName]))
							{
								$this->_pluginFileMap[$handle][$folderName][] = $file;
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
	 * @param string $handle
	 * @throws Exception
	 * @return void
	 */
	private function _registerPluginServices($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (isset($this->_pluginFileMap[$plugin->getClassHandle()]['services']))
		{
			foreach ($this->_pluginFileMap[$plugin->getClassHandle()]['services'] as $filePath)
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
					throw new Exception(Blocks::t('The plugin “{handle}” tried to register a service “{service}” that conflicts with a core service name.', array('handle' => $handle, 'service' => $serviceName)));
				}
			}
		}
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
		$fullPath = $pluginsPath.$iHandle.'/'.$iHandle.'Plugin.php';

		if (($file = IOHelper::fileExists($fullPath, true)) !== false)
		{
			$file = IOHelper::getFileName($file, false);
			return substr($file, 0, strlen($file) - strlen('Plugin'));
		}

		return false;
	}
}
