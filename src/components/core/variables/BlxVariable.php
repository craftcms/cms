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

		if ($plugin && $plugin->record !== null && $plugin->record->enabled)
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

		if ($plugin && $plugin->record !== null && $plugin->record->enabled)
		{
			return true;
		}

		return false;
	}

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
	 * @return ContentVariable
	 */
	public function content()
	{
		return new ContentVariable();
	}

	/**
	 * @return BlocksVariable
	 */
	public function blocks()
	{
		return new BlocksVariable();
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
	 * @return DateVariable
	 */
	public function date()
	{
		return new DateVariable();
	}

	/**
	 * @return EmailVariable
	 */
	public function email()
	{
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			return new EmailVariable();
		}
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

	/**
	 * @param string $category
	 * @param        $message
	 * @param array  $params
	 * @param null   $source
	 * @param null   $language
	 * @return string|void
	 */
	public function t($category = 'App', $message, $params = array(), $source = null, $language = null)
	{
		return Blocks::t($category, $message, $params, $source, $language);
	}
}
