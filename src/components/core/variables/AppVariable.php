<?php
namespace Blocks;

/**
 * App functions
 */
class AppVariable
{
	/**
	 * Returns the current Blocks version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return Blocks::getVersion();
	}

	/**
	 * Returns the current Blocks build.
	 *
	 * @return string
	 */
	public function getBuild()
	{
		return Blocks::getBuild();
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 */
	public function getSiteName()
	{
		return Blocks::getSiteName();
	}

	/**
	 * Returns the site URL.
	 *
	 * @return string
	 */
	public function getSiteUrl()
	{
		return Blocks::getSiteUrl();
	}

	/**
	 * Returns the site language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return Blocks::getLanguage();
	}

	/**
	 * Returns the license key.
	 *
	 * @return string
	 */
	public function getLicenseKey()
	{
		return Blocks::getLicenseKey();
	}
}
