<?php
namespace Blocks;

/**
 * Contains all global variables.
 */
class BlxVariable
{
	private $_rebrandVariable;

	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$plugin = blx()->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			$pluginName = $plugin->getClassHandle();
			$className = __NAMESPACE__.'\\'.$pluginName.'Variable';

			// Variables should already be imported by the plugin service, but let's double check.
			if (!class_exists($className))
			{
				Blocks::import('plugins.'.$pluginName.'.variables.'.$pluginName.'Variable');
			}

			return new $className;
		}
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$plugin = blx()->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			return true;
		}

		return false;
	}

	/**
	 * Gets the current language in use.
	 *
	 * @return string
	 */
	public function locale()
	{
		return blx()->language;
	}

	/**
	 * Returns the packages in this Blocks install, as defined by the blx_info table.
	 *
	 * @return array
	 */
	public function getPackages()
	{
		return Blocks::getStoredPackages();
	}

	/**
	 * Returns whether a package is included in the Blocks build.
	 *
	 * @param $packageName;
	 * @return bool
	 */
	public function hasPackage($packageName)
	{
		return Blocks::hasPackage($packageName);
	}

	// -------------------------------------------
	//  Template variable classes
	// -------------------------------------------

	/**
	 * @return AppVariable
	 */
	public function app()
	{
		return new AppVariable();
	}

	/**
	 * @return AssetsVariable
	 */
	public function assets()
	{
		return new AssetsVariable();
	}

	/**
	 * @return ConfigVariable
	 */
	public function config()
	{
		return new ConfigVariable();
	}

	/**
	 * @return FieldTypesVariable
	 */
	public function fieldTypes()
	{
		return new FieldTypesVariable();
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
	 * @return EmailMessagesVariable
	 */
	public function emailMessages()
	{
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			return new EmailMessagesVariable();
		}
	}

	/**
	 * @param array|null $criteria
	 * @return EntryCriteriaModel
	 */
	public function entries($criteria = null)
	{
		return blx()->entries->getEntryCriteria('SectionEntry', $criteria);
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
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
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
	 * @return LinksVariable
	 */
	public function links()
	{
		return new LinksVariable();
	}

	/**
	 * @return SingletonsVariable
	 */
	public function singletons()
	{
		return new SingletonsVariable();
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
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
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
	 * @return UpdatesVariable
	 */
	public function updates()
	{
		return new UpdatesVariable();
	}

	/**
	 * @param array|null $criteria
	 * @return EntryCriteriaModel|null
	 */
	public function users($criteria = null)
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return blx()->entries->getEntryCriteria('User', $criteria);
		}
	}

	/**
	 * @return UserGroupsVariable|null
	 */
	public function userGroups()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return new UserGroupsVariable();
		}
	}

	/**
	 * @return UserPermissionsVariable|null
	 */
	public function userPermissions()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
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
