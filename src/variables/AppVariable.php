<?php
namespace Craft;

/**
 * Class AppVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class AppVariable
{
	// Public Methods
	// =========================================================================

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
	 * Returns the edition Craft is actually licensed to run in.
	 *
	 * @return int|null
	 */
	public function getLicensedEdition()
	{
		return craft()->getLicensedEdition();
	}

	/**
	 * Returns the name of the edition Craft is actually licensed to run in.
	 *
	 * @return string|null
	 */
	public function getLicensedEditionName()
	{
		return craft()->getLicensedEditionName();
	}

	/**
	 * Returns whether Craft is running with the wrong edition.
	 *
	 * @return bool
	 */
	public function hasWrongEdition()
	{
		return craft()->hasWrongEdition();
	}

	/**
	 * Returns whether Craft is elligible to be upgraded to a different edition.
	 *
	 * @return bool
	 */
	public function canUpgradeEdition()
	{
		return craft()->canUpgradeEdition();
	}

	/**
	 * Returns whether Craft is running on a domain that is eligible to test out
	 * the editions.
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
		$maxUpload = AppHelper::getPhpConfigValueInBytes('upload_max_filesize');
		$maxPost = AppHelper::getPhpConfigValueInBytes('post_max_size');
		$memoryLimit = AppHelper::getPhpConfigValueInBytes('memory_limit');

		$uploadInBytes = min($maxUpload, $maxPost);

		if ($memoryLimit > 0)
		{
			$uploadInBytes = min($uploadInBytes, $memoryLimit);
		}

		$configLimit = (int) craft()->config->get('maxUploadFileSize');

		if ($configLimit)
		{
			$uploadInBytes = min($uploadInBytes, $configLimit);
		}

		return $uploadInBytes;
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
