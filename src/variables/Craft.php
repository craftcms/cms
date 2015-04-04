<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\elements\Asset;
use craft\app\elements\Category;
use craft\app\elements\db\AssetQuery;
use craft\app\elements\db\CategoryQuery;
use craft\app\elements\db\EntryQuery;
use craft\app\elements\db\TagQuery;
use craft\app\elements\db\UserQuery;
use craft\app\elements\Entry;
use craft\app\elements\Tag;
use craft\app\elements\User;
use craft\app\helpers\StringHelper;
use yii\base\Object;

/**
 * Contains all global variables.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Craft extends Object
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_rebrandVariable;

	// Public Methods
	// =========================================================================

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		/*$plugin = \Craft::$app->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			$pluginName = $plugin->getClassHandle();
			$className = __NAMESPACE__.'\\'.$pluginName.'Variable';

			// Variables should already be imported by the plugin service, but let's double check.
			if (!class_exists($className))
			{
				\Craft::import('plugins.'.StringHelper::toLowerCase($pluginName).'.variables.'.$pluginName.'Variable');
			}

			return new $className;
		}*/
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		$plugin = \Craft::$app->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gets the current language in use.
	 *
	 * @return string
	 */
	public function locale()
	{
		return \Craft::$app->language;
	}

	/**
	 * Returns whether this site has multiple locales.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return \Craft::$app->isLocalized();
	}

	// Template variable classes
	// -------------------------------------------------------------------------

	/**
	 * @return App
	 */
	public function getApp()
	{
		return new App();
	}

	/**
	 * Returns a new AssetQuery instance.
	 *
	 * @param array|null $criteria
	 *
	 * @return AssetQuery
	 */
	public function assets($criteria = null)
	{
		return Asset::find()->configure($criteria);
	}

	/**
	 * Returns a new CategoryQuery instance.
	 *
	 * @param array|null $criteria
	 *
	 * @return CategoryQuery
	 */
	public function categories($criteria = null)
	{
		return Category::find()->configure($criteria);
	}

	/**
	 * @return Config
	 */
	public function config()
	{
		return new Config();
	}

	/**
	 * @return Elements
	 */
	public function elements()
	{
		return new Elements();
	}

	/**
	 * @return Cp
	 */
	public function cp()
	{
		return new Cp();
	}

	/**
	 * @return Dashboard
	 */
	public function dashboard()
	{
		return new Dashboard();
	}

	/**
	 * @return Deprecator
	 */
	public function deprecator()
	{
		return new Deprecator();
	}

	/**
	 * @return EmailMessages
	 */
	public function emailMessages()
	{
		if (\Craft::$app->getEdition() >= \Craft::Client)
		{
			return new EmailMessages();
		}
	}

	/**
	 * Returns a new EntryQuery instance.
	 *
	 * @param array|null $criteria
	 *
	 * @return EntryQuery
	 */
	public function entries($criteria = null)
	{
		return Entry::find()->configure($criteria);
	}

	/**
	 * @return Fields
	 */
	public function fields()
	{
		return new Fields();
	}

	/**
	 * @return EntryRevisions
	 */
	public function entryRevisions()
	{
		if (\Craft::$app->getEdition() >= \Craft::Client)
		{
			return new EntryRevisions();
		}
	}

	/**
	 * @return Feeds
	 */
	public function feeds()
	{
		return new Feeds();
	}

	/**
	 * @return Globals
	 */
	public function globals()
	{
		return new Globals();
	}

	/**
	 * @return Plugins
	 */
	public function plugins()
	{
		return new Plugins();
	}

	/**
	 * @return Rebrand
	 */
	public function rebrand()
	{
		if (\Craft::$app->getEdition() >= \Craft::Client)
		{
			if (!isset($this->_rebrandVariable))
			{
				$this->_rebrandVariable = new Rebrand();
			}

			return $this->_rebrandVariable;
		}
	}

	/**
	 * @return HttpRequest
	 */
	public function request()
	{
		return new HttpRequest();
	}

 	/**
	 * @return Routes
	 */
	public function routes()
	{
		return new Routes();
	}

	/**
	 * @return Sections
	 */
	public function sections()
	{
		return new Sections();
	}

	/**
	 * @return SystemSettings
	 */
	public function systemSettings()
	{
		return new SystemSettings();
	}

	/**
	 * Returns a new TagQuery instance.
	 *
	 * @param array|null $criteria
	 *
	 * @return TagQuery
	 */
	public function tags($criteria = null)
	{
		return Tag::find()->configure($criteria);
	}

	/**
	 * @return Tasks
	 */
	public function tasks()
	{
		return new Tasks();
	}

	/**
	 * @return Updates
	 */
	public function updates()
	{
		return new Updates();
	}

	/**
	 * Returns a new UserQuery instance
	 *
	 * @param array|null $criteria
	 *
	 * @return UserQuery
	 */
	public function users($criteria = null)
	{
		return User::find()->configure($criteria);
	}

	/**
	 * @return UserGroups|null
	 */
	public function userGroups()
	{
		if (\Craft::$app->getEdition() == \Craft::Pro)
		{
			return new UserGroups();
		}
	}

	/**
	 * @return UserPermissions|null
	 */
	public function userPermissions()
	{
		if (\Craft::$app->getEdition() == \Craft::Pro)
		{
			return new UserPermissions();
		}
	}

	/**
	 * @return UserSession
	 */
	public function session()
	{
		return new UserSession();
	}

	/**
	 * @return I18N
	 */
	public function i18n()
	{
		return new I18N();
	}
}
