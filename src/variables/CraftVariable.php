<?php
namespace Craft;

/**
 * Contains all global variables.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
 */
class CraftVariable
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_rebrandVariable;

	/**
	 * @var array
	 */
	private $_pluginVariableInstances;

	// Public Methods
	// =========================================================================

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		$plugin = craft()->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			$pluginName = $plugin->getClassHandle();
			$className = __NAMESPACE__.'\\'.$pluginName.'Variable';

			// Variables should already be imported by the plugin service, but let's double check.
			if (!class_exists($className))
			{
				Craft::import('plugins.'.StringHelper::toLowerCase($pluginName).'.variables.'.$pluginName.'Variable');
			}

			// If we haven't done this one yet, create it and save it for later.
			if (!isset($this->_pluginVariableInstances[$className]))
			{
				$this->_pluginVariableInstances[$className] = new $className;
			}

			return $this->_pluginVariableInstances[$className];
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		$plugin = craft()->plugins->getPlugin($name);

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
		return craft()->language;
	}

	/**
	 * Returns whether this site has multiple locales.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return craft()->isLocalized();
	}

	/**
	 * Returns whether a package is included in the Craft build.
	 *
	 * @param string $packageName;
	 *
	 * @deprecated Deprecated in 2.0.
	 * @return bool
	 *
	 */
	public function hasPackage($packageName)
	{
		return craft()->hasPackage($packageName);
	}

	// Template variable classes
	// -------------------------------------------------------------------------

	/**
	 * @return AppVariable
	 */
	public function app()
	{
		return new AppVariable();
	}

	/**
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function assets($criteria = null)
	{
		return craft()->elements->getCriteria(ElementType::Asset, $criteria);
	}

	/**
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function categories($criteria = null)
	{
		return craft()->elements->getCriteria(ElementType::Category, $criteria);
	}

	/**
	 * @return ConfigVariable
	 */
	public function config()
	{
		return new ConfigVariable();
	}

	/**
	 * @return ElementsVariable
	 */
	public function elements()
	{
		return new ElementsVariable();
	}

	/**
	 * @return CpVariable
	 */
	public function cp()
	{
		return new CpVariable();
	}

	/**
	 * @return DashboardVariable
	 */
	public function dashboard()
	{
		return new DashboardVariable();
	}

	/**
	 * @return DeprecatorVariable
	 */
	public function deprecator()
	{
		return new DeprecatorVariable();
	}

	/**
	 * @return EmailMessagesVariable
	 */
	public function emailMessages()
	{
		if (craft()->getEdition() >= Craft::Client)
		{
			return new EmailMessagesVariable();
		}
	}

	/**
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function entries($criteria = null)
	{
		return craft()->elements->getCriteria(ElementType::Entry, $criteria);
	}

	/**
	 * @return FieldsVariable
	 */
	public function fields()
	{
		return new FieldsVariable();
	}

	/**
	 * @return EntryRevisionsVariable
	 */
	public function entryRevisions()
	{
		if (craft()->getEdition() >= Craft::Client)
		{
			return new EntryRevisionsVariable();
		}
	}

	/**
	 * @return FeedsVariable
	 */
	public function feeds()
	{
		return new FeedsVariable();
	}

	/**
	 * @return GlobalsVariable
	 */
	public function globals()
	{
		return new GlobalsVariable();
	}

	/**
	 * @return PluginsVariable
	 */
	public function plugins()
	{
		return new PluginsVariable();
	}

	/**
	 * @return RebrandVariable
	 */
	public function rebrand()
	{
		if (craft()->getEdition() >= Craft::Client)
		{
			if (!isset($this->_rebrandVariable))
			{
				$this->_rebrandVariable = new RebrandVariable();
			}

			return $this->_rebrandVariable;
		}
	}

	/**
	 * @return HttpRequestVariable
	 */
	public function request()
	{
		return new HttpRequestVariable();
	}

 	/**
	 * @return RoutesVariable
	 */
	public function routes()
	{
		return new RoutesVariable();
	}

	/**
	 * @return SectionsVariable
	 */
	public function sections()
	{
		return new SectionsVariable();
	}

	/**
	 * @return SystemSettingsVariable
	 */
	public function systemSettings()
	{
		return new SystemSettingsVariable();
	}

	/**
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */
	public function tags($criteria = null)
	{
		return craft()->elements->getCriteria(ElementType::Tag, $criteria);
	}

	/**
	 * @return TasksVariable
	 */
	public function tasks()
	{
		return new TasksVariable();
	}

	/**
	 * @return UpdatesVariable
	 */
	public function updates()
	{
		return new UpdatesVariable();
	}

	/**
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel|null
	 */
	public function users($criteria = null)
	{
		if (craft()->getEdition() == Craft::Pro)
		{
			return craft()->elements->getCriteria(ElementType::User, $criteria);
		}
	}

	/**
	 * @return UserGroupsVariable|null
	 */
	public function userGroups()
	{
		if (craft()->getEdition() == Craft::Pro)
		{
			return new UserGroupsVariable();
		}
	}

	/**
	 * @return UserPermissionsVariable|null
	 */
	public function userPermissions()
	{
		if (craft()->getEdition() == Craft::Pro)
		{
			return new UserPermissionsVariable();
		}
	}

	/**
	 * @return UserSessionVariable
	 */
	public function session()
	{
		return new UserSessionVariable();
	}

	/**
	 * @return LocalizationVariable
	 */
	public function i18n()
	{
		return new LocalizationVariable();
	}
}
