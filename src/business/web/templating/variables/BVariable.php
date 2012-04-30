<?php
namespace Blocks;

/**
 * Namespace for all internal tag variables.
 */
class BVariable
{
	/**
	 * @return AssetsVariable
	 */
	public function assets()
	{
		return new AssetsVariable;
	}

	/**
	 * Returns the current Blocks build
	 * @return string
	 */
	public function build()
	{
		return Blocks::getBuild();
	}

	/**
	 * @return ConfigVariable
	 */
	public function config()
	{
		return new ConfigVariable;
	}

	/**
	 * @return ContentVariable
	 */
	public function content()
	{
		return new ContentVariable;
	}

	/**
	 * @return ContentBlocksVariable
	 */
	public function contentblocks()
	{
		return new ContentBlocksVariable;
	}

	/**
	 * @return CpVariable
	 */
	public function cp()
	{
		return new CpVariable;
	}

	/**
	 * @return DashboardVariable
	 */
	public function dashboard()
	{
		return new DashboardVariable;
	}

	/**
	 * @return DateVariable
	 */
	public function date()
	{
		return new DateVariable;
	}

	/**
	 * Returns the current Blocks edition
	 * @return string
	 */
	public function edition()
	{
		return Blocks::getEdition();
	}

	/**
	 * @return EmailVariable
	 */
	public function email()
	{
		return new EmailVariable;
	}

	/**
	 * @return PluginsVariable
	 */
	public function plugins()
	{
		return new PluginsVariable;
	}

	/**
	 * @return RequestVariable
	 */
	public function request()
	{
		return new RequestVariable;
	}

	/**
	 * @return SitesVariable
	 */
	public function sites()
	{
		return new SitesVariable;
	}

	/**
	 * @return UpdatesVariable
	 */
	public function updates()
	{
		return new UpdatesVariable;
	}

	/**
	 * @return UrlVariable
	 */
	public function url()
	{
		return new UrlVariable;
	}

	/**
	 * @return UsersVariable
	 */
	public function users()
	{
		return new UsersVariable;
	}

	/**
	 * @return SecurityVariable
	 */
	public function security()
	{
		return new SecurityVariable;
	}

	/**
	 * @return SessionVariable
	 */
	public function session()
	{
		return new SessionVariable;
	}

	/**
	 * Returns the current Blocks version
	 * @return string
	 */
	public function version()
	{
		return Blocks::getVersion();
	}
}
