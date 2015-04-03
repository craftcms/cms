<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\db\MigrationManager;
use craft\app\events\Event;
use yii\base\Module;

/**
 * Plugin is the base class for classes representing plugins in terms of objects.
 *
 * @property MigrationManager $migrator The plugin’s migration manager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Plugin extends Module implements PluginInterface
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

		$migration = $this->createInstallMigration();

		if ($migration !== null)
		{
			if ($this->getMigrator()->migrateUp($migration) === false)
			{
				return false;
			}
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
		Craft::$app->controller->renderTemplate('settings/plugins/_settings', [
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

	// Protected Methods
	// =========================================================================

	/**
	 * Instantiates and returns the plugin’s installation migration, if it has one.
	 *
	 * @return Migration|null The plugin’s installation migration
	 */
	protected function createInstallMigration()
	{
		return null;
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
}
