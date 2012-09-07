<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends ApplicationComponent
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
				$this->_importPluginModels($plugin->getClassHandle());
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

				if (($path = File::fileExists($path, false)) !== false)
					require_once $path;
				else
					return false;
			}

			if (class_exists($nsClass, false))
			{
				$plugin = new $nsClass;
				$plugin->init();

				$this->_plugins[$classHandle] = $plugin;
			}
			else
				return false;
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
			$pluginsPath = blx()->file->set(blx()->path->getPluginsPath());
			$paths = $pluginsPath->getContents(true, '/[^_]Plugin.php/');

			if (is_array($paths) && count($paths) > 0)
			{
				foreach ($paths as $path)
				{
					$handle = pathinfo($path, PATHINFO_FILENAME);
					$handle = substr($handle, 0, strlen($handle) - strlen('Plugin'));

					// Plugin file name (minus 'Plugin') == the class handle
					$plugin = $this->getPlugin($handle);
					if ($plugin)
						$this->_allPlugins[$plugin->getClassHandle()] = $plugin;
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
					$plugin->record = $record;
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
			return 0;
		else
			return ($a->getName() < $b->getName()) ? -1 : 1;
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
		$this->getAllPlugins();
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			$this->_noPluginExists($className);

		if (!$plugin->isInstalled())
			throw new Exception(Blocks::t('“{plugin}” can’t be enabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));

		$plugin->record->enabled = true;
		if ($plugin->record->save())
			return true;
		else
			return false;
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
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			$this->_noPluginExists($className);

		if (!$plugin->isInstalled())
			throw new Exception(Blocks::t('“{plugin}” can’t be disabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));

		$plugin->record->enabled = false;
		if ($plugin->record->save())
			return true;
		else
			return false;
	}

	/**
	 * Installs a plugin.
	 *
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function installPlugin($className)
	{
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			$this->_noPluginExists($className);

		if ($plugin->isInstalled())
			throw new Exception(Blocks::t('“{plugin}” is already installed.', array('plugin' => $plugin->getName())));

		$record = new PluginRecord();
		$record->class = $plugin->getClassHandle();
		$record->version = $plugin->version;
		$record->enabled = true;

		if ($record->save())
			return true;
		else
			return false;
	}

	/**
	 * Uninstalls a plugin by removing it's record from the database.
	 *
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function uninstallPlugin($className)
	{
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			$this->_noPluginExists($className);

		if (!$plugin->isInstalled())
			throw new Exception(Blocks::t('“{plugin}” is already uninstalled.', array('plugin' => $plugin->getName())));

		blx()->db->createCommand()->delete('plugins', array('class' => $className));
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
	 * Imports any models provided by a plugin.
	 *
	 * @access private
	 * @param string $className
	 */
	private function _importPluginModels($className)
	{
		$modelsDirectory = blx()->path->getPluginsPath().$className.'/models/';

		// Make sure it exists.
		if (is_dir($modelsDirectory))
		{
			// See if it has any files in ClassName*Service.php format.
			$files = glob($modelsDirectory.$className."_*.php");
			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					// Get the file name minus the extension.
					$fileName = pathinfo($file, PATHINFO_FILENAME);

					// Import the class.
					Blocks::import('plugins.'.$className.'.models.'.$fileName);
				}
			}
		}
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @access private
	 * @param string $className
	 */
	private function _registerPluginServices($className)
	{
		// Get the services directory for the plugin.
		$serviceDirectory = blx()->path->getPluginsPath().$className.'/services/';

		// Make sure it exists.
		if (is_dir($serviceDirectory))
		{
			// See if it has any files in ClassName*Service.php format.
			$files = glob($serviceDirectory.$className."*Service.php");
			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					// Get the file name minus the extension.
					$fileName = pathinfo($file, PATHINFO_FILENAME);

					// Import the class.
					Blocks::import('plugins.'.$className.'.services.'.$fileName);

					// Register the component with the handle as (ClassName or ClassName_*) minus "Service" if multiple.
					blx()->setComponents(array(strtolower(substr($fileName, 0, strpos($fileName, 'Service')) ) => array('class' => __NAMESPACE__.'\\'.$fileName)), false);
				}
			}
		}
	}
}
