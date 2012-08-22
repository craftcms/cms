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

		if ($plugin && $plugin->enabled)
		{
			$pluginName = $plugin->getClassHandle();
			$path = blx()->path->getPluginsPath().$pluginName.'/'.$pluginName.'Variable.php';

			if (File::fileExists($path))
			{
				Blocks::import('plugins.'.$pluginName.'.'.$pluginName.'Variable');
				$variableName = __NAMESPACE__.'\\'.$pluginName.'Variable';
				return new $variableName;
			}
		}
	}

	/**
	 * @return AppVariable
	 */
	public function app()
	{
		return new AppVariable();
	}

	/**
	 * @return AccountsVariable
	 */
	public function accounts()
	{
		return new AccountsVariable();
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
	 * @return ContentBlocksVariable
	 */
	public function contentblocks()
	{
		return new ContentBlocksVariable();
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
		return new EmailVariable();
	}

	/**
	 * @return PluginsVariable
	 */
	public function plugins()
	{
		return new PluginsVariable();
	}

	/**
	 * @return RequestVariable
	 */
	public function request()
	{
		return new RequestVariable();
	}

	/**
	 * @return SettingsVariable
	 */
	public function settings()
	{
		return new SettingsVariable();
	}

	/**
	 * @return UpdatesVariable
	 */
	public function updates()
	{
		return new UpdatesVariable();
	}

	/**
	 * @return SecurityVariable
	 */
	public function security()
	{
		return new SecurityVariable();
	}

	/**
	 * @return SessionVariable
	 */
	public function session()
	{
		return new SessionVariable();
	}

	/**
	 * @return HttpStatusVariable
	 */
	public function httpstatus()
	{
		return new HttpStatusVariable();
	}

	/**
	 * @return LocalizationVariable
	 */
	public function localization()
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
