<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\db\MigrationManager;
use craft\app\events\Event;
use craft\app\helpers\IOHelper;
use yii\base\Module;

/**
 * Plugin is the base class for classes representing plugins in terms of objects.
 *
 * @property MigrationManager $migrator The plugin’s migration manager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugin extends Module implements PluginInterface
{
	// Traits
	// =========================================================================

	use PluginTrait;

	// Constants
	// =========================================================================

	/**
	 * @event Event The event that is triggered before the plugin is installed
	 *
	 * You may set [[Event::performAction]] to `false` to prevent the plugin from getting installed.
	 */
	const EVENT_BEFORE_INSTALL = 'beforeInstall';

	/**
	 * @event Event The event that is triggered after the plugin is installed
	 */
	const EVENT_AFTER_INSTALL = 'afterInstall';

	/**
	 * @event Event The event that is triggered before the plugin is updated
	 *
	 * You may set [[Event::performAction]] to `false` to prevent the plugin from getting updated.
	 */
	const EVENT_BEFORE_UPDATE = 'beforeUpdate';

	/**
	 * @event Event The event that is triggered after the plugin is updated
	 */
	const EVENT_AFTER_UPDATE = 'afterUpdate';

	/**
	 * @event Event The event that is triggered before the plugin is uninstalled
	 *
	 * You may set [[Event::performAction]] to `false` to prevent the plugin from getting uninstalled.
	 */
	const EVENT_BEFORE_UNINSTALL = 'beforeUninstall';

	/**
	 * @event Event The event that is triggered after the plugin is uninstalled
	 */
	const EVENT_AFTER_UNINSTALL = 'afterUninstall';

	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function hasCpSection()
	{
		return false;
	}

	// Properties
	// =========================================================================

	/**
	 * @var Model|boolean The model used to store the plugin’s settings
	 * @see getSettingsModel()
	 */
	private $_settingsModel;

	/**
	 * @var string The plugin’s base path
	 */
	private $_basePath;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getHandle()
	{
		return $this->id;
	}

	/**
	 * @inheritdoc
	 */
	public function install()
	{
		if ($this->beforeInstall() === false)
		{
			return false;
		}

		$migrator = $this->getMigrator();

		// Run the install migration, if there is one
		$migration = $this->createInstallMigration();

		if ($migration !== null)
		{
			if ($migrator->migrateUp($migration) === false)
			{
				return false;
			}
		}

		// Mark all existing migrations as applied
		foreach ($migrator->getNewMigrations() as $name)
		{
			$migrator->addMigrationHistory($name);
		}

		$this->afterInstall();
	}

	/**
	 * @inheritdoc
	 */
	public function update($fromVersion)
	{
		if ($this->beforeUpdate() === false)
		{
			return false;
		}

		if ($this->getMigrator()->up() === false)
		{
			return false;
		}

		$this->afterUpdate();
	}

	/**
	 * @inheritdoc
	 */
	public function uninstall()
	{
		if ($this->beforeUninstall() === false)
		{
			return false;
		}

		$migration = $this->createInstallMigration();

		if ($migration !== null)
		{
			if ($this->getMigrator()->migrateDown($migration) === false)
			{
				return false;
			}
		}

		$this->afterUninstall();
	}

	/**
	 * @inheritdoc
	 */
	public function getSettings()
	{
		if ($this->_settingsModel === null)
		{
			$this->_settingsModel = $this->createSettingsModel() ?: false;
		}

		if ($this->_settingsModel !== false)
		{
			return $this->_settingsModel;
		}
		else
		{
			return null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsResponse()
	{
		return Craft::$app->controller->renderTemplate('settings/plugins/_settings', [
			'plugin'       => $this,
			'settingsHtml' => $this->getSettingsHtml()
		]);
	}

	/**
	 * Returns the plugin’s migration manager
	 *
	 * @return MigrationManager The plugin’s migration manager
	 */
	public function getMigrator()
	{
		return $this->get('migrator');
	}

	/**
	 * @inheritdoc
	 */
	public function getVariableDefinition()
	{
		return null;
	}

	/**
	  * Returns the root directory of the module.
	  * It defaults to the directory containing the module class file.
	  * @return string the root directory of the module.
	  */
	 public function getBasePath()
	 {
		 if ($this->_basePath === null)
		 {
			 $this->_basePath = Craft::$app->getPath()->getPluginsPath().'/'.$this->id;
		 }

		 return $this->_basePath;
	 }

	// Component Registration
	// -------------------------------------------------------------------------

	/**
	 * Returns the plugin’s available field types.
	 *
	 * @return FieldInterface[]|Field[]|null
	 */
	public function getFieldTypes()
	{
		return $this->getClassesInSubpath('fields');
	}

	/**
	 * Returns the plugin’s available widget types.
	 *
	 * @return WidgetInterface[]|Widget[]|null
	 */
	public function getWidgetTypes()
	{
		return $this->getClassesInSubpath('widgets');
	}

	/**
	 * Returns the plugin’s available volume types.
	 *
	 * @return VolumeInterface[]|Volume[]|null
	 */
	public function getVolumeTypes()
	{
		return $this->getClassesInSubpath('volumes');
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Instantiates and returns the plugin’s installation migration, if it has one.
	 *
	 * @return Migration|null The plugin’s installation migration
	 */
	protected function createInstallMigration()
	{
		// See if there's an Install migration in the plugin’s migrations folder
		$migrator = $this->getMigrator();
		$path = $migrator->migrationPath.'/Install.php';

		if (IOHelper::fileExists($path))
		{
			require_once($path);

			$class = $migrator->migrationNamespace.'\\Install';
			return new $class;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Performs any actions before the plugin is installed.
	 *
	 * @return boolean Whether the plugin should be installed
	 */
	protected function beforeInstall()
	{
		$event = new Event();
		$this->trigger(static::EVENT_BEFORE_INSTALL, $event);
		return $event->performAction;
	}

	/**
	 * Performs any actions after the plugin is installed.
	 */
	protected function afterInstall()
	{
		$this->trigger(static::EVENT_AFTER_INSTALL, new Event());
	}

	/**
	 * Performs any actions before the plugin is updated.
	 *
	 * @return boolean Whether the plugin should be updated
	 */
	protected function beforeUpdate()
	{
		$event = new Event();
		$this->trigger(static::EVENT_BEFORE_UPDATE, $event);
		return $event->performAction;
	}

	/**
	 * Performs any actions after the plugin is updated.
	 */
	protected function afterUpdate()
	{
		$this->trigger(static::EVENT_AFTER_UPDATE, new Event());
	}

	/**
	 * Performs any actions before the plugin is installed.
	 *
	 * @return boolean Whether the plugin should be installed
	 */
	protected function beforeUninstall()
	{
		$event = new Event();
		$this->trigger(static::EVENT_BEFORE_UNINSTALL, $event);
		return $event->performAction;
	}

	/**
	 * Performs any actions after the plugin is installed.
	 */
	protected function afterUninstall()
	{
		$this->trigger(static::EVENT_AFTER_UNINSTALL, new Event());
	}

	/**
	 * Creates and returns the model used to store the plugin’s settings.
	 *
	 * @return Model|null
	 */
	protected function createSettingsModel()
	{
		return null;
	}

	/**
	 * Returns the rendered settings HTML, which will be inserted into the content block on the settings page.
	 *
	 * @return string The rendered settings HTML
	 */
	protected function getSettingsHtml()
	{
		return null;
	}

	/**
	 * Returns the names of classes located within a subpath of this plugin’s base path.
	 *
	 * @param string $subpath The path to check relative to this plugin’s base path
	 * @param boolean $recursive Whether the path should be checked recursively
	 * @return string
	 */
	protected function getClassesInSubpath($subpath = '', $recursive = true)
	{
		$path = $this->getBasePath().'/'.$subpath;
		// Regex pulled from http://php.net/manual/en/language.oop5.basic.php
		$files = IOHelper::getFolderContents($path, $recursive, ['\/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\.php$']);
		$classes = [];

		if (!empty($files))
		{
			$chop = strlen(IOHelper::getRealPath($path));
			$classPrefix = "craft\\plugins\\{$this->id}\\".trim(str_replace('/', '\\', $subpath), '\\').'\\';

			foreach ($files as $file)
			{
				$classes[] = $classPrefix.str_replace('/', '\\', substr($file, $chop, -4));
			}
		}

		return $classes;
	}
}
