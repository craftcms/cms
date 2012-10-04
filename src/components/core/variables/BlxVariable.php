<?php
namespace Blocks;

/**
 * Contains all global variables.
 */
class BlxVariable
{
	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$plugin = blx()->plugins->getPlugin($name);

		if ($plugin && $plugin->model !== null && $plugin->model->enabled)
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

		if ($plugin && $plugin->model !== null && $plugin->model->enabled)
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
	public function language()
	{
		return blx()->language;
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
	 * @return AccountVariable
	 */
	public function account()
	{
		return new AccountVariable();
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
	 * @return BlockTypesVariable
	 */
	public function blockTypes()
	{
		return new BlockTypesVariable();
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
	 * @return EntriesVariable
	 */
	public function entries()
	{
		return new EntriesVariable();
	}

	/**
	 * @return EntryBlocksVariable
	 */
	public function entryBlocks()
	{
		return new EntryBlocksVariable();
	}

	/**
	 * @return PluginsVariable
	 */
	public function plugins()
	{
		return new PluginsVariable();
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
	 * @return SectionsVariable|null
	 */
	public function sections()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			return new SectionsVariable();
		}
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
	 * @return UsersVariable
	 */
	public function users()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return new UsersVariable();
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
