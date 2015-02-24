<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\enums\ElementType as ElementTypeEnum;
use craft\app\helpers\StringHelper;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
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
		$plugin = \Craft::$app->plugins->getPlugin($name);

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
		}
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
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function assets($criteria = null)
	{
		return \Craft::$app->elements->getCriteria(ElementTypeEnum::Asset, $criteria);
	}

	/**
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function categories($criteria = null)
	{
		return \Craft::$app->elements->getCriteria(ElementTypeEnum::Category, $criteria);
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
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function entries($criteria = null)
	{
		return \Craft::$app->elements->getCriteria(ElementTypeEnum::Entry, $criteria);
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
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function tags($criteria = null)
	{
		return \Craft::$app->elements->getCriteria(ElementTypeEnum::Tag, $criteria);
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
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel|null
	 */
	public function users($criteria = null)
	{
		if (\Craft::$app->getEdition() == \Craft::Pro)
		{
			return \Craft::$app->elements->getCriteria(ElementTypeEnum::User, $criteria);
		}
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
