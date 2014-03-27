<?php
namespace Craft;

/**
 * App functions
 */
class AppVariable
{
	/**
	 * Returns the Craft edition.
	 *
	 * @return string
	 */
	public function getEdition()
	{
		return craft()->getEdition();
	}

	/**
	 * Returns the name of the Craft edition.
	 *
	 * @return string
	 */
	public function getEditionName()
	{
		return craft()->getEditionName();
	}

	/**
	 * Returns whether Craft is running on a domain that is elligible to test out the editions.
	 *
	 * @return bool
	 */
	public function canTestEditions()
	{
		return craft()->canTestEditions();
	}

	/**
	 * Returns the installed Craft version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return craft()->getVersion();
	}

	/**
	 * Returns the installed Craft build.
	 *
	 * @return string
	 */
	public function getBuild()
	{
		return craft()->getBuild();
	}

	/**
	 * Returns the installed Craft release date.
	 *
	 * @return DateTime
	 */
	public function getReleaseDate()
	{
		return craft()->getReleaseDate();
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 */
	public function getSiteName()
	{
		return craft()->getSiteName();
	}

	/**
	 * Returns the site URL.
	 *
	 * @return string
	 */
	public function getSiteUrl()
	{
		return craft()->getSiteUrl();
	}

	/**
	 * Returns the site UID.
	 *
	 * @return string
	 */
	public function getSiteUid()
	{
		return craft()->getSiteUid();
	}

	/**
	 * Returns the site language.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return craft()->getLanguage();
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @return string
	 */
	public function isSystemOn()
	{
		return craft()->isSystemOn();
	}

	/**
	 * Returns whether the update info is cached.
	 *
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return craft()->updates->isUpdateInfoCached();
	}

	/**
	 * Returns how many updates are available.
	 *
	 * @return int
	 */
	public function getTotalAvailableUpdates()
	{
		return craft()->updates->getTotalAvailableUpdates();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function isCriticalUpdateAvailable()
	{
		return craft()->updates->isCriticalUpdateAvailable();
	}

	/**
	 * Return max upload size in bytes.
	 *
	 * @return int
	 */
	public function getMaxUploadSize()
	{
		$maxUpload = (int)(ini_get('upload_max_filesize'));
		$maxPost = (int)(ini_get('post_max_size'));
		$memoryLimit = (int)(ini_get('memory_limit'));
		$uploadMb = min($maxUpload, $maxPost, $memoryLimit);

		// Convert MB to B and return
		return $uploadMb * 1048576; // 1024 x 1024 = 1048576
	}

	/**
	 * Returns a list of file kinds.
	 *
	 * @return array
	 */
	public function getFileKinds()
	{
		return IOHelper::getFileKinds();
	}
}
