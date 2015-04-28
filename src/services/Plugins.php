<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Plugin;
use craft\app\base\PluginInterface;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use yii\base\Component;
use yii\base\InvalidParamException;

/**
 * The Plugins service provides APIs for managing plugins.
 *
 * An instance of the Plugins service is globally accessible in Craft via [[Application::plugins `Craft::$app->getPlugins()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugins extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event Event The event that is triggered after all plugins have been loaded.
     */
    const EVENT_AFTER_LOAD_PLUGINS = 'afterLoadPlugins';

	// Properties
	// =========================================================================

	/**
	 * Stores whether plugins have been loaded yet for this request.
	 *
	 * @var bool
	 */
	private $_pluginsLoaded = false;

	/**
	 * Stores whether plugins are in the middle of being loaded.
	 *
	 * @var bool
	 */
	private $_loadingPlugins = false;

	/**
	 * Stores references to all the enabled plugins.
	 *
	 * @var array
	 */
	private $_plugins = [];

	/**
	 * Holds a list of all of the stored info for enabled plugins, indexed by the plugins’ handles.
	 *
	 * @var array
	 */
	private $_installedPluginInfo;

	// Public Methods
	// =========================================================================

	/**
	 * Loads the enabled plugins.
	 */
	public function loadPlugins()
	{
		if ($this->_pluginsLoaded === true || $this->_loadingPlugins === true || Craft::$app->isInstalled() === false || Craft::$app->getUpdates()->isCraftDbMigrationNeeded() === true)
		{
			return;
		}

		// Prevent this function from getting called twice.
		$this->_loadingPlugins = true;

		// Find all of the installed plugins
		$this->_installedPluginInfo = (new Query())
			->select(['id', 'handle', 'version', 'enabled', 'settings', 'installDate'])
			->from('{{%plugins}}')
			->indexBy('handle')
			->all();

		foreach ($this->_installedPluginInfo as $handle => &$row)
		{
			// Clean up the row data
			$row['enabled'] = (bool) $row['enabled'];
			$row['settings'] = JsonHelper::decode($row['settings']);
			$row['installDate'] = DateTimeHelper::toDateTime($row['installDate']);

			// Skip disabled plugins
			if ($row['enabled'] !== true)
			{
				continue;
			}

			$plugin = $this->createPlugin($handle, $row);

			if ($plugin !== null)
			{
				$this->_registerPlugin($handle, $plugin);
			}
		}

		$this->_loadingPlugins = false;
		$this->_pluginsLoaded = true;

		// Fire an 'afterLoadPlugins' event
		$this->trigger(static::EVENT_AFTER_LOAD_PLUGINS);
	}

	/**
	 * Returns whether plugins have been loaded yet for this request.
	 *
	 * @return bool Whether plugins have been loaded yet.
	 */
	public function arePluginsLoaded()
	{
		return $this->_pluginsLoaded;
	}

	/**
	 * Returns an enabled plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle
	 * @return PluginInterface|Plugin|null The plugin, or null if it doesn’t exist
	 */
	public function getPlugin($handle)
	{
		$this->loadPlugins();

		if (isset($this->_plugins[$handle]))
		{
			return $this->_plugins[$handle];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Returns all the enabled plugins.
	 *
	 * @return PluginInterface[]|Plugin[] The enabled plugins
	 */
	public function getAllPlugins()
	{
		$this->loadPlugins();
		return $this->_plugins;
	}

	/**
	 * Enables a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 * @return bool Whether the plugin was enabled successfully.
	 * @throws Exception if the plugin isn't installed
	 */
	public function enablePlugin($handle)
	{
		$this->loadPlugins();

		if (!isset($this->_installedPluginInfo[$handle]))
		{
			$this->_noPluginExists($handle);
		}

		if ($this->_installedPluginInfo[$handle]['enabled'] === true)
		{
			// It's already enabled
			return true;
		}

		$plugin = $this->createPlugin($handle, $this->_installedPluginInfo[$handle]);

		if ($plugin === null)
		{
			$this->_noPluginExists($handle);
		}

		Craft::$app->getDb()->createCommand()->update('{{%plugins}}', ['enabled' => '1'], ['handle' => $handle])->execute();
		$this->_installedPluginInfo[$handle]['enabled'] = true;
		$this->_registerPlugin($handle, $plugin);
		return true;
	}

	/**
	 * Disables a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle
	 * @return bool Whether the plugin was disabled successfully
	 * @throws Exception if the plugin isn’t installed
	 */
	public function disablePlugin($handle)
	{
		$this->loadPlugins();

		if (!isset($this->_installedPluginInfo[$handle]))
		{
			$this->_noPluginExists($handle);
		}

		if ($this->_installedPluginInfo[$handle]['enabled'] === false)
		{
			// It's already disabled
			return true;
		}

		$plugin = $this->getPlugin($handle);

		if ($plugin === null)
		{
			$this->_noPluginExists($handle);
		}

		Craft::$app->getDb()->createCommand()->update('{{%plugins}}', ['enabled' => '0'], ['handle' => $handle])->execute();
		$this->_installedPluginInfo[$handle]['enabled'] = false;
		$this->_unregisterPlugin($handle);
		return true;
	}

	/**
	 * Installs a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 *
	 * @throws Exception|\Exception
	 * @return bool Whether the plugin was installed successfully.
	 */
	public function installPlugin($handle)
	{
		$this->loadPlugins();

		if (isset($this->_installedPluginInfo[$handle]))
		{
			// It's already installed
			return true;
		}

		$plugin = $this->createPlugin($handle);

		if ($plugin === null)
		{
			$this->_noPluginExists($handle);
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			$info = [
				'handle'      => $handle,
				'version'     => $plugin->version,
				'enabled'     => true,
				'installDate' => DateTimeHelper::currentTimeForDb(),
			];

			Craft::$app->getDb()->createCommand()->insert('{{%plugins}}', $info)->execute();

			$info['installDate'] = DateTimeHelper::toDateTime($info['installDate']);
			$info['id'] = Craft::$app->getDb()->getLastInsertID();

			$this->_setPluginMigrator($plugin, $handle, $info['id']);

			if ($plugin->install() !== false)
			{
				if ($transaction !== null)
				{
					$transaction->commit();
				}

				$this->_installedPluginInfo[$handle] = $info;
				$this->_registerPlugin($handle, $plugin);
				return true;
			}
			else
			{
				if ($transaction !== null)
				{
					$transaction->rollBack();
				}

				return false;
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Uninstalls a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 * @return bool Whether the plugin was uninstalled successfully
	 * @throws Exception|\Exception
	 */
	public function uninstallPlugin($handle)
	{
		$this->loadPlugins();

		if (!isset($this->_installedPluginInfo[$handle]))
		{
			// It's already uninstalled
			return true;
		}

		// Is it enabled?
		if ($this->_installedPluginInfo[$handle]['enabled'] === true)
		{
			$plugin = $this->getPlugin($handle);
		}
		else
		{
			$plugin = $this->createPlugin($handle, $this->_installedPluginInfo[$handle]);
		}

		if ($plugin === null)
		{
			$this->_noPluginExists($handle);
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			if ($plugin->uninstall() !== false)
			{
				// Clean up the plugins and migrations tables
				$id = $this->_installedPluginInfo[$handle]['id'];
				Craft::$app->getDb()->createCommand()->delete('{{%plugins}}', ['id' => $id])->execute();
				Craft::$app->getDb()->createCommand()->delete('{{%migrations}}', ['pluginId' => $id])->execute();

				if ($transaction !== null)
				{
					// Let's commit to this.
					$transaction->commit();
				}

				$this->_unregisterPlugin($handle);
				unset($this->_installedPluginInfo[$handle]);
				return true;
			}
			else
			{
				if ($transaction !== null)
				{
					$transaction->rollBack();
				}

				return false;
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Saves a plugin's settings.
	 *
	 * @param PluginInterface|Plugin $plugin   The plugin.
	 * @param array $settings The plugin’s new settings.
	 * @return bool Whether the plugin’s settings were saved successfully.
	 */
	public function savePluginSettings(PluginInterface $plugin, $settings)
	{
		// Save the settings on the plugin
		$plugin->getSettings()->setAttributes($settings, false);

		// Validate them, now that it's a model
		if ($plugin->getSettings()->validate() === false)
		{
			return false;
		}

		// JSON-encode them and save the plugin row
		$settings = JsonHelper::encode($plugin->getSettings());

		$affectedRows = Craft::$app->getDb()->createCommand()->update(
			'{{%plugins}}',
			['settings' => $settings],
			['handle' => $plugin->getHandle()]
		)->execute();

		return (bool) $affectedRows;
	}

	/**
	 * Calls a method on all plugins that have it, and returns an array of the results, indexed by plugin handles.
	 *
	 * @param string $method     The name of the method.
	 * @param array  $args       Any arguments that should be passed when calling the method on the plugins.
	 * @param bool   $ignoreNull Whether plugins that have the method but return a null response should be ignored. Defaults to false.
	 *
	 * @return array An array of the plugins’ responses.
	 */
	public function call($method, $args = [], $ignoreNull = false)
	{
		$allResults = [];

		foreach ($this->getAllPlugins() as $handle => $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result = call_user_func_array([$plugin, $method], $args);

				if (!$ignoreNull || $result !== null)
				{
					$allResults[$handle] = $result;
					unset($result);
				}
			}
		}

		return $allResults;
	}

	/**
	 * Calls a method on the first plugin that has it, and returns the result.
	 *
	 * @param string $method     The name of the method.
	 * @param array  $args       Any arguments that should be passed when calling the method on the plugins.
	 * @param bool   $ignoreNull Whether plugins that have the method but return a null response should be ignored. Defaults to false.
	 *
	 * @return mixed The plugin’s response, or null.
	 */
	public function callFirst($method, $args = [], $ignoreNull = false)
	{
		foreach ($this->getAllPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result = call_user_func_array([$plugin, $method], $args);

				if (!$ignoreNull || $result !== null)
				{
					return $result;
				}
			}
		}
	}

	/**
	 * Returns whether the given plugin’s local version number is greater than the record we have in the database.
	 *
	 * @param PluginInterface|Plugin $plugin The plugin
	 * @return bool Whether the plugin’s local version number is greater than the record we have in the database
	 */
	public function doesPluginRequireDatabaseUpdate(PluginInterface $plugin)
	{
		$this->loadPlugins();
		return version_compare($plugin->version, $this->_installedPluginInfo[$plugin->getHandle()]['version'], '>');
	}

	/**
	 * Returns the stored info for a given plugin.
	 *
	 * @param string $handle The plugin handle
	 * @return array|null The stored info, if there is any.
	 */
	public function getStoredPluginInfo($handle)
	{
		$this->loadPlugins();

		if (isset($this->_installedPluginInfo[$handle]))
		{
			return $this->_installedPluginInfo[$handle];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Creates and returns a new plugin instance based on its class handle.
	 *
	 * @param string $handle The plugin’s handle
	 * @param array $row The plugin’s row in the plugins table, if any
	 * @return PluginInterface|Plugin|null
	 */
	public function createPlugin($handle, $row = null)
	{
		$config = $this->getConfig($handle);

		// Make sure it was a valid config
		if ($config === null)
		{
			return null;
		}

		$class = $config['class'];

		// Skip the autoloader since we haven't added a @craft\plugins\PluginHandle alias yet
		if (!class_exists($class, false))
		{
			$path = Craft::$app->getPath()->getPluginsPath()."/$handle/Plugin.php";

			if (($path = IOHelper::fileExists($path)) !== false)
			{
				require $path;
			}
		}

		// Make sure the class exists and it implements PluginInterface
		if (!is_subclass_of($class, 'craft\app\base\PluginInterface'))
		{
			return null;
		}

		// Make this plugin's classes autoloadable and then create it
		Craft::setAlias("@craft/plugins/$handle", "@plugins/$handle");
		/** @var PluginInterface|Plugin $plugin */
		$plugin = Craft::createObject($config, [$handle, Craft::$app]);

		// Set its settings
		if (isset($row['settings']))
		{
			$plugin->getSettings()->setAttributes($row['settings'], false);
		}

		if (isset($row['id']))
		{
			$this->_setPluginMigrator($plugin, $handle, $row['id']);
		}

		return $plugin;
	}

	/**
	 * Returns the config array for a plugin, based on its class handle.
	 *
	 * @param string $handle The plugin’s handle
	 * @return array|null The plugin’s config, if it exists
	 */
	public function getConfig($handle)
	{
		// Make sure this plugin has a config.json file
		$basePath = Craft::$app->getPath()->getPluginsPath().'/'.$handle;
		$configPath = $basePath.'/config.json';

		if (($configPath = IOHelper::fileExists($configPath)) === false)
		{
			Craft::warning("Could not find a config.json file for the plugin '$handle'.");
			return null;
		}

		try
		{
			$config = array_merge([
				'developer' => null,
				'developerUrl' => null
			], JsonHelper::decode(IOHelper::getFileContents($configPath)));
		}
		catch (InvalidParamException $e)
		{
			Craft::warning("Could not decode $configPath: ".$e->getMessage());
			return null;
		}

		// Make sure it's valid
		if (!isset($config['name'], $config['version']))
		{
			Craft::warning("Missing 'name' or 'version' keys in $configPath.");
			return null;
		}

		// Set the class
		if (empty($config['class']))
		{
			// Do they have a custom Plugin class?
			if (IOHelper::fileExists($basePath.'/Plugin.php'))
			{
				$config['class'] = "\\craft\\plugins\\$handle\\Plugin";
			}
			else
			{
				// Just use the base one
				$config['class'] = Plugin::className();
			}
		}

		return $config;
	}

	/**
	 * Returns info about all of the plugins saved in craft/plugins, whether they’re installed or not.
	 *
	 * @return array Info about all of the plugins saved in craft/plugins
	 */
	public function getPluginInfo()
	{
		$this->loadPlugins();

		$info = [];
		$names = [];

		$pluginsPath = Craft::$app->getPath()->getPluginsPath();
		$folders = IOHelper::getFolderContents($pluginsPath, false);

		if ($folders !== false)
		{
			foreach ($folders as $folder)
			{
				// Skip if it's not a folder
				if (IOHelper::folderExists($folder) === false)
				{
					continue;
				}

				$folder = IOHelper::normalizePathSeparators($folder);
				$handle = IOHelper::getFolderName($folder, false);
				$config = $this->getConfig($handle);

				// Skip if it doesn't have a valid config file
				if ($config === null)
				{
					continue;
				}

				$plugin = $this->getPlugin($handle);

				$config['isInstalled'] = isset($this->_installedPluginInfo[$handle]);
				$config['isEnabled'] = ($plugin !== null);
				$config['hasSettings'] = ($plugin !== null && $plugin->getSettings() !== null);

				$info[$handle] = $config;
				$names[] = $config['name'];
			}
		}

		// Sort plugins by their names
		array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $info);

		return $info;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Registers a plugin internally and as an application module.
	 *
	 * This should only be called for enabled plugins
	 *
	 * @param string $handle The plugin’s handle
	 * @param PluginInterface|Plugin $plugin The plugin
	 */
	private function _registerPlugin($handle, PluginInterface $plugin)
	{
		$this->_plugins[$handle] = $plugin;
		Craft::$app->setModule($handle, $plugin);
	}

	/**
	 * Unregisters a plugin internally and as an application module.
	 *
	 * @param string $handle The plugin’s handle
	 */
	private function _unregisterPlugin($handle)
	{
		unset($this->_plugins[$handle]);
		Craft::$app->setModule($handle, null);
	}

	/**
	 * Throws a "no plugin exists" exception.
	 *
	 * @param string $handle
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _noPluginExists($handle)
	{
		throw new Exception("No plugin exists with the handle '$handle'.");
	}

	/**
	 * Sets the 'migrator' component on a plugin.
	 *
	 * @param PluginInterface|Plugin $plugin The plugin
	 * @param string $handle The plugin’s handle
	 * @param integer $id The plugin’s ID
	 */
	private function _setPluginMigrator(PluginInterface $plugin, $handle, $id)
	{
		$plugin->setComponents([
			'migrator' => [
				'class' => 'craft\app\db\MigrationManager',
				'migrationNamespace' => "craft\\plugins\\$handle\\migrations",
				'migrationPath' => "@plugins/$handle/migrations",
				'fixedColumnValues' => ['type' => 'plugin', 'pluginId' => $id],
			]
		]);
	}
}
