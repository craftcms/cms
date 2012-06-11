<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends \CApplicationComponent
{
	/**
	 * Stores all enabled plugins. Populated on init.
	 * @var array
	 */
	private $_enabledPlugins = array();

	/**
	 * Stores all initialized plugins for the current request.
	 * @access private
	 * @var array
	 */
	private $_plugins;

	/**
	 * Stores all plugins, whether installed or not
	 * @access private
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * Get all enabled plugins right away.
	 */
	public function init()
	{
		$records = Plugin::model()->findAllByAttributes(array(
			'enabled' => true
		));

		foreach ($records as $record)
		{
			$plugin = $this->getPlugin($record->class);
			if ($plugin)
			{
				$key = strtolower($plugin->getClassHandle());
				$this->_enabledPlugins[$key] = $plugin;

				$plugin->record = $record;

				$this->_registerPluginServices($plugin->getClassHandle());
				$this->_importPluginModels($plugin->getClassHandle());
			}
		}
	}

	/**
	 * @return array
	 */
	public function getEnabledPlugins()
	{
		return $this->_enabledPlugins;
	}

	/**
	 * Returns a plugin by its class handle, regardless of whether it's installed or not.
	 * @param string $classHandle
	 * @return BasePlugin
	 */
	public function getPlugin($classHandle)
	{
		// Plugins are indexed by lowercase class handles
		$key = strtolower($classHandle);

		if (!isset($this->_plugins[$key]))
		{
			// Get the full class name
			$class = $classHandle.'Plugin';
			$nsClass = __NAMESPACE__.'\\'.$class;

			// Skip the autoloader
			if (!class_exists($nsClass, false))
			{
				$path = blx()->path->getPluginsPath().$classHandle.'/'.$class.'.php';
				if (($path = File::fileExists($path)) !== false)
					require_once $path;
			}

			if (!class_exists($nsClass, false))
				$this->_plugins[$key] = false;
			else
			{
				$this->_plugins[$key] = new $nsClass;
				$this->_plugins[$key]->init();
			}
		}

		return $this->_plugins[$key];
	}

	/**
	 * Returns all plugins, whether they're installed or not.
	 * @return array
	 */
	public function getAllPlugins()
	{
		if (!isset($this->_allPlugins))
		{
			$this->_allPlugins = array();

			// Find all of the plugins in the plugins folder
			$pluginsPath = blx()->path->getPluginsPath();
			$folders = scandir($pluginsPath);
			foreach ($folders as $folder)
			{
				// Ignore files and relative directories
				if (strncmp($folder, '.', 1) === 0 || !is_dir($pluginsPath.$folder))
					continue;

				// Folder names == the class handle
				$plugin = $this->getPlugin($folder);
				if ($plugin)
				{
					$key = strtolower($plugin->getClassHandle());
					$this->_allPlugins[$key] = $plugin;
				}
			}

			// Sort plugins by their names
			uasort($this->_allPlugins, array($this, '_comparePluginNames'));

			// Now figure out which of these are installed
			$records = Plugin::model()->findAll();

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
	 * @access private
	 * @param $a BasePlugin
	 * @param $b BasePlugin
	 * @return int
	 */
	private function _comparePluginNames($a, $b)
	{
		if ($a->name == $b->name)
			return 0;
		else
			return ($a->name < $b->name) ? -1 : 1;
	}

	/**
	 * Enables a plugin.
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function enablePlugin($className)
	{
		$this->getAllPlugins();
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			throw new Exception('No plugin exists with the class “'.$className.'”');

		if (!$plugin->getIsInstalled())
			throw new Exception($plugin->name.' can’t be enabled because it isn’t installed yet.');

		$plugin->record->enabled = true;
		if ($plugin->record->save())
			return true;
		else
			return false;
	}

	/**
	 * Disables a plugin.
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function disablePlugin($className)
	{
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			throw new Exception('No plugin exists with the class “'.$className.'”');

		if (!$plugin->getIsInstalled())
			throw new Exception($plugin->name.' can’t be disabled because it isn’t installed yet.');

		$plugin->record->enabled = false;
		if ($plugin->record->save())
			return true;
		else
			return false;
	}

	/**
	 * Installs a plugin.
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function installPlugin($className)
	{
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			throw new Exception('No plugin exists with the class “'.$className.'”');

		if ($plugin->getIsInstalled())
			throw new Exception($plugin->name.' is already installed.');

		$record = new Plugin;
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
	 * @param $className
	 * @throws Exception
	 * @return bool
	 */
	public function uninstallPlugin($className)
	{
		$plugin = $this->getPlugin($className);

		if (!$plugin)
			throw new Exception('No plugin exists with the class “'.$className.'”');

		if (!$plugin->getIsInstalled())
			throw new Exception($plugin->name.' is already uninstalled.');

		if ($plugin->record->delete())
		{
			unset($plugin->record);
			return true;
		}
		else
			return false;
	}

	/**
	 * Calls a hook in any plugin that has it.
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
	 * Imports any models provided by a plugin.
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
			if (($files = @glob($modelsDirectory.$className."_*.php")) !== false)
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
			if (($files = @glob($serviceDirectory.$className."*Service.php")) !== false)
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
